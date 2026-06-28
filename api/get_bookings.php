<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../config/database.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    jsonResponse(['status' => 'error', 'message' => 'Please login first'], 401);
}

$bus_code = $_GET['bus_code'] ?? null;

try {
    $db = getDb();

    if ($_SESSION['role'] === 'admin') {
        if ($bus_code) {
            $stmt = $db->prepare("
                SELECT bk.id, b.bus_code, b.bus_name, s.seat_number, 
                       bk.booking_date, bk.status, bk.payment_method, bk.created_at,
                       u.full_name, u.phone, u.email
                FROM bookings bk
                JOIN buses b ON bk.bus_id = b.id
                JOIN seats s ON bk.seat_id = s.id
                JOIN users u ON bk.user_id = u.id
                WHERE b.bus_code = ?
                ORDER BY bk.created_at DESC
            ");
            $stmt->execute([$bus_code]);
        } else {
            $stmt = $db->query("
                SELECT bk.id, b.bus_code, b.bus_name, s.seat_number, 
                       bk.booking_date, bk.status, bk.payment_method, bk.created_at,
                       u.full_name, u.phone, u.email
                FROM bookings bk
                JOIN buses b ON bk.bus_id = b.id
                JOIN seats s ON bk.seat_id = s.id
                JOIN users u ON bk.user_id = u.id
                ORDER BY bk.created_at DESC
            ");
        }
    } else {
        $stmt = $db->prepare("
            SELECT bk.id, b.bus_code, b.bus_name, s.seat_number, 
                   bk.booking_date, bk.status, bk.payment_method, bk.created_at,
                   u.full_name, u.phone
            FROM bookings bk
            JOIN buses b ON bk.bus_id = b.id
            JOIN seats s ON bk.seat_id = s.id
            JOIN users u ON bk.user_id = u.id
            WHERE bk.user_id = ?
            ORDER BY bk.created_at DESC
        ");
        $stmt->execute([$_SESSION['user_id']]);
    }

    $bookings = $stmt->fetchAll();
    jsonResponse(['status' => 'success', 'data' => $bookings]);
} catch (Exception $e) {
    errorResponse();
}
