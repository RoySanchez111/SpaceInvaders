<?php
/**
 * Space Invaders - Portal Web Simple
 * Stack: Nginx + PHP 8.5 (FPM) + MariaDB + phpMyAdmin
 */

require_once __DIR__ . '/config/db.php';

$pdo = getDatabaseConnection();
$dbConnected = ($pdo !== null);

$serverHostname = gethostname() ?: 'srv2112995';
$phpVersion     = PHP_VERSION;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Space Invaders</title>
    
    <!-- Fuentes limpias -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">
    
    <!-- Estilos -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="main-wrapper">
        
        <!-- ENCABEZADO -->
        <header class="navbar">
            <div class="nav-brand">
                <span class="pixel-invader">👾</span>
                <h1>SPACE INVADERS</h1>
            </div>

            <div class="nav-status">
                <span class="status-pill">Host: <strong><?= htmlspecialchars($serverHostname, ENT_QUOTES, 'UTF-8') ?></strong></span>
                <span class="status-pill">PHP <strong><?= htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8') ?></strong></span>
                <span class="status-pill">
                    <span class="status-dot <?= $dbConnected ? 'online' : 'offline' ?>" id="dbStatusDot"></span>
                    BD: <strong id="dbStatusTxt"><?= $dbConnected ? 'Conectada' : 'Desconectada' ?></strong>
                </span>
                <a href="/phpmyadmin/" target="_blank" rel="noopener noreferrer" class="btn-phpmyadmin">
                    phpMyAdmin
                </a>
            </div>
        </header>

        <!-- SECCIÓN CENTRAL DEL JUEGO -->
        <main class="game-container">
            
            <!-- HUD DEL JUEGO -->
            <div class="game-hud">
                <div class="hud-item">
                    <span class="hud-label">PUNTOS</span>
                    <span class="hud-val" id="hudScore">0</span>
                </div>
                <div class="hud-item">
                    <span class="hud-label">RÉCORD</span>
                    <span class="hud-val gold" id="hudHighScore">0</span>
                </div>
                <div class="hud-item">
                    <span class="hud-label">VIDAS</span>
                    <span class="hud-val red" id="hudLives">🚀 🚀 🚀</span>
                </div>
                <div class="hud-item">
                    <span class="hud-label">NIVEL</span>
                    <span class="hud-val cyan" id="hudLevel">01</span>
                </div>
            </div>

            <!-- CANVAS DE JUEGO -->
            <div class="canvas-wrapper">
                <canvas id="gameCanvas" width="640" height="480"></canvas>
            </div>

            <!-- BARRA DE CONTROLES INFERIOR -->
            <div class="game-controls">
                <div class="controls-guide">
                    <span><strong>← →</strong> o <strong>A D</strong> para moverte</span>
                    <span>•</span>
                    <span><strong>ESPACIO</strong> para disparar</span>
                    <span>•</span>
                    <span><strong>P</strong> pausa</span>
                </div>

                <div class="controls-btns">
                    <button type="button" class="btn btn-primary" id="btnNewGame">
                        Iniciar Juego
                    </button>
                    <button type="button" class="btn btn-secondary" id="btnViewScores">
                        🏆 Ver Puntuaciones
                    </button>
                </div>
            </div>

        </main>

        <!-- MODAL DE PUNTUACIONES / FIN DE JUEGO -->
        <div class="modal-backdrop" id="scoreModal">
            <div class="modal-box">
                <div class="modal-header">
                    <h2 id="modalTitle">¡JUEGO TERMINADO!</h2>
                    <button type="button" class="btn-close" id="btnCloseModal">✕</button>
                </div>

                <!-- Caja de puntaje obtenido -->
                <div class="final-score-box" id="finalScoreBox">
                    <span class="final-score-label">Tu Puntuación:</span>
                    <span class="final-score-val" id="finalScoreVal">0</span>
                </div>

                <!-- Formulario para guardar nombre -->
                <form class="score-form" id="scoreForm">
                    <input type="text" id="inputPlayer" placeholder="Escribe tu nombre..." maxlength="20" required autocomplete="off">
                    <button type="submit" class="btn btn-primary" id="btnSaveScore">Guardar</button>
                </form>

                <div class="save-notice" id="saveNotice" style="display: none;"></div>

                <!-- Tabla de Puntuaciones -->
                <div class="table-wrapper">
                    <h3>🏆 TOP 10 JUGADORES</h3>
                    <table class="scores-table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Jugador</th>
                                <th>Puntos</th>
                                <th>Fecha</th>
                            </tr>
                        </thead>
                        <tbody id="leaderboardBody">
                            <tr><td colspan="4" style="text-align:center; padding: 16px;">Cargando...</td></tr>
                        </tbody>
                    </table>
                </div>

                <div class="modal-footer">
                    <button type="button" class="btn btn-primary" id="btnPlayAgain">Jugar de Nuevo</button>
                </div>
            </div>
        </div>

        <!-- PIE DE PÁGINA -->
        <footer class="footer">
            <span>Space Invaders • Servidor: NGINX + PHP <?= htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8') ?> + MariaDB</span>
            <a href="/phpmyadmin/" target="_blank" rel="noopener noreferrer">Acceder a phpMyAdmin</a>
        </footer>

    </div>

    <!-- Scripts -->
    <script src="assets/js/game.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
