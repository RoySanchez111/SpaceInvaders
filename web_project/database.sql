-- =============================================================================
-- Base de Datos: game_portal_db (Space Invaders)
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `game_portal_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `game_portal_db`;

-- -----------------------------------------------------------------------------
-- Tabla de Usuarios con Contraseña HASH y Token JSON de Sesión
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `users` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL UNIQUE,
    `password_hash` VARCHAR(255) NOT NULL,
    `auth_token` VARCHAR(255) NULL,
    `token_expires_at` DATETIME NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_username` (`username`),
    INDEX `idx_token` (`auth_token`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Tabla de Puntuaciones
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `players_scores` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL,
    `score` INT UNSIGNED NOT NULL DEFAULT 0,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_score` (`score` DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Registros iniciales de prueba
-- -----------------------------------------------------------------------------
INSERT INTO `players_scores` (`username`, `score`, `created_at`) VALUES
('Alex', 1500, NOW() - INTERVAL 2 DAY),
('Sara', 1250, NOW() - INTERVAL 1 DAY),
('Carlos', 980, NOW() - INTERVAL 10 HOUR),
('Elena', 720, NOW() - INTERVAL 4 HOUR),
('David', 500, NOW() - INTERVAL 1 HOUR);
