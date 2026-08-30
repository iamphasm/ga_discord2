<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/bootstrap.php';
require_admin();

$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_verify();
    $action = $_POST['action'] ?? '';

    if ($action === 'add') {
        $title = trim((string) ($_POST['title'] ?? ''));
        $content = trim((string) ($_POST['content'] ?? ''));
        $position = (int) ($_POST['position'] ?? 0);

        if ($title === '' || $content === '') {
            $error = 'Title and content are required.';
        } elseif (mb_strlen($title) > 255) {
            $error = 'Title must be 255 characters or fewer.';
        } elseif (mb_strlen($content) > 4000) {
            $error = 'Content must be 4000 characters or fewer.';
        } else {
            $stmt = $pdo->prepare(
                'INSERT INTO rules (position, title, content, updated_by) VALUES (?, ?, ?, ?)'
            );
            $stmt->execute([$position, $title, $content, $_SESSION['admin_discord_id']]);
        }
    } elseif ($action === 'delete') {
        $id = (int) ($_POST['id'] ?? 0);
        $stmt = $pdo->prepare('DELETE FROM rules WHERE id = ?');
        $stmt->execute([$id]);
    }
}

$rules = $pdo->query('SELECT id, position, title, content FROM rules ORDER BY position ASC, id ASC')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <title>Rules</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body>
<?php require __DIR__ . '/../includes/partials/nav.php'; ?>
<div class="container">
    <h1>Rules</h1>
    <?php if ($error): ?>
        <div class="card badge error"><?= htmlspecialchars($error, ENT_QUOTES) ?></div>
    <?php endif; ?>

    <div class="card">
        <h2>Add rule</h2>
        <form method="post">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="add">
            <label>Position (order shown, lower first)</label>
            <input type="number" name="position" value="0">
            <label>Title</label>
            <input type="text" name="title" maxlength="255" required>
            <label>Content</label>
            <textarea name="content" rows="4" maxlength="4000" required></textarea>
            <button type="submit">Add rule</button>
        </form>
    </div>

    <div class="card">
        <h2>Current rules</h2>
        <table>
            <tr><th>#</th><th>Title</th><th>Content</th><th></th></tr>
            <?php foreach ($rules as $rule): ?>
            <tr>
                <td><?= (int) $rule['position'] ?></td>
                <td><?= htmlspecialchars($rule['title'], ENT_QUOTES) ?></td>
                <td><?= nl2br(htmlspecialchars($rule['content'], ENT_QUOTES)) ?></td>
                <td>
                    <form method="post" onsubmit="return confirm('Delete this rule?');">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="delete">
                        <input type="hidden" name="id" value="<?= (int) $rule['id'] ?>">
                        <button type="submit" class="danger">Delete</button>
                    </form>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>
    </div>
</div>
</body>
</html>
