-- Fix Full Payment Verification for Online Payments
-- This script automatically verifies full payments that were made via PayMongo
-- but were not automatically verified due to the previous webhook logic

-- Update all reservations where:
-- 1. Full payment has been paid (full_payment_paid = 1)
-- 2. Full payment is not yet verified (full_payment_verified = 0)
-- 3. Payment was made via online methods (payment_method is set)
-- These should have been auto-verified when the payment was received

UPDATE reservations
SET 
    full_payment_verified = 1,
    full_payment_verified_at = full_payment_paid_at
WHERE 
    full_payment_paid = 1
    AND full_payment_verified = 0
    AND payment_method IS NOT NULL
    AND payment_method IN ('gcash', 'paymaya', 'grab_pay', 'card', 'dob_bpi', 'dob_ubp', 'atome');

-- Show the updated reservations
SELECT 
    reservation_id,
    guest_name,
    payment_method,
    full_payment_paid_at,
    full_payment_verified,
    full_payment_verified_at
FROM reservations
WHERE 
    full_payment_paid = 1
    AND payment_method IS NOT NULL;
