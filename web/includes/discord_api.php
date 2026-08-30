<?php
declare(strict_types=1);

class DiscordApiException extends RuntimeException {}

function discord_http_request(string $method, string $url, array $headers = [], ?array $formBody = null): array
{
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_CUSTOMREQUEST => $method,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => $headers,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_SSL_VERIFYHOST => 2,
    ]);
    if ($formBody !== null) {
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($formBody));
    }

    $response = curl_exec($ch);
    if ($response === false) {
        $error = curl_error($ch);
        curl_close($ch);
        throw new DiscordApiException("Discord API request failed: $error");
    }

    $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    $decoded = json_decode($response, true);
    if ($status < 200 || $status >= 300) {
        throw new DiscordApiException("Discord API returned HTTP $status");
    }

    return is_array($decoded) ? $decoded : [];
}

function discord_exchange_code(array $config, string $code): array
{
    $discord = $config['discord'];
    return discord_http_request(
        'POST',
        'https://discord.com/api/oauth2/token',
        ['Content-Type: application/x-www-form-urlencoded'],
        [
            'client_id' => $discord['client_id'],
            'client_secret' => $discord['client_secret'],
            'grant_type' => 'authorization_code',
            'code' => $code,
            'redirect_uri' => $discord['redirect_uri'],
        ]
    );
}

function discord_get_current_user(string $accessToken): array
{
    return discord_http_request(
        'GET',
        'https://discord.com/api/users/@me',
        ["Authorization: Bearer $accessToken"]
    );
}

/**
 * Looks up a user's membership (and roles) in the configured guild, using the
 * bot token. Returns null if the user is not a member of the guild.
 */
function discord_get_guild_member(array $config, string $userId): ?array
{
    $discord = $config['discord'];
    $url = sprintf(
        'https://discord.com/api/guilds/%s/members/%s',
        $discord['guild_id'],
        $userId
    );

    try {
        return discord_http_request('GET', $url, ["Authorization: Bot {$discord['bot_token']}"]);
    } catch (DiscordApiException $e) {
        return null;
    }
}
