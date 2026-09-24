-- ========================================================
-- SISTEM IT TICKET & HELPDESK - DATABASE SCHEMA (MySQL / MariaDB)
-- Arsitektur Role-Based: Staf Kantor (Reporter) & Tim IT (Technician/Admin)
-- ========================================================

CREATE DATABASE IF NOT EXISTS `it_ticket_advent` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE `it_ticket_advent`;

SET FOREIGN_KEY_CHECKS = 0;
DROP TABLE IF EXISTS `ticket_logs`;
DROP TABLE IF EXISTS `ticket_replies`;
DROP TABLE IF EXISTS `tickets`;
DROP TABLE IF EXISTS `categories`;
DROP TABLE IF EXISTS `users`;
SET FOREIGN_KEY_CHECKS = 1;

-- 1. TABEL USERS
CREATE TABLE `users` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama` VARCHAR(100) NOT NULL,
    `email` VARCHAR(100) NOT NULL UNIQUE,
    `password` VARCHAR(255) NOT NULL,
    `role` ENUM('staff', 'it') NOT NULL DEFAULT 'staff',
    `departemen` VARCHAR(100) NOT NULL,
    `no_hp` VARCHAR(20) DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`),
    INDEX `idx_users_email` (`email`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABEL CATEGORIES
CREATE TABLE `categories` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nama_kategori` VARCHAR(100) NOT NULL,
    `icon` VARCHAR(50) DEFAULT 'bi-gear',
    `warna` VARCHAR(30) DEFAULT '#4f46e5',
    `deskripsi` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABEL TICKETS
CREATE TABLE `tickets` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `kode_tiket` VARCHAR(30) NOT NULL UNIQUE,
    `user_id` INT NOT NULL,
    `category_id` INT NOT NULL,
    `assigned_to` INT DEFAULT NULL,
    `judul` VARCHAR(255) NOT NULL,
    `deskripsi` TEXT NOT NULL,
    `lokasi` VARCHAR(150) NOT NULL,
    `prioritas` ENUM('low', 'medium', 'high', 'urgent') NOT NULL DEFAULT 'medium',
    `status` ENUM('open', 'in_progress', 'pending', 'resolved', 'closed') NOT NULL DEFAULT 'open',
    `lampiran_foto` VARCHAR(255) DEFAULT NULL,
    `solusi` TEXT DEFAULT NULL,
    `rating` TINYINT UNSIGNED DEFAULT NULL,
    `rating_feedback` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    `resolved_at` DATETIME DEFAULT NULL,
    CONSTRAINT `fk_tickets_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_tickets_category` FOREIGN KEY (`category_id`) REFERENCES `categories` (`id`) ON DELETE RESTRICT,
    CONSTRAINT `fk_tickets_assignee` FOREIGN KEY (`assigned_to`) REFERENCES `users` (`id`) ON DELETE SET NULL,
    INDEX `idx_tickets_code` (`kode_tiket`),
    INDEX `idx_tickets_status` (`status`),
    INDEX `idx_tickets_prioritas` (`prioritas`),
    INDEX `idx_tickets_user` (`user_id`),
    INDEX `idx_tickets_category` (`category_id`),
    INDEX `idx_tickets_created` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABEL TICKET_REPLIES (Percakapan & Catatan Internal IT)
CREATE TABLE `ticket_replies` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `pesan` TEXT NOT NULL,
    `lampiran` VARCHAR(255) DEFAULT NULL,
    `is_internal` TINYINT(1) NOT NULL DEFAULT 0 COMMENT '0: Publik untuk Staf & IT, 1: Rahasia internal Tim IT',
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_replies_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_replies_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_replies_ticket` (`ticket_id`, `is_internal`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABEL TICKET_LOGS (Audit Trail)
CREATE TABLE `ticket_logs` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `ticket_id` INT NOT NULL,
    `user_id` INT NOT NULL,
    `aksi` VARCHAR(100) NOT NULL,
    `catatan` TEXT DEFAULT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_logs_ticket` FOREIGN KEY (`ticket_id`) REFERENCES `tickets` (`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_logs_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`id`) ON DELETE CASCADE,
    INDEX `idx_logs_ticket` (`ticket_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
