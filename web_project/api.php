<?php
/**
 * API Simple para Space Invaders
 * Endpoints:
 * - GET  /api.php?action=leaderboard
 * - POST /api.php?action=submit_score (guarda username y score)
 * - GET  /api.php?action=status
 */

header('Content-Type: application/json; charset=utf-8');
ini_set('display_errors', '0');
error_reporting(E_ALL);

require_once __DIR__ . '/config/db.php';

function jsonOut(bool $ok, string $msg, $data = null, int $code = 200): void {
    http_response_code($code);
    echo json_encode(['success' => $ok, 'message' => $msg, 'data' => $data], JSON_UNESCAPED_UNICODE);
    exit;
}

$pdo = getDatabaseConnection();
$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $_GET['action'] ?? ($method === 'POST' ? 'submit_score' : 'leaderboard');

// Estado del servidor / BD
if ($action === 'status') {
    $dbOk = false;
    if ($pdo !== null) {
        try {
            $pdo->query("SELECT 1");
            $dbOk = true;
        } catch (Exception $e) {}
    }
    jsonOut(true, 'Estado del servidor', [
        'db_connected' => $dbOk,
        'php_version'  => PHP_VERSION,
        'hostname'     => gethostname() ?: 'srv2112995'
    ]);
}

// Obtener tabla de puntuaciones
if ($action === 'leaderboard') {
    // Si la BD no está conectada, devolver datos de prueba
    if ($pdo === null) {
        $mock = [
            ['rank' => 1, 'username' => 'Alex', 'score' => 1500, 'date' => 'Ayer'],
            ['rank' => 2, 'username' => 'Sara', 'score' => 1250, 'date' => 'Ayer'],
            ['rank' => 3, 'username' => 'Carlos', 'score' => 980, 'date' => 'Hoy'],
            ['rank' => 4, 'username' => 'Elena', 'score' => 720, 'date' => 'Hoy'],
            ['rank' => 5, 'username' => 'David', 'score' => 500, 'date' => 'Hoy']
        ];
        jsonOut(true, 'Leaderboard demo', ['scores' => $mock, 'db_connected' => false]);
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

// Guardar nueva puntuación
if ($action === 'submit_score') {
    if ($method !== 'POST') {
        jsonOut(false, 'Método no permitido', null, 405);
    }

    $raw = file_get_contents('php://input');
    $input = json_decode($raw, true) ?: $_POST;

    $username = trim($input['username'] ?? '');
    $score    = isset($input['score']) ? max(0, (int)$input['score']) : 0;

    // Validación básica de nombre
    $username = preg_replace('/[^\w\s\-\.]/u', '', $username);
    if (empty($username)) {
        $username = 'Jugador_' . rand(100, 999);
    }
    if (mb_strlen($username) > 20) {
        $username = mb_substr($username, 0, 20);
    }

    if ($pdo === null) {
        jsonOut(false, 'Base de datos no disponible', null, 503);
    }

    try {
        $stmt = $pdo->prepare("INSERT INTO players_scores (username, score, created_at) VALUES (:u, :s, NOW())");
        $stmt->execute([':u' => $username, ':s' => $score]);

        // Calcular posición en el ranking
        $rStmt = $pdo->prepare("SELECT COUNT(*) + 1 as user_rank FROM players_scores WHERE score > :s");
        $rStmt->execute([':s' => $score]);
        $rank = (int)$rStmt->fetch()['user_rank'];

        jsonOut(true, 'Puntuación guardada con éxito', [
            'username' => $username,
            'score'    => $score,
            'rank'     => $rank
        ]);
    } catch (PDOException $e) {
        jsonOut(false, 'Error al guardar puntuación', null, 500);
    }
}

jsonOut(false, 'Acción no válida', null, 400);
