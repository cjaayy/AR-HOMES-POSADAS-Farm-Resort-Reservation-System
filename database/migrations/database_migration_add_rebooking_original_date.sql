-- Migration: Add rebooking_original_date and rebooking_original_time columns
-- This ensures the original check-in date is preserved when rebookings are approved

-- Add columns if they don't exist
ALTER TABLE reservations 
ADD COLUMN IF NOT EXISTS rebooking_original_date DATE NULL AFTER rebooking_new_date,
ADD COLUMN IF NOT EXISTS rebooking_original_time TIME NULL AFTER rebooking_original_date;

-- For existing approved rebookings, try to extract original date from admin_notes
-- This is a one-time migration for existing data
UPDATE reservations 
SET rebooking_original_date = SUBSTRING_INDEX(SUBSTRING_INDEX(admin_notes, '[Original Date: ', -1), ' ', 1)
WHERE rebooking_approved = 1 
AND rebooking_original_date IS NULL
AND admin_notes LIKE '%[Original Date: %'
AND SUBSTRING_INDEX(SUBSTRING_INDEX(admin_notes, '[Original Date: ', -1), ' ', 1) REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$';

-- Extract time if available
UPDATE reservations 
SET rebooking_original_time = TIME(STR_TO_DATE(SUBSTRING_INDEX(SUBSTRING_INDEX(admin_notes, '[Original Date: ', -1), ']', 1), '%Y-%m-%d %H:%i:%s'))
WHERE rebooking_approved = 1 
AND rebooking_original_time IS NULL
AND admin_notes LIKE '%[Original Date: %'
AND SUBSTRING_INDEX(SUBSTRING_INDEX(admin_notes, '[Original Date: ', -1), ']', 1) LIKE '%:%';

