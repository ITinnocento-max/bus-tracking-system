<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

try {
    $db = getDb();
    $stmt = $db->query("SELECT id, bus_code, bus_name, total_seats, current_lat, current_lng, status, last_update FROM buses ORDER BY bus_code");
    $buses = $stmt->fetchAll();
    jsonResponse(['status' => 'success', 'data' => $buses]);
} catch (Exception $e) {
    errorResponse();
}
