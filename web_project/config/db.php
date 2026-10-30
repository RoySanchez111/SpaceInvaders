<?php
/**
 * Configuración de Conexión a MariaDB / MySQL
 * Servidor: Nginx + PHP 8.5 (FPM)
 * Base de Datos: game_portal_db
 */

// Parámetros de conexión predeterminados del servidor
defined('DB_HOST') or define('DB_HOST', getenv('DB_HOST') ?: 'localhost');
defined('DB_PORT') or define('DB_PORT', getenv('DB_PORT') ?: '3306');
defined('DB_NAME') or define('DB_NAME', getenv('DB_NAME') ?: 'game_portal_db');
defined('DB_USER') or define('DB_USER', getenv('DB_USER') ?: 'admin_bd');
defined('DB_PASS') or define('DB_PASS', getenv('DB_PASS') ?: 'AdminPass2026!');
defined('DB_CHARSET') or define('DB_CHARSET', 'utf8mb4');

/**
 * Obtiene la instancia singleton de la conexión PDO.
 * 
 * @return PDO|null Retorna la conexión activa o null en caso de error
 */
function getDatabaseConnection(): ?PDO {
    static $pdo = null;

    if ($pdo !== null) {
        return $pdo;
    }

    $dsn = sprintf(
        'mysql:host=%s;port=%s;dbname=%s;charset=%s',
        DB_HOST,
        DB_PORT,
        DB_NAME,
        DB_CHARSET
    );

    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::ATTR_TIMEOUT            => 3,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES " . DB_CHARSET . " COLLATE utf8mb4_unicode_ci"
    ];

    try {
        $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
        return $pdo;
    } catch (PDOException $e) {
        // Registrar error en log del sistema sin exponer contraseñas al cliente
        error_log("[Database Connection Error] " . $e->getMessage());
        return null;
    }
}

