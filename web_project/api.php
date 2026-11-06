<?php
/**
 * API REST para Space Invaders
 * Autenticación Segura con Token JSON y Contraseña HASH (BCRYPT)
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';

function jsonOut(bool $ok, string $msg, $data = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode([
        'success'   => $ok,
        'message'   => $msg,
        'timestamp' => time(),
        'data'      => $data
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDatabaseConnection();

// Migración y aseguramiento de tablas en MariaDB
if ($pdo !== null) {
    try {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `users` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL UNIQUE,
                `password_hash` VARCHAR(255) NOT NULL DEFAULT '',
                `auth_token` VARCHAR(255) NULL,
                `token_expires_at` DATETIME NULL,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_username` (`username`),
                INDEX `idx_token` (`auth_token`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

            CREATE TABLE IF NOT EXISTS `players_scores` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL,
                `score` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_score` (`score` DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");

        // Si la tabla users ya existía de versiones anteriores sin password_hash o token, añadirlas
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `password_hash` VARCHAR(255) NOT NULL DEFAULT ''"); } catch(Exception $e1) {
            try { $pdo->exec("ALTER TABLE `users` ADD `password_hash` VARCHAR(255) NOT NULL DEFAULT ''"); } catch(Exception $e2) {}
        }
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `auth_token` VARCHAR(255) NULL"); } catch(Exception $e1) {
            try { $pdo->exec("ALTER TABLE `users` ADD `auth_token` VARCHAR(255) NULL"); } catch(Exception $e2) {}
        }
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `token_expires_at` DATETIME NULL"); } catch(Exception $e1) {
            try { $pdo->exec("ALTER TABLE `users` ADD `token_expires_at` DATETIME NULL"); } catch(Exception $e2) {}
        }
    } catch (Exception $e) {
        error_log("[Database Setup Warning] " . $e->getMessage());
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? 'submit_score' : 'leaderboard');

// Leer datos JSON o form-urlencoded
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true);
if (!is_array($payload)) {
    $payload = $_POST;
}

// Extraer Bearer Token del header HTTP o payload
function extractToken(array $payload): ?string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
        return trim($m[1]);
    }
    return !empty($payload['token']) ? trim($payload['token']) : null;
}

// Helper para validar formato de usuario
function cleanUsername(string $raw): string {
    $cleaned = preg_replace('/[^\w\s\-\.]/u', '', trim($raw));
    return trim(preg_replace('/\s+/', ' ', $cleaned));
}

// -----------------------------------------------------------------------------
// 1. REGISTRO DE USUARIO
// -----------------------------------------------------------------------------
if ($action === 'register') {
    if ($method !== 'POST') jsonOut(false, 'Método HTTP inválido', null, 405);

    $username = cleanUsername($payload['username'] ?? '');
    $password = trim($payload['password'] ?? '');

    if (mb_strlen($username) < 2 || mb_strlen($username) > 25) {
        jsonOut(false, 'El nombre debe tener entre 2 y 25 caracteres.', null, 400);
    }
    if (mb_strlen($password) < 3) {
        jsonOut(false, 'La contraseña debe tener al menos 3 caracteres.', null, 400);
    }

    if ($pdo === null) jsonOut(false, 'Base de datos no disponible.', null, 503);

    try {
        // Verificar si el usuario ya existe
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(:u)");
        $checkStmt->execute([':u' => $username]);
        if ($checkStmt->fetch()) {
            jsonOut(false, 'Este nombre de usuario ya está en uso.', ['user_exists' => true], 409);
        }

        // Hashear contraseña con BCRYPT
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

        // Generar Token de sesión
        $token = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, auth_token, token_expires_at, created_at)
            VALUES (:u, :p, :t, DATE_ADD(NOW(), INTERVAL 30 DAY), NOW())
        ");
        $stmt->execute([
            ':u' => $username,
            ':p' => $passwordHash,
            ':t' => $token
        ]);

        jsonOut(true, '¡Cuenta creada exitosamente!', [
            'username' => $username,
            'token'    => $token
        ], 201);
    } catch (PDOException $e) {
        jsonOut(false, 'Error al registrar el usuario: ' . $e->getMessage(), null, 500);
    }
}

// -----------------------------------------------------------------------------
// 2. INICIO DE SESIÓN
// -----------------------------------------------------------------------------
if ($action === 'login') {
    if ($method !== 'POST') jsonOut(false, 'Método HTTP inválido', null, 405);

    $username = cleanUsername($payload['username'] ?? '');
    $password = trim($payload['password'] ?? '');

    if (empty($username) || empty($password)) {
        jsonOut(false, 'Por favor ingresa usuario y contraseña.', null, 400);
    }

    if ($pdo === null) jsonOut(false, 'Base de datos no disponible.', null, 503);

    try {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE LOWER(username) = LOWER(:u)");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonOut(false, 'Usuario no encontrado.', ['not_found' => true], 404);
        }

        // Validar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            jsonOut(false, 'Contraseña incorrecta.', null, 401);
        }

        // Generar y actualizar Token JSON de sesión
        $token = bin2hex(random_bytes(32));
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET auth_token = :t, token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY) 
            WHERE id = :id
        ");
        $updateStmt->execute([':t' => $token, ':id' => $user['id']]);

        jsonOut(true, '¡Bienvenido de nuevo!', [
            'username' => $user['username'],
            'token'    => $token
        ]);
    } catch (PDOException $e) {
        jsonOut(false, 'Error en inicio de sesión: ' . $e->getMessage(), null, 500);
    }
}

// -----------------------------------------------------------------------------
// 3. VERIFICAR TOKEN DE SESIÓN ACTIVA
// -----------------------------------------------------------------------------
if ($action === 'verify_token') {
    $token = extractToken($payload);
    if (!$token) jsonOut(false, 'Token no proporcionado', null, 401);

    if ($pdo === null) jsonOut(false, 'BD desconectada', null, 503);

    try {
        $stmt = $pdo->prepare("
            SELECT username FROM users 
            WHERE auth_token = :t AND (token_expires_at IS NULL OR token_expires_at > NOW())
        ");
        $stmt->execute([':t' => $token]);
        $user = $stmt->fetch();

        if ($user) {
            jsonOut(true, 'Sesión válida', ['username' => $user['username']]);
        } else {
            jsonOut(false, 'Sesión expirada', null, 401);
        }
    } catch (PDOException $e) {
        jsonOut(false, 'Error verificando sesión', null, 500);
    }
}

// -----------------------------------------------------------------------------
// 3.5 CAMBIAR CONTRASEÑA (DESDE AJUSTES)
// -----------------------------------------------------------------------------
if ($action === 'change_password') {
    if ($method !== 'POST') jsonOut(false, 'Método HTTP inválido', null, 405);

    $token           = extractToken($payload);
    $usernameInput   = cleanUsername($payload['username'] ?? '');
    $currentPassword = trim($payload['current_password'] ?? '');
    $newPassword     = trim($payload['new_password'] ?? '');
    $confirmPassword = isset($payload['confirm_password']) ? trim($payload['confirm_password']) : $newPassword;

    if (empty($currentPassword) || empty($newPassword)) {
        jsonOut(false, 'Por favor completa la contraseña actual y la nueva contraseña.', null, 400);
    }

    if (mb_strlen($newPassword) < 3) {
        jsonOut(false, 'La nueva contraseña debe tener al menos 3 caracteres.', null, 400);
    }

    if ($newPassword !== $confirmPassword) {
        jsonOut(false, 'La nueva contraseña y su confirmación no coinciden.', null, 400);
    }

    if ($currentPassword === $newPassword) {
        jsonOut(false, 'La nueva contraseña debe ser diferente a la actual.', null, 400);
    }

    if ($pdo === null) jsonOut(false, 'Base de datos no disponible.', null, 503);

    try {
        $user = null;

        // Buscar primero por Token activo
        if ($token) {
            $stmt = $pdo->prepare("
                SELECT id, username, password_hash FROM users 
                WHERE auth_token = :t AND (token_expires_at IS NULL OR token_expires_at > NOW())
            ");
            $stmt->execute([':t' => $token]);
            $user = $stmt->fetch();
        }

        // Si no se encontró por token, buscar por nombre de usuario
        if (!$user && !empty($usernameInput)) {
            $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE LOWER(username) = LOWER(:u)");
            $stmt->execute([':u' => $usernameInput]);
            $user = $stmt->fetch();
        }

        if (!$user) {
            jsonOut(false, 'Debes iniciar sesión con una cuenta válida para cambiar la contraseña.', null, 401);
        }

        // Verificar que la contraseña actual sea correcta
        if (!password_verify($currentPassword, $user['password_hash'])) {
            jsonOut(false, 'La contraseña actual es incorrecta.', null, 401);
        }

        // Generar nuevo hash BCRYPT y renovar el token de sesión
        $newHash  = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $newToken = bin2hex(random_bytes(32));

        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = :p, auth_token = :t, token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY)
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':p'  => $newHash,
            ':t'  => $newToken,
            ':id' => $user['id']
        ]);

        jsonOut(true, '¡Contraseña actualizada correctamente!', [
            'username' => $user['username'],
            'token'    => $newToken
        ]);
    } catch (PDOException $e) {
        jsonOut(false, 'Error al actualizar la contraseña: ' . $e->getMessage(), null, 500);
    }
}

// -----------------------------------------------------------------------------
// 4. GUARDAR PUNTUACIÓN (Asociada al Usuario verificado por Token)
// -----------------------------------------------------------------------------
if ($action === 'submit_score') {
    if ($method !== 'POST') jsonOut(false, 'Método HTTP inválido', null, 405);

    $token = extractToken($payload);
    $score = isset($payload['score']) ? max(0, (int)$payload['score']) : 0;

    if ($pdo === null) jsonOut(false, 'Base de datos no disponible.', null, 503);

    $username = null;

    // 1. Validar el Token en MariaDB
    if ($token) {
        try {
            $stmt = $pdo->prepare("
                SELECT username FROM users 
                WHERE auth_token = :t AND (token_expires_at IS NULL OR token_expires_at > NOW())
            ");
            $stmt->execute([':t' => $token]);
            $user = $stmt->fetch();
            if ($user) {
                $username = $user['username'];
            }
        } catch (Exception $e) {}
    }

    // 2. Si no hay token válido, rechazar o requerir login
    if (!$username) {
        jsonOut(false, 'Debes iniciar sesión para que tu puntuación quede registrada.', null, 401);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO players_scores (username, score, created_at) VALUES (:u, :s, NOW())");
        $stmt->execute([':u' => $username, ':s' => $score]);

        // Calcular posición en el ranking
        $rStmt = $pdo->prepare("SELECT COUNT(*) + 1 as user_rank FROM players_scores WHERE score > :s");
        $rStmt->execute([':s' => $score]);
        $rank = (int)$rStmt->fetch()['user_rank'];

        jsonOut(true, 'Puntuación guardada exitosamente', [
            'username' => $username,
            'score'    => $score,
            'rank'     => $rank
        ]);
    } catch (PDOException $e) {
        jsonOut(false, 'Error al guardar puntuación: ' . $e->getMessage(), null, 500);
    }
}

// -----------------------------------------------------------------------------
// 5. LEADERBOARD (Top 10)
// -----------------------------------------------------------------------------
if ($action === 'leaderboard') {
    if ($pdo === null) {
        $mock = [
            ['rank' => 1, 'username' => 'Alex', 'score' => 1500, 'date' => 'Ayer'],
            ['rank' => 2, 'username' => 'Sara', 'score' => 1250, 'date' => 'Ayer'],
            ['rank' => 3, 'username' => 'Carlos', 'score' => 980, 'date' => 'Hoy'],
            ['rank' => 4, 'username' => 'Elena', 'score' => 720, 'date' => 'Hoy'],
            ['rank' => 5, 'username' => 'David', 'score' => 500, 'date' => 'Hoy']
        ];
        jsonOut(true, 'Puntuaciones demo', ['scores' => $mock, 'db_connected' => false]);
    }

    try {
        $stmt = $pdo->query("
            SELECT username, score, DATE_FORMAT(created_at, '%d/%m/%Y %H:%i') as date
            FROM players_scores
            ORDER BY score DESC, created_at ASC
            LIMIT 10
        ");
        $rows = $stmt->fetchAll();

        $scores = [];
        $rank = 1;
        foreach ($rows as $r) {
            $scores[] = [
                'rank'     => $rank++,
                'username' => htmlspecialchars($r['username'], ENT_QUOTES, 'UTF-8'),
                'score'    => (int)$r['score'],
                'date'     => $r['date']
            ];
        }

        jsonOut(true, 'Puntuaciones cargadas', ['scores' => $scores, 'db_connected' => true]);
    } catch (PDOException $e) {
        jsonOut(false, 'Error al consultar puntuaciones', null, 500);
    }
}

jsonOut(false, 'Acción no válida', null, 400);
