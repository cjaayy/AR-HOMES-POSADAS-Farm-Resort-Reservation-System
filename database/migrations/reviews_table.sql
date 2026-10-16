-- Reviews Table Migration
-- AR Homes Posadas Farm Resort Reservation System
-- Run this SQL in phpMyAdmin or MySQL CLI

-- Create reviews table
CREATE TABLE IF NOT EXISTS `reviews` (
    `review_id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `user_id` VARCHAR(20) NOT NULL,
    `reservation_id` VARCHAR(25) NOT NULL,
    `rating` TINYINT(1) NOT NULL,
    `title` VARCHAR(255) NOT NULL,
    `content` TEXT NOT NULL,
    `helpful_count` INT(11) DEFAULT 0,
    `status` ENUM('active', 'hidden', 'deleted') DEFAULT 'active',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    CONSTRAINT `fk_review_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_review_reservation` FOREIGN KEY (`reservation_id`) 
        REFERENCES `reservations`(`reservation_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Ensure one review per reservation
    UNIQUE KEY `unique_reservation_review` (`reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Create review helpful votes table (to track who found reviews helpful)
CREATE TABLE IF NOT EXISTS `review_helpful_votes` (
    `vote_id` INT(11) AUTO_INCREMENT PRIMARY KEY,
    `review_id` INT(11) NOT NULL,
    `user_id` VARCHAR(20) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign key constraints
    CONSTRAINT `fk_vote_review` FOREIGN KEY (`review_id`) 
        REFERENCES `reviews`(`review_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    CONSTRAINT `fk_vote_user` FOREIGN KEY (`user_id`) 
        REFERENCES `users`(`user_id`) ON DELETE CASCADE ON UPDATE CASCADE,
    
    -- Ensure one vote per user per review
    UNIQUE KEY `unique_user_review_vote` (`review_id`, `user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Add indexes for better performance
CREATE INDEX `idx_reviews_user` ON `reviews`(`user_id`);
CREATE INDEX `idx_reviews_status` ON `reviews`(`status`);
CREATE INDEX `idx_reviews_rating` ON `reviews`(`rating`);
CREATE INDEX `idx_reviews_created` ON `reviews`(`created_at`);
