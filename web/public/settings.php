<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$keys = ['guild_id', 'admin_role_id', 'welcome_channel_id', 'welcome_message'];
$saved = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $stmt = $pdo->prepare('UPDATE settings SET `value` = ? WHERE `key` = ?');
    foreach ($keys as $key) {
        $value = trim((string) ($_POST[$key] ?? ''));
        $stmt->execute([$value, $key]);
    }
    $saved = true;
}

$placeholders = implode(',', array_fill(0, count($keys), '?'));
$stmt = $pdo->prepare("SELECT `key`, `value` FROM settings WHERE `key` IN ($placeholders)");
$stmt->execute($keys);
$settings = array_column($stmt->fetchAll(), 'value', 'key');
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Settings</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/partials/nav.php'; ?>
<div class="container">
    <h1>Settings</h1>
    <?php if ($saved): ?><div class="card badge info">Settings saved.</div><?php endif; ?>
    <div class="card">
        <form method="post">
            <?= csrf_field() ?>
            <label>Guild (server) ID</label>
            <input type="text" name="guild_id" value="<?= htmlspecialchars($settings['guild_id'] ?? '', ENT_QUOTES) ?>">

            <label>Admin role ID (members with this role can log into this panel)</label>
            <input type="text" name="admin_role_id" value="<?= htmlspecialchars($settings['admin_role_id'] ?? '', ENT_QUOTES) ?>">

            <label>Welcome channel ID</label>
            <input type="text" name="welcome_channel_id" value="<?= htmlspecialchars($settings['welcome_channel_id'] ?? '', ENT_QUOTES) ?>">

            <label>Welcome message (placeholders: {mention}, {user}, {guild})</label>
            <textarea name="welcome_message" rows="3"><?= htmlspecialchars($settings['welcome_message'] ?? '', ENT_QUOTES) ?></textarea>

            <button type="submit">Save settings</button>
        </form>
    </div>
</div>
</body>
</html>
