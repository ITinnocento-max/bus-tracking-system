<?php
header('Content-Type: text/plain');
require_once __DIR__ . '/../config/database.php';

// ESP32 calls via GET with ticket_id

$ticket_id = $_GET['ticket_id'] ?? $_POST['ticket_id'] ?? null;
$sms_id = $_GET['sms_id'] ?? $_POST['sms_id'] ?? null;

if (!$ticket_id && !$sms_id) {
    echo "ERROR: ticket_id or sms_id required";
    exit;
}

try {
    $db = getDb();

    if ($sms_id) {
        $stmt = $db->prepare("UPDATE sms_logs SET status = 'sent', sent_at = NOW() WHERE id = ? AND status = 'pending'");
        $stmt->execute([$sms_id]);
    } else {
        $stmt = $db->prepare("
            UPDATE sms_logs SET status = 'sent', sent_at = NOW() 
            WHERE booking_id = ? AND status = 'pending'
        ");
        $stmt->execute([$ticket_id]);
    }

    if ($stmt->rowCount() > 0) {
        if ($ticket_id) {
            $stmt2 = $db->prepare("UPDATE bookings SET sms_sent = 1 WHERE id = ?");
            $stmt2->execute([$ticket_id]);
        }
        echo "OK";
    } else {
        echo "ERROR: SMS not found or already sent";
    }
} catch (Exception $e) {
    echo "ERROR: Confirmation failed";
}
