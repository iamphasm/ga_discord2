import logging

import discord
from discord.ext import commands

import db

log = logging.getLogger("bot")


class LoggingCog(commands.Cog):
    def __init__(self, bot: commands.Bot) -> None:
        self.bot = bot

    @commands.Cog.listener()
    async def on_member_remove(self, member: discord.Member) -> None:
        await db.log_event(
            self.bot.pool, "info", "member_leave", f"{member} left", str(member.id)
        )

    @commands.Cog.listener()
    async def on_app_command_error(
        self,
        interaction: discord.Interaction,
        error: discord.app_commands.AppCommandError,
    ) -> None:
        log.exception("Slash command error", exc_info=error)
        await db.log_event(
            self.bot.pool,
            "error",
            "command_error",
            f"/{interaction.command.name if interaction.command else '?'}: {error}",
            str(interaction.user.id),
        )
        if not interaction.response.is_done():
            await interaction.response.send_message(
                "Something went wrong running that command.", ephemeral=True
            )


async def setup(bot: commands.Bot) -> None:
    await bot.add_cog(LoggingCog(bot))
