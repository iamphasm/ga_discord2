<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$error = null;

$welcomeChannelId = (string) ($pdo->query("SELECT `value` FROM settings WHERE `key` = 'welcome_channel_id'")->fetchColumn() ?: '');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $message = trim((string) ($_POST['message'] ?? ''));

    if ($welcomeChannelId === '') {
        $error = 'No welcome channel is configured yet — set one in Settings first.';
    } elseif ($message === '') {
        $error = 'Message cannot be empty.';
    } elseif (mb_strlen($message) > 2000) {
        $error = 'Message must be 2000 characters or fewer (Discord message limit).';
    } else {
        $stmt = $pdo->prepare('INSERT INTO channel_messages (message, created_by) VALUES (?, ?)');
        $stmt->execute([$message, $_SESSION['admin_discord_id']]);
    }
}

$messages = $pdo->query(
    'SELECT id, message, status, error, created_at, sent_at FROM channel_messages ORDER BY id DESC LIMIT 20'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Announce</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/partials/nav.php'; ?>
<div class="container">
    <h1>Post to welcome channel</h1>
    <p>Sends a single message to the configured welcome channel (not a DM to members).
       The bot picks up pending messages within about 15 seconds.</p>
    <?php if ($error): ?>
        <div class="card badge error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="post">
            <?= csrf_field() ?>
            <label>Message</label>
            <textarea name="message" rows="4" maxlength="2000" required></textarea>
            <button type="submit">Post message</button>
        </form>
    </div>

    <div class="card">
        <h2>Recent messages</h2>
        <table>
            <tr><th>#</th><th>Message</th><th>Status</th><th>Created</th><th>Sent</th></tr>
            <?php foreach ($messages as $m): ?>
            <tr>
                <td><?= (int) $m['id'] ?></td>
                <td><?= htmlspecialchars(mb_strimwidth($m['message'], 0, 60, '…'), ENT_QUOTES) ?></td>
                <td>
                    <span class="badge <?= $m['status'] === 'failed' ? 'error' : '' ?>"><?= htmlspecialchars($m['status'], ENT_QUOTES) ?></span>
                    <?php if ($m['error']): ?><br><small><?= htmlspecialchars($m['error'], ENT_QUOTES) ?></small><?php endif; ?>
                </td>
                <td><?= htmlspecialchars($m['created_at'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($m['sent_at'] ?? '', ENT_QUOTES) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
