<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 50;
$offset = ($page - 1) * $perPage;

$levelFilter = $_GET['level'] ?? '';
$allowedLevels = ['info', 'warning', 'error'];

$where = '';
$params = [];
if (in_array($levelFilter, $allowedLevels, true)) {
    $where = 'WHERE level = ?';
    $params[] = $levelFilter;
}

$stmt = $pdo->prepare("SELECT level, event_type, message, discord_user_id, created_at FROM logs $where ORDER BY id DESC LIMIT $perPage OFFSET $offset");
$stmt->execute($params);
$logs = $stmt->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Logs</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/partials/nav.php'; ?>
<div class="container">
    <h1>Logs</h1>
    <div class="card">
        <form method="get">
            <label>Filter by level</label>
            <select name="level" onchange="this.form.submit()">
                <option value="">All</option>
                <?php foreach ($allowedLevels as $lvl): ?>
                    <option value="<?= $lvl ?>" <?= $levelFilter === $lvl ? 'selected' : '' ?>><?= ucfirst($lvl) ?></option>
                <?php endforeach; ?>
            </select>
        </form>
    </div>

    <div class="card">
        <table>
            <tr><th>Time</th><th>Level</th><th>Event</th><th>Message</th><th>User ID</th></tr>
            <?php foreach ($logs as $log): ?>
            <tr>
                <td><?= htmlspecialchars($log['created_at'], ENT_QUOTES) ?></td>
                <td><span class="badge <?= htmlspecialchars($log['level'], ENT_QUOTES) ?>"><?= htmlspecialchars($log['level'], ENT_QUOTES) ?></span></td>
                <td><?= htmlspecialchars($log['event_type'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($log['message'], ENT_QUOTES) ?></td>
                <td><?= htmlspecialchars($log['discord_user_id'] ?? '', ENT_QUOTES) ?></td>
            </tr>
            <?php endforeach; ?>
        </table>
        <p>
            <?php if ($page > 1): ?><a href="?page=<?= $page - 1 ?>&level=<?= urlencode($levelFilter) ?>">Newer</a><?php endif; ?>
            <?php if (count($logs) === $perPage): ?> | <a href="?page=<?= $page + 1 ?>&level=<?= urlencode($levelFilter) ?>">Older</a><?php endif; ?>
        </p>
    </div>
</div>
</body>
</html>
