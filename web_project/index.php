<?php
/**
 * Space Invaders - Arcade Clásico Web
 * Stack: NGINX + PHP 8.5 (FPM) + MariaDB + phpMyAdmin
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
    
    <!-- Fuentes de Google: Press Start 2P (Retro) y Rajdhani (UI limpia) -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Press+Start+2P&family=Rajdhani:wght@600;700&display=swap" rel="stylesheet">

    <!-- ESTILOS EMBEBIDOS Y AUTOSUFICIENTES (CERO DEPENDENCIAS EXTERNAS QUE PUEDAN FALLAR) -->
    <style>
        :root {
            --bg-body: #0a0b12;
            --bg-card: rgba(18, 20, 32, 0.95);
            --border-card: rgba(255, 255, 255, 0.12);
            --neon-crimson: #ff0055;
            --neon-gold: #ffb703;
            --neon-cyan: #00f5d4;
            --neon-green: #39ff14;
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
            --font-arcade: 'Press Start 2P', monospace, sans-serif;
            --font-ui: 'Rajdhani', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-body);
            color: var(--text-main);
            font-family: var(--font-ui);
            font-size: 16px;
            line-height: 1.4;
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: flex-start;
            padding: 16px;
            background-image: 
                radial-gradient(circle at 50% 10%, rgba(255, 0, 85, 0.15) 0%, transparent 50%),
                radial-gradient(circle at 80% 90%, rgba(0, 245, 212, 0.1) 0%, transparent 45%);
            background-attachment: fixed;
        }

        .arcade-cabinet {
            width: 100%;
            max-width: 720px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            margin: 0 auto;
        }

        /* BARRA SUPERIOR (HEADER) */
        .top-navbar {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 12px;
            padding: 12px 20px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
        }

        .brand-title {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .brand-title h1 {
            font-family: var(--font-arcade);
            font-size: 1.15rem;
            color: var(--neon-crimson);
            text-shadow: 0 0 10px rgba(255, 0, 85, 0.6);
            letter-spacing: 1px;
        }

        .brand-icon {
            font-size: 1.6rem;
            animation: alienFloat 2s ease-in-out infinite alternate;
        }

        @keyframes alienFloat {
            from { transform: translateY(-2px); }
            to { transform: translateY(2px); }
        }

        .server-pills {
            display: flex;
            align-items: center;
            gap: 8px;
            flex-wrap: wrap;
            font-size: 0.82rem;
        }

        .pill {
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 5px 10px;
            border-radius: 6px;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .pill strong {
            color: #fff;
        }

        .status-dot {
            width: 8px;
            height: 8px;
            border-radius: 50%;
            display: inline-block;
        }

        .status-dot.online {
            background: #2ec4b6;
            box-shadow: 0 0 8px #2ec4b6;
        }

        .status-dot.offline {
            background: var(--neon-crimson);
            box-shadow: 0 0 8px var(--neon-crimson);
        }

        .btn-phpmyadmin {
            background: rgba(255, 183, 3, 0.1);
            color: var(--neon-gold);
            border: 1px solid rgba(255, 183, 3, 0.5);
            padding: 5px 12px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 700;
            transition: all 0.2s;
        }

        .btn-phpmyadmin:hover {
            background: var(--neon-gold);
            color: #000;
            box-shadow: 0 0 12px rgba(255, 183, 3, 0.6);
        }

        /* ÁREA PRINCIPAL DEL JUEGO */
        .game-card {
            background: var(--bg-card);
            border: 1px solid var(--border-card);
            border-radius: 14px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.7);
        }

        /* HUD SUPERIOR DEL JUEGO */
        .arcade-hud {
            width: 100%;
            max-width: 640px;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 8px;
            background: rgba(0, 0, 0, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 8px;
            padding: 10px 14px;
            font-family: var(--font-arcade);
            text-align: center;
        }

        .hud-box {
            display: flex;
            flex-direction: column;
            gap: 6px;
        }

        .hud-title {
            font-size: 0.65rem;
            color: var(--text-dim);
            letter-spacing: 0.5px;
        }

        .hud-value {
            font-size: 1rem;
            color: #fff;
        }

        .hud-value.gold { color: var(--neon-gold); text-shadow: 0 0 8px rgba(255, 183, 3, 0.5); }
        .hud-value.cyan { color: var(--neon-cyan); text-shadow: 0 0 8px rgba(0, 245, 212, 0.5); }
        .hud-value.lives-icons { font-family: sans-serif; font-size: 0.95rem; }

        /* CONTENEDOR DEL CANVAS */
        .canvas-frame {
            width: 100%;
            max-width: 640px;
            aspect-ratio: 4 / 3;
            background: #000;
            border-radius: 10px;
            overflow: hidden;
            border: 2px solid rgba(0, 245, 212, 0.4);
            box-shadow: 0 0 25px rgba(0, 245, 212, 0.2), inset 0 0 40px rgba(0, 0, 0, 0.9);
            position: relative;
        }

        #gameCanvas {
            display: block;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        /* CONTROLES Y BOTONES INFERIORES */
        .controls-deck {
            width: 100%;
            max-width: 640px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
        }

        .keys-hint {
            font-size: 0.85rem;
            color: var(--text-dim);
            display: flex;
            align-items: center;
            gap: 6px;
            flex-wrap: wrap;
        }

        .key-tag {
            background: rgba(255, 255, 255, 0.1);
            color: #fff;
            padding: 2px 6px;
            border-radius: 4px;
            font-family: monospace;
            font-weight: 700;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .action-buttons {
            display: flex;
            gap: 10px;
        }

        /* BOTONES DE ESTILO RETRO COOL */
        .arcade-btn {
            font-family: var(--font-ui);
            font-size: 0.95rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            padding: 10px 18px;
            border-radius: 8px;
            border: none;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-play {
            background: var(--neon-crimson);
            color: #fff;
            box-shadow: 0 0 15px rgba(255, 0, 85, 0.5);
        }

        .btn-play:hover {
            background: #ff226e;
            box-shadow: 0 0 25px rgba(255, 0, 85, 0.8);
            transform: translateY(-2px);
        }

        .btn-scores {
            background: rgba(255, 255, 255, 0.08);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .btn-scores:hover {
            background: rgba(255, 255, 255, 0.16);
            border-color: var(--neon-gold);
            color: var(--neon-gold);
            transform: translateY(-2px);
        }

        .btn-audio {
            background: rgba(0, 0, 0, 0.4);
            color: var(--text-dim);
            border: 1px solid rgba(255, 255, 255, 0.1);
            padding: 10px 14px;
        }

        .btn-audio:hover {
            color: #fff;
            border-color: rgba(255, 255, 255, 0.3);
        }

        /* =====================================================================
           MODAL DE PUNTUACIONES Y GAME OVER (OCULTO POR DEFECTO CON DISPLAY: NONE)
           ===================================================================== */
        .modal-screen-overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.85);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            display: none; /* Crucial: no visible hasta activarse */
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 16px;
            animation: fadeIn 0.2s ease forwards;
        }

        .modal-screen-overlay.active {
            display: flex !important;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        .modal-card {
            background: #11131d;
            border: 1px solid rgba(255, 0, 85, 0.4);
            border-radius: 14px;
            max-width: 520px;
            width: 100%;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-shadow: 0 0 50px rgba(0, 0, 0, 0.9), 0 0 20px rgba(255, 0, 85, 0.2);
            animation: scaleIn 0.25s ease forwards;
        }

        @keyframes scaleIn {
            from { transform: scale(0.92); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-top {
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            padding-bottom: 12px;
        }

        .modal-headline {
            font-family: var(--font-arcade);
            font-size: 1.1rem;
            color: var(--neon-crimson);
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .close-icon-btn {
            background: none;
            border: none;
            color: var(--text-dim);
            font-size: 1.4rem;
            cursor: pointer;
            line-height: 1;
        }

        .close-icon-btn:hover {
            color: #fff;
        }

        .score-callout {
            background: rgba(255, 183, 3, 0.08);
            border: 1px dashed var(--neon-gold);
            border-radius: 10px;
            padding: 14px;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .score-callout-title {
            font-size: 0.85rem;
            color: var(--text-dim);
            text-transform: uppercase;
        }

        .score-callout-points {
            font-family: var(--font-arcade);
            font-size: 1.8rem;
            color: var(--neon-gold);
            text-shadow: 0 0 12px rgba(255, 183, 3, 0.5);
        }

        .save-form {
            display: flex;
            gap: 10px;
        }

        .save-form input {
            flex: 1;
            background: rgba(0, 0, 0, 0.5);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            font-size: 0.95rem;
            outline: none;
            font-family: var(--font-ui);
            font-weight: 600;
        }

        .save-form input:focus {
            border-color: var(--neon-crimson);
            box-shadow: 0 0 10px rgba(255, 0, 85, 0.4);
        }

        .status-alert {
            background: rgba(46, 196, 182, 0.15);
            color: #2ec4b6;
            border: 1px solid rgba(46, 196, 182, 0.4);
            padding: 10px;
            border-radius: 8px;
            text-align: center;
            font-weight: 700;
            font-size: 0.9rem;
        }

        .leaderboard-title {
            font-family: var(--font-ui);
            font-size: 1rem;
            font-weight: 700;
            color: var(--neon-gold);
            letter-spacing: 0.5px;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .leaderboard-table-box {
            background: rgba(0, 0, 0, 0.4);
            border-radius: 8px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            overflow: hidden;
            max-height: 250px;
            overflow-y: auto;
        }

        .scores-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.88rem;
        }

        .scores-table th {
            background: rgba(0, 0, 0, 0.6);
            padding: 8px 12px;
            text-align: left;
            color: var(--text-dim);
            font-weight: 700;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .scores-table td {
            padding: 8px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.04);
        }

        .scores-table .col-rank { width: 45px; font-weight: 700; }
        .scores-table .col-pts { text-align: right; color: var(--neon-gold); font-family: monospace; font-weight: 700; font-size: 1rem; }
        .scores-table .col-date { text-align: right; color: var(--text-dim); font-size: 0.78rem; width: 130px; }

        .scores-table tr.rank-1 td.col-user { color: var(--neon-gold); font-weight: 700; }
        .scores-table tr.rank-2 td.col-user { color: #cbd5e1; font-weight: 700; }
        .scores-table tr.rank-3 td.col-user { color: var(--neon-crimson); font-weight: 700; }

        .modal-bottom {
            display: flex;
            justify-content: flex-end;
            gap: 10px;
            border-top: 1px solid rgba(255, 255, 255, 0.08);
            padding-top: 14px;
        }

        /* PIE DE PÁGINA */
        .bottom-footer {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 16px;
            font-size: 0.82rem;
            color: var(--text-dim);
            flex-wrap: wrap;
            gap: 8px;
        }

        .bottom-footer a {
            color: var(--neon-gold);
            text-decoration: none;
            font-weight: 600;
        }

        .bottom-footer a:hover {
            text-decoration: underline;
        }

        @media (max-width: 650px) {
            .arcade-hud {
                grid-template-columns: repeat(2, 1fr);
            }
            .controls-deck {
                flex-direction: column;
                align-items: stretch;
            }
            .action-buttons {
                justify-content: center;
            }
        }
    </style>
</head>
<body>

    <div class="arcade-cabinet">
        
        <!-- ENCABEZADO SUPERIOR -->
        <header class="top-navbar">
            <div class="brand-title">
                <span class="brand-icon">👾</span>
                <h1>SPACE INVADERS</h1>
            </div>

            <div class="server-pills">
                <div class="pill">Host: <strong><?= htmlspecialchars($serverHostname, ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div class="pill">PHP: <strong><?= htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8') ?></strong></div>
                <div class="pill">
                    <span class="status-dot <?= $dbConnected ? 'online' : 'offline' ?>" id="dbDot"></span>
                    BD: <strong id="dbText"><?= $dbConnected ? 'Conectada' : 'Desconectada' ?></strong>
                </div>
                <a href="/phpmyadmin/" target="_blank" rel="noopener noreferrer" class="btn-phpmyadmin" title="Abrir phpMyAdmin">
                    phpMyAdmin
                </a>
            </div>
        </header>

        <!-- TARJETA PRINCIPAL DEL JUEGO -->
        <main class="game-card">
            
            <!-- HUD TELEMETRÍA DEL JUEGO -->
            <div class="arcade-hud">
                <div class="hud-box">
                    <span class="hud-title">PUNTOS</span>
                    <span class="hud-value" id="hudScore">0</span>
                </div>
                <div class="hud-box">
                    <span class="hud-title">RÉCORD</span>
                    <span class="hud-value gold" id="hudHiScore">0</span>
                </div>
                <div class="hud-box">
                    <span class="hud-title">VIDAS</span>
                    <span class="hud-value lives-icons" id="hudLives">🚀 🚀 🚀</span>
                </div>
                <div class="hud-box">
                    <span class="hud-title">NIVEL</span>
                    <span class="hud-value cyan" id="hudLevel">01</span>
                </div>
            </div>

            <!-- PANTALLA CANVAS RETRO -->
            <div class="canvas-frame">
                <canvas id="gameCanvas" width="640" height="480"></canvas>
            </div>

            <!-- CONTROLES Y GUÍA -->
            <div class="controls-deck">
                <div class="keys-hint">
                    <span class="key-tag">← →</span> o <span class="key-tag">A D</span> Moverse
                    <span>•</span>
                    <span class="key-tag">ESPACIO</span> Disparar
                    <span>•</span>
                    <span class="key-tag">P</span> Pausa
                </div>

                <div class="action-buttons">
                    <button type="button" class="arcade-btn btn-play" id="btnStartGame">
                        ▶ JUGAR AHORA
                    </button>
                    <button type="button" class="arcade-btn btn-scores" id="btnOpenScores">
                        🏆 RÉCORDS
                    </button>
                    <button type="button" class="arcade-btn btn-audio" id="btnAudioToggle" title="Activar/Silenciar sonido">
                        🔊
                    </button>
                </div>
            </div>

        </main>

        <!-- PIE DE PÁGINA -->
        <footer class="bottom-footer">
            <span>Servidor Web: <strong>NGINX</strong> + <strong>PHP <?= htmlspecialchars($phpVersion, ENT_QUOTES, 'UTF-8') ?> FPM</strong> + <strong>MariaDB</strong></span>
            <a href="/phpmyadmin/" target="_blank" rel="noopener noreferrer">Administrar Base de Datos &rarr;</a>
        </footer>

    </div>

    <!-- =========================================================================
         VENTANA MODAL // GAME OVER Y TABLA DE MEJORES PUNTUACIONES
         ========================================================================= -->
    <div class="modal-screen-overlay" id="scoreModal">
        <div class="modal-card">
            
            <div class="modal-top">
                <div class="modal-headline" id="modalHeading">
                    <span>👾</span> ¡FIN DEL JUEGO!
                </div>
                <button type="button" class="close-icon-btn" id="btnCloseModal" title="Cerrar ventana">✕</button>
            </div>

            <!-- Puntaje obtenido en la partida -->
            <div class="score-callout" id="calloutScoreBox">
                <span class="score-callout-title">Puntuación Final</span>
                <span class="score-callout-points" id="finalScoreDisplay">0</span>
            </div>

            <!-- Formulario para ingresar nombre y registrar récord -->
            <form class="save-form" id="saveScoreForm">
                <input type="text" id="playerNameInput" placeholder="Escribe tu nombre o apodo..." maxlength="20" required autocomplete="off">
                <button type="submit" class="arcade-btn btn-play" id="btnSubmitScore">Guardar</button>
            </form>

            <div class="status-alert" id="saveSuccessAlert" style="display: none;"></div>

            <!-- Tabla de Mejores Puntuaciones -->
            <div class="leaderboard-title">
                <span>🏆</span> TOP 10 MEJORES PUNTUACIONES
            </div>

            <div class="leaderboard-table-box">
                <table class="scores-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Jugador</th>
                            <th style="text-align: right;">Puntos</th>
                            <th style="text-align: right;">Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="scoresListBody">
                        <tr><td colspan="4" style="text-align:center; padding: 14px; color: var(--text-dim);">Cargando puntuaciones...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-bottom">
                <button type="button" class="arcade-btn btn-play" id="btnRestartGame">
                    🔄 JUGAR OTRA VEZ
                </button>
            </div>

        </div>
    </div>

    <!-- =========================================================================
         LÓGICA DEL JUEGO Y CONTROLADOR EMBEBIDO (GARANTIZA FUNCIONAMIENTO TOTAL)
         ========================================================================= -->
    <script>
        // SINTETIZADOR DE AUDIO RETRO (Web Audio API)
        class RetroAudioEngine {
            constructor() {
                this.ctx = null;
                this.enabled = true;
            }
            init() {
                if (!this.ctx && (window.AudioContext || window.webkitAudioContext)) {
                    const AC = window.AudioContext || window.webkitAudioContext;
                    this.ctx = new AC();
                }
                if (this.ctx && this.ctx.state === 'suspended') {
                    this.ctx.resume();
                }
            }
            toggle() {
                this.enabled = !this.enabled;
                return this.enabled;
            }
            shoot() {
                if (!this.enabled || !this.ctx) return;
                try {
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(800, this.ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(140, this.ctx.currentTime + 0.08);
                    gain.gain.setValueAtTime(0.12, this.ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.08);
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    osc.start();
                    osc.stop(this.ctx.currentTime + 0.08);
                } catch(e){}
            }
            kill() {
                if (!this.enabled || !this.ctx) return;
                try {
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.type = 'sawtooth';
                    osc.frequency.setValueAtTime(320, this.ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(40, this.ctx.currentTime + 0.14);
                    gain.gain.setValueAtTime(0.18, this.ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.14);
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    osc.start();
                    osc.stop(this.ctx.currentTime + 0.14);
                } catch(e){}
            }
            hit() {
                if (!this.enabled || !this.ctx) return;
                try {
                    const dur = 0.25;
                    const buf = this.ctx.createBuffer(1, this.ctx.sampleRate * dur, this.ctx.sampleRate);
                    const d = buf.getChannelData(0);
                    for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
                    const noise = this.ctx.createBufferSource();
                    noise.buffer = buf;
                    const gain = this.ctx.createGain();
                    gain.gain.setValueAtTime(0.2, this.ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + dur);
                    noise.connect(gain);
                    gain.connect(this.ctx.destination);
                    noise.start();
                } catch(e){}
            }
        }

        // MOTOR PRINCIPAL SPACE INVADERS
        class RetroSpaceInvaders {
            constructor(canvasId, callbacks) {
                this.canvas = document.getElementById(canvasId);
                this.ctx = this.canvas.getContext('2d');
                this.audio = new RetroAudioEngine();
                this.callbacks = callbacks || {};

                this.width = this.canvas.width;
                this.height = this.canvas.height;

                this.score = 0;
                this.lives = 3;
                this.level = 1;
                this.state = 'IDLE'; // IDLE, PLAYING, PAUSED, GAMEOVER

                this.player = {
                    w: 36,
                    h: 20,
                    x: this.width / 2 - 18,
                    y: this.height - 40,
                    speed: 5.5,
                    isHit: false,
                    hitTimer: 0
                };

                this.bullets = [];
                this.bombs = [];
                this.aliens = [];
                this.bunkers = [];
                this.alienDir = 1;
                this.alienSpeed = 1.0;
                this.dropTimer = 0;
                this.shootCooldown = 0;

                this.keys = { left: false, right: false, shoot: false };

                this.initListeners();
                this.reset();
                this.render();
            }

            initListeners() {
                window.addEventListener('keydown', (e) => {
                    if (['ArrowLeft', 'KeyA'].includes(e.code)) { this.keys.left = true; e.preventDefault(); }
                    if (['ArrowRight', 'KeyD'].includes(e.code)) { this.keys.right = true; e.preventDefault(); }
                    if (['Space', 'ArrowUp', 'KeyW'].includes(e.code)) {
                        this.keys.shoot = true;
                        e.preventDefault();
                        if (this.state === 'IDLE') this.start();
                    }
                    if (e.code === 'KeyP') this.togglePause();
                });

                window.addEventListener('keyup', (e) => {
                    if (['ArrowLeft', 'KeyA'].includes(e.code)) this.keys.left = false;
                    if (['ArrowRight', 'KeyD'].includes(e.code)) this.keys.right = false;
                    if (['Space', 'ArrowUp', 'KeyW'].includes(e.code)) this.keys.shoot = false;
                });

                this.canvas.addEventListener('click', () => {
                    this.audio.init();
                    if (this.state === 'IDLE') this.start();
                });
            }

            initAliens() {
                this.aliens = [];
                const rows = 4;
                const cols = 8;
                for (let r = 0; r < rows; r++) {
                    for (let c = 0; c < cols; c++) {
                        let type = 'bottom';
                        let pts = 10;
                        let color = '#00f5d4';

                        if (r === 0) { type = 'top'; pts = 30; color = '#ff0055'; }
                        else if (r === 1 || r === 2) { type = 'mid'; pts = 20; color = '#ffb703'; }

                        this.aliens.push({
                            x: 60 + c * 48,
                            y: 50 + r * 34,
                            w: 28,
                            h: 20,
                            type: type,
                            pts: pts,
                            color: color,
                            alive: true
                        });
                    }
                }
                this.alienDir = 1;
                this.alienSpeed = 0.8 + (this.level * 0.25);
            }

            initBunkers() {
                this.bunkers = [];
                const count = 3;
                const bWidth = 56;
                const gap = (this.width - (count * bWidth)) / (count + 1);

                for (let i = 0; i < count; i++) {
                    const bx = gap + i * (bWidth + gap);
                    const by = this.height - 100;
                    for (let r = 0; r < 4; r++) {
                        for (let c = 0; c < 7; c++) {
                            if (r === 3 && (c >= 2 && c <= 4)) continue;
                            this.bunkers.push({
                                x: bx + c * 8,
                                y: by + r * 8,
                                w: 8,
                                h: 8,
                                hp: 3
                            });
                        }
                    }
                }
            }

            reset() {
                this.score = 0;
                this.lives = 3;
                this.level = 1;
                this.bullets = [];
                this.bombs = [];
                this.player.x = this.width / 2 - 18;
                this.initBunkers();
                this.initAliens();
                if (this.callbacks.onScoreUpdate) {
                    this.callbacks.onScoreUpdate(this.score, this.lives, this.level);
                }
            }

            start() {
                this.audio.init();
                this.reset();
                this.state = 'PLAYING';
                this.loop();
            }

            togglePause() {
                if (this.state === 'PLAYING') {
                    this.state = 'PAUSED';
                    this.render();
                } else if (this.state === 'PAUSED') {
                    this.state = 'PLAYING';
                    this.loop();
                }
            }

            update() {
                if (this.state !== 'PLAYING') return;

                // 1. Mover Jugador
                if (this.keys.left && this.player.x > 10) this.player.x -= this.player.speed;
                if (this.keys.right && this.player.x < this.width - this.player.w - 10) this.player.x += this.player.speed;

                // 2. Disparar
                if (this.shootCooldown > 0) this.shootCooldown--;
                if (this.keys.shoot && this.shootCooldown === 0 && this.bullets.length < 2) {
                    this.bullets.push({
                        x: this.player.x + this.player.w / 2 - 2,
                        y: this.player.y - 4,
                        w: 4,
                        h: 12,
                        vy: -8
                    });
                    this.shootCooldown = 16;
                    this.audio.shoot();
                }

                // 3. Mover proyectiles
                for (let i = this.bullets.length - 1; i >= 0; i--) {
                    const b = this.bullets[i];
                    b.y += b.vy;
                    if (b.y < -10) { this.bullets.splice(i, 1); continue; }

                    // Colisión con Bunkers
                    let hitBunker = false;
                    for (let j = this.bunkers.length - 1; j >= 0; j--) {
                        const bk = this.bunkers[j];
                        if (b.x < bk.x + bk.w && b.x + b.w > bk.x && b.y < bk.y + bk.h && b.y + b.h > bk.y) {
                            bk.hp--;
                            if (bk.hp <= 0) this.bunkers.splice(j, 1);
                            this.bullets.splice(i, 1);
                            hitBunker = true;
                            break;
                        }
                    }
                    if (hitBunker) continue;

                    // Colisión con Invasores
                    for (let k = 0; k < this.aliens.length; k++) {
                        const a = this.aliens[k];
                        if (a.alive && b.x < a.x + a.w && b.x + b.w > a.x && b.y < a.y + a.h && b.y + b.h > a.y) {
                            a.alive = false;
                            this.bullets.splice(i, 1);
                            this.score += a.pts;
                            this.audio.kill();
                            if (this.callbacks.onScoreUpdate) {
                                this.callbacks.onScoreUpdate(this.score, this.lives, this.level);
                            }

                            const alive = this.aliens.filter(x => x.alive).length;
                            this.alienSpeed = Math.min(4.5, 0.8 + (this.level * 0.25) + ((32 - alive) * 0.08));

                            if (alive === 0) {
                                this.level++;
                                this.score += 500;
                                this.bullets = [];
                                this.bombs = [];
                                this.initAliens();
                                this.initBunkers();
                                if (this.callbacks.onScoreUpdate) {
                                    this.callbacks.onScoreUpdate(this.score, this.lives, this.level);
                                }
                            }
                            break;
                        }
                    }
                }

                // 4. Mover Invasores
                let wallHit = false;
                const aliveList = this.aliens.filter(a => a.alive);

                for (const a of aliveList) {
                    a.x += this.alienDir * this.alienSpeed;
                    if (a.x + a.w > this.width - 12 || a.x < 12) wallHit = true;
                    if (a.y + a.h >= this.player.y) {
                        this.gameOver();
                        return;
                    }
                }

                if (wallHit) {
                    this.alienDir *= -1;
                    for (const a of aliveList) a.y += 14;
                }

                // 5. Bombas alienígenas
                this.dropTimer++;
                if (this.dropTimer > Math.max(30, 80 - (this.level * 8)) && aliveList.length > 0) {
                    this.dropTimer = 0;
                    const shooter = aliveList[Math.floor(Math.random() * aliveList.length)];
                    this.bombs.push({
                        x: shooter.x + shooter.w / 2 - 2,
                        y: shooter.y + shooter.h,
                        w: 4,
                        h: 10,
                        vy: 3.5 + (this.level * 0.3)
                    });
                }

                // 6. Mover bombas
                for (let i = this.bombs.length - 1; i >= 0; i--) {
                    const bm = this.bombs[i];
                    bm.y += bm.vy;
                    if (bm.y > this.height) { this.bombs.splice(i, 1); continue; }

                    // Bunkers
                    let hitBk = false;
                    for (let j = this.bunkers.length - 1; j >= 0; j--) {
                        const bk = this.bunkers[j];
                        if (bm.x < bk.x + bk.w && bm.x + bm.w > bk.x && bm.y < bk.y + bk.h && bm.y + bm.h > bk.y) {
                            bk.hp--;
                            if (bk.hp <= 0) this.bunkers.splice(j, 1);
                            this.bombs.splice(i, 1);
                            hitBk = true;
                            break;
                        }
                    }
                    if (hitBk) continue;

                    // Jugador
                    if (bm.x < this.player.x + this.player.w &&
                        bm.x + bm.w > this.player.x &&
                        bm.y < this.player.y + this.player.h &&
                        bm.y + bm.h > this.player.y) {
                        
                        this.bombs.splice(i, 1);
                        this.lives--;
                        this.audio.hit();
                        this.player.isHit = true;
                        this.player.hitTimer = 25;
                        if (this.callbacks.onScoreUpdate) {
                            this.callbacks.onScoreUpdate(this.score, this.lives, this.level);
                        }

                        if (this.lives <= 0) {
                            this.gameOver();
                            return;
                        }
                    }
                }

                if (this.player.hitTimer > 0) {
                    this.player.hitTimer--;
                    if (this.player.hitTimer === 0) this.player.isHit = false;
                }
            }

            render() {
                this.ctx.fillStyle = '#06070d';
                this.ctx.fillRect(0, 0, this.width, this.height);

                // Estrellas
                this.ctx.fillStyle = 'rgba(255, 255, 255, 0.25)';
                for (let s = 0; s < 35; s++) {
                    const sx = (s * 89) % this.width;
                    const sy = (s * 47) % this.height;
                    this.ctx.fillRect(sx, sy, 2, 2);
                }

                // Bunkers
                for (const bk of this.bunkers) {
                    const alpha = bk.hp === 3 ? 1.0 : (bk.hp === 2 ? 0.7 : 0.4);
                    this.ctx.fillStyle = `rgba(0, 245, 212, ${alpha})`;
                    this.ctx.fillRect(bk.x, bk.y, bk.w, bk.h);
                }

                // Invasores (pixelados animados en 2 pasos)
                const step = Math.floor(Date.now() / 400) % 2;
                for (const a of this.aliens) {
                    if (!a.alive) continue;
                    this.ctx.fillStyle = a.color;
                    this.ctx.shadowColor = a.color;
                    this.ctx.shadowBlur = 6;

                    if (a.type === 'top') {
                        this.ctx.fillRect(a.x + 6, a.y + 2, a.w - 12, a.h - 8);
                        if (step === 0) {
                            this.ctx.fillRect(a.x + 2, a.y + a.h - 6, 4, 6);
                            this.ctx.fillRect(a.x + a.w - 6, a.y + a.h - 6, 4, 6);
                        } else {
                            this.ctx.fillRect(a.x + 8, a.y + a.h - 6, 4, 6);
                            this.ctx.fillRect(a.x + a.w - 12, a.y + a.h - 6, 4, 6);
                        }
                    } else if (a.type === 'mid') {
                        this.ctx.fillRect(a.x + 4, a.y + 3, a.w - 8, a.h - 7);
                        this.ctx.fillRect(a.x, a.y + (step === 0 ? 6 : 9), 4, 6);
                        this.ctx.fillRect(a.x + a.w - 4, a.y + (step === 0 ? 6 : 9), 4, 6);
                    } else {
                        this.ctx.fillRect(a.x + 5, a.y + 3, a.w - 10, a.h - 6);
                        this.ctx.fillRect(a.x + (step === 0 ? 3 : 6), a.y + a.h - 5, 5, 5);
                        this.ctx.fillRect(a.x + a.w - (step === 0 ? 8 : 11), a.y + a.h - 5, 5, 5);
                    }
                    this.ctx.shadowBlur = 0;

                    // Ojos
                    this.ctx.fillStyle = '#06070d';
                    this.ctx.fillRect(a.x + 8, a.y + 6, 3, 3);
                    this.ctx.fillRect(a.x + a.w - 11, a.y + 6, 3, 3);
                }

                // Proyectiles
                this.ctx.fillStyle = '#00f5d4';
                for (const b of this.bullets) this.ctx.fillRect(b.x, b.y, b.w, b.h);

                // Bombas alien
                this.ctx.fillStyle = '#ff0055';
                for (const bm of this.bombs) this.ctx.fillRect(bm.x, bm.y, bm.w, bm.h);

                // Jugador
                if (!this.player.isHit || Math.floor(Date.now() / 80) % 2 === 0) {
                    this.ctx.fillStyle = '#ffb703';
                    this.ctx.shadowColor = '#ffb703';
                    this.ctx.shadowBlur = 8;
                    const x = this.player.x, y = this.player.y, w = this.player.w, h = this.player.h;
                    this.ctx.beginPath();
                    this.ctx.moveTo(x + w / 2, y);
                    this.ctx.lineTo(x + w / 2 + 3, y + 6);
                    this.ctx.lineTo(x + w - 4, y + 6);
                    this.ctx.lineTo(x + w, y + h);
                    this.ctx.lineTo(x, y + h);
                    this.ctx.lineTo(x + 4, y + 6);
                    this.ctx.lineTo(x + w / 2 - 3, y + 6);
                    this.ctx.closePath();
                    this.ctx.fill();
                    this.ctx.shadowBlur = 0;
                }

                // Línea inferior
                this.ctx.strokeStyle = 'rgba(0, 245, 212, 0.3)';
                this.ctx.lineWidth = 1;
                this.ctx.beginPath();
                this.ctx.moveTo(10, this.height - 15);
                this.ctx.lineTo(this.width - 10, this.height - 15);
                this.ctx.stroke();

                // Pantalla Pausa
                if (this.state === 'PAUSED') {
                    this.ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
                    this.ctx.fillRect(0, 0, this.width, this.height);
                    this.ctx.fillStyle = '#ffb703';
                    this.ctx.font = 'bold 22px "Press Start 2P", monospace';
                    this.ctx.textAlign = 'center';
                    this.ctx.fillText('PAUSA', this.width / 2, this.height / 2);
                    this.ctx.font = '14px sans-serif';
                    this.ctx.fillStyle = '#fff';
                    this.ctx.fillText('Presiona P para continuar', this.width / 2, this.height / 2 + 35);
                    this.ctx.textAlign = 'left';
                }

                // Pantalla Inicial
                if (this.state === 'IDLE') {
                    this.ctx.fillStyle = 'rgba(0, 0, 0, 0.75)';
                    this.ctx.fillRect(0, 0, this.width, this.height);
                    this.ctx.fillStyle = '#ff0055';
                    this.ctx.font = '22px "Press Start 2P", monospace';
                    this.ctx.textAlign = 'center';
                    this.ctx.fillText('SPACE INVADERS', this.width / 2, this.height / 2 - 30);

                    this.ctx.fillStyle = '#ffb703';
                    this.ctx.font = '14px "Press Start 2P", monospace';
                    this.ctx.fillText('CLIC PARA EMPEZAR', this.width / 2, this.height / 2 + 15);

                    this.ctx.fillStyle = '#94a3b8';
                    this.ctx.font = '14px sans-serif';
                    this.ctx.fillText('← → o A/D para moverte  •  ESPACIO para disparar', this.width / 2, this.height / 2 + 50);
                    this.ctx.textAlign = 'left';
                }
            }

            gameOver() {
                this.state = 'GAMEOVER';
                this.audio.hit();
                this.render();
                if (this.callbacks.onGameOver) {
                    this.callbacks.onGameOver(this.score, this.level);
                }
            }

            loop() {
                if (this.state === 'PLAYING') {
                    this.update();
                    this.render();
                    requestAnimationFrame(() => this.loop());
                }
            }
        }

        // =====================================================================
        // CONTROLADOR DE PÁGINA Y MODAL
        // =====================================================================
        document.addEventListener('DOMContentLoaded', () => {
            const hudScore       = document.getElementById('hudScore');
            const hudHiScore     = document.getElementById('hudHiScore');
            const hudLives       = document.getElementById('hudLives');
            const hudLevel       = document.getElementById('hudLevel');

            const btnStartGame   = document.getElementById('btnStartGame');
            const btnOpenScores  = document.getElementById('btnOpenScores');
            const btnAudioToggle = document.getElementById('btnAudioToggle');

            const scoreModal       = document.getElementById('scoreModal');
            const modalHeading     = document.getElementById('modalHeading');
            const calloutScoreBox  = document.getElementById('calloutScoreBox');
            const finalScoreDisplay= document.getElementById('finalScoreDisplay');
            const saveScoreForm    = document.getElementById('saveScoreForm');
            const playerNameInput  = document.getElementById('playerNameInput');
            const btnSubmitScore   = document.getElementById('btnSubmitScore');
            const saveSuccessAlert = document.getElementById('saveSuccessAlert');
            const scoresListBody   = document.getElementById('scoresListBody');
            const btnRestartGame   = document.getElementById('btnRestartGame');
            const btnCloseModal    = document.getElementById('btnCloseModal');

            let currentScore = 0;
            let hiScore = parseInt(localStorage.getItem('si_hiscore') || '0', 10);
            hudHiScore.textContent = hiScore.toLocaleString();

            // Instancia del Juego
            const game = new RetroSpaceInvaders('gameCanvas', {
                onScoreUpdate: (score, lives, level) => {
                    currentScore = score;
                    hudScore.textContent = score.toLocaleString();
                    hudLevel.textContent = String(level).padStart(2, '0');

                    let lifeIcons = '';
                    for (let i = 0; i < lives; i++) lifeIcons += '🚀 ';
                    hudLives.textContent = lifeIcons || '0';

                    if (score > hiScore) {
                        hiScore = score;
                        localStorage.setItem('si_hiscore', hiScore);
                        hudHiScore.textContent = hiScore.toLocaleString();
                    }
                },
                onGameOver: (score) => {
                    currentScore = score;
                    openLeaderboardModal(true);
                }
            });

            // Función para abrir la ventana modal
            async function openLeaderboardModal(isGameOver = false) {
                saveSuccessAlert.style.display = 'none';

                if (isGameOver) {
                    modalHeading.innerHTML = '<span>👾</span> ¡FIN DEL JUEGO!';
                    calloutScoreBox.style.display = 'flex';
                    finalScoreDisplay.textContent = currentScore.toLocaleString();
                    saveScoreForm.style.display = 'flex';
                    playerNameInput.value = '';
                    playerNameInput.focus();
                } else {
                    modalHeading.innerHTML = '<span>🏆</span> MEJORES PUNTUACIONES';
                    calloutScoreBox.style.display = 'none';
                    saveScoreForm.style.display = 'none';
                }

                scoreModal.classList.add('active');
                await loadScores();
            }

            function closeLeaderboardModal() {
                scoreModal.classList.remove('active');
            }

            // Cargar puntuaciones desde api.php
            async function loadScores() {
                scoresListBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 14px; color: var(--text-dim);">Cargando...</td></tr>`;

                try {
                    const res = await fetch('api.php?action=leaderboard');
                    const json = await res.json();

                    if (json.success && json.data && json.data.scores) {
                        renderScoresTable(json.data.scores);
                    } else {
                        scoresListBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 14px;">No hay registros disponibles</td></tr>`;
                    }
                } catch(e) {
                    scoresListBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 14px; color: #ff0055;">Error conectando a la base de datos</td></tr>`;
                }
            }

            function renderScoresTable(scores) {
                if (!scores.length) {
                    scoresListBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 14px;">Sé el primero en la lista</td></tr>`;
                    return;
                }

                let html = '';
                scores.forEach((s) => {
                    let medal = `#${s.rank}`;
                    let rowClass = '';
                    if (s.rank === 1) { medal = '🥇 #1'; rowClass = 'rank-1'; }
                    else if (s.rank === 2) { medal = '🥈 #2'; rowClass = 'rank-2'; }
                    else if (s.rank === 3) { medal = '🥉 #3'; rowClass = 'rank-3'; }

                    html += `
                        <tr class="${rowClass}">
                            <td class="col-rank">${medal}</td>
                            <td class="col-user">${escapeHtml(s.username)}</td>
                            <td class="col-pts">${Number(s.score).toLocaleString()}</td>
                            <td class="col-date">${escapeHtml(s.date || '')}</td>
                        </tr>
                    `;
                });
                scoresListBody.innerHTML = html;
            }

            function escapeHtml(text) {
                const p = document.createElement('p');
                p.textContent = text;
                return p.innerHTML;
            }

            // Guardar Puntuación
            saveScoreForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const username = playerNameInput.value.trim();
                if (!username) return;

                btnSubmitScore.disabled = true;
                btnSubmitScore.textContent = 'Guardando...';

                try {
                    const res = await fetch('api.php?action=submit_score', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username, score: currentScore })
                    });
                    const json = await res.json();

                    if (json.success) {
                        saveSuccessAlert.textContent = `¡Récord guardado con éxito! Rango #${json.data.rank}`;
                        saveSuccessAlert.style.display = 'block';
                        saveScoreForm.style.display = 'none';
                        await loadScores();
                    } else {
                        alert(json.message || 'Error al guardar');
                    }
                } catch(err) {
                    alert('Error de conexión con el servidor');
                } finally {
                    btnSubmitScore.disabled = false;
                    btnSubmitScore.textContent = 'Guardar';
                }
            });

            // Eventos de botones
            btnStartGame.addEventListener('click', () => {
                closeLeaderboardModal();
                game.start();
            });

            btnRestartGame.addEventListener('click', () => {
                closeLeaderboardModal();
                game.start();
            });

            btnOpenScores.addEventListener('click', () => {
                openLeaderboardModal(false);
            });

            btnCloseModal.addEventListener('click', () => {
                closeLeaderboardModal();
            });

            btnAudioToggle.addEventListener('click', () => {
                const on = game.audio.toggle();
                btnAudioToggle.textContent = on ? '🔊' : '🔇';
            });
        });
    </script>
</body>
</html>
