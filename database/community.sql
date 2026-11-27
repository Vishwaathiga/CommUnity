-- ============================================================
-- CommUnity - Unified Residential Community Platform
-- Database Schema + Demo Data
-- Import via phpMyAdmin into a database named `community`
-- ============================================================

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
SET AUTOCOMMIT = 0;
START TRANSACTION;
SET time_zone = "+00:00";

-- ------------------------------------------------------------
-- Table: users
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `users`;
CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `email` varchar(100) NOT NULL,
  `phone` varchar(20) NOT NULL,
  `password` varchar(255) NOT NULL,
  `role` enum('admin','watchman','resident') NOT NULL DEFAULT 'resident',
  `block` varchar(20) DEFAULT NULL,
  `door_number` varchar(20) DEFAULT NULL,
  `occupation` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `profile_image` varchar(255) DEFAULT NULL,
  `availability` enum('Available','Inside','Not Available') NOT NULL DEFAULT 'Available',
  `verification_status` enum('Verified','Pending') NOT NULL DEFAULT 'Pending',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: blocks
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `blocks`;
CREATE TABLE `blocks` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `block_name` varchar(50) NOT NULL,
  `floors` int(11) NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `block_name` (`block_name`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: visitors
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `visitors`;
CREATE TABLE `visitors` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `visitor_name` varchar(100) NOT NULL,
  `visitor_phone` varchar(20) NOT NULL,
  `visitor_type` enum('Guest','Delivery','Service Personnel','Cab / Driver','Other') NOT NULL DEFAULT 'Guest',
  `purpose` varchar(255) DEFAULT NULL,
  `expected_date` date NOT NULL,
  `expected_time` time DEFAULT NULL,
  `pin` varchar(6) NOT NULL,
  `pin_expiry` datetime NOT NULL,
  `arrival_time` datetime DEFAULT NULL,
  `approval_status` enum('Pending','Approved','Rejected') NOT NULL DEFAULT 'Pending',
  `entry_time` datetime DEFAULT NULL,
  `exit_time` datetime DEFAULT NULL,
  `status` enum('Expected','Arrived','Approved','Rejected','Inside','Exited') NOT NULL DEFAULT 'Expected',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `updated_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `resident_id` (`resident_id`),
  CONSTRAINT `visitors_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: deliveries
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `deliveries`;
CREATE TABLE `deliveries` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `delivery_person_name` varchar(100) NOT NULL,
  `arrival_time` datetime NOT NULL,
  `company_name` varchar(100) NOT NULL,
  `delivery_type` enum('Food','Courier','E-commerce','Grocery','Other') NOT NULL DEFAULT 'Other',
  `status` enum('Received','Notified','Collected') NOT NULL DEFAULT 'Received',
  `notification_sent` tinyint(1) NOT NULL DEFAULT 0,
  `collected_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `resident_id` (`resident_id`),
  CONSTRAINT `deliveries_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: skills
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `skills`;
CREATE TABLE `skills` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `skill_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text,
  `experience` varchar(50) DEFAULT NULL,
  `expected_cost` decimal(10,2) DEFAULT NULL,
  `availability` varchar(50) DEFAULT NULL,
  `response_time` varchar(50) DEFAULT NULL,
  `preferred_contact` varchar(50) DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `resident_id` (`resident_id`),
  CONSTRAINT `skills_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: help_requests
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `help_requests`;
CREATE TABLE `help_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `requester_id` int(11) NOT NULL,
  `skill_id` int(11) NOT NULL,
  `provider_id` int(11) DEFAULT NULL,
  `description` text NOT NULL,
  `preferred_date` date DEFAULT NULL,
  `preferred_time` time DEFAULT NULL,
  `urgency` enum('Low','Medium','High','Urgent') NOT NULL DEFAULT 'Medium',
  `location` varchar(100) DEFAULT NULL,
  `notes` text,
  `status` enum('Pending','Accepted','In Progress','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `rating` tinyint(1) DEFAULT NULL,
  `feedback` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `requester_id` (`requester_id`),
  KEY `skill_id` (`skill_id`),
  KEY `provider_id` (`provider_id`),
  CONSTRAINT `help_requests_ibfk_1` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `help_requests_ibfk_2` FOREIGN KEY (`skill_id`) REFERENCES `skills` (`id`) ON DELETE CASCADE,
  CONSTRAINT `help_requests_ibfk_3` FOREIGN KEY (`provider_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: resources
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `resources`;
CREATE TABLE `resources` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `owner_id` int(11) NOT NULL,
  `item_name` varchar(100) NOT NULL,
  `category` varchar(50) NOT NULL,
  `description` text,
  `image` varchar(255) DEFAULT NULL,
  `condition` varchar(50) DEFAULT NULL,
  `sharing_type` enum('Borrow','Lend','Rent','Sell','Donate','Exchange') NOT NULL DEFAULT 'Borrow',
  `price` decimal(10,2) DEFAULT NULL,
  `security_deposit` decimal(10,2) DEFAULT NULL,
  `availability` enum('Available','Currently Unavailable') NOT NULL DEFAULT 'Available',
  `preferred_contact` varchar(50) DEFAULT NULL,
  `location` varchar(100) DEFAULT NULL,
  `notes` text,
  `status` enum('Available','Request Pending','Reserved','Borrowed','Rented','Sold','Donated','Exchanged','Unavailable') NOT NULL DEFAULT 'Available',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `owner_id` (`owner_id`),
  CONSTRAINT `resources_ibfk_1` FOREIGN KEY (`owner_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: resource_requests
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `resource_requests`;
CREATE TABLE `resource_requests` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `message` text,
  `status` enum('Pending','Accepted','Rejected','Completed','Cancelled') NOT NULL DEFAULT 'Pending',
  `requested_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  `approved_at` datetime DEFAULT NULL,
  `completed_at` datetime DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `requester_id` (`requester_id`),
  CONSTRAINT `resource_requests_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resource_requests_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: notifications
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `notifications`;
CREATE TABLE `notifications` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) NOT NULL,
  `title` varchar(150) NOT NULL,
  `message` text NOT NULL,
  `type` enum('info','success','warning','danger','delivery','visitor','help','resource','system') NOT NULL DEFAULT 'info',
  `reference_id` int(11) DEFAULT NULL,
  `is_read` tinyint(1) NOT NULL DEFAULT 0,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `notifications_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: activity_logs
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `activity_logs`;
CREATE TABLE `activity_logs` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user_id` int(11) DEFAULT NULL,
  `action` varchar(100) NOT NULL,
  `module` varchar(50) NOT NULL,
  `description` text,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `user_id` (`user_id`),
  CONSTRAINT `activity_logs_ibfk_1` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ------------------------------------------------------------
-- Table: settings
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `settings`;
CREATE TABLE `settings` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `setting_key` varchar(100) NOT NULL,
  `setting_value` text,
  PRIMARY KEY (`id`),
  UNIQUE KEY `setting_key` (`setting_key`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: complaints
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `complaints`;
CREATE TABLE `complaints` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `complainer_id` int(11) NOT NULL,
  `type` enum('Maintenance','Security','Facility','Noise','Other') NOT NULL DEFAULT 'Other',
  `title` varchar(150) NOT NULL,
  `description` text NOT NULL,
  `status` enum('Open','In Progress','Resolved','Closed') NOT NULL DEFAULT 'Open',
  `assigned_to` int(11) DEFAULT NULL,
  `resolved_at` datetime DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `complainer_id` (`complainer_id`),
  KEY `assigned_to` (`assigned_to`),
  CONSTRAINT `complaints_ibfk_1` FOREIGN KEY (`complainer_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
  CONSTRAINT `complaints_ibfk_2` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: workers
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `workers`;
CREATE TABLE `workers` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `name` varchar(100) NOT NULL,
  `role` varchar(50) NOT NULL,
  `block` varchar(20) DEFAULT NULL,
  `door_number` varchar(20) DEFAULT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `emergency_contact` varchar(20) DEFAULT NULL,
  `status` enum('Active','Inactive','On Leave') NOT NULL DEFAULT 'Active',
  `hire_date` date DEFAULT NULL,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: resident_family_members
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `resident_family_members`;
CREATE TABLE `resident_family_members` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resident_id` int(11) NOT NULL,
  `family_member_name` varchar(100) NOT NULL,
  `relation` varchar(50) NOT NULL,
  `phone` varchar(20) DEFAULT NULL,
  `email` varchar(100) DEFAULT NULL,
  `address` varchar(100) DEFAULT NULL,
  `move_in_date` date DEFAULT NULL,
  `move_out_date` date DEFAULT NULL,
  `status` enum('Active','Moved Out') NOT NULL DEFAULT 'Active',
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `resident_id` (`resident_id`),
  CONSTRAINT `resident_family_members_ibfk_1` FOREIGN KEY (`resident_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: visitor_pin_attempts
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `visitor_pin_attempts`;
CREATE TABLE `visitor_pin_attempts` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `visitor_id` int(11) NOT NULL,
  `attempt_time` datetime NOT NULL,
  `success` tinyint(1) NOT NULL DEFAULT 0,
  `ip_address` varchar(45) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `visitor_id` (`visitor_id`),
  CONSTRAINT `visitor_pin_attempts_ibfk_1` FOREIGN KEY (`visitor_id`) REFERENCES `visitors` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Table: resource_transactions
-- ------------------------------------------------------------
DROP TABLE IF EXISTS `resource_transactions`;
CREATE TABLE `resource_transactions` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `resource_id` int(11) NOT NULL,
  `requester_id` int(11) NOT NULL,
  `borrowed_at` datetime NOT NULL,
  `due_date` datetime DEFAULT NULL,
  `returned_at` datetime DEFAULT NULL,
  `status` enum('Borrowed','Returned','Overdue','Lost') NOT NULL DEFAULT 'Borrowed',
  `return_fee` decimal(10,2) DEFAULT 0.00,
  `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (`id`),
  KEY `resource_id` (`resource_id`),
  KEY `requester_id` (`requester_id`),
  CONSTRAINT `resource_transactions_ibfk_1` FOREIGN KEY (`resource_id`) REFERENCES `resources` (`id`) ON DELETE CASCADE,
  CONSTRAINT `resource_transactions_ibfk_2` FOREIGN KEY (`requester_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- ============================================================
-- DEMO DATA
-- ============================================================

-- Blocks
INSERT INTO `blocks` (`block_name`, `floors`) VALUES
('Block A', 3),
('Block B', 4),
('Block C', 3);

-- Users (passwords hashed with bcrypt)
-- admin123 / watchman123 / resident123
INSERT INTO `users` (`name`, `email`, `phone`, `password`, `role`, `block`, `door_number`, `occupation`, `emergency_contact`, `profile_image`, `availability`, `verification_status`) VALUES
('System Admin', 'admin@community.com', '9876500001', '$2b$10$w1qglB0Kb4JmaT.cvusvh.XtnB2wyk88MAzYMCxkte.vL26wZeWES', 'admin', NULL, NULL, 'Administrator', '9876500000', NULL, 'Available', 'Verified'),
('Security Watchman', 'watchman@community.com', '9876500002', '$2b$10$/fLzVCOYBfvrLKdiW0XlnOdiyRQ6kssU9nw8wicRwJeVfqsFqmrLm', 'watchman', NULL, NULL, 'Security Guard', '9876500000', NULL, 'Available', 'Verified'),
('Arun Kumar', 'arun@community.com', '9876543210', '$2b$10$ExMW6WK70JazZokBvbT1auZ3Z3fpkmeoQLaLCbK0R6ljOx7cUKflC', 'resident', 'Block A', 'A-101', 'Software Engineer', '9876543211', NULL, 'Available', 'Verified'),
('Priya S', 'priya@community.com', '9876543211', '$2b$10$ExMW6WK70JazZokBvbT1auZ3Z3fpkmeoQLaLCbK0R6ljOx7cUKflC', 'resident', 'Block A', 'A-203', 'Doctor', '9876543212', NULL, 'Inside', 'Verified'),
('Vishwaathiga', 'vishwa@community.com', '9876543212', '$2b$10$ExMW6WK70JazZokBvbT1auZ3Z3fpkmeoQLaLCbK0R6ljOx7cUKflC', 'resident', 'Block B', 'B-302', 'Student', '9876543213', NULL, 'Not Available', 'Verified'),
('Karthik R', 'karthik@community.com', '9876543213', '$2b$10$ExMW6WK70JazZokBvbT1auZ3Z3fpkmeoQLaLCbK0R6ljOx7cUKflC', 'resident', 'Block B', 'B-405', 'Teacher', '9876543214', NULL, 'Available', 'Verified');

-- Skills
INSERT INTO `skills` (`resident_id`, `skill_name`, `category`, `description`, `experience`, `expected_cost`, `availability`, `response_time`, `preferred_contact`) VALUES
(3, 'Plumbing', 'Plumbing', 'Expert in leak repairs, pipe fittings, bathroom and kitchen plumbing issues.', '5 years', 300.00, 'Weekdays 6-9 PM', 'Within 2 hours', 'Phone'),
(3, 'Electrical Repair', 'Electrical', 'Home electrical repairs, fan/light installation, socket and switch fixes.', '4 years', 250.00, 'Weekends', 'Within 3 hours', 'Phone'),
(4, 'Computer Assistance', 'Computer / Technology', 'Laptop/PC troubleshooting, software installation, printer and network setup.', '6 years', 200.00, 'Anytime', 'Within 1 hour', 'WhatsApp'),
(5, 'Mathematics Tuition', 'Tuition', 'Mathematics tuition for classes 6-12, CBSE/State board.', '3 years', 500.00, 'Mon-Fri 5-8 PM', 'Same day', 'Phone'),
(6, 'Gardening', 'Gardening', 'Plant care, terrace gardening, pest control and organic fertilizers.', '2 years', 150.00, 'Morning 7-9 AM', 'Within 4 hours', 'Phone');

-- Resources
INSERT INTO `resources` (`owner_id`, `item_name`, `category`, `description`, `image`, `condition`, `sharing_type`, `price`, `security_deposit`, `availability`, `preferred_contact`, `location`, `notes`, `status`) VALUES
(3, 'Hammer', 'Tools', 'Claw hammer, good grip, suitable for home repairs.', NULL, 'Good', 'Borrow', NULL, 50.00, 'Available', 'Phone', 'Block A', 'Return within 2 days.', 'Available'),
(3, 'Ladder', 'Tools', '6-foot aluminum ladder, foldable.', NULL, 'Good', 'Rent', 50.00, 200.00, 'Available', 'Phone', 'Block A', 'Per day rental.', 'Available'),
(4, 'Drill Machine', 'Tools', 'Cordless drill machine with drill bits set.', NULL, 'Excellent', 'Rent', 100.00, 500.00, 'Available', 'WhatsApp', 'Block A', 'Per day rental.', 'Available'),
(5, 'Books', 'Books', 'Engineering mathematics and physics textbooks (set of 5).', NULL, 'Good', 'Lend', NULL, NULL, 'Available', 'Phone', 'Block B', 'Lend for 2 weeks.', 'Available'),
(6, 'Projector', 'Electronics', 'HD projector with HDMI cable and screen.', NULL, 'Good', 'Rent', 200.00, 1000.00, 'Available', 'Phone', 'Block B', 'Per day rental.', 'Available'),
(6, 'Kitchen Appliance', 'Kitchen', 'Mixer grinder, 3-jar, working condition.', NULL, 'Good', 'Sell', 800.00, NULL, 'Available', 'Phone', 'Block B', 'Negotiable price.', 'Available');

-- Settings
INSERT INTO `settings` (`setting_key`, `setting_value`) VALUES
('community_name', 'CommUnity'),
('community_tagline', 'Unified Residential Community Platform'),
('announcement', 'Welcome to CommUnity! Please keep your profile and availability updated.');

-- Workers (demo data)
INSERT INTO `workers` (`name`, `role`, `block`, `door_number`, `phone`, `email`, `status`, `hire_date`) VALUES
('Rahul Singh', 'Maintenance', 'Block A', 'A-101', '9876543215', 'rahul@community.com', 'Active', '2024-01-15'),
('Sunita Devi', 'Housekeeping', 'Block B', 'B-202', '9876543216', 'sunita@community.com', 'Active', '2024-03-01'),
('Mohan Patel', 'Security', 'Block A', 'A-301', '9876543217', 'mohan@community.com', 'Active', '2023-11-20');

-- Resident Family Members (demo data)
INSERT INTO `resident_family_members` (`resident_id`, `family_member_name`, `relation`, `phone`, `move_in_date`, `status`) VALUES
(3, 'Sunita Kumari', 'Spouse', '9876543211', '2020-01-15', 'Active'),
(3, 'Aditya Kumar', 'Son', '9876543217', '2021-03-20', 'Active'),
(4, 'Rohit Sharma', 'Son', '9876543218', '2019-06-10', 'Active'),
(5, 'Kavita Rani', 'Daughter', '9876543219', '2020-09-05', 'Active');

-- Visitor PIN Attempts (demo data)
INSERT INTO `visitor_pin_attempts` (`visitor_id`, `attempt_time`, `success`) VALUES
(1, NOW(), 0),
(1, DATE_SUB(NOW(), INTERVAL 1 HOUR), 0),
(2, NOW(), 1);

-- Resource Transactions (demo data)
INSERT INTO `resource_transactions` (`resource_id`, `requester_id`, `borrowed_at`, `due_date`, `status`) VALUES
(1, 4, NOW(), DATE_ADD(NOW(), INTERVAL 3 DAY), 'Borrowed'),
(3, 5, NOW(), DATE_ADD(NOW(), INTERVAL 5 DAY), 'Borrowed');

COMMIT;
