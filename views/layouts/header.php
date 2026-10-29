<?php
declare(strict_types=1);
$currentUser = $_SESSION['user_id'] ?? null ? [
    'id'       => (int)$_SESSION['user_id'],
    'username' => $_SESSION['username'],
    'role'     => $_SESSION['role']
] : null;

$activePage = $activePage ?? 'dashboard';
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark">
    <title><?= e($pageTitle ?? 'TMC Console') ?> // Roadway Surveillance</title>
    <link rel="icon" type="image/svg+xml" href="<?= APP_URL ?>/assets/img/favicon.svg">
    <link rel="alternate icon" type="image/png" href="<?= APP_URL ?>/assets/img/favicon.png">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/style.css">
    <script>
        window.APP_URL = "<?= APP_URL ?>";
        window.STREAMER_PORT = <?= (int)($_ENV['STREAMER_PORT'] ?? 9999) ?>;
    </script>
</head>
<body>

<?php if ($currentUser): ?>
<header class="navbar">
    <div class="nav-brand">
        <img src="<?= APP_URL ?>/assets/img/logo.svg" alt="TMC Logo" width="28" height="28" style="display: block; border-radius: 5px;">
        <span>TRAFFIC OPS CONSOLE</span>
        <span class="brand-badge">NODE: :9999</span>
    </div>

    <nav class="nav-menu">
        <a href="<?= APP_URL ?>/dashboard" class="nav-link <?= $activePage === 'dashboard' ? 'active' : '' ?>">Live Grid</a>
        <a href="<?= APP_URL ?>/motion-events" class="nav-link <?= $activePage === 'motion-events' ? 'active' : '' ?>">
            Motion Logs
            <span id="motionUnreadBadge" class="brand-badge" style="background:#7f1d1d;border-color:#b91c1c;color:#fecaca;display:none;margin-left:0.3rem;"></span>
        </a>
        <?php if ($currentUser['role'] === 'admin'): ?>
            <a href="<?= APP_URL ?>/admin/cameras" class="nav-link <?= $activePage === 'cameras' ? 'active' : '' ?>">Perangkat CCTV</a>
            <a href="<?= APP_URL ?>/admin/users" class="nav-link <?= $activePage === 'users' ? 'active' : '' ?>">Operator</a>
        <?php endif; ?>
        <a href="<?= APP_URL ?>/about" class="nav-link <?= $activePage === 'about' ? 'active' : '' ?>">Tentang</a>
    </nav>

    <div class="nav-actions">
        <span class="clock-display" id="topNavClock">--:--:-- WIB</span>
        <div class="user-badge">
            <span class="role-pill role-<?= e($currentUser['role']) ?>"><?= e($currentUser['role']) ?></span>
            <span style="font-weight: 600; font-family: ui-monospace, monospace;"><?= e($currentUser['username']) ?></span>
        </div>
        <a href="<?= APP_URL ?>/logout" class="btn btn-secondary" style="padding: 0.3rem 0.65rem; font-size: 0.75rem;">Logout</a>
    </div>
</header>

<script>
function updateTopClock() {
    const el = document.getElementById('topNavClock');
    if (!el) return;
    const now = new Date();
    el.textContent = now.toLocaleTimeString('id-ID', { hour12: false }) + ' WIB';
}
setInterval(updateTopClock, 1000);
updateTopClock();
</script>
<?php endif; ?>

<main class="container">
