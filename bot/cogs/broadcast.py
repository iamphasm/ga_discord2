import asyncio
import logging

import discord
from discord.ext import commands, tasks

import config
import db

log = logging.getLogger("bot")

# Discord DM rate limits are strict and unofficial; pacing sends keeps us well
# under them and avoids the bot being temporarily blocked from sending DMs.
DM_DELAY_SECONDS = 1.5


class Broadcast(commands.Cog):
    def __init__(self, bot: commands.Bot) -> None:
        self.bot = bot
        self.poll_jobs.start()

    def cog_unload(self) -> None:
        self.poll_jobs.cancel()

    @tasks.loop(seconds=15)
    async def poll_jobs(self) -> None:
        async with self.bot.pool.acquire() as conn, conn.cursor() as cur:
            await cur.execute(
                "SELECT id, message FROM broadcast_jobs "
                "WHERE status = 'pending' ORDER BY id ASC LIMIT 1"
            )
            job = await cur.fetchone()
            if job is None:
                return
            job_id, message = job
            await cur.execute(
                "UPDATE broadcast_jobs SET status = 'running', started_at = NOW() "
                "WHERE id = %s",
                (job_id,),
            )

        await self._run_job(job_id, message)

    @poll_jobs.before_loop
    async def before_poll_jobs(self) -> None:
        await self.bot.wait_until_ready()

    async def _run_job(self, job_id: int, message: str) -> None:
        settings = await db.get_settings(self.bot.pool, ["guild_id"])
        guild_id = settings.get("guild_id") or config.DISCORD_GUILD_ID
        guild = self.bot.get_guild(int(guild_id)) if guild_id else None

        if guild is None:
            await self._finish_job(job_id, "failed", 0, 0, 0)
            await db.log_event(
                self.bot.pool, "error", "broadcast_failed",
                f"Job {job_id}: configured guild not found",
            )
            return

        members = [m for m in guild.members if not m.bot]
        total = len(members)
        sent = 0
        failed = 0

        for member in members:
            try:
                await member.send(message)
                sent += 1
            except (discord.Forbidden, discord.HTTPException):
                failed += 1
            await asyncio.sleep(DM_DELAY_SECONDS)

        await self._finish_job(job_id, "done", total, sent, failed)
        await db.log_event(
            self.bot.pool, "info", "broadcast_done",
            f"Job {job_id}: sent={sent} failed={failed} total={total}",
        )

    async def _finish_job(
        self, job_id: int, status: str, total: int, sent: int, failed: int
    ) -> None:
        async with self.bot.pool.acquire() as conn, conn.cursor() as cur:
            await cur.execute(
                "UPDATE broadcast_jobs SET status = %s, finished_at = NOW(), "
                "total_recipients = %s, sent_count = %s, failed_count = %s "
                "WHERE id = %s",
                (status, total, sent, failed, job_id),
            )


async def setup(bot: commands.Bot) -> None:
    await bot.add_cog(Broadcast(bot))
