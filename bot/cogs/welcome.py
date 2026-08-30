import discord
from discord.ext import commands

import db


class Welcome(commands.Cog):
    def __init__(self, bot: commands.Bot) -> None:
        self.bot = bot

    @commands.Cog.listener()
    async def on_member_join(self, member: discord.Member) -> None:
        settings = await db.get_settings(
            self.bot.pool, ["welcome_channel_id", "welcome_message"]
        )
        channel_id = settings.get("welcome_channel_id")
        if not channel_id:
            return

        channel = member.guild.get_channel(int(channel_id))
        if channel is None:
            await db.log_event(
                self.bot.pool,
                "warning",
                "welcome_failed",
                f"Configured welcome_channel_id={channel_id} was not found in guild "
                f"{member.guild.id}",
            )
            return

        template = settings.get("welcome_message") or "Welcome {mention} to {guild}!"
        text = (
            template.replace("{mention}", member.mention)
            .replace("{guild}", member.guild.name)
            .replace("{user}", member.display_name)
        )

        try:
            await channel.send(text, allowed_mentions=discord.AllowedMentions(users=[member]))
            await db.log_event(
                self.bot.pool, "info", "member_join", f"{member} joined", str(member.id)
            )
        except discord.Forbidden:
            await db.log_event(
                self.bot.pool,
                "error",
                "welcome_failed",
                f"Missing permission to send welcome message in channel {channel_id}",
                str(member.id),
            )


async def setup(bot: commands.Bot) -> None:
    await bot.add_cog(Welcome(bot))
