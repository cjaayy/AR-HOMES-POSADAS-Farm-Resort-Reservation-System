-- Add edit_count column to reviews table
-- AR Homes Posadas Farm Resort Reservation System
-- Run this SQL in phpMyAdmin or MySQL CLI to add edit tracking

-- Add edit_count column (default 0, max 3 edits allowed)
ALTER TABLE `reviews` ADD COLUMN `edit_count` TINYINT(1) DEFAULT 0 AFTER `helpful_count`;

-- Optional: Update existing reviews to have 0 edit count
UPDATE `reviews` SET `edit_count` = 0 WHERE `edit_count` IS NULL;
