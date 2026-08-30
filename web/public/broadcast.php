<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $message = trim((string) ($_POST['message'] ?? ''));
    $confirmed = isset($_POST['confirm']);

    if ($message === '') {
        $error = 'Message cannot be empty.';
    } elseif (mb_strlen($message) > 2000) {
        $error = 'Message must be 2000 characters or fewer (Discord DM limit).';
    } elseif (!$confirmed) {
        $error = 'You must confirm before sending a broadcast to all members.';
    } else {
        $stmt = $pdo->prepare('INSERT INTO broadcast_jobs (message, created_by) VALUES (?, ?)');
        $stmt->execute([$message, $_SESSION['admin_discord_id']]);
    }
}

$jobs = $pdo->query(
    'SELECT id, message, status, created_at, total_recipients, sent_count, failed_count ' .
    'FROM broadcast_jobs ORDER BY id DESC LIMIT 20'
)->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Broadcast</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/partials/nav.php'; ?>
<div class="container">
    <h1>Broadcast</h1>
    <p>Sends a direct message to every member of the server. The bot picks up pending jobs
       within about 15 seconds and paces sends to respect Discord's rate limits.</p>
    <?php if ($error): ?>
        <div class="card badge error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <div class="card">
        <form method="post">
            <?= csrf_field() ?>
            <label>Message</label>
            <textarea name="message" rows="4" maxlength="2000" required></textarea>
            <label><input type="checkbox" name="confirm" style="width:auto;display:inline-block;margin-right:0.5rem;">
                I understand this will DM every member of the server.</label>
            <button type="submit">Queue broadcast</button>
        </form>
    </div>

    <div class="card">
        <h2>Recent jobs</h2>
        <table>
            <tr><th>#</th><th>Message</th><th>Status</th><th>Sent</th><th>Failed</th><th>Total</th><th>Created</th></tr>
            <?php foreach ($jobs as $job): ?>
            <tr>
                <td><?= (int) $job['id'] ?></td>
                <td><?= htmlspecialchars(mb_strimwidth($job['message'], 0, 60, '…'), ENT_QUOTES) ?></td>
                <td><span class="badge"><?= htmlspecialchars($job['status'], ENT_QUOTES) ?></span></td>
                <td><?= (int) $job['sent_count'] ?></td>
                <td><?= (int) $job['failed_count'] ?></td>
                <td><?= (int) $job['total_recipients'] ?></td>
                <td><?= htmlspecialchars($job['created_at'], ENT_QUOTES) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
