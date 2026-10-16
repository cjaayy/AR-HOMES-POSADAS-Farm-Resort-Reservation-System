-- Migration: Add rebooking-related columns to reservations table
-- Date: 2026-01-04
-- Adds columns used by the rebooking workflow

ALTER TABLE `reservations`
  ADD COLUMN `rebooking_requested` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `rebooking_new_date` DATE NULL,
  ADD COLUMN `rebooking_reason` TEXT NULL,
  ADD COLUMN `rebooking_approved` TINYINT(1) NULL COMMENT 'NULL = pending, 1 = approved, -1 = rejected',
  ADD COLUMN `rebooking_approved_by` INT NULL,
  ADD COLUMN `rebooking_approved_at` DATETIME NULL,
  ADD COLUMN `admin_notes` TEXT NULL,
  ADD COLUMN `date_locked` TINYINT(1) NOT NULL DEFAULT 0,
  ADD COLUMN `locked_until` DATETIME NULL;

-- Optional: add index to speed up rebooking queries
CREATE INDEX idx_rebooking_requested ON `reservations` (`rebooking_requested`);
CREATE INDEX idx_rebooking_approved ON `reservations` (`rebooking_approved`);

-- Optional: add foreign key to admin_users if you use InnoDB and have admin_users table
-- ALTER TABLE `reservations` ADD CONSTRAINT fk_rebooking_approved_by FOREIGN KEY (rebooking_approved_by) REFERENCES admin_users(admin_id) ON DELETE SET NULL;

-- End of migration
