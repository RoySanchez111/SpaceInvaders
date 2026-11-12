<?php
/**
 * Validadores de formato de jugador y contraseña robusta
 */

function cleanUsername(string $raw): string {
    return trim($raw);
}

function extractBearerToken(array $payload): ?string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
        return trim($m[1]);
    }
    return !empty($payload['token']) ? trim($payload['token']) : null;
}

/**
 * Valida las reglas de contraseña robusta (aplicada en Crear Cuenta y en Cambiar Contraseña)
 */
function validatePasswordRules(string $password, string $username = ''): array {
    $errors = [];

    if (mb_strlen($password) < 8) {
        $errors[] = 'Mínimo 8 caracteres.';
    }
    if (!preg_match('/[A-Z]/', $password) || !preg_match('/[a-z]/', $password)) {
        $errors[] = 'Mayúsculas y minúsculas.';
    }
    if (!preg_match('/[0-9]/', $password)) {
        $errors[] = 'Al menos un número.';
    }
    if (!preg_match('/[^A-Za-z0-9]/', $password)) {
        $errors[] = 'Al menos un símbolo (!@#$%...).';
    }

    if (!empty($username) && mb_strlen($username) >= 3 && stripos($password, $username) !== false) {
        $errors[] = 'No debe contener tu nombre de usuario.';
    }

    $common = [
        '12345678', '123456789', '1234567890', 'password', 'password1', 'password123',
        'qwertyui', 'qwerty123', 'admin123', 'admin2026', 'contrasena', 'contraseña',
        'spaceinvaders', 'invaders123', '11111111', '00000000'
    ];
    if (in_array(strtolower($password), $common, true) || preg_match('/^(.)\1{4,}$/', $password)) {
        $errors[] = 'Elige una combinación menos predecible.';
    }

    return [
        'valid'  => empty($errors),
        'errors' => $errors
    ];
}
