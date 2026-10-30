<?php
/**
 * API RESTful del Portal Táctico de Videojuegos
 * Maneja Leaderboard, Envío de Puntuaciones y Telemetría del Sistema
 */

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Desactivar salida de errores crudos que romperían el payload JSON
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';

// Helper para responder en formato JSON consistente
function sendJsonResponse(bool $success, string $message, $data = null, int $statusCode = 200): void {
    http_response_code($statusCode);
    echo json_encode([
        'success'   => $success,
        'message'   => $message,
        'timestamp' => date('Y-m-d H:i:s'),
        'data'      => $data
    ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    exit;
}

// Obtener método HTTP y acción solicitada
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? 'submit_score' : 'leaderboard');

$pdo = getDatabaseConnection();

// -----------------------------------------------------------------------------
// Endpoint: STATUS (Telemetría del Servidor y Base de Datos)
// -----------------------------------------------------------------------------
if ($action === 'status') {
    $dbOk = false;
    $dbError = null;

    if ($pdo !== null) {
        try {
            $pdo->query("SELECT 1");
            $dbOk = true;
        } catch (Exception $e) {
            $dbError = $e->getMessage();
        }
    }

    sendJsonResponse(true, 'Telemetría del sistema recuperada', [
        'status'          => $dbOk ? 'OPERACIONAL' : 'MODO_DEGRADADO',
        'db_connected'    => $dbOk,
        'php_version'     => PHP_VERSION,
        'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Nginx / PHP-FPM',
        'server_hostname' => gethostname() ?: 'srv2112995',
        'server_time'     => date('Y-m-d H:i:s T'),
        'db_error'        => $dbError
    ]);
}

// -----------------------------------------------------------------------------
// Endpoint: LEADERBOARD (Top Puntuaciones y Búsqueda)
// -----------------------------------------------------------------------------
if ($action === 'leaderboard') {
    $search = trim($_GET['search'] ?? $_GET['q'] ?? '');
    $limit  = isset($_GET['limit']) ? min(max(1, (int)$_GET['limit']), 50) : 10;

    // Fallback de demostración si la base de datos aún no está disponible
    if ($pdo === null) {
        $mockScores = [
            ['rank' => 1, 'id' => 1, 'username' => 'Cmdr_Viper', 'faction' => 'Solar Vanguard', 'score' => 45200, 'wave_reached' => 14, 'created_at' => date('Y-m-d H:i', strtotime('-2 days'))],
            ['rank' => 2, 'id' => 2, 'username' => 'NebulaGhost', 'faction' => 'Void Syndicate', 'score' => 38950, 'wave_reached' => 12, 'created_at' => date('Y-m-d H:i', strtotime('-1 day'))],
            ['rank' => 3, 'id' => 3, 'username' => 'Aegis_Prime', 'faction' => 'Cyber Ronin', 'score' => 29400, 'wave_reached' => 9, 'created_at' => date('Y-m-d H:i', strtotime('-12 hours'))],
            ['rank' => 4, 'id' => 4, 'username' => 'StarCruiser99', 'faction' => 'Nebula Fleet', 'score' => 21100, 'wave_reached' => 7, 'created_at' => date('Y-m-d H:i', strtotime('-4 hours'))],
            ['rank' => 5, 'id' => 5, 'username' => 'NovaStrike', 'faction' => 'Solar Vanguard', 'score' => 15800, 'wave_reached' => 5, 'created_at' => date('Y-m-d H:i', strtotime('-1 hour'))]
        ];

        if ($search !== '') {
            $mockScores = array_values(array_filter($mockScores, function($row) use ($search) {
                return stripos($row['username'], $search) !== false || stripos($row['faction'], $search) !== false;
            }));
        }

        sendJsonResponse(true, 'Leaderboard en modo fallback (sin conexión a BD)', [
            'scores'       => array_slice($mockScores, 0, $limit),
            'total'        => count($mockScores),
            'db_connected' => false
        ]);
    }

    try {
        if ($search !== '') {
            $stmt = $pdo->prepare("
                SELECT id, username, faction, score, wave_reached, created_at 
                FROM players_scores 
                WHERE username LIKE :q OR faction LIKE :q
                ORDER BY score DESC, wave_reached DESC, created_at ASC
                LIMIT :lim
            ");
            $searchTerm = '%' . $search . '%';
            $stmt->bindValue(':q', $searchTerm, PDO::PARAM_STR);
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
        } else {
            $stmt = $pdo->prepare("
                SELECT id, username, faction, score, wave_reached, created_at 
                FROM players_scores 
                ORDER BY score DESC, wave_reached DESC, created_at ASC
                LIMIT :lim
            ");
            $stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
            $stmt->execute();
        }

        $rawScores = $stmt->fetchAll();
        $formatted = [];
        $rank = 1;

        foreach ($rawScores as $row) {
            $formatted[] = [
                'rank'         => $rank++,
                'id'           => (int)$row['id'],
                'username'     => htmlspecialchars($row['username'], ENT_QUOTES, 'UTF-8'),
                'faction'      => htmlspecialchars($row['faction'], ENT_QUOTES, 'UTF-8'),
                'score'        => (int)$row['score'],
                'wave_reached' => (int)$row['wave_reached'],
                'created_at'   => $row['created_at'],
                'formatted_date' => date('d/m/Y H:i', strtotime($row['created_at']))
            ];
        }

        sendJsonResponse(true, 'Leaderboard actualizado', [
            'scores'       => $formatted,
            'total'        => count($formatted),
            'db_connected' => true
        ]);
    } catch (PDOException $e) {
        sendJsonResponse(false, 'Error consultando puntuaciones: ' . $e->getMessage(), null, 500);
    }
}

// -----------------------------------------------------------------------------
// Endpoint: SUBMIT_SCORE (Registro de Nuevo Puntaje)
// -----------------------------------------------------------------------------
if ($action === 'submit_score') {
    if ($method !== 'POST') {
        sendJsonResponse(false, 'Método HTTP inválido. Se requiere POST.', null, 405);
    }

    // Leer payload (admite tanto application/json como form-data convencional)
    $payload = [];
    $rawInput = file_get_contents('php://input');
    if (!empty($rawInput)) {
        $jsonDecoded = json_decode($rawInput, true);
        if (is_array($jsonDecoded)) {
            $payload = $jsonDecoded;
        }
    }
    if (empty($payload)) {
        $payload = $_POST;
    }

    // Sanitización y Validación de Campos
    $username    = trim($payload['username'] ?? 'Comandante');
    $faction     = trim($payload['faction'] ?? 'Solar Vanguard');
    $score       = isset($payload['score']) ? max(0, (int)$payload['score']) : 0;
    $waveReached = isset($payload['wave_reached']) ? max(1, (int)$payload['wave_reached']) : 1;

    // Validación de Callsign
    $username = preg_replace('/[^\w\s\-\.]/u', '', $username);
    if (mb_strlen($username) < 2) {
        $username = 'Piloto_' . rand(100, 999);
    }
    if (mb_strlen($username) > 30) {
        $username = mb_substr($username, 0, 30);
    }

    // Validación de Facciones permitidas
    $allowedFactions = ['Solar Vanguard', 'Void Syndicate', 'Cyber Ronin', 'Nebula Fleet'];
    if (!in_array($faction, $allowedFactions, true)) {
        $faction = 'Solar Vanguard';
    }

    if ($pdo === null) {
        sendJsonResponse(false, 'La base de datos MariaDB no está disponible en este momento.', null, 503);
    }

    try {
        $stmt = $pdo->prepare("
            INSERT INTO players_scores (username, faction, score, wave_reached, created_at)
            VALUES (:username, :faction, :score, :wave_reached, NOW())
        ");
        $stmt->execute([
            ':username'     => $username,
            ':faction'      => $faction,
            ':score'        => $score,
            ':wave_reached' => $waveReached
        ]);

        $newId = (int)$pdo->lastInsertId();

        // Calcular posición en el ranking global
        $rankStmt = $pdo->prepare("SELECT COUNT(*) + 1 AS calculated_rank FROM players_scores WHERE score > :score");
        $rankStmt->execute([':score' => $score]);
        $rankData = $rankStmt->fetch();
        $calculatedRank = (int)($rankData['calculated_rank'] ?? 1);

        sendJsonResponse(true, "¡Puntaje transmitido a la Base de Comando!", [
            'id'           => $newId,
            'rank'         => $calculatedRank,
            'username'     => $username,
            'faction'      => $faction,
            'score'        => $score,
            'wave_reached' => $waveReached
        ], 201);
    } catch (PDOException $e) {
        sendJsonResponse(false, 'Error al registrar puntaje en MariaDB: ' . $e->getMessage(), null, 500);
    }
}

// Acción no reconocida
sendJsonResponse(false, 'Acción no reconocida en la API táctica.', null, 400);

