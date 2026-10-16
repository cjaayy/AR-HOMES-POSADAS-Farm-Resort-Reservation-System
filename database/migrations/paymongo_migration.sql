-- =====================================================
-- PayMongo Payment Integration Migration
-- AR Homes Posadas Farm Resort Reservation System
-- Run this SQL to add PayMongo payment columns to the reservations table
-- =====================================================

-- Add PayMongo payment columns to reservations table
ALTER TABLE reservations
    -- PayMongo Source/Payment columns for downpayment
    ADD COLUMN paymongo_source_id VARCHAR(255) NULL AFTER payment_method,
    ADD COLUMN paymongo_payment_id VARCHAR(255) NULL AFTER paymongo_source_id,
    ADD COLUMN paymongo_payment_type VARCHAR(50) NULL AFTER paymongo_payment_id,
    
    -- PayMongo columns for full payment
    ADD COLUMN paymongo_full_payment_id VARCHAR(255) NULL AFTER full_payment_reference;

-- Add index for faster lookups
ALTER TABLE reservations
    ADD INDEX idx_paymongo_source (paymongo_source_id),
    ADD INDEX idx_paymongo_payment (paymongo_payment_id);

-- Show success message
SELECT 'PayMongo payment columns added successfully!' AS status;
