-- Schema definition for RTSP Web CCTV Monitoring System
CREATE DATABASE IF NOT EXISTS `rstp_cctv_db` 
CHARACTER SET utf8mb4 
COLLATE utf8mb4_unicode_ci;

USE `rstp_cctv_db`;

CREATE TABLE IF NOT EXISTS `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('admin', 'observer') NOT NULL DEFAULT 'observer',
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `last_login_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_status` (`is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `cameras` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `name` VARCHAR(100) NOT NULL,
    `location` VARCHAR(255) NULL,
    `ip_address` VARCHAR(45) NOT NULL,
    `port` INT NOT NULL DEFAULT 554,
    `rtsp_path` VARCHAR(255) NOT NULL DEFAULT '/live/ch0',
    `username` VARCHAR(100) NULL,
    `encrypted_password` TEXT NULL,
    `iv_vector` VARCHAR(64) NULL,
    `status` ENUM('online', 'offline', 'error') NOT NULL DEFAULT 'offline',
    `motion_enabled` TINYINT(1) NOT NULL DEFAULT 1,
    `motion_sensitivity` ENUM('low', 'medium', 'high') NOT NULL DEFAULT 'medium',
    `last_seen_at` DATETIME NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cameras_status` (`status`),
    INDEX `idx_cameras_motion` (`motion_enabled`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `motion_events` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `camera_id` INT NOT NULL,
    `snapshot_path` VARCHAR(255) NOT NULL,
    `confidence_score` DECIMAL(5,2) NOT NULL DEFAULT 0.00,
    `is_viewed` TINYINT(1) NOT NULL DEFAULT 0,
    `detected_at` DATETIME NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_motion_camera` FOREIGN KEY (`camera_id`) REFERENCES `cameras` (`id`) ON DELETE CASCADE,
    INDEX `idx_motion_detected_at` (`detected_at`),
    INDEX `idx_motion_is_viewed` (`is_viewed`),
    INDEX `idx_motion_camera_time` (`camera_id`, `detected_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS `system_logs` (
    `id` BIGINT AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT NULL,
    `action` VARCHAR(50) NOT NULL,
    `target_entity` VARCHAR(50) NULL,
    `target_id` INT NULL,
    `ip_address` VARCHAR(45) NULL,
    `details` TEXT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_logs_action` (`action`),
    INDEX `idx_logs_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

