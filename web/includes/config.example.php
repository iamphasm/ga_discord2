<?php
// Copy to config.php (kept outside the web root's document root) and fill in real values.
// Never commit config.php.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'ga_discord',
        'user' => 'ga_discord_web',
        'password' => '',
    ],
    'discord' => [
        'client_id' => '',
        'client_secret' => '',
        'redirect_uri' => 'https://your-domain.example/oauth_callback.php',
        // Bot token is used server-side only, to look up guild membership/roles.
        'bot_token' => '',
        'guild_id' => '',
        'admin_role_id' => '',
    ],
];
