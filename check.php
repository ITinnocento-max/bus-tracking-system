<?php
header('Content-Type: text/plain');
echo "PHP Version: " . PHP_VERSION . "\n";
echo "Server: " . ($_SERVER['SERVER_NAME'] ?? 'unknown') . "\n\n";

echo "--- Testing PDO MySQL ---\n";
if (!class_exists('PDO')) {
    echo "PDO: NOT AVAILABLE\n";
    exit;
}
echo "PDO: Available\n";
$drivers = PDO::getAvailableDrivers();
echo "PDO Drivers: " . implode(', ', $drivers) . "\n";

echo "\n--- Testing config/helpers.php ---\n";
$hp = __DIR__ . '/config/helpers.php';
if (is_file($hp)) {
    echo "config/helpers.php: EXISTS (" . filesize($hp) . "B)\n";
    require_once $hp;
    $test = icon('bus');
    echo "icon('bus') returned: " . (strlen($test) > 0 ? strlen($test) . " chars" : "EMPTY") . "\n";
} else {
    echo "config/helpers.php: MISSING\n";
}

echo "\n--- Testing config/database.php ---\n";
$dp = __DIR__ . '/config/database.php';
if (is_file($dp)) {
    echo "config/database.php: EXISTS (" . filesize($dp) . "B)\n";
} else {
    echo "config/database.php: MISSING\n";
}

echo "\n--- Testing assets ---\n";
foreach (['assets/css/style.css', 'assets/icons/bus.svg'] as $f) {
    echo $f . ": " . (is_file(__DIR__ . '/' . $f) ? 'EXISTS' : 'MISSING') . "\n";
}

echo "\n--- Testing DB Connection ---\n";
require_once $dp;
try {
    $db = getDb();
    echo "DB Connection: OK\n";
    $tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Tables: " . implode(', ', $tables) . "\n";
    foreach (['buses', 'bookings', 'users', 'seats', 'sms_logs'] as $t) {
        try {
            $c = $db->query("SELECT COUNT(*) FROM $t")->fetchColumn();
            echo "  $t: $c rows\n";
        } catch (Exception $e) {
            echo "  $t: ERROR - " . $e->getMessage() . "\n";
        }
    }
} catch (Exception $e) {
    echo "DB Connection: FAILED - " . $e->getMessage() . "\n";
}
