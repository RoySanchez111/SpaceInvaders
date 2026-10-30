<?php
/**
 * Portal Táctico de Videojuegos - Core Defender: Void Protocol
 * Stack: Nginx + PHP 8.5 (FPM) + MariaDB + phpMyAdmin
 */

require_once __DIR__ . '/config/db.php';

$pdo = getDatabaseConnection();
$dbConnected = ($pdo !== null);

// Obtener datos iniciales para Server-Side Rendering (SSR)
$initialScores = [];
if ($dbConnected) {
    try {
        $stmt = $pdo->query("
            SELECT id, username, faction, score, wave_reached, created_at 
            FROM players_scores 
            ORDER BY score DESC, wave_reached DESC, created_at ASC 
            LIMIT 10
        ");
        $initialScores = $stmt->fetchAll();
    } catch (Exception $e) {
        $dbConnected = false;
    }
}

// Fallback si la base de datos aún no se ha inicializado
if (empty($initialScores)) {
    $initialScores = [
        ['id' => 1, 'username' => 'Cmdr_Viper', 'faction' => 'Solar Vanguard', 'score' => 45200, 'wave_reached' => 14, 'created_at' => date('Y-m-d H:i', strtotime('-2 days'))],
        ['id' => 2, 'username' => 'NebulaGhost', 'faction' => 'Void Syndicate', 'score' => 38950, 'wave_reached' => 12, 'created_at' => date('Y-m-d H:i', strtotime('-1 day'))],
        ['id' => 3, 'username' => 'Aegis_Prime', 'faction' => 'Cyber Ronin', 'score' => 29400, 'wave_reached' => 9, 'created_at' => date('Y-m-d H:i', strtotime('-12 hours'))],
        ['id' => 4, 'username' => 'StarCruiser99', 'faction' => 'Nebula Fleet', 'score' => 21100, 'wave_reached' => 7, 'created_at' => date('Y-m-d H:i', strtotime('-4 hours'))],
        ['id' => 5, 'username' => 'NovaStrike', 'faction' => 'Solar Vanguard', 'score' => 15800, 'wave_reached' => 5, 'created_at' => date('Y-m-d H:i', strtotime('-1 hour'))]
    ];
}

$serverHostname = gethostname() ?: 'srv2112995';
$phpVersion     = PHP_VERSION;
$serverSoftware = $_SERVER['SERVER_SOFTWARE'] ?? 'Nginx';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CORE DEFENDER // Void Protocol - Portal Táctico Arcade</title>
    
    <!-- Tipografías Tácticas Futuristas -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Orbitron:wght@500;700;900&family=Rajdhani:wght@500;600;700&family=Share+Tech+Mono&display=swap" rel="stylesheet">
    
    <!-- Hoja de Estilos Glassmorphism -->
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body class="tactical-hud">
    <!-- Capa de Fondo con Cuadrícula Holográfica y Ruido Digital -->
    <div class="hologram-grid-bg"></div>
    <div class="scanlines-overlay"></div>

    <div class="viewport-wrapper">
        <!-- ===================================================================
             TOP HUD / HEADER TELEMETRÍA DEL SISTEMA
             =================================================================== -->
        <header class="tactical-header glass-card">
            <div class="brand-zone">
                <div class="insignia-symbol"></div>
                <div class="brand-titles">
                    <h1 class="brand-glitch" data-text="CORE DEFENDER">CORE DEFENDER</h1>
                    <span class="sub-label">VOID PROTOCOL // SISTEMA DE DEFENSA PLANETARIA</span>
                </div>
            </div>

            <!-- Telemetría en Vivo del Servidor -->
            <div class="server-telemetry">
                <div class="telemetry-pill">
                    <span class="telemetry-tag">HOST:</span>
                    <span class="telemetry-val highlight"><?= htmlspecialchars($serverHostname, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="telemetry-pill">
                    <span class="telemetry-tag">STACK:</span>
                    <span class="telemetry-val">NGINX + PHP <?= htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <div class="telemetry-pill status-pill">
                    <span class="pulse-indicator <?= $dbConnected ? 'online' : 'degraded' ?>" id="dbPulse"></span>
                    <span class="telemetry-tag">MARIADB:</span>
                    <span class="telemetry-val <?= $dbConnected ? 'text-green' : 'text-amber' ?>" id="dbStatusLabel">
                        <?= $dbConnected ? 'ONLINE' : 'DEGRADADO' ?>
                    </span>
                </div>
                <a href="/phpmyadmin/" target="_blank" rel="noopener noreferrer" class="hud-btn hud-btn-outline phpmyadmin-btn" title="Abrir Consola phpMyAdmin">
                    <span class="btn-icon">⚡</span> phpMyAdmin
                </a>
            </div>
        </header>

        <!-- ===================================================================
             GRID PRINCIPAL: TERMINAL TÁCTICA + PANEL DE MANDO Y RANKING
             =================================================================== -->
        <main class="tactical-grid">
            
            <!-- SECCIÓN IZQUIERDA: TERMINAL DE COMBATE (CANVAS MINIJUEGO) -->
            <section class="combat-terminal-section glass-card">
                <div class="terminal-header">
                    <div class="terminal-title-bar">
                        <span class="status-bracket">[</span>
                        <h2 class="terminal-title">TERMINAL TÁCTICA DE CONTENCIÓN</h2>
                        <span class="status-bracket">]</span>
                    </div>

                    <!-- Métricas del Juego en Tiempo Real -->
                    <div class="combat-stats-hud">
                        <div class="stat-cluster">
                            <span class="stat-name">PUNTUACIÓN</span>
                            <span class="stat-digit score-highlight" id="hudScore">000000</span>
                        </div>
                        <div class="stat-cluster">
                            <span class="stat-name">OLEADA</span>
                            <span class="stat-digit wave-highlight" id="hudWave">01</span>
                        </div>
                        <div class="stat-cluster">
                            <span class="stat-name">MULTIPLICADOR</span>
                            <span class="stat-digit" id="hudMultiplier">x1.0</span>
                        </div>
                        <div class="stat-cluster core-health-cluster">
                            <span class="stat-name">INTEGRIDAD DEL NÚCLEO</span>
                            <div class="progress-bar-rail">
                                <div class="progress-bar-fill" id="hudCoreHealthBar" style="width: 100%;"></div>
                            </div>
                            <span class="stat-digit" id="hudCoreHealthText">100%</span>
                        </div>
                    </div>
                </div>

                <!-- Contenedor del Canvas de Juego -->
                <div class="canvas-viewport" id="canvasViewport">
                    <canvas id="defenseCanvas" width="840" height="520"></canvas>
                    
                    <!-- Overlay de Inicio / Pausa sobre el Canvas -->
                    <div class="canvas-screen-overlay active" id="canvasOverlay">
                        <div class="overlay-panel">
                            <div class="shield-glyph"></div>
                            <h3 class="overlay-headline" id="overlayHeadline">DEFENSA ORBITAL INACTIVA</h3>
                            <p class="overlay-desc" id="overlayDesc">
                                Protege el Núcleo de Energía Central contra las hordas del Vacío Espacial.
                                Maniobra la torreta orbital, destruye drones hostiles y no dejes que el núcleo colapse.
                            </p>
                            
                            <div class="controls-cheat-sheet">
                                <div class="cheat-item"><span class="key-badge">RATÓN</span> Apuntar Cañón Plasma</div>
                                <div class="cheat-item"><span class="key-badge">CLIC IZQ</span> Disparar Ráfagas</div>
                                <div class="cheat-item"><span class="key-badge">W A S D</span> Maniobrar Nave Orbital</div>
                                <div class="cheat-item"><span class="key-badge">ESPACIO</span> Onda de Pulso EMP</div>
                            </div>

                            <button type="button" class="hud-btn hud-btn-crimson btn-massive" id="btnOverlayAction">
                                <span class="btn-glow"></span>
                                INICIALIZAR NÚCLEO DE COMBATE
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Barra de Controles Tácticos Inferiores -->
                <div class="combat-controls-deck">
                    <div class="control-actions-left">
                        <button type="button" class="hud-btn hud-btn-crimson" id="btnStartGame">
                            ▶ INICIAR DEFENSA
                        </button>
                        <button type="button" class="hud-btn hud-btn-secondary" id="btnPauseGame" disabled>
                            ⏸ PAUSAR
                        </button>
                        <button type="button" class="hud-btn hud-btn-gold" id="btnEmpPulse" title="Descarga una onda que aniquila drones cercanos">
                            💥 PULSO EMP (<span id="empCooldownText">LISTO</span>)
                        </button>
                    </div>

                    <div class="control-actions-right">
                        <button type="button" class="hud-btn hud-btn-icon" id="btnAudioToggle" title="Activar/Desactivar Audio Sintético">
                            <span id="audioIcon">🔊</span> <span id="audioLabel">AUDIO ON</span>
                        </button>
                    </div>
                </div>
            </section>

            <!-- SECCIÓN DERECHA: LEADERBOARD GLOBAL Y TRANSMISIÓN DE PUNTAJES -->
            <aside class="command-center-section glass-card">
                
                <!-- Pestañas / Encabezado de Leaderboard -->
                <div class="panel-header">
                    <div class="panel-title-group">
                        <span class="panel-icon">🏆</span>
                        <h2 class="panel-title">LEADERBOARD GLOBAL</h2>
                    </div>
                    <button type="button" class="refresh-btn" id="btnRefreshLeaderboard" title="Sincronizar con MariaDB">
                        <span class="refresh-icon" id="refreshIcon">↻</span>
                    </button>
                </div>

                <!-- Filtro / Buscador Táctico de Jugadores -->
                <div class="search-module">
                    <div class="search-input-wrapper">
                        <span class="search-lens">⌕</span>
                        <input type="text" id="leaderboardSearch" class="tactical-input" placeholder="Buscar comandante o facción..." autocomplete="off">
                        <button type="button" class="clear-search-btn" id="btnClearSearch" style="display: none;">✕</button>
                    </div>
                </div>

                <!-- Lista de Jugadores (Pre-renderizada por PHP y dinamizada con JS) -->
                <div class="leaderboard-scrollable-container" id="leaderboardContainer">
                    <div class="leaderboard-table-header">
                        <span class="col-rank">RANGO</span>
                        <span class="col-player">COMANDANTE / FACCIÓN</span>
                        <span class="col-wave">OLEADA</span>
                        <span class="col-score">PUNTOS</span>
                    </div>

                    <div class="leaderboard-rows-list" id="leaderboardRows">
                        <?php $rank = 1; foreach ($initialScores as $scoreRow): ?>
                            <?php
                                $rankClass = 'rank-default';
                                if ($rank === 1) $rankClass = 'rank-gold';
                                elseif ($rank === 2) $rankClass = 'rank-silver';
                                elseif ($rank === 3) $rankClass = 'rank-bronze';
                                
                                $faction = htmlspecialchars($scoreRow['faction'] ?? 'Solar Vanguard', ENT_QUOTES, 'UTF-8');
                                $factionSlug = strtolower(str_replace(' ', '-', $faction));
                            ?>
                            <div class="leaderboard-row <?= $rankClass ?>" data-username="<?= strtolower(htmlspecialchars($scoreRow['username'], ENT_QUOTES, 'UTF-8')) ?>" data-faction="<?= strtolower($faction) ?>">
                                <div class="col-rank">
                                    <span class="rank-badge <?= $rankClass ?>">#<?= $rank ?></span>
                                </div>
                                <div class="col-player">
                                    <div class="player-callsign"><?= htmlspecialchars($scoreRow['username'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="player-faction-tag faction-<?= $factionSlug ?>"><?= $faction ?></div>
                                </div>
                                <div class="col-wave">
                                    <span class="wave-pill">W-<?= str_pad((string)($scoreRow['wave_reached'] ?? 1), 2, '0', STR_PAD_LEFT) ?></span>
                                </div>
                                <div class="col-score">
                                    <span class="score-number"><?= number_format((int)$scoreRow['score']) ?></span>
                                </div>
                            </div>
                        <?php $rank++; endforeach; ?>
                    </div>
                </div>

                <!-- Formulario de Registro / Transmisión de Puntuación -->
                <div class="submit-score-module">
                    <div class="module-header">
                        <span class="module-title">TRANSMISIÓN DE EXPEDIENTE TÁCTICO</span>
                        <span class="module-badge">MARIADB LIVE</span>
                    </div>

                    <form id="scoreSubmitForm" class="tactical-form">
                        <div class="form-row">
                            <div class="form-group flex-2">
                                <label for="inputCallsign" class="form-label">CALLSIGN / COMANDANTE</label>
                                <input type="text" id="inputCallsign" class="tactical-input" placeholder="Ej: Cmdr_Valkyrie" maxlength="30" required value="Operador_<?= rand(100, 999) ?>">
                            </div>
                            <div class="form-group flex-2">
                                <label for="selectFaction" class="form-label">FACCIÓN</label>
                                <select id="selectFaction" class="tactical-select">
                                    <option value="Solar Vanguard" selected>Solar Vanguard</option>
                                    <option value="Void Syndicate">Void Syndicate</option>
                                    <option value="Cyber Ronin">Cyber Ronin</option>
                                    <option value="Nebula Fleet">Nebula Fleet</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group flex-1">
                                <label for="inputScore" class="form-label">PUNTAJE A REGISTRAR</label>
                                <input type="number" id="inputScore" class="tactical-input highlight-gold" min="0" value="0">
                            </div>
                            <div class="form-group flex-1">
                                <label for="inputWave" class="form-label">OLEADA FINAL</label>
                                <input type="number" id="inputWave" class="tactical-input" min="1" value="1">
                            </div>
                        </div>

                        <button type="submit" class="hud-btn hud-btn-crimson btn-full" id="btnSubmitScore">
                            <span class="btn-glow"></span>
                            TRANSMITIR PUNTUACIÓN AL CUARTEL GENERAL
                        </button>
                    </form>
                </div>

            </aside>
        </main>

        <!-- ===================================================================
             MODAL DE DEBRIEFING / GAME OVER
             =================================================================== -->
        <div class="tactical-modal-backdrop" id="debriefModal">
            <div class="tactical-modal glass-card alert-border">
                <div class="modal-header">
                    <span class="alert-icon">⚠</span>
                    <h3 class="modal-title">COLAPSO DE NÚCLEO // FIN DE MISIÓN</h3>
                </div>
                <div class="modal-body">
                    <p class="modal-message">
                        El reactor central ha alcanzado masa crítica tras la incursión del enjambre. La misión de contención ha finalizado.
                    </p>
                    <div class="debrief-stats-grid">
                        <div class="debrief-card">
                            <span class="debrief-label">PUNTUACIÓN OBTENIDA</span>
                            <span class="debrief-val highlight-gold" id="debriefScore">0</span>
                        </div>
                        <div class="debrief-card">
                            <span class="debrief-label">OLEADAS RESISTIDAS</span>
                            <span class="debrief-val highlight-crimson" id="debriefWave">0</span>
                        </div>
                        <div class="debrief-card">
                            <span class="debrief-label">DRONES NEUTRALIZADOS</span>
                            <span class="debrief-val" id="debriefKills">0</span>
                        </div>
                        <div class="debrief-card">
                            <span class="debrief-label">PRECISIÓN DE TIRO</span>
                            <span class="debrief-val" id="debriefAccuracy">0%</span>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="hud-btn hud-btn-crimson" id="btnModalSaveScore">
                        ENVIAR RÉCORD A MARIADB
                    </button>
                    <button type="button" class="hud-btn hud-btn-secondary" id="btnModalRetry">
                        REINTENTAR OPERACIÓN
                    </button>
                </div>
            </div>
        </div>

        <!-- ===================================================================
             SISTEMA DE NOTIFICACIONES TOAST TÁCTICAS
             =================================================================== -->
        <div class="toast-container" id="toastContainer"></div>

        <!-- ===================================================================
             PIE DE PÁGINA TÁCTICO CON TELEMETRÍA Y ACCESO A PHPMYADMIN
             =================================================================== -->
        <footer class="tactical-footer glass-card">
            <div class="footer-left">
                <span class="terminal-prompt">&gt;</span>
                <span class="footer-text">
                    NODO ACTIVO: <strong><?= htmlspecialchars($serverHostname, ENT_QUOTES, 'UTF-8') ?></strong> (Ubuntu 26.04 LTS) | 
                    MOTOR WEB: <strong><?= htmlspecialchars($serverSoftware, ENT_QUOTES, 'UTF-8') ?></strong> | 
                    INTÉRPRETE: <strong>PHP <?= htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8') ?> (FPM)</strong> |
                    MOTOR DB: <strong>MariaDB 10.x+</strong>
                </span>
            </div>
            <div class="footer-right">
                <a href="/phpmyadmin/" target="_blank" rel="noopener noreferrer" class="footer-link">
                    Acceder a Panel phpMyAdmin
                </a>
                <span class="separator">•</span>
                <span class="footer-ping" id="footerPing">PING: <span id="pingVal">-- ms</span></span>
            </div>
        </footer>
    </div>

    <!-- Scripts de la Aplicación -->
    <script src="assets/js/game.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>

