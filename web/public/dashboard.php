<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$ruleCount = (int) $pdo->query('SELECT COUNT(*) FROM rules')->fetchColumn();
$pendingBroadcasts = (int) $pdo->query("SELECT COUNT(*) FROM broadcast_jobs WHERE status IN ('pending','running')")->fetchColumn();
$recentJoins = (int) $pdo->query("SELECT COUNT(*) FROM logs WHERE event_type = 'member_join' AND created_at > NOW() - INTERVAL 7 DAY")->fetchColumn();
$recentErrors = (int) $pdo->query("SELECT COUNT(*) FROM logs WHERE level = 'error' AND created_at > NOW() - INTERVAL 7 DAY")->fetchColumn();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Dashboard</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/partials/nav.php'; ?>
<div class="container">
    <h1>Dashboard</h1>
    <div class="card">
        <p><strong>Rules configured:</strong> <?= $ruleCount ?></p>
        <p><strong>Pending/running broadcasts:</strong> <?= $pendingBroadcasts ?></p>
        <p><strong>Joins (last 7 days):</strong> <?= $recentJoins ?></p>
        <p><strong>Errors logged (last 7 days):</strong> <?= $recentErrors ?></p>
    </div>
</div>
</body>
</html>
