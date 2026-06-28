<?php
session_start();
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ../auth/login.php');
    exit;
}
require_once __DIR__ . '/../config/database.php';
try { $db = getDb(); } catch (Exception $e) { $db = null; }
$buses = [];
if ($db) {
    try { $buses = $db->query("SELECT * FROM buses ORDER BY bus_code")->fetchAll(); } catch (Exception $e) {}
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buses - Admin - Smart Bus Tracking</title>
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
<nav>
    <a href="../index.php" class="nav-brand"><?= icon('bus') ?> SmartBus Tracker</a>
    <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
    </button>
    <div class="nav-links" id="navLinks">
        <a href="../index.php">Live Tracking</a>
        <div class="nav-user">
            <span><?= icon('user') ?> <?= htmlspecialchars($_SESSION['full_name']) ?> (Admin)</span>
            <a href="../auth/logout.php" class="btn btn-sm btn-primary">Logout</a>
        </div>
    </div>
</nav>
<div class="admin-layout">
    <button class="hamburger" id="sidebarToggle" aria-label="Toggle sidebar" style="display:none;">
        <span></span><span></span><span></span>
    </button>
    <div class="admin-sidebar" id="adminSidebar">
        <h3>Admin Panel</h3>
        <a href="index.php"><?= icon('chart') ?> Dashboard</a>
        <a href="admin_buses.php" class="active"><?= icon('bus') ?> Buses</a>
        <a href="admin_bookings.php"><?= icon('ticket') ?> Bookings</a>
        <a href="admin_sms_logs.php"><?= icon('mail') ?> SMS Logs</a>
        <a href="admin_passengers.php"><?= icon('users') ?> Passengers</a>
    </div>
    <div class="sidebar-overlay" id="sidebarOverlay"></div>
    <div class="admin-content">
        <h2 style="margin-bottom:24px;"><?= icon('bus') ?> Bus Management</h2>
        <div class="card">
            <div class="table-container">
                <table>
                    <thead><tr>
                        <th>Code</th><th>Name</th><th>Total Seats</th><th>Status</th><th>Latitude</th><th>Longitude</th><th>Last Update</th>
                    </tr></thead>
                    <tbody>
                        <?php foreach ($buses as $bus): ?>
                        <tr>
                            <td><strong><?= sec($bus['bus_code']) ?></strong></td>
                            <td><?= sec($bus['bus_name']) ?></td>
                            <td><?= sec($bus['total_seats']) ?></td>
                            <td><span class="badge badge-<?= $bus['status'] === 'active' ? 'success' : 'danger' ?>"><?= sec($bus['status']) ?></span></td>
                            <td style="font-family:monospace;font-size:0.8rem;"><?= sec($bus['current_lat']) ?></td>
                            <td style="font-family:monospace;font-size:0.8rem;"><?= sec($bus['current_lng']) ?></td>
                            <td style="font-size:0.8rem;"><?= sec($bus['last_update']) ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
<script>
document.getElementById('hamburger')?.addEventListener('click', function() {
    this.classList.toggle('active');
    document.getElementById('navLinks').classList.toggle('open');
});
document.addEventListener('click', function(e) {
    const nav = document.querySelector('nav');
    if (nav && !nav.contains(e.target) && !e.target.closest('.admin-sidebar')) {
        document.getElementById('hamburger')?.classList.remove('active');
        document.getElementById('navLinks')?.classList.remove('open');
    }
});
if (window.innerWidth < 992) {
    document.getElementById('sidebarToggle').style.display = 'flex';
}
document.getElementById('sidebarToggle')?.addEventListener('click', function() {
    document.getElementById('adminSidebar').classList.toggle('open');
    document.getElementById('sidebarOverlay').classList.toggle('open');
});
document.getElementById('sidebarOverlay')?.addEventListener('click', function() {
    document.getElementById('adminSidebar').classList.remove('open');
    document.getElementById('sidebarOverlay').classList.remove('open');
});
</script>
</body>
</html>
