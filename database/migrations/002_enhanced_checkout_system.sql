-- ================================================================
-- AR Homes Posadas Farm Resort Reservation System
-- Enhanced Checkout Reservation Migration
-- ================================================================
-- This migration adds enhanced status tracking and audit fields
-- Run this migration after the initial database setup
-- ================================================================

-- Add new status value 'expired' if not exists
-- Note: MySQL doesn't support easy enum modification, so we'll add a check

-- First, check if the columns exist and add them if not

-- Add locked_until column for reservation expiry tracking
ALTER TABLE reservations 
ADD COLUMN IF NOT EXISTS locked_until DATETIME NULL AFTER date_locked;

-- Add form submission tracking
ALTER TABLE reservations 
ADD COLUMN IF NOT EXISTS submission_token VARCHAR(64) NULL AFTER updated_at;

-- Add IP address tracking for security
ALTER TABLE reservations 
ADD COLUMN IF NOT EXISTS created_ip VARCHAR(45) NULL AFTER submission_token;

-- Create index for faster expiry checks
CREATE INDEX IF NOT EXISTS idx_reservations_expiry 
ON reservations (status, downpayment_paid, locked_until);

-- Create index for user reservations
CREATE INDEX IF NOT EXISTS idx_reservations_user_status 
ON reservations (user_id, status, check_in_date);

-- Create index for date availability checks
CREATE INDEX IF NOT EXISTS idx_reservations_availability 
ON reservations (check_in_date, booking_type, status, downpayment_verified);

-- ================================================================
-- Audit Log Table for Status Transitions
-- ================================================================

CREATE TABLE IF NOT EXISTS reservation_audit_log (
    id INT AUTO_INCREMENT PRIMARY KEY,
    reservation_id VARCHAR(20) NOT NULL,
    action VARCHAR(50) NOT NULL,
    previous_status VARCHAR(30) NULL,
    new_status VARCHAR(30) NULL,
    actor_id VARCHAR(20) NULL,
    actor_type ENUM('user', 'admin', 'staff', 'system') DEFAULT 'system',
    details JSON NULL,
    ip_address VARCHAR(45) NULL,
    user_agent TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    INDEX idx_audit_reservation (reservation_id),
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_actor (actor_id, actor_type),
    
    FOREIGN KEY (reservation_id) 
        REFERENCES reservations(reservation_id) 
        ON DELETE CASCADE
);

-- ================================================================
-- Rate Limiting Table
-- ================================================================

CREATE TABLE IF NOT EXISTS rate_limits (
    id INT AUTO_INCREMENT PRIMARY KEY,
    identifier VARCHAR(255) NOT NULL,
    action VARCHAR(50) NOT NULL,
    attempts INT DEFAULT 1,
    first_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_attempt TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    blocked_until TIMESTAMP NULL,
    
    UNIQUE KEY unique_rate_limit (identifier, action),
    INDEX idx_rate_action (action, last_attempt)
);

-- ================================================================
-- CSRF Tokens Table (for persistent storage if needed)
-- ================================================================

CREATE TABLE IF NOT EXISTS csrf_tokens (
    id INT AUTO_INCREMENT PRIMARY KEY,
    session_id VARCHAR(128) NOT NULL,
    token VARCHAR(64) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    expires_at TIMESTAMP NOT NULL,
    used TINYINT(1) DEFAULT 0,
    
    UNIQUE KEY unique_token (token),
    INDEX idx_session (session_id),
    INDEX idx_expires (expires_at)
);

-- ================================================================
-- Form Submission Tracking (Prevent Duplicates)
-- ================================================================

CREATE TABLE IF NOT EXISTS form_submissions (
    id INT AUTO_INCREMENT PRIMARY KEY,
    form_token VARCHAR(64) NOT NULL,
    user_id VARCHAR(20) NULL,
    form_type VARCHAR(50) NOT NULL,
    result_id VARCHAR(50) NULL,
    ip_address VARCHAR(45) NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    UNIQUE KEY unique_form_token (form_token),
    INDEX idx_user_form (user_id, form_type),
    INDEX idx_created (created_at)
);

-- ================================================================
-- Cleanup procedure for expired tokens/records
-- ================================================================

DELIMITER //

CREATE PROCEDURE IF NOT EXISTS cleanup_expired_records()
BEGIN
    -- Delete expired CSRF tokens
    DELETE FROM csrf_tokens WHERE expires_at < NOW();
    
    -- Delete old rate limit records (older than 24 hours)
    DELETE FROM rate_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL 24 HOUR);
    
    -- Delete old form submissions (older than 7 days)
    DELETE FROM form_submissions WHERE created_at < DATE_SUB(NOW(), INTERVAL 7 DAY);
    
    -- Archive old audit logs (optional - move to archive table after 90 days)
    -- This is a placeholder for future implementation
END //

DELIMITER ;

-- ================================================================
-- Event to auto-expire reservations (if MySQL events are enabled)
-- ================================================================

-- Enable event scheduler if not already enabled
-- SET GLOBAL event_scheduler = ON;

DELIMITER //

CREATE EVENT IF NOT EXISTS expire_unpaid_reservations
ON SCHEDULE EVERY 15 MINUTE
DO
BEGIN
    UPDATE reservations 
    SET 
        status = 'expired',
        date_locked = 0,
        locked_until = NULL,
        admin_notes = CONCAT(
            COALESCE(admin_notes, ''),
            '\n[', NOW(), '] Auto-expired: Payment not received within 24 hours.'
        ),
        updated_at = NOW()
    WHERE 
        status = 'pending_payment'
        AND (downpayment_paid = 0 OR downpayment_paid IS NULL)
        AND (
            created_at <= DATE_SUB(NOW(), INTERVAL 24 HOUR)
            OR (locked_until IS NOT NULL AND locked_until <= NOW())
        );
END //

DELIMITER ;

-- ================================================================
-- View for dashboard statistics
-- ================================================================

CREATE OR REPLACE VIEW v_reservation_stats AS
SELECT 
    status,
    COUNT(*) as count,
    SUM(total_amount) as total_revenue,
    AVG(total_amount) as avg_amount
FROM reservations
WHERE created_at >= DATE_SUB(CURDATE(), INTERVAL 30 DAY)
GROUP BY status;

-- ================================================================
-- View for today's operations
-- ================================================================

CREATE OR REPLACE VIEW v_today_operations AS
SELECT 
    'check_in' as operation_type,
    reservation_id,
    guest_name,
    check_in_time,
    booking_type,
    status
FROM reservations 
WHERE check_in_date = CURDATE() 
  AND status = 'confirmed' 
  AND checked_in = 0

UNION ALL

SELECT 
    'check_out' as operation_type,
    reservation_id,
    guest_name,
    check_out_time,
    booking_type,
    status
FROM reservations 
WHERE check_out_date = CURDATE() 
  AND status = 'checked_in' 
  AND checked_out = 0;

-- ================================================================
-- Grant permissions (adjust as needed for your setup)
-- ================================================================

-- GRANT SELECT, INSERT, UPDATE, DELETE ON reservation_audit_log TO 'your_app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON rate_limits TO 'your_app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON csrf_tokens TO 'your_app_user'@'localhost';
-- GRANT SELECT, INSERT, UPDATE, DELETE ON form_submissions TO 'your_app_user'@'localhost';

-- ================================================================
-- End of Migration
-- ================================================================
