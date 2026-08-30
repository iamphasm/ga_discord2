<?php
declare(strict_types=1);

function is_logged_in(): bool
{
    return !empty($_SESSION['admin_discord_id']);
}

function require_admin(): void
{
    if (!is_logged_in()) {
        header('Location: login.php');
        exit;
    }
}

/**
 * Decides whether a Discord user is allowed into the admin panel: either they
 * hold the configured admin role in the guild, or they're on the manual
 * allowlist (the `admins` table) already.
 */
function user_is_authorized(array $config, PDO $pdo, string $userId): bool
{
    $member = discord_get_guild_member($config, $userId);
    $adminRoleId = $config['discord']['admin_role_id'] ?? '';
    if ($member !== null && $adminRoleId !== '' && in_array($adminRoleId, $member['roles'] ?? [], true)) {
        return true;
    }

    $stmt = $pdo->prepare('SELECT 1 FROM admins WHERE discord_id = ?');
    $stmt->execute([$userId]);
    return (bool) $stmt->fetchColumn();
}

function record_admin_login(PDO $pdo, string $userId, string $username): void
{
    $stmt = $pdo->prepare(
        'INSERT INTO admins (discord_id, discord_username, last_login_at) VALUES (?, ?, NOW())
         ON DUPLICATE KEY UPDATE discord_username = VALUES(discord_username), last_login_at = NOW()'
    );
    $stmt->execute([$userId, $username]);
}
