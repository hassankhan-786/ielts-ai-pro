-- IELTS AI Pro - Complete MySQL Relational Schema
-- Compatible with MySQL 5.7+ / 8.0+ and MariaDB 10.4+ (XAMPP Default)

SET FOREIGN_KEY_CHECKS = 0;

DROP TABLE IF EXISTS `attempt_answers`;
DROP TABLE IF EXISTS `attempts`;
DROP TABLE IF EXISTS `questions`;
DROP TABLE IF EXISTS `tests`;
DROP TABLE IF EXISTS `study_plans`;
DROP TABLE IF EXISTS `system_settings`;
DROP TABLE IF EXISTS `users`;

CREATE TABLE `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `full_name` VARCHAR(120) NOT NULL,
    `email` VARCHAR(190) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `role` ENUM('student', 'admin') NOT NULL DEFAULT 'student',
    `exam_type` ENUM('academic', 'general') NOT NULL DEFAULT 'academic',
    `target_band` DECIMAL(2,1) NOT NULL DEFAULT 7.5,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_users_role` (`role`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `tests` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `title` VARCHAR(200) NOT NULL,
    `module` ENUM('reading', 'listening', 'writing', 'speaking') NOT NULL,
    `exam_type` ENUM('academic', 'general', 'both') NOT NULL DEFAULT 'both',
    `duration_minutes` INT UNSIGNED NOT NULL DEFAULT 60,
    `description` TEXT NULL,
    `passage_or_script` LONGTEXT NULL,
    `audio_url` VARCHAR(500) NULL,
    `is_active` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_tests_module_active` (`module`, `is_active`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `questions` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `test_id` INT UNSIGNED NOT NULL,
    `question_number` INT UNSIGNED NOT NULL,
    `question_type` ENUM('mcq', 'tfng', 'fill_blank', 'matching', 'writing_task1', 'writing_task2', 'speaking_part1', 'speaking_part2', 'speaking_part3') NOT NULL,
    `question_text` TEXT NOT NULL,
    `options_json` JSON NULL,
    `correct_answer` TEXT NULL,
    `explanation` TEXT NULL,
    `points` INT UNSIGNED NOT NULL DEFAULT 1,
    CONSTRAINT `fk_questions_test` FOREIGN KEY (`test_id`) REFERENCES `tests`(`id`) ON DELETE CASCADE,
    INDEX `idx_questions_test_num` (`test_id`, `question_number`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attempts` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `test_id` INT UNSIGNED NOT NULL,
    `module` ENUM('reading', 'listening', 'writing', 'speaking') NOT NULL,
    `status` ENUM('in_progress', 'completed') NOT NULL DEFAULT 'in_progress',
    `started_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `completed_at` DATETIME NULL,
    `time_spent_seconds` INT UNSIGNED NOT NULL DEFAULT 0,
    `raw_score` INT UNSIGNED NOT NULL DEFAULT 0,
    `max_score` INT UNSIGNED NOT NULL DEFAULT 0,
    `band_score` DECIMAL(2,1) NOT NULL DEFAULT 0.0,
    `criteria_scores_json` JSON NULL,
    `ai_feedback_json` JSON NULL,
    CONSTRAINT `fk_attempts_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_attempts_test` FOREIGN KEY (`test_id`) REFERENCES `tests`(`id`) ON DELETE CASCADE,
    INDEX `idx_attempts_user_module` (`user_id`, `module`, `status`),
    INDEX `idx_attempts_completed` (`completed_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `attempt_answers` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `attempt_id` INT UNSIGNED NOT NULL,
    `question_id` INT UNSIGNED NOT NULL,
    `user_answer` LONGTEXT NULL,
    `is_correct` TINYINT(1) NULL DEFAULT NULL,
    `score_awarded` DECIMAL(4,1) NOT NULL DEFAULT 0.0,
    `ai_comment` TEXT NULL,
    CONSTRAINT `fk_answers_attempt` FOREIGN KEY (`attempt_id`) REFERENCES `attempts`(`id`) ON DELETE CASCADE,
    CONSTRAINT `fk_answers_question` FOREIGN KEY (`question_id`) REFERENCES `questions`(`id`) ON DELETE CASCADE,
    UNIQUE KEY `uq_attempt_question` (`attempt_id`, `question_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `study_plans` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `user_id` INT UNSIGNED NOT NULL,
    `current_band` DECIMAL(2,1) NOT NULL DEFAULT 6.0,
    `target_band` DECIMAL(2,1) NOT NULL DEFAULT 7.5,
    `weakest_module` VARCHAR(50) NOT NULL DEFAULT 'writing',
    `plan_json` JSON NOT NULL,
    `created_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT `fk_study_plans_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`id`) ON DELETE CASCADE,
    INDEX `idx_study_plans_user` (`user_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE `system_settings` (
    `setting_key` VARCHAR(100) PRIMARY KEY,
    `setting_value` TEXT NULL,
    `updated_at` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
