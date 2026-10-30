<?php
/**
 * Space Invaders - Juego Web
 */
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Space Invaders</title>
    
    <!-- Tipografía moderna y amigable -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@500;600;700;800&family=Press+Start+2P&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg-color: #0e111a;
            --card-bg: #161b26;
            --card-border: rgba(255, 255, 255, 0.08);
            --accent-coral: #ff5c5c;
            --accent-amber: #f7b267;
            --accent-mint: #38bdf8;
            --accent-green: #34d399;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --font-main: 'Plus Jakarta Sans', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
            --font-pixel: 'Press Start 2P', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg-color);
            color: var(--text-primary);
            font-family: var(--font-main);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            background-image: 
                radial-gradient(circle at 50% 0%, rgba(56, 189, 248, 0.08) 0%, transparent 60%),
                radial-gradient(circle at 100% 100%, rgba(255, 92, 92, 0.05) 0%, transparent 50%);
        }

        .game-wrapper {
            width: 100%;
            max-width: 680px;
            display: flex;
            flex-direction: column;
            gap: 14px;
        }

        /* BARRA SUPERIOR */
        .header-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 12px 18px;
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 14px;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.25);
        }

        .game-logo {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .game-logo span {
            font-size: 1.5rem;
        }

        .game-logo h1 {
            font-family: var(--font-pixel);
            font-size: 0.95rem;
            color: #fff;
            letter-spacing: 0.5px;
        }

        .header-actions {
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-header {
            background: rgba(255, 255, 255, 0.06);
            border: 1px solid rgba(255, 255, 255, 0.1);
            color: var(--text-primary);
            padding: 8px 14px;
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.2s ease;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .btn-header:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.2);
            transform: translateY(-1px);
        }

        /* CONTENEDOR PRINCIPAL */
        .arcade-card {
            background: var(--card-bg);
            border: 1px solid var(--card-border);
            border-radius: 16px;
            padding: 18px;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 14px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.35);
        }

        /* HUD PUNTUACIÓN Y VIDAS */
        .stats-bar {
            width: 100%;
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 10px;
            background: rgba(0, 0, 0, 0.25);
            padding: 10px 14px;
            border-radius: 10px;
            border: 1px solid rgba(255, 255, 255, 0.04);
            text-align: center;
        }

        .stat-item {
            display: flex;
            flex-direction: column;
            gap: 3px;
        }

        .stat-label {
            font-size: 0.72rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-number {
            font-family: var(--font-pixel);
            font-size: 0.95rem;
            color: #fff;
        }

        .stat-number.amber { color: var(--accent-amber); }
        .stat-number.mint { color: var(--accent-mint); }
        .stat-number.lives { font-family: sans-serif; font-size: 0.9rem; }

        /* PANTALLA CANVAS */
        .canvas-container {
            width: 100%;
            max-width: 640px;
            aspect-ratio: 4 / 3;
            background: #080a10;
            border-radius: 12px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.5);
            position: relative;
        }

        #gameCanvas {
            display: block;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        /* PIE DE CONTROLES */
        .controls-footer {
            width: 100%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 12px;
            padding-top: 4px;
        }

        .instructions {
            font-size: 0.85rem;
            color: var(--text-secondary);
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .key {
            background: rgba(255, 255, 255, 0.08);
            border: 1px solid rgba(255, 255, 255, 0.15);
            color: #fff;
            padding: 2px 7px;
            border-radius: 5px;
            font-size: 0.78rem;
            font-weight: 700;
        }

        .btn-primary {
            background: var(--accent-coral);
            color: #fff;
            border: none;
            padding: 10px 22px;
            border-radius: 9px;
            font-family: var(--font-main);
            font-size: 0.95rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s ease;
            box-shadow: 0 4px 12px rgba(255, 92, 92, 0.3);
        }

        .btn-primary:hover {
            background: #ff4747;
            transform: translateY(-2px);
            box-shadow: 0 6px 16px rgba(255, 92, 92, 0.4);
        }

        /* =====================================================================
           MODAL DE PUNTUACIONES (OCULTO POR DEFECTO CON DISPLAY: NONE)
           ===================================================================== */
        .modal-overlay {
            position: fixed;
            inset: 0;
            background: rgba(10, 12, 18, 0.8);
            backdrop-filter: blur(8px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 9999;
            padding: 16px;
        }

        .modal-overlay.active {
            display: flex !important;
        }

        .modal-dialog {
            background: #171c28;
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            max-width: 480px;
            width: 100%;
            padding: 24px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.6);
            animation: popIn 0.2s ease forwards;
        }

        @keyframes popIn {
            from { transform: scale(0.94); opacity: 0; }
            to { transform: scale(1); opacity: 1; }
        }

        .modal-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-title {
            font-size: 1.15rem;
            font-weight: 800;
            color: #fff;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .btn-close {
            background: none;
            border: none;
            color: var(--text-secondary);
            font-size: 1.3rem;
            cursor: pointer;
            line-height: 1;
        }

        .btn-close:hover {
            color: #fff;
        }

        /* CAJA DE PUNTAJE */
        .score-banner {
            background: rgba(247, 178, 103, 0.1);
            border: 1px solid rgba(247, 178, 103, 0.3);
            border-radius: 12px;
            padding: 14px;
            text-align: center;
            display: flex;
            flex-direction: column;
            gap: 4px;
        }

        .score-banner-label {
            font-size: 0.8rem;
            font-weight: 600;
            color: var(--text-secondary);
            text-transform: uppercase;
        }

        .score-banner-value {
            font-family: var(--font-pixel);
            font-size: 1.6rem;
            color: var(--accent-amber);
        }

        /* FORMULARIO DE NOMBRE */
        .save-form {
            display: flex;
            gap: 8px;
        }

        .save-form input {
            flex: 1;
            background: rgba(0, 0, 0, 0.3);
            border: 1px solid rgba(255, 255, 255, 0.12);
            color: #fff;
            padding: 10px 14px;
            border-radius: 8px;
            font-family: var(--font-main);
            font-size: 0.95rem;
            outline: none;
        }

        .save-form input:focus {
            border-color: var(--accent-mint);
        }

        .toast-msg {
            background: rgba(52, 211, 153, 0.15);
            color: var(--accent-green);
            border: 1px solid rgba(52, 211, 153, 0.3);
            padding: 10px;
            border-radius: 8px;
            font-size: 0.9rem;
            font-weight: 600;
            text-align: center;
        }

        /* TABLA DE POSICIONES */
        .table-section-title {
            font-size: 0.88rem;
            font-weight: 700;
            color: var(--text-secondary);
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-top: 4px;
        }

        .scores-table-container {
            background: rgba(0, 0, 0, 0.2);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 10px;
            overflow: hidden;
            max-height: 220px;
            overflow-y: auto;
        }

        .clean-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .clean-table th {
            background: rgba(0, 0, 0, 0.3);
            padding: 8px 12px;
            text-align: left;
            color: var(--text-secondary);
            font-size: 0.78rem;
            font-weight: 700;
            border-bottom: 1px solid rgba(255, 255, 255, 0.06);
        }

        .clean-table td {
            padding: 8px 12px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.03);
        }

        .clean-table .col-pos { width: 45px; font-weight: 700; color: var(--text-secondary); }
        .clean-table .col-pts { text-align: right; font-family: monospace; font-weight: 700; color: var(--accent-amber); }
        .clean-table .col-time { text-align: right; color: var(--text-secondary); font-size: 0.78rem; width: 120px; }

        .clean-table tr.top-1 td.col-name { color: var(--accent-amber); font-weight: 700; }
        .clean-table tr.top-2 td.col-name { color: #e2e8f0; font-weight: 700; }
        .clean-table tr.top-3 td.col-name { color: var(--accent-coral); font-weight: 700; }

        .modal-footer {
            display: flex;
            justify-content: flex-end;
            padding-top: 8px;
        }

        @media (max-width: 600px) {
            .stats-bar { grid-template-columns: repeat(2, 1fr); }
            .controls-footer { flex-direction: column; align-items: stretch; }
            .btn-primary { width: 100%; }
        }
    </style>
</head>
<body>

    <div class="game-wrapper">
        
        <!-- BARRA SUPERIOR (LIMPIA, SIN TEXTOS TÉCNICOS) -->
        <header class="header-bar">
            <div class="game-logo">
                <span>👾</span>
                <h1>SPACE INVADERS</h1>
            </div>

            <div class="header-actions">
                <button type="button" class="btn-header" id="btnToggleSound" title="Sonido">
                    <span id="soundIcon">🔊</span>
                </button>
                <button type="button" class="btn-header" id="btnShowScores">
                    🏆 Puntuaciones
                </button>
            </div>
        </header>

        <!-- TARJETA DEL JUEGO -->
        <main class="arcade-card">
            
            <!-- MARCADOR -->
            <div class="stats-bar">
                <div class="stat-item">
                    <span class="stat-label">Puntos</span>
                    <span class="stat-number" id="hudScore">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Récord</span>
                    <span class="stat-number amber" id="hudHiScore">0</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Vidas</span>
                    <span class="stat-number lives" id="hudLives">🚀 🚀 🚀</span>
                </div>
                <div class="stat-item">
                    <span class="stat-label">Nivel</span>
                    <span class="stat-number mint" id="hudLevel">1</span>
                </div>
            </div>

            <!-- PANTALLA CANVAS -->
            <div class="canvas-container">
                <canvas id="gameCanvas" width="640" height="480"></canvas>
            </div>

            <!-- CONTROLES -->
            <div class="controls-footer">
                <div class="instructions">
                    <span class="key">← →</span> o <span class="key">A D</span> Moverse
                    <span>•</span>
                    <span class="key">Espacio</span> Disparar
                    <span>•</span>
                    <span class="key">P</span> Pausa
                </div>

                <button type="button" class="btn-primary" id="btnPlayGame">
                    Jugar Partida
                </button>
            </div>

        </main>

    </div>

    <!-- =========================================================================
         MODAL // FIN DE PARTIDA Y TABLA DE POSICIONES
         ========================================================================= -->
    <div class="modal-overlay" id="scoreModal">
        <div class="modal-dialog">
            
            <div class="modal-header">
                <div class="modal-title" id="modalHeading">
                    <span>🏆</span> Tabla de Puntuaciones
                </div>
                <button type="button" class="btn-close" id="btnCloseModal">✕</button>
            </div>

            <!-- Resumen de puntuación tras jugar -->
            <div class="score-banner" id="scoreBannerBox">
                <span class="score-banner-label">Puntuación de la Partida</span>
                <span class="score-banner-value" id="finalScoreVal">0</span>
            </div>

            <!-- Formulario simple de registro -->
            <form class="save-form" id="saveScoreForm">
                <input type="text" id="playerNameInput" placeholder="Ingresa tu nombre..." maxlength="20" required autocomplete="off">
                <button type="submit" class="btn-primary" id="btnSaveSubmit">Guardar</button>
            </form>

            <div class="toast-msg" id="saveSuccessNotice" style="display: none;"></div>

            <!-- Tabla de Récords -->
            <div class="table-section-title">Top 10 Jugadores</div>
            <div class="scores-table-container">
                <table class="clean-table">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Jugador</th>
                            <th style="text-align: right;">Puntos</th>
                            <th style="text-align: right;">Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="scoresBody">
                        <tr><td colspan="4" style="text-align:center; padding: 14px; color: var(--text-secondary);">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>

            <div class="modal-footer">
                <button type="button" class="btn-primary" id="btnPlayAgain">
                    Jugar de Nuevo
                </button>
            </div>

        </div>
    </div>

    <!-- =========================================================================
         MOTOR DE JUEGO (CORREGIDO EL BUG DEL ÚLTIMO INVASOR + TRANSICIÓN LIMPIA)
         ========================================================================= -->
    <script>
        // EFECTOS DE SONIDO SIMPLES Y RETRO
        class SoundFX {
            constructor() {
                this.ctx = null;
                this.muted = false;
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
                this.muted = !this.muted;
                return !this.muted;
            }
            laser() {
                if (this.muted || !this.ctx) return;
                try {
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.type = 'square';
                    osc.frequency.setValueAtTime(800, this.ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(150, this.ctx.currentTime + 0.08);
                    gain.gain.setValueAtTime(0.1, this.ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.08);
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    osc.start();
                    osc.stop(this.ctx.currentTime + 0.08);
                } catch(e){}
            }
            pop() {
                if (this.muted || !this.ctx) return;
                try {
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(320, this.ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(60, this.ctx.currentTime + 0.12);
                    gain.gain.setValueAtTime(0.15, this.ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.12);
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    osc.start();
                    osc.stop(this.ctx.currentTime + 0.12);
                } catch(e){}
            }
            boom() {
                if (this.muted || !this.ctx) return;
                try {
                    const dur = 0.25;
                    const buf = this.ctx.createBuffer(1, this.ctx.sampleRate * dur, this.ctx.sampleRate);
                    const d = buf.getChannelData(0);
                    for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
                    const noise = this.ctx.createBufferSource();
                    noise.buffer = buf;
                    const gain = this.ctx.createGain();
                    gain.gain.setValueAtTime(0.18, this.ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + dur);
                    noise.connect(gain);
                    gain.connect(this.ctx.destination);
                    noise.start();
                } catch(e){}
            }
        }

        // MOTOR PRINCIPAL SPACE INVADERS
        class SpaceInvaders {
            constructor(canvasId, callbacks) {
                this.canvas = document.getElementById(canvasId);
                this.ctx = this.canvas.getContext('2d');
                this.sound = new SoundFX();
                this.callbacks = callbacks || {};

                this.width = this.canvas.width;
                this.height = this.canvas.height;

                this.score = 0;
                this.lives = 3;
                this.level = 1;
                this.state = 'IDLE'; // IDLE, PLAYING, PAUSED, LEVEL_CLEAR, GAMEOVER
                this.levelClearTimer = 0;

                this.player = {
                    w: 36,
                    h: 18,
                    x: this.width / 2 - 18,
                    y: this.height - 38,
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

                this.initControls();
                this.reset();
                this.render();
            }

            initControls() {
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
                    this.sound.init();
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
                        let color = '#38bdf8'; // Celeste suave

                        if (r === 0) { type = 'top'; pts = 30; color = '#ff5c5c'; } // Coral
                        else if (r === 1 || r === 2) { type = 'mid'; pts = 20; color = '#f7b267'; } // Ámbar

                        this.aliens.push({
                            x: 64 + c * 48,
                            y: 54 + r * 34,
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
                this.alienSpeed = 0.8 + (this.level * 0.2);
            }

            initBunkers() {
                this.bunkers = [];
                const count = 3;
                const bWidth = 56;
                const gap = (this.width - (count * bWidth)) / (count + 1);

                for (let i = 0; i < count; i++) {
                    const bx = gap + i * (bWidth + gap);
                    const by = this.height - 95;
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
                this.sound.init();
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
                if (this.state === 'LEVEL_CLEAR') {
                    this.levelClearTimer--;
                    if (this.levelClearTimer <= 0) {
                        this.level++;
                        this.score += 500;
                        this.bullets = [];
                        this.bombs = [];
                        this.initAliens();
                        this.initBunkers();
                        this.state = 'PLAYING';
                        if (this.callbacks.onScoreUpdate) {
                            this.callbacks.onScoreUpdate(this.score, this.lives, this.level);
                        }
                    }
                    return;
                }

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
                    this.shootCooldown = 15;
                    this.sound.laser();
                }

                // 3. Proyectiles (SEGURO: SIN MUTAR EL ARRAY INCONSISTENTEMENTE)
                let allAliensKilled = false;

                for (let i = this.bullets.length - 1; i >= 0; i--) {
                    const b = this.bullets[i];
                    if (!b) continue;

                    b.y += b.vy;
                    if (b.y < -10) {
                        this.bullets.splice(i, 1);
                        continue;
                    }

                    // Bunkers
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

                    // Invasores
                    for (let k = 0; k < this.aliens.length; k++) {
                        const a = this.aliens[k];
                        if (a.alive && b.x < a.x + a.w && b.x + b.w > a.x && b.y < a.y + a.h && b.y + b.h > a.y) {
                            a.alive = false;
                            this.bullets.splice(i, 1);
                            this.score += a.pts;
                            this.sound.pop();

                            if (this.callbacks.onScoreUpdate) {
                                this.callbacks.onScoreUpdate(this.score, this.lives, this.level);
                            }

                            const remaining = this.aliens.filter(x => x.alive).length;
                            this.alienSpeed = Math.min(4.5, 0.8 + (this.level * 0.2) + ((32 - remaining) * 0.08));

                            // ¿Murió el último personaje?
                            if (remaining === 0) {
                                allAliensKilled = true;
                            }
                            break;
                        }
                    }

                    if (allAliensKilled) break;
                }

                // SI SE LIMPIÓ LA OLEADA: Transición suave sin trabarse
                if (allAliensKilled) {
                    this.bullets = [];
                    this.bombs = [];
                    this.state = 'LEVEL_CLEAR';
                    this.levelClearTimer = 50; // ~0.8 segundos de pausa celebratoria
                    return;
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

                // 5. Bombas alien
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
                    if (!bm) continue;

                    bm.y += bm.vy;
                    if (bm.y > this.height) {
                        this.bombs.splice(i, 1);
                        continue;
                    }

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
                        this.sound.boom();
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
                // Fondo oscuro limpio y suave
                this.ctx.fillStyle = '#080a10';
                this.ctx.fillRect(0, 0, this.width, this.height);

                // Estrellas sutiles
                this.ctx.fillStyle = 'rgba(255, 255, 255, 0.15)';
                for (let s = 0; s < 30; s++) {
                    const sx = (s * 89) % this.width;
                    const sy = (s * 47) % this.height;
                    this.ctx.fillRect(sx, sy, 2, 2);
                }

                // Bunkers (verde menta suave)
                for (const bk of this.bunkers) {
                    const alpha = bk.hp === 3 ? 0.9 : (bk.hp === 2 ? 0.6 : 0.35);
                    this.ctx.fillStyle = `rgba(52, 211, 153, ${alpha})`;
                    this.ctx.fillRect(bk.x, bk.y, bk.w, bk.h);
                }

                // Invasores (colores amigables y animación clásica de 2 pasos)
                const step = Math.floor(Date.now() / 400) % 2;
                for (const a of this.aliens) {
                    if (!a.alive) continue;
                    this.ctx.fillStyle = a.color;

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

                    // Ojos
                    this.ctx.fillStyle = '#080a10';
                    this.ctx.fillRect(a.x + 8, a.y + 6, 3, 3);
                    this.ctx.fillRect(a.x + a.w - 11, a.y + 6, 3, 3);
                }

                // Proyectiles jugador (celeste brillante)
                this.ctx.fillStyle = '#38bdf8';
                for (const b of this.bullets) this.ctx.fillRect(b.x, b.y, b.w, b.h);

                // Bombas alien (coral)
                this.ctx.fillStyle = '#ff5c5c';
                for (const bm of this.bombs) this.ctx.fillRect(bm.x, bm.y, bm.w, bm.h);

                // Cañón del Jugador (verde menta suave)
                if (!this.player.isHit || Math.floor(Date.now() / 80) % 2 === 0) {
                    this.ctx.fillStyle = '#34d399';
                    const x = this.player.x, y = this.player.y, w = this.player.w, h = this.player.h;
                    this.ctx.beginPath();
                    this.ctx.moveTo(x + w / 2, y);
                    this.ctx.lineTo(x + w / 2 + 3, y + 5);
                    this.ctx.lineTo(x + w - 4, y + 5);
                    this.ctx.lineTo(x + w, y + h);
                    this.ctx.lineTo(x, y + h);
                    this.ctx.lineTo(x + 4, y + 5);
                    this.ctx.lineTo(x + w / 2 - 3, y + 5);
                    this.ctx.closePath();
                    this.ctx.fill();
                }

                // Línea inferior de defensa
                this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.08)';
                this.ctx.lineWidth = 1;
                this.ctx.beginPath();
                this.ctx.moveTo(10, this.height - 14);
                this.ctx.lineTo(this.width - 10, this.height - 14);
                this.ctx.stroke();

                // Cartel de Nivel Superado
                if (this.state === 'LEVEL_CLEAR') {
                    this.ctx.fillStyle = 'rgba(0, 0, 0, 0.6)';
                    this.ctx.fillRect(0, 0, this.width, this.height);
                    this.ctx.fillStyle = '#34d399';
                    this.ctx.font = 'bold 20px "Plus Jakarta Sans", sans-serif';
                    this.ctx.textAlign = 'center';
                    this.ctx.fillText('¡OLEADA SUPERADA!', this.width / 2, this.height / 2 - 10);
                    this.ctx.fillStyle = '#f7b267';
                    this.ctx.font = '14px "Plus Jakarta Sans", sans-serif';
                    this.ctx.fillText('+500 Puntos Bonus', this.width / 2, this.height / 2 + 18);
                    this.ctx.textAlign = 'left';
                }

                // Pantalla Pausa
                if (this.state === 'PAUSED') {
                    this.ctx.fillStyle = 'rgba(0, 0, 0, 0.65)';
                    this.ctx.fillRect(0, 0, this.width, this.height);
                    this.ctx.fillStyle = '#f7b267';
                    this.ctx.font = 'bold 22px "Plus Jakarta Sans", sans-serif';
                    this.ctx.textAlign = 'center';
                    this.ctx.fillText('JUEGO EN PAUSA', this.width / 2, this.height / 2 - 10);
                    this.ctx.font = '14px "Plus Jakarta Sans", sans-serif';
                    this.ctx.fillStyle = '#94a3b8';
                    this.ctx.fillText('Presiona P para continuar jugando', this.width / 2, this.height / 2 + 20);
                    this.ctx.textAlign = 'left';
                }

                // Pantalla Inicial
                if (this.state === 'IDLE') {
                    this.ctx.fillStyle = 'rgba(0, 0, 0, 0.65)';
                    this.ctx.fillRect(0, 0, this.width, this.height);
                    this.ctx.fillStyle = '#fff';
                    this.ctx.font = 'bold 24px "Plus Jakarta Sans", sans-serif';
                    this.ctx.textAlign = 'center';
                    this.ctx.fillText('¿Listo para jugar?', this.width / 2, this.height / 2 - 20);

                    this.ctx.fillStyle = '#ff5c5c';
                    this.ctx.font = '15px "Plus Jakarta Sans", sans-serif';
                    this.ctx.fillText('Haz clic o presiona Espacio para comenzar', this.width / 2, this.height / 2 + 15);
                    this.ctx.textAlign = 'left';
                }
            }

            gameOver() {
                this.state = 'GAMEOVER';
                this.sound.boom();
                this.render();
                if (this.callbacks.onGameOver) {
                    this.callbacks.onGameOver(this.score, this.level);
                }
            }

            loop() {
                if (this.state === 'PLAYING' || this.state === 'LEVEL_CLEAR') {
                    this.update();
                    this.render();
                    requestAnimationFrame(() => this.loop());
                }
            }
        }

        // =====================================================================
        // CONTROLADOR DE PÁGINA
        // =====================================================================
        document.addEventListener('DOMContentLoaded', () => {
            const hudScore       = document.getElementById('hudScore');
            const hudHiScore     = document.getElementById('hudHiScore');
            const hudLives       = document.getElementById('hudLives');
            const hudLevel       = document.getElementById('hudLevel');

            const btnPlayGame    = document.getElementById('btnPlayGame');
            const btnShowScores  = document.getElementById('btnShowScores');
            const btnToggleSound = document.getElementById('btnToggleSound');
            const soundIcon      = document.getElementById('soundIcon');

            const scoreModal       = document.getElementById('scoreModal');
            const modalHeading     = document.getElementById('modalHeading');
            const scoreBannerBox   = document.getElementById('scoreBannerBox');
            const finalScoreVal    = document.getElementById('finalScoreVal');
            const saveScoreForm    = document.getElementById('saveScoreForm');
            const playerNameInput  = document.getElementById('playerNameInput');
            const btnSaveSubmit    = document.getElementById('btnSaveSubmit');
            const saveSuccessNotice= document.getElementById('saveSuccessNotice');
            const scoresBody       = document.getElementById('scoresBody');
            const btnPlayAgain     = document.getElementById('btnPlayAgain');
            const btnCloseModal    = document.getElementById('btnCloseModal');

            let currentScore = 0;
            let record = parseInt(localStorage.getItem('si_best_score') || '0', 10);
            hudHiScore.textContent = record.toLocaleString();

            const game = new SpaceInvaders('gameCanvas', {
                onScoreUpdate: (score, lives, level) => {
                    currentScore = score;
                    hudScore.textContent = score.toLocaleString();
                    hudLevel.textContent = level;

                    let hearts = '';
                    for (let i = 0; i < lives; i++) hearts += '🚀 ';
                    hudLives.textContent = hearts || '0';

                    if (score > record) {
                        record = score;
                        localStorage.setItem('si_best_score', record);
                        hudHiScore.textContent = record.toLocaleString();
                    }
                },
                onGameOver: (score) => {
                    currentScore = score;
                    openModal(true);
                }
            });

            async function openModal(isGameOver = false) {
                saveSuccessNotice.style.display = 'none';

                if (isGameOver) {
                    modalHeading.innerHTML = '<span>👾</span> ¡Partida Terminada!';
                    scoreBannerBox.style.display = 'flex';
                    finalScoreVal.textContent = currentScore.toLocaleString();
                    saveScoreForm.style.display = 'flex';
                    playerNameInput.value = '';
                    playerNameInput.focus();
                } else {
                    modalHeading.innerHTML = '<span>🏆</span> Mejores Puntuaciones';
                    scoreBannerBox.style.display = 'none';
                    saveScoreForm.style.display = 'none';
                }

                scoreModal.classList.add('active');
                await fetchLeaderboard();
            }

            function closeModal() {
                scoreModal.classList.remove('active');
            }

            async function fetchLeaderboard() {
                scoresBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 14px; color: var(--text-secondary);">Cargando puntuaciones...</td></tr>`;

                try {
                    const res = await fetch('api.php?action=leaderboard');
                    const json = await res.json();

                    if (json.success && json.data && json.data.scores) {
                        renderTable(json.data.scores);
                    } else {
                        scoresBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 14px;">No hay registros aún</td></tr>`;
                    }
                } catch(e) {
                    scoresBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 14px; color: var(--accent-coral);">No se pudo conectar a la base de datos</td></tr>`;
                }
            }

            function renderTable(scores) {
                if (!scores.length) {
                    scoresBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 14px;">Sé el primero en registrar récord</td></tr>`;
                    return;
                }

                let html = '';
                scores.forEach((s) => {
                    let pos = `#${s.rank}`;
                    let rowClass = '';
                    if (s.rank === 1) { pos = '🥇 1'; rowClass = 'top-1'; }
                    else if (s.rank === 2) { pos = '🥈 2'; rowClass = 'top-2'; }
                    else if (s.rank === 3) { pos = '🥉 3'; rowClass = 'top-3'; }

                    html += `
                        <tr class="${rowClass}">
                            <td class="col-pos">${pos}</td>
                            <td class="col-name">${escapeHtml(s.username)}</td>
                            <td class="col-pts">${Number(s.score).toLocaleString()}</td>
                            <td class="col-time">${escapeHtml(s.date || '')}</td>
                        </tr>
                    `;
                });
                scoresBody.innerHTML = html;
            }

            function escapeHtml(text) {
                const el = document.createElement('div');
                el.textContent = text;
                return el.innerHTML;
            }

            saveScoreForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                const username = playerNameInput.value.trim();
                if (!username) return;

                btnSaveSubmit.disabled = true;
                btnSaveSubmit.textContent = 'Guardando...';

                try {
                    const res = await fetch('api.php?action=submit_score', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ username, score: currentScore })
                    });
                    const json = await res.json();

                    if (json.success) {
                        saveSuccessNotice.textContent = `¡Récord guardado con éxito! Posición #${json.data.rank}`;
                        saveSuccessNotice.style.display = 'block';
                        saveScoreForm.style.display = 'none';
                        await fetchLeaderboard();
                    } else {
                        alert(json.message || 'Error al guardar');
                    }
                } catch(err) {
                    alert('Error al guardar la puntuación');
                } finally {
                    btnSaveSubmit.disabled = false;
                    btnSaveSubmit.textContent = 'Guardar';
                }
            });

            btnPlayGame.addEventListener('click', () => {
                closeModal();
                game.start();
            });

            btnPlayAgain.addEventListener('click', () => {
                closeModal();
                game.start();
            });

            btnShowScores.addEventListener('click', () => {
                openModal(false);
            });

            btnCloseModal.addEventListener('click', () => {
                closeModal();
            });

            btnToggleSound.addEventListener('click', () => {
                const active = game.sound.toggle();
                soundIcon.textContent = active ? '🔊' : '🔇';
            });
        });
    </script>
</body>
</html>
