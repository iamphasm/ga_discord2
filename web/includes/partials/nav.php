<nav class="nav">
    <div class="nav-brand">Discord Admin</div>
    <div class="nav-links">
        <a href="dashboard.php">Dashboard</a>
        <a href="rules.php">Rules</a>
        <a href="announce.php">Announce</a>
        <a href="broadcast.php">Broadcast</a>
        <a href="settings.php">Settings</a>
        <a href="logs.php">Logs</a>
    </div>
    <div class="nav-user">
        <span><?= htmlspecialchars($_SESSION['admin_username'] ?? '', ENT_QUOTES) ?></span>
        <a href="logout.php">Log out</a>
    </div>
</nav>
