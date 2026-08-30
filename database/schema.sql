-- Schema for the Discord bot + admin backend.
-- Run once against an empty database: mysql -u root -p ga_discord < database/schema.sql

CREATE TABLE IF NOT EXISTS settings (
    `key`   VARCHAR(64) PRIMARY KEY,
    `value` TEXT NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

INSERT IGNORE INTO settings (`key`, `value`) VALUES
    ('guild_id', ''),
    ('admin_role_id', ''),
    ('welcome_channel_id', ''),
    ('welcome_message', 'Welcome {mention} to {guild}! 🎉');

CREATE TABLE IF NOT EXISTS rules (
    id          INT AUTO_INCREMENT PRIMARY KEY,
    position    INT NOT NULL DEFAULT 0,
    title       VARCHAR(255) NOT NULL,
    content     TEXT NOT NULL,
    updated_by  VARCHAR(32) NOT NULL,
    updated_at  TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Admins are granted access either by holding admin_role_id in Discord, or by
-- being listed here explicitly (fallback / audit trail of who has logged in).
CREATE TABLE IF NOT EXISTS admins (
    discord_id       VARCHAR(32) PRIMARY KEY,
    discord_username VARCHAR(64) NOT NULL,
    added_at         TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_login_at    TIMESTAMP NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS broadcast_jobs (
    id                INT AUTO_INCREMENT PRIMARY KEY,
    message           TEXT NOT NULL,
    status            ENUM('pending','running','done','failed') NOT NULL DEFAULT 'pending',
    created_by        VARCHAR(32) NOT NULL,
    created_at        TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    started_at        TIMESTAMP NULL,
    finished_at       TIMESTAMP NULL,
    total_recipients  INT NOT NULL DEFAULT 0,
    sent_count        INT NOT NULL DEFAULT 0,
    failed_count      INT NOT NULL DEFAULT 0,
    INDEX idx_status (status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

CREATE TABLE IF NOT EXISTS logs (
    id               BIGINT AUTO_INCREMENT PRIMARY KEY,
    level            ENUM('info','warning','error') NOT NULL DEFAULT 'info',
    event_type       VARCHAR(64) NOT NULL,
    message          TEXT NOT NULL,
    discord_user_id  VARCHAR(32) NULL,
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_created_at (created_at),
    INDEX idx_event_type (event_type)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
