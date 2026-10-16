-- =====================================================
-- Full Payment Online Option Migration
-- AR Homes Posadas Farm Resort Reservation System
-- Adds column to track PayMongo link for full payment/remaining balance
-- =====================================================

-- Add PayMongo full payment link column to reservations table (if not exists)
ALTER TABLE reservations
    ADD COLUMN IF NOT EXISTS paymongo_full_payment_link_id VARCHAR(255) NULL 
    COMMENT 'PayMongo link ID for full payment/remaining balance'
    AFTER paymongo_full_payment_id;

-- Add index for faster lookups
ALTER TABLE reservations
    ADD INDEX IF NOT EXISTS idx_paymongo_full_link (paymongo_full_payment_link_id);

-- Show success message
SELECT 'Full payment link column added successfully!' AS status;
