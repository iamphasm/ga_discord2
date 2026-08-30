<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';

if (is_logged_in()) {
    header('Location: dashboard.php');
    exit;
}

$state = bin2hex(random_bytes(16));
$_SESSION['oauth_state'] = $state;

$authorizeUrl = 'https://discord.com/oauth2/authorize?' . http_build_query([
    'client_id' => $config['discord']['client_id'],
    'redirect_uri' => $config['discord']['redirect_uri'],
    'response_type' => 'code',
    'scope' => 'identify',
    'state' => $state,
    'prompt' => 'consent',
]);
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Admin Login</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
    <div class="login-box card">
        <h1>Discord Admin</h1>
        <p>Sign in with the Discord account you use in the server.</p>
        <a href="<?= htmlspecialchars($authorizeUrl, ENT_QUOTES) ?>"><button>Log in with Discord</button></a>
    </div>
</body>
</html>
