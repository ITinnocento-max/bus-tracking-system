<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

$bus_code = $_GET['bus_code'] ?? null;

if (!$bus_code) {
    jsonResponse(['status' => 'error', 'message' => 'bus_code required'], 400);
}

try {
    $db = getDb();

    $stmt = $db->prepare("
        SELECT s.id, s.seat_number, s.status, s.ir_sensor_status
        FROM seats s
        JOIN buses b ON s.bus_id = b.id
        WHERE b.bus_code = ?
        ORDER BY s.seat_number
    ");
    $stmt->execute([$bus_code]);
    $seats = $stmt->fetchAll();

    jsonResponse(['status' => 'success', 'data' => $seats, 'bus_code' => $bus_code]);
} catch (Exception $e) {
    errorResponse();
}
