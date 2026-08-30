import os
import sys

from dotenv import load_dotenv

load_dotenv()


def _require(name: str) -> str:
    value = os.getenv(name)
    if not value:
        sys.exit(f"Missing required environment variable: {name}")
    return value


DISCORD_BOT_TOKEN = _require("DISCORD_BOT_TOKEN")
DISCORD_GUILD_ID = os.getenv("DISCORD_GUILD_ID") or None

DB_HOST = os.getenv("DB_HOST", "127.0.0.1")
DB_PORT = int(os.getenv("DB_PORT", "3306"))
DB_NAME = _require("DB_NAME")
DB_USER = _require("DB_USER")
DB_PASSWORD = _require("DB_PASSWORD")
