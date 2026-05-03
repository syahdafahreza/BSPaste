-- BSPaste Database Schema
-- Last updated: 2026-05-03 (Hybrid Vault Update)

SET SQL_MODE = "NO_AUTO_VALUE_ON_ZERO";
START TRANSACTION;
SET time_zone = "+00:00";

-- --------------------------------------------------------

--
-- Table structure for table `users`
--

CREATE TABLE `users` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `username` varchar(50) NOT NULL,
  `email` varchar(100) NOT NULL,
  `password` varchar(255) NOT NULL,
  `verification_token` varchar(100) DEFAULT NULL,
  `is_verified` tinyint(1) DEFAULT 0,
  `reset_token` varchar(100) DEFAULT NULL,
  `reset_token_expires_at` datetime DEFAULT NULL,
  
  -- UI Customization
  `theme` varchar(20) DEFAULT 'light',
  `accent_color` varchar(7) DEFAULT '#4e73df',
  `notes_per_page` int(11) DEFAULT 6,
  
  -- Hybrid Vault Security
  `is_migrated` tinyint(1) DEFAULT 0,
  `is_vault_active` tinyint(1) DEFAULT 0,
  `encryption_salt` varchar(64) DEFAULT NULL,
  `master_vault_key_enc_pass` text DEFAULT NULL,
  `master_vault_key_enc_recovery` text DEFAULT NULL,
  `recovery_key_salt` varchar(64) DEFAULT NULL,
  
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`),
  UNIQUE KEY `email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `notes`
--

CREATE TABLE `notes` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(50) NOT NULL,
  `title` text NOT NULL,
  `text` text NOT NULL,
  `created_at` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- --------------------------------------------------------

--
-- Table structure for table `activity_log`
--

CREATE TABLE `activity_log` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `user` varchar(50) NOT NULL,
  `action` varchar(100) NOT NULL,
  `details` text DEFAULT NULL,
  `timestamp` timestamp NOT NULL DEFAULT current_timestamp(),
  PRIMARY KEY (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

COMMIT;
