-- Migration: Add/Update Rebooking Columns
-- Date: 2026-01-03
-- Description: Ensures all rebooking-related columns exist in the reservations table

-- Add rebooking columns if they don't exist
-- Note: Run these manually if needed, as MySQL doesn't support IF NOT EXISTS for columns

-- Check and add rebooking_requested column
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS rebooking_requested TINYINT(1) DEFAULT 0;

-- Check and add rebooking_new_date column  
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS rebooking_new_date DATE NULL;

-- Check and add rebooking_reason column
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS rebooking_reason TEXT NULL;

-- Check and add rebooking_requested_at column
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS rebooking_requested_at DATETIME NULL;

-- Check and add rebooking_approved column (NULL = pending, 1 = approved, -1 = rejected)
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS rebooking_approved TINYINT(1) NULL;

-- Check and add rebooking_approved_by column
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS rebooking_approved_by INT NULL;

-- Check and add rebooking_approved_at column
ALTER TABLE reservations ADD COLUMN IF NOT EXISTS rebooking_approved_at DATETIME NULL;

-- Add index for faster rebooking queries
ALTER TABLE reservations ADD INDEX IF NOT EXISTS idx_rebooking_requested (rebooking_requested);
ALTER TABLE reservations ADD INDEX IF NOT EXISTS idx_rebooking_approved (rebooking_approved);

-- If the above syntax doesn't work (older MySQL versions), use these alternative statements:
-- 
-- First check if columns exist:
-- SHOW COLUMNS FROM reservations LIKE 'rebooking_requested';
-- 
-- Then add missing columns:
-- ALTER TABLE reservations ADD rebooking_requested TINYINT(1) DEFAULT 0;
-- ALTER TABLE reservations ADD rebooking_new_date DATE NULL;
-- ALTER TABLE reservations ADD rebooking_reason TEXT NULL;
-- ALTER TABLE reservations ADD rebooking_requested_at DATETIME NULL;
-- ALTER TABLE reservations ADD rebooking_approved TINYINT(1) NULL;
-- ALTER TABLE reservations ADD rebooking_approved_by INT NULL;
-- ALTER TABLE reservations ADD rebooking_approved_at DATETIME NULL;
