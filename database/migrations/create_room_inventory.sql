-- Create room_inventory table to track real-time room occupancy
CREATE TABLE IF NOT EXISTS `room_inventory` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `room_number` varchar(50) NOT NULL,
  `room_type` varchar(100) DEFAULT NULL,
  `status` enum('available','occupied','maintenance','reserved') DEFAULT 'available',
  `current_reservation_id` int(11) DEFAULT NULL,
  `last_updated` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `unique_room_number` (`room_number`),
  KEY `idx_status` (`status`),
  KEY `fk_reservation` (`current_reservation_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Insert sample room inventory based on available room types
-- Adjust these room numbers and types based on your actual resort rooms
INSERT INTO `room_inventory` (`room_number`, `room_type`, `status`) VALUES
('R101', 'Standard Room', 'available'),
('R102', 'Standard Room', 'available'),
('R103', 'Standard Room', 'available'),
('R104', 'Standard Room', 'available'),
('R105', 'Standard Room', 'available'),
('R201', 'Deluxe Room', 'available'),
('R202', 'Deluxe Room', 'available'),
('R203', 'Deluxe Room', 'available'),
('R204', 'Deluxe Room', 'available'),
('R205', 'Deluxe Room', 'available'),
('R301', 'Suite', 'available'),
('R302', 'Suite', 'available'),
('R303', 'Suite', 'available'),
('R304', 'Suite', 'available'),
('R305', 'Suite', 'available');
