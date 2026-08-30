import aiomysql

import config


async def create_pool() -> aiomysql.Pool:
    return await aiomysql.create_pool(
        host=config.DB_HOST,
        port=config.DB_PORT,
        db=config.DB_NAME,
        user=config.DB_USER,
        password=config.DB_PASSWORD,
        charset="utf8mb4",
        autocommit=True,
        minsize=1,
        maxsize=5,
    )


async def get_settings(pool: aiomysql.Pool, keys: list[str]) -> dict[str, str]:
    if not keys:
        return {}
    placeholders = ",".join(["%s"] * len(keys))
    async with pool.acquire() as conn, conn.cursor() as cur:
        await cur.execute(
            f"SELECT `key`, `value` FROM settings WHERE `key` IN ({placeholders})",
            keys,
        )
        rows = await cur.fetchall()
    return {key: value for key, value in rows}


async def log_event(
    pool: aiomysql.Pool,
    level: str,
    event_type: str,
    message: str,
    discord_user_id: str | None = None,
) -> None:
    async with pool.acquire() as conn, conn.cursor() as cur:
        await cur.execute(
            "INSERT INTO logs (level, event_type, message, discord_user_id) "
            "VALUES (%s, %s, %s, %s)",
            (level, event_type, message, discord_user_id),
        )
