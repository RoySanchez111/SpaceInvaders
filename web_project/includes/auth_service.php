<?php
/**
 * Servicios de cuenta, sesión y puntuaciones del juego
 */

function ensureDatabaseSchema(?PDO $pdo): void {
    if ($pdo === null) return;

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

        $columns = [
            "ADD COLUMN IF NOT EXISTS `password_hash` VARCHAR(255) NOT NULL DEFAULT ''",
            "ADD COLUMN IF NOT EXISTS `auth_token` VARCHAR(255) NULL",
            "ADD COLUMN IF NOT EXISTS `token_expires_at` DATETIME NULL",
            "ADD COLUMN IF NOT EXISTS `failed_attempts` INT UNSIGNED NOT NULL DEFAULT 0",
            "ADD COLUMN IF NOT EXISTS `locked_until` DATETIME NULL"
        ];
        foreach ($columns as $colSql) {
            try { $pdo->exec("ALTER TABLE `users` {$colSql}"); } catch (Exception $e) {}
        }
    } catch (Exception $e) {
        error_log("[Schema Setup] " . $e->getMessage());
    }
}

function getAuthenticatedUser(?PDO $pdo, ?string $token): ?array {
    if ($pdo === null || empty($token)) return null;

    $stmt = $pdo->prepare("
        SELECT id, username, password_hash 
        FROM users 
        WHERE auth_token = :t AND (token_expires_at IS NULL OR token_expires_at > NOW())
        LIMIT 1
    ");
    $stmt->execute([':t' => $token]);
    $user = $stmt->fetch();

    return $user ?: null;
}

function handleRegister(?PDO $pdo, array $payload): void {
    $username = cleanUsername($payload['username'] ?? '');
    $password = trim($payload['password'] ?? '');

    if (mb_strlen($username) < 2 || mb_strlen($username) > 25) {
        sendJson(false, 'El nombre de jugador debe tener entre 2 y 25 caracteres.', null, 400);
    }

    $validation = validatePasswordRules($password, $username);
    if (!$validation['valid']) {
        sendJson(false, 'La contraseña requiere: ' . implode(' ', $validation['errors']), [
            'errors' => $validation['errors']
        ], 422);
    }

    if ($pdo === null) sendJson(false, 'Servidor de datos no disponible.', null, 503);

    try {
        $check = $pdo->prepare("SELECT id FROM users WHERE LOWER(username) = LOWER(:u)");
        $check->execute([':u' => $username]);
        if ($check->fetch()) {
            sendJson(false, 'Ese nombre de jugador ya está ocupado.', ['user_exists' => true], 409);
        }

        $hash  = password_hash($password, PASSWORD_BCRYPT, ['cost' => 10]);
        $token = bin2hex(random_bytes(32));

        $stmt = $pdo->prepare("
            INSERT INTO users (username, password_hash, auth_token, token_expires_at, failed_attempts, locked_until, created_at)
            VALUES (:u, :p, :t, DATE_ADD(NOW(), INTERVAL 30 DAY), 0, NULL, NOW())
        ");
        $stmt->execute([':u' => $username, ':p' => $hash, ':t' => $token]);

        sendJson(true, 'Cuenta creada.', [
            'username' => $username,
            'token'    => $token
        ], 201);
    } catch (PDOException $e) {
        sendJson(false, 'No se pudo completar el registro.', null, 500);
    }
}

function handleLogin(?PDO $pdo, array $payload): void {
    $username = cleanUsername($payload['username'] ?? '');
    $password = trim($payload['password'] ?? '');

    if (empty($username) || empty($password)) {
        sendJson(false, 'Ingresa tu usuario y contraseña.', null, 400);
    }

    if ($pdo === null) sendJson(false, 'Servidor de datos no disponible.', null, 503);

    try {
        $stmt = $pdo->prepare("
            SELECT id, username, password_hash, failed_attempts, locked_until 
            FROM users 
            WHERE LOWER(username) = LOWER(:u)
        ");
        $stmt->execute([':u' => $username]);
        $user = $stmt->fetch();

        if (!$user) {
            sendJson(false, 'No existe una cuenta con ese nombre.', ['not_found' => true], 404);
        }

        if (!empty($user['locked_until']) && strtotime($user['locked_until']) > time()) {
            $mins = max(1, (int)ceil((strtotime($user['locked_until']) - time()) / 60));
            sendJson(false, "Demasiados intentos. Espera {$mins} min para volver a entrar.", ['locked' => true], 429);
        }

        if (!password_verify($password, $user['password_hash'])) {
            $attempts = (int)($user['failed_attempts'] ?? 0) + 1;

            if ($attempts >= 5) {
                $lock = $pdo->prepare("
                    UPDATE users 
                    SET failed_attempts = :fa, locked_until = DATE_ADD(NOW(), INTERVAL 5 MINUTE) 
                    WHERE id = :id
                ");
                $lock->execute([':fa' => $attempts, ':id' => $user['id']]);
                sendJson(false, 'Demasiados intentos fallidos. Espera 5 minutos.', ['locked' => true], 429);
            }

            $upd = $pdo->prepare("UPDATE users SET failed_attempts = :fa WHERE id = :id");
            $upd->execute([':fa' => $attempts, ':id' => $user['id']]);
            sendJson(false, 'Contraseña incorrecta.', ['attempts_left' => 5 - $attempts], 401);
        }

        $token = bin2hex(random_bytes(32));
        $upd = $pdo->prepare("
            UPDATE users 
            SET auth_token = :t, token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY), failed_attempts = 0, locked_until = NULL 
            WHERE id = :id
        ");
        $upd->execute([':t' => $token, ':id' => $user['id']]);

        sendJson(true, 'Sesión iniciada.', [
            'username' => $user['username'],
            'token'    => $token
        ]);
    } catch (PDOException $e) {
        sendJson(false, 'Error al iniciar sesión.', null, 500);
    }
}

function handleVerifyToken(?PDO $pdo, array $payload): void {
    $token = extractBearerToken($payload);
    $user  = getAuthenticatedUser($pdo, $token);

    if (!$user) {
        sendJson(false, 'Sesión no activa.', null, 401);
    }

    // Obtener también la mejor puntuación del jugador
    $bestScore = 0;
    try {
        $sStmt = $pdo->prepare("SELECT MAX(score) as best FROM players_scores WHERE LOWER(username) = LOWER(:u)");
        $sStmt->execute([':u' => $user['username']]);
        $row = $sStmt->fetch();
        $bestScore = (int)($row['best'] ?? 0);
    } catch (Exception $e) {}

    sendJson(true, 'Sesión activa.', [
        'username'   => $user['username'],
        'best_score' => $bestScore
    ]);
}

function handleLogout(?PDO $pdo, array $payload): void {
    $token = extractBearerToken($payload);
    if ($pdo !== null && $token) {
        try {
            $stmt = $pdo->prepare("UPDATE users SET auth_token = NULL, token_expires_at = NULL WHERE auth_token = :t");
            $stmt->execute([':t' => $token]);
        } catch (Exception $e) {}
    }
    sendJson(true, 'Sesión cerrada.');
}

function handleChangePassword(?PDO $pdo, array $payload): void {
    $token           = extractBearerToken($payload);
    $currentPassword = trim($payload['current_password'] ?? '');
    $newPassword     = trim($payload['new_password'] ?? '');
    $confirmPassword = isset($payload['confirm_password']) ? trim($payload['confirm_password']) : $newPassword;

    $user = getAuthenticatedUser($pdo, $token);
    if (!$user) {
        sendJson(false, 'Tu sesión expiró. Vuelve a iniciar sesión.', null, 401);
    }

    if (empty($currentPassword) || empty($newPassword)) {
        sendJson(false, 'Completa todos los campos.', null, 400);
    }

    if ($newPassword !== $confirmPassword) {
        sendJson(false, 'Las contraseñas nuevas no coinciden.', null, 400);
    }

    if ($currentPassword === $newPassword) {
        sendJson(false, 'La nueva contraseña debe ser distinta a la actual.', null, 400);
    }

    if (!password_verify($currentPassword, $user['password_hash'])) {
        sendJson(false, 'La contraseña actual no es correcta.', null, 401);
    }

    $validation = validatePasswordRules($newPassword, $user['username']);
    if (!$validation['valid']) {
        sendJson(false, 'La nueva contraseña requiere: ' . implode(' ', $validation['errors']), [
            'errors' => $validation['errors']
        ], 422);
    }

    try {
        $newHash  = password_hash($newPassword, PASSWORD_BCRYPT, ['cost' => 10]);
        $newToken = bin2hex(random_bytes(32));

        $upd = $pdo->prepare("
            UPDATE users 
            SET password_hash = :p, auth_token = :t, token_expires_at = DATE_ADD(NOW(), INTERVAL 30 DAY), failed_attempts = 0, locked_until = NULL
            WHERE id = :id
        ");
        $upd->execute([':p' => $newHash, ':t' => $newToken, ':id' => $user['id']]);

        sendJson(true, 'Contraseña actualizada.', [
            'username' => $user['username'],
            'token'    => $newToken
        ]);
    } catch (PDOException $e) {
        sendJson(false, 'No se pudo actualizar la contraseña.', null, 500);
    }
}

function handleSubmitScore(?PDO $pdo, array $payload): void {
    $token = extractBearerToken($payload);
    $score = isset($payload['score']) ? max(0, (int)$payload['score']) : 0;

    $user = getAuthenticatedUser($pdo, $token);
    if (!$user) {
        sendJson(false, 'Inicia sesión para guardar tu puntuación.', null, 401);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO players_scores (username, score, created_at) VALUES (:u, :s, NOW())");
        $stmt->execute([':u' => $user['username'], ':s' => $score]);

        $rStmt = $pdo->prepare("SELECT COUNT(*) + 1 as user_rank FROM players_scores WHERE score > :s");
        $rStmt->execute([':s' => $score]);
        $rank = (int)$rStmt->fetch()['user_rank'];

        sendJson(true, 'Puntuación registrada.', [
            'username' => $user['username'],
            'score'    => $score,
            'rank'     => $rank
        ]);
    } catch (PDOException $e) {
        sendJson(false, 'No se pudo guardar la puntuación.', null, 500);
    }
}

function handleLeaderboard(?PDO $pdo, array $payload): void {
    $token = extractBearerToken($payload);
    $user  = getAuthenticatedUser($pdo, $token);
    if (!$user) {
        sendJson(false, 'Inicia sesión para consultar la clasificación.', null, 401);
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

        sendJson(true, 'Clasificación cargada.', ['scores' => $scores]);
    } catch (PDOException $e) {
        sendJson(false, 'No se pudo cargar la clasificación.', null, 500);
    }
}
