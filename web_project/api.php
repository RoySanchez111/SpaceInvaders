<?php
/**
 * Controlador principal de la API - Space Invaders
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/crypto.php';
require_once __DIR__ . '/includes/validator.php';
require_once __DIR__ . '/includes/auth_service.php';

$isRequestEncoded = false;
$pdo = getDatabaseConnection();
ensureDatabaseSchema($pdo);

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$rawInput = file_get_contents('php://input');
$payload = decodeRequestPayload($rawInput, $isRequestEncoded);

$action = $payload['action'] ?? $_GET['action'] ?? '';

switch ($action) {
    case 'register':
        if ($method !== 'POST') sendJson(false, 'Método no permitido.', null, 405);
        handleRegister($pdo, $payload);
        break;

    case 'login':
        if ($method !== 'POST') sendJson(false, 'Método no permitido.', null, 405);
        handleLogin($pdo, $payload);
        break;

    case 'verify_token':
        handleVerifyToken($pdo, $payload);
        break;

    case 'logout':
        handleLogout($pdo, $payload);
        break;

    case 'change_password':
        if ($method !== 'POST') sendJson(false, 'Método no permitido.', null, 405);
        handleChangePassword($pdo, $payload);
        break;

    case 'submit_score':
        if ($method !== 'POST') sendJson(false, 'Método no permitido.', null, 405);
        handleSubmitScore($pdo, $payload);
        break;

    case 'leaderboard':
        handleLeaderboard($pdo, $payload);
        break;

    default:
        sendJson(false, 'Acción no disponible.', null, 400);
}
