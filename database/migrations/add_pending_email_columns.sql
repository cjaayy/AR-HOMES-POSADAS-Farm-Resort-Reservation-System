-- Add Pending Email Columns for Email Change Verification
-- AR Homes Posadas Farm Resort Reservation System
-- 
-- This migration adds columns to support email change verification
-- When admin changes a user's email, it goes to pending until verified

-- Add pending_email column (stores the new email waiting for verification)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS pending_email VARCHAR(255) DEFAULT NULL AFTER email;

-- Add pending_email_token column (stores the verification token hash)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS pending_email_token VARCHAR(64) DEFAULT NULL AFTER pending_email;

-- Add pending_email_expires column (stores when the verification expires)
ALTER TABLE users 
ADD COLUMN IF NOT EXISTS pending_email_expires DATETIME DEFAULT NULL AFTER pending_email_token;

-- Add index for faster token lookups
CREATE INDEX IF NOT EXISTS idx_pending_email_token ON users(pending_email_token);

-- Add index for finding users by pending email
CREATE INDEX IF NOT EXISTS idx_pending_email ON users(pending_email);
