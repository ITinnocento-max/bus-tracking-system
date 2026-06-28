<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

// ESP32 calls via GET, expects JSON with status, phone, message, ticket_id

$bus_id_param = $_GET['bus_id'] ?? null;

try {
    $db = getDb();

    $stmt = $db->prepare("
        SELECT s.id AS sms_id, s.booking_id, s.phone, s.message
        FROM sms_logs s
        WHERE s.status = 'pending'
        ORDER BY s.id ASC
        LIMIT 1
    ");
    $stmt->execute();
    $sms = $stmt->fetch();

    if (!$sms) {
        echo json_encode(['status' => 'error', 'message' => 'No pending SMS']);
        exit;
    }

    echo json_encode([
        'status'    => 'success',
        'ticket_id' => (string)$sms['booking_id'],
        'sms_id'    => (string)$sms['sms_id'],
        'phone'     => $sms['phone'],
        'message'   => $sms['message']
    ]);
} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Service unavailable']);
}
