<?php
/**
 * Validadores de entrada y formato de usuario/contraseña
 */

function cleanUsername(string $raw): string {
    $cleaned = preg_replace('/[^\w\s\-\.]/u', '', trim($raw));
    return trim(preg_replace('/\s+/', ' ', $cleaned));
}

function extractBearerToken(array $payload): ?string {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $auth = $headers['Authorization'] ?? $headers['authorization'] ?? ($_SERVER['HTTP_AUTHORIZATION'] ?? '');
    if (preg_match('/Bearer\s+(\S+)/i', $auth, $m)) {
        return trim($m[1]);
    }
    return !empty($payload['token']) ? trim($payload['token']) : null;
}

function validatePasswordRules(string $password, string $username = ''): array {
    $errors = [];

    if (mb_strlen($password) < 8) {
        $errors[] = 'Al menos 8 caracteres.';
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
        $errors[] = 'No debe incluir tu nombre de usuario.';
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
