-- =============================================================================
-- Base de Datos: game_portal_db
-- Sistema: Portal Táctico Arcade (Space Invaders / Core Defender)
-- Servidor: Nginx + PHP 8.5 + MariaDB
-- =============================================================================

CREATE DATABASE IF NOT EXISTS `game_portal_db`
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE `game_portal_db`;

-- -----------------------------------------------------------------------------
-- Estructura de la Tabla: players_scores
-- -----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `players_scores` (
    `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    `username` VARCHAR(50) NOT NULL COMMENT 'Callsign del Comandante',
    `faction` VARCHAR(50) NOT NULL DEFAULT 'Solar Vanguard' COMMENT 'Facción de combate',
    `score` INT UNSIGNED NOT NULL DEFAULT 0 COMMENT 'Puntuación máxima alcanzada',
    `wave_reached` INT UNSIGNED NOT NULL DEFAULT 1 COMMENT 'Oleada final de supervivencia',
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP COMMENT 'Fecha y hora del registro',
    INDEX `idx_score_wave` (`score` DESC, `wave_reached` DESC),
    INDEX `idx_username` (`username`),
    INDEX `idx_created_at` (`created_at`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------------------------
-- Registros Semilla de Prueba (5 Comandantes de Élite)
-- -----------------------------------------------------------------------------
INSERT INTO `players_scores` (`username`, `faction`, `score`, `wave_reached`, `created_at`) VALUES
('Cmdr_Viper', 'Solar Vanguard', 45200, 14, NOW() - INTERVAL 2 DAY),
('NebulaGhost', 'Void Syndicate', 38950, 12, NOW() - INTERVAL 1 DAY),
('Aegis_Prime', 'Cyber Ronin', 29400, 9, NOW() - INTERVAL 12 HOUR),
('StarCruiser99', 'Nebula Fleet', 21100, 7, NOW() - INTERVAL 4 HOUR),
('NovaStrike', 'Solar Vanguard', 15800, 5, NOW() - INTERVAL 1 HOUR);

