<?php
/**
 * API REST para Space Invaders
 * Autenticación y Transmisión Segura:
 * - Cifrado E2E de paquetes de entrada y salida (AES-256-CBC + HMAC-SHA256)
 * - Verificación de fortaleza de contraseña robusta (rechazo de contraseñas débiles)
 * - Protección contra ataques de fuerza bruta (bloqueo por intentos fallidos)
 * - Token JSON de sesión con HASH BCRYPT
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';

// Variable de estado que indica si la petición actual vino encriptada
$isRequestEncrypted = false;

/**
 * Obtiene la clave binaria derivada de 256 bits para cifrado AES
 */
function getSecurityKey(): string {
    return hash('sha256', APP_SECURITY_KEY, true);
}

/**
 * Desencripta paquetes entrantes si vienen en formato encriptado
 */
function decryptInputPayload(?string $rawInput, bool &$wasEncrypted): ?array {
    $wasEncrypted = false;
    if (empty($rawInput)) return null;

    $json = json_decode($rawInput, true);
    if (!is_array($json)) return null;

    // Detectar paquete cifrado
    if (!empty($json['encrypted']) && !empty($json['payload']) && !empty($json['iv'])) {
        $key = getSecurityKey();
        $payload = (string)$json['payload'];
        $iv = (string)$json['iv'];

        // Verificar firma HMAC si está presente
        if (!empty($json['signature'])) {
            $expectedSig = hash_hmac('sha256', $iv . ':' . $payload, $key);
            if (!hash_equals($expectedSig, (string)$json['signature'])) {
                jsonOut(false, 'Paquete alterado: la firma criptográfica no coincide.', null, 400);
            }
        }

        $rawCipher = base64_decode($payload);
        $rawIv = base64_decode($iv);
        if ($rawCipher === false || $rawIv === false || strlen($rawIv) !== 16) {
            jsonOut(false, 'Estructura o longitud del paquete cifrado inválida.', null, 400);
        }

        $decrypted = openssl_decrypt($rawCipher, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $rawIv);
        if ($decrypted === false) {
            jsonOut(false, 'No se pudo descifrar el paquete de entrada.', null, 400);
        }

        $data = json_decode($decrypted, true);
        if (!is_array($data)) {
            jsonOut(false, 'El paquete descifrado no contiene un JSON válido.', null, 400);
        }

        $wasEncrypted = true;
        return $data;
    }

    return $json;
}

/**
 * Encripta la respuesta en un paquete AES-256-CBC con firma HMAC
 */
function encryptOutputData(array $responseArray): array {
    $key = getSecurityKey();
    $jsonPlain = json_encode($responseArray, JSON_UNESCAPED_UNICODE);
    $iv = openssl_random_pseudo_bytes(16);
    $cipher = openssl_encrypt($jsonPlain, 'AES-256-CBC', $key, OPENSSL_RAW_DATA, $iv);

    $b64Payload = base64_encode($cipher);
    $b64Iv = base64_encode($iv);
    $sig = hash_hmac('sha256', $b64Iv . ':' . $b64Payload, $key);

    return [
        'encrypted' => true,
        'payload'   => $b64Payload,
        'iv'        => $b64Iv,
        'signature' => $sig,
        'timestamp' => time()
    ];
}

/**
 * Emite la respuesta JSON (cifrada o en plano según contexto) y termina la ejecución
 */
function jsonOut(bool $ok, string $msg, $data = null, int $code = 200): void {
    global $isRequestEncrypted;
    http_response_code($code);

    $response = [
        'success'   => $ok,
        'message'   => $msg,
        'timestamp' => time(),
        'data'      => $data
    ];

    // Verificar si el cliente solicitó cifrado explícito o si la petición fue cifrada
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $wantEnc = !empty($headers['X-Encrypt-Response'])
            || !empty($headers['x-encrypt-response'])
            || !empty($_SERVER['HTTP_X_ENCRYPT_RESPONSE'])
            || (isset($_GET['enc']) && $_GET['enc'] === '1');

    if (!empty($isRequestEncrypted) || $wantEnc) {
        header('X-Payload-Encrypted: 1');
        echo json_encode(encryptOutputData($response), JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode($response, JSON_UNESCAPED_UNICODE);
    }
    exit;
}

/**
 * Metodología de Contraseña Robusta
 * Evalúa longitud, mayúsculas, minúsculas, dígitos, caracteres especiales y listas negras.
 */
function evaluatePasswordPolicy(string $password, string $username = ''): array {
    $errors = [];
    $score = 0; // 0 a 5

    $len = mb_strlen($password);
    if ($len < 8) {
        $errors[] = "Debe tener al menos 8 caracteres (actual: {$len}).";
    } else {
        $score++;
        if ($len >= 12) $score++;
    }

    if (!preg_match('/[A-Z]/', $password)) {
        $errors[] = 'Debe incluir al menos una letra mayúscula (A-Z).';
    } else {
        $score++;
    }

    if (!preg_match('/[a-z]/', $password)) {
        $errors[] = 'Debe incluir al menos una letra minúscula (a-z).';
    } else {
        $score++;
    }

    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Debe incluir al menos un número (0-9).';
    } else {
        $score++;
    }

    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Debe incluir al menos un carácter especial o símbolo (!@#$%^&* etc.).';
    } else {
        $score++;
    }

    // No permitir que contenga el nombre de usuario
    if (!empty($username) && mb_strlen($username) >= 3) {
        if (stripos($password, $username) !== false) {
            $errors[] = 'Por seguridad, la contraseña no puede contener tu nombre de usuario.';
        }
    }

    // Lista negra de contraseñas débiles o triviales conocidas
    $blacklist = [
        '12345678', '123456789', '1234567890', 'password', 'password1', 'password123',
        'qwertyui', 'qwerty123', 'admin123', 'admin2026', 'contrasena', 'contraseña',
        'spaceinvaders', 'invaders123', 'abcdefgh', 'iloveyou', 'welcome1', 'master123',
        'secret123', 'superman1', '11111111', '00000000', 'temporal1'
    ];
    if (in_array(strtolower($password), $blacklist, true)) {
        $errors[] = 'Esta contraseña es demasiado común o predecible. Elige una más robusta.';
    }

    // Prohibir secuencias de caracteres repetidos idénticos (ej. aaaaaaaa)
    if (preg_match('/^(.)\1{4,}$/', $password)) {
        $errors[] = 'No se permiten caracteres repetidos de forma consecutiva.';
    }

    return [
        'valid'  => empty($errors),
        'errors' => $errors,
        'score'  => min(5, $score)
    ];
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
                `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0,
                `locked_until` DATETIME NULL,
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

        // Asegurar columnas requeridas si la tabla ya existía
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `password_hash` VARCHAR(255) NOT NULL DEFAULT ''"); } catch(Exception $e1) {
            try { $pdo->exec("ALTER TABLE `users` ADD `password_hash` VARCHAR(255) NOT NULL DEFAULT ''"); } catch(Exception $e2) {}
        }
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `auth_token` VARCHAR(255) NULL"); } catch(Exception $e1) {
            try { $pdo->exec("ALTER TABLE `users` ADD `auth_token` VARCHAR(255) NULL"); } catch(Exception $e2) {}
        }
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `token_expires_at` DATETIME NULL"); } catch(Exception $e1) {
            try { $pdo->exec("ALTER TABLE `users` ADD `token_expires_at` DATETIME NULL"); } catch(Exception $e2) {}
        }
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0"); } catch(Exception $e1) {
            try { $pdo->exec("ALTER TABLE `users` ADD `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0"); } catch(Exception $e2) {}
        }
        try { $pdo->exec("ALTER TABLE `users` ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL"); } catch(Exception $e1) {
            try { $pdo->exec("ALTER TABLE `users` ADD `locked_until` DATETIME NULL"); } catch(Exception $e2) {}
        }
    } catch (Exception $e) {
        error_log("[Database Setup Warning] " . $e->getMessage());
    }
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';

// 1. Descifrar datos JSON de entrada (o leer plano si no viene cifrado)
$rawInput = file_get_contents('php://input');
$payload = decryptInputPayload($rawInput, $isRequestEncrypted);
if (!is_array($payload)) {
    $payload = $_POST;
}

// Determinar acción (puede venir en URL o dentro del paquete cifrado)
$action = $payload['action'] ?? $_GET['action'] ?? ($method === 'POST' ? 'submit_score' : 'leaderboard');

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
// 1. REGISTRO DE USUARIO (Con Verificación de Contraseña Robusta)
// -----------------------------------------------------------------------------
if ($action === 'register') {
    if ($method !== 'POST') jsonOut(false, 'Método HTTP inválido', null, 405);

    $username = cleanUsername($payload['username'] ?? '');
    $password = trim($payload['password'] ?? '');

    if (mb_strlen($username) < 2 || mb_strlen($username) > 25) {
        jsonOut(false, 'El nombre debe tener entre 2 y 25 caracteres.', null, 400);
    }

    // Metodología de contraseña robusta
    $policy = evaluatePasswordPolicy($password, $username);
    if (!$policy['valid']) {
        jsonOut(false, 'Contraseña débil rechazada: ' . implode(' ', $policy['errors']), [
            'weak_password' => true,
            'policy_errors' => $policy['errors'],
            'score'         => $policy['score']
        ], 422);
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
            INSERT INTO users (username, password_hash, auth_token, token_expires_at, failed_attempts, locked_until, created_at)
            VALUES (:u, :p, :t, DATE_ADD(NOW(), INTERVAL 30 DAY), 0, NULL, NOW())
        ");
        $stmt->execute([
            ':u' => $username,
            ':p' => $passwordHash,
            ':t' => $token
        ]);

        jsonOut(true, '¡Cuenta creada exitosamente con credenciales robustas!', [
            'username' => $username,
            'token'    => $token
        ], 201);
    } catch (PDOException $e) {
        jsonOut(false, 'Error al registrar el usuario: ' . $e->getMessage(), null, 500);
    }
}

// -----------------------------------------------------------------------------
// 2. INICIO DE SESIÓN (Con Protección contra Fuerza Bruta)
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
        $stmt = $pdo->prepare("
            SELECT id, username, password_hash, failed_attempts, locked_until 
            FROM users 
            WHERE LOWER(username) = LOWER(:u)
        ");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            jsonOut(false, 'Usuario no encontrado.', ['not_found' => true], 404);
        }

        // 1. Verificar si la cuenta se encuentra bloqueada por intentos fallidos
        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $remaining = strtotime($user['locked_until']) - time();
            $mins = ceil($remaining / 60);
            jsonOut(false, "Cuenta bloqueada temporalmente por seguridad tras 5 intentos fallidos. Intenta de nuevo en {$mins} minuto(s).", [
                'locked' => true,
                'remaining_seconds' => $remaining
            ], 429);
        }

        // 2. Validar contraseña
        if (!password_verify($password, $user['password_hash'])) {
            $newAttempts = (int)($user['failed_attempts'] ?? 0) + 1;

            if ($newAttempts >= 5) {
                // Bloquear cuenta durante 5 minutos
                $lockStmt = $pdo->prepare("
                    UPDATE users 
                    SET failed_attempts = :fa, locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) 
                    WHERE id = :id
                ");
                $lockStmt->execute([':fa' => $newAttempts, ':id' => $user['id']]);

                jsonOut(false, 'Has excedido el límite de 5 intentos fallidos. Tu cuenta ha sido bloqueada temporalmente por 5 minutos por seguridad.', [
                    'locked' => true,
                    'remaining_seconds' => 300
                ], 429);
            } else {
                $attStmt = $pdo->prepare("UPDATE users SET failed_attempts = :fa WHERE id = :id");
                $attStmt->execute([':fa' => $newAttempts, ':id' => $user['id']]);

                $left = 5 - $newAttempts;
                jsonOut(false, "Contraseña incorrecta. Te quedan {$left} intento(s) antes del bloqueo de seguridad.", [
                    'attempts_left' => $left
                ], 401);
            }
        }

        // 3. Inicio exitoso: reiniciar contador de intentos fallidos y generar nuevo token
        $token = bin2hex(random_bytes(32));
        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET auth_token = :t, 
                token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY),
                failed_attempts = 0,
                locked_until = NULL 
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
// 3.5 CAMBIAR CONTRASEÑA (Con Verificación de Contraseña Robusta)
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

        // Validar que la nueva contraseña sea robusta
        $policy = evaluatePasswordPolicy($newPassword, $user['username']);
        if (!$policy['valid']) {
            jsonOut(false, 'Nueva contraseña rechazada: ' . implode(' ', $policy['errors']), [
                'weak_password' => true,
                'policy_errors' => $policy['errors'],
                'score'         => $policy['score']
            ], 422);
        }

        // Generar nuevo hash BCRYPT y renovar el token de sesión
        $newHash  = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $newToken = bin2hex(random_bytes(32));

        $updateStmt = $pdo->prepare("
            UPDATE users 
            SET password_hash = :p, 
                auth_token = :t, 
                token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY),
                failed_attempts = 0,
                locked_until = NULL
            WHERE id = :id
        ");
        $updateStmt->execute([
            ':p'  => $newHash,
            ':t'  => $newToken,
            ':id' => $user['id']
        ]);

        jsonOut(true, '¡Contraseña actualizada correctamente con directiva robusta!', [
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

    // 2. Si no hay token válido, rechazar
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

// -----------------------------------------------------------------------------
// 6. ESTADO DEL SISTEMA (Healthcheck)
// -----------------------------------------------------------------------------
if ($action === 'status') {
    jsonOut(true, 'Servidor activo', [
        'db_connected' => ($pdo !== null),
        'encryption'   => 'AES-256-CBC + HMAC-SHA256',
        'auth_policy'  => 'Strong Password Policy + Brute Force Protection'
    ]);
}

jsonOut(false, 'Acción no válida', null, 400);
