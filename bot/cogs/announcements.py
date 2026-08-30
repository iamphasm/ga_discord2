import discord
from discord.ext import commands, tasks

import db


class Announcements(commands.Cog):
    """Posts one-off admin-authored messages to the configured welcome channel."""

    def __init__(self, bot: commands.Bot) -> None:
        self.bot = bot
        self.poll_messages.start()

    def cog_unload(self) -> None:
        self.poll_messages.cancel()

    @tasks.loop(seconds=15)
    async def poll_messages(self) -> None:
        async with self.bot.pool.acquire() as conn, conn.cursor() as cur:
            await cur.execute(
                "SELECT id, message FROM channel_messages "
                "WHERE status = 'pending' ORDER BY id ASC LIMIT 1"
            )
            row = await cur.fetchone()
            if row is None:
                return
            message_id, message = row

        await self._send(message_id, message)

    @poll_messages.before_loop
    async def before_poll_messages(self) -> None:
        await self.bot.wait_until_ready()

    async def _send(self, message_id: int, message: str) -> None:
        settings = await db.get_settings(self.bot.pool, ["welcome_channel_id"])
        channel_id = settings.get("welcome_channel_id")
        channel = self.bot.get_channel(int(channel_id)) if channel_id else None

        if channel is None:
            await self._finish(message_id, "failed", "Welcome channel not configured or not found")
            return

        try:
            await channel.send(message)
            await self._finish(message_id, "sent", None)
            await db.log_event(
                self.bot.pool, "info", "channel_message_sent", f"Posted message {message_id} to channel"
            )
        except (discord.Forbidden, discord.HTTPException) as e:
            await self._finish(message_id, "failed", str(e)[:255])
            await db.log_event(
                self.bot.pool, "error", "channel_message_failed", f"Message {message_id}: {e}"
            )

    async def _finish(self, message_id: int, status: str, error: str | None) -> None:
        async with self.bot.pool.acquire() as conn, conn.cursor() as cur:
            await cur.execute(
                "UPDATE channel_messages SET status = %s, error = %s, sent_at = NOW() WHERE id = %s",
                (status, error, message_id),
            )


async def setup(bot: commands.Bot) -> None:
    await bot.add_cog(Announcements(bot))
