<?php
/**
 * API REST para Space Invaders con Autenticación por Token JSON y Contraseña HASH
 * Endpoints:
 * - POST /api.php?action=register     (Crea cuenta con password_hash y genera token)
 * - POST /api.php?action=login        (Verifica hash, genera nuevo token y lo guarda en BD)
 * - POST /api.php?action=verify_token (Verifica validez del token)
 * - POST /api.php?action=submit_score (Guarda puntuación para el usuario del token)
 * - GET  /api.php?action=leaderboard  (Top 10 puntuaciones)
 */

header('Content-Type: application/json; charset=utf-8');
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

// Asegurar existencia de tablas si la BD está conectada
if ($pdo !== null) {
    try {
        $pdo->exec("
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

            CREATE TABLE IF NOT EXISTS `players_scores` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `username` VARCHAR(50) NOT NULL,
                `score` INT UNSIGNED NOT NULL DEFAULT 0,
                `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                INDEX `idx_score` (`score` DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
        ");
    } catch (Exception $e) {}
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? 'submit_score' : 'leaderboard');

// Obtener payload JSON o POST
$rawInput = file_get_contents('php://input');
$payload = json_decode($rawInput, true) ?: $_POST;

// Helper para extraer Token del header o payload
function getBearerToken(array $payload): ?string {
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? '';
    if (preg_match('/Bearer\s+(\S+)/i', $auth, $matches)) {
        return $matches[1];
    }
    return $payload['token'] ?? null;
}

// -----------------------------------------------------------------------------
// 1. REGISTRO DE USUARIO (Password HASH + Generación de Token JSON)
// -----------------------------------------------------------------------------
if ($action === 'register') {
    if ($method !== 'POST') jsonOut(false, 'Método no permitido', null, 405);

    $username = trim($payload['username'] ?? '');
    $password = trim($payload['password'] ?? '');

    $username = preg_replace('/[^\w\s\-\.]/u', '', $username);
    if (mb_strlen($username) < 3 || mb_strlen($username) > 20) {
        jsonOut(false, 'El nombre debe tener entre 3 y 20 caracteres.', null, 400);
    }
    if (mb_strlen($password) < 4) {
        jsonOut(false, 'La contraseña debe tener al menos 4 caracteres.', null, 400);
    }

    if ($pdo === null) jsonOut(false, 'Base de datos no disponible.', null, 503);

    try {
        // Verificar si el usuario ya existe
        $checkStmt = $pdo->prepare("SELECT id FROM users WHERE username = :u");
        $checkStmt->execute([':u' => $username]);
        if ($checkStmt->fetch()) {
            jsonOut(false, 'Ese nombre de usuario ya está registrado. Por favor inicia sesión.', null, 409);
        }

        // Hashear contraseña con BCRYPT
        $passwordHash = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);

        // Generar Token criptográficamente seguro
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

        jsonOut(true, 'Cuenta creada exitosamente', [
            'username' => $username,
            'token'    => $token
        ], 201);
    } catch (PDOException $e) {
        jsonOut(false, 'Error al registrar usuario: ' . $e->getMessage(), null, 500);
    }
}

// -----------------------------------------------------------------------------
// 2. INICIO DE SESIÓN (Verificación de Password HASH + Generación y guardado de Token)
// -----------------------------------------------------------------------------
if ($action === 'login') {
    if ($method !== 'POST') jsonOut(false, 'Método no permitido', null, 405);

    $username = trim($payload['username'] ?? '');
    $password = trim($payload['password'] ?? '');

    if (empty($username) || empty($password)) {
        jsonOut(false, 'Debes ingresar usuario y contraseña.', null, 400);
    }

    if ($pdo === null) jsonOut(false, 'Base de datos no disponible.', null, 503);

    try {
        $stmt = $pdo->prepare("SELECT id, username, password_hash FROM users WHERE username = :u");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        // Validar contraseña contra el HASH almacenado en la BD
        if (!$user || !password_verify($password, $user['password_hash'])) {
            jsonOut(false, 'Usuario o contraseña incorrectos.', null, 401);
        }

        // Generar nuevo token JSON de sesión y guardarlo en la BD
        $token = bin2hex(random_bytes(32));
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET auth_token = :t, token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY) 
            WHERE id = :id
        ");
        $updateStmt->execute([':t' => $token, ':id' => $user['id']]);

        jsonOut(true, 'Inicio de sesión exitoso', [
            'username' => $user['username'],
            'token'    => $token
        ]);
    } catch (PDOException $e) {
        jsonOut(false, 'Error en inicio de sesión: ' . $e->getMessage(), null, 500);
    }
}

// -----------------------------------------------------------------------------
// 3. VERIFICAR TOKEN
// -----------------------------------------------------------------------------
if ($action === 'verify_token') {
    $token = getBearerToken($payload);
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
            jsonOut(true, 'Token válido', ['username' => $user['username']]);
        } else {
            jsonOut(false, 'Token expirado o inválido', null, 401);
        }
    } catch (PDOException $e) {
        jsonOut(false, 'Error verificando token', null, 500);
    }
}

// -----------------------------------------------------------------------------
// 4. GUARDAR PUNTUACIÓN (Asociada al Usuario autenticado por Token)
// -----------------------------------------------------------------------------
if ($action === 'submit_score') {
    if ($method !== 'POST') jsonOut(false, 'Método no permitido', null, 405);

    $token = getBearerToken($payload);
    $score = isset($payload['score']) ? max(0, (int)$payload['score']) : 0;

    if ($pdo === null) jsonOut(false, 'Base de datos no disponible.', null, 503);

    $username = null;

    // 1. Validar el Token del usuario en la base de datos
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

    // 2. Si no hay token válido, rechazar o tomar fallback si vino nombre explícito
    if (!$username) {
        $username = trim($payload['username'] ?? '');
        $username = preg_replace('/[^\w\s\-\.]/u', '', $username);
        if (empty($username)) {
            jsonOut(false, 'No se pudo autenticar al usuario para guardar la puntuación. Inicia sesión.', null, 401);
        }
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
        jsonOut(false, 'Error guardando puntuación: ' . $e->getMessage(), null, 500);
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
