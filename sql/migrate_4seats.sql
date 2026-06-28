-- Migration: Remove extra seats (B1-C4), keep only A1-A4 per bus
-- Run this if you already imported the old 12-seat schema

-- 1. Delete bookings referencing seats B1-C4 first (foreign key constraint)
DELETE FROM sms_logs WHERE booking_id IN (
    SELECT id FROM bookings WHERE seat_id IN (
        SELECT id FROM seats WHERE seat_number IN ('B1','B2','B3','B4','C1','C2','C3','C4')
    )
);
DELETE FROM bookings WHERE seat_id IN (
    SELECT id FROM seats WHERE seat_number IN ('B1','B2','B3','B4','C1','C2','C3','C4')
);

-- 2. Delete extra seats
DELETE FROM seats WHERE seat_number IN ('B1','B2','B3','B4','C1','C2','C3','C4');

-- 3. Update bus total_seats
UPDATE buses SET total_seats = 4;
