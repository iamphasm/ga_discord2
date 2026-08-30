<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

$state = $_GET['state'] ?? '';
$code = $_GET['code'] ?? '';
$expectedState = $_SESSION['oauth_state'] ?? '';
unset($_SESSION['oauth_state']);

if ($state === '' || $expectedState === '' || !hash_equals($expectedState, $state)) {
    http_response_code(400);
    exit('Invalid OAuth state.');
}
if ($code === '') {
    http_response_code(400);
    exit('Missing authorization code.');
}

try {
    $token = discord_exchange_code($config, $code);
    if (empty($token['access_token'])) {
        throw new DiscordApiException('No access token returned.');
    }

    $user = discord_get_current_user($token['access_token']);
    $userId = $user['id'] ?? null;
    $username = $user['username'] ?? 'unknown';

    if ($userId === null || !user_is_authorized($config, $pdo, $userId)) {
        http_response_code(403);
        exit('Your Discord account is not authorized to access this admin panel.');
    }

    record_admin_login($pdo, $userId, $username);

    session_regenerate_id(true);
    $_SESSION['admin_discord_id'] = $userId;
    $_SESSION['admin_username'] = $username;

    header('Location: dashboard.php');
} catch (DiscordApiException $e) {
    error_log('OAuth login failed: ' . $e->getMessage());
    http_response_code(502);
    exit('Login failed while contacting Discord. Please try again.');
}
