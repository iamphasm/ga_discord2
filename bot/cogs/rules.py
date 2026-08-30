import discord
from discord import app_commands
from discord.ext import commands


class Rules(commands.Cog):
    def __init__(self, bot: commands.Bot) -> None:
        self.bot = bot

    @app_commands.command(name="rules", description="Show the server rules")
    async def rules(self, interaction: discord.Interaction) -> None:
        async with self.bot.pool.acquire() as conn, conn.cursor() as cur:
            await cur.execute(
                "SELECT title, content FROM rules ORDER BY position ASC, id ASC"
            )
            rows = await cur.fetchall()

        if not rows:
            await interaction.response.send_message(
                "No rules have been configured yet.", ephemeral=True
            )
            return

        embed = discord.Embed(title="Server Rules", color=discord.Color.blurple())
        for title, content in rows[:25]:  # embeds allow at most 25 fields
            embed.add_field(name=title[:256], value=content[:1024], inline=False)

        await interaction.response.send_message(embed=embed)


async def setup(bot: commands.Bot) -> None:
    await bot.add_cog(Rules(bot))
