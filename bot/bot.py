import asyncio
import logging

import discord
from discord.ext import commands

import config
import db

logging.basicConfig(level=logging.INFO, format="%(asctime)s %(levelname)s %(name)s: %(message)s")
log = logging.getLogger("bot")

INITIAL_COGS = (
    "cogs.welcome",
    "cogs.rules",
    "cogs.broadcast",
    "cogs.announcements",
    "cogs.logging_cog",
)


class GaBot(commands.Bot):
    def __init__(self) -> None:
        intents = discord.Intents.default()
        intents.members = True  # required: welcome messages + broadcast recipient list
        super().__init__(command_prefix="!", intents=intents, help_command=None)
        self.pool: db.aiomysql.Pool | None = None

    async def setup_hook(self) -> None:
        self.pool = await db.create_pool()
        for cog in INITIAL_COGS:
            await self.load_extension(cog)

        try:
            if config.DISCORD_GUILD_ID:
                guild = discord.Object(id=int(config.DISCORD_GUILD_ID))
                self.tree.copy_global_to(guild=guild)
                await self.tree.sync(guild=guild)
            else:
                await self.tree.sync()
        except discord.Forbidden:
            log.error(
                "Slash command sync failed with 'Missing Access'. The bot was likely "
                "invited without the 'applications.commands' OAuth2 scope, or "
                "DISCORD_GUILD_ID doesn't match a server the bot is in. Re-invite the "
                "bot with both 'bot' and 'applications.commands' scopes checked in the "
                "Discord Developer Portal's OAuth2 URL Generator. Continuing startup "
                "without slash commands."
            )

    async def on_ready(self) -> None:
        log.info("Logged in as %s (id=%s)", self.user, self.user.id)

    async def close(self) -> None:
        if self.pool is not None:
            self.pool.close()
            await self.pool.wait_closed()
        await super().close()


async def main() -> None:
    bot = GaBot()
    async with bot:
        await bot.start(config.DISCORD_BOT_TOKEN)


if __name__ == "__main__":
    asyncio.run(main())
