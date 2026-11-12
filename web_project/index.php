<?php
require_once __DIR__ . '/config/db.php';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Space Invaders</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">

    <style>
        :root {
            --bg: #09090b;
            --surface: #121215;
            --surface-elevated: #18181c;
            --surface-hover: #222228;
            --border: #24242b;
            --border-subtle: #1c1c22;
            --text: #f4f4f5;
            --text-muted: #8e8e99;
            --text-dim: #52525b;
            --primary: #f4f4f5;
            --primary-fg: #09090b;
            --accent: #10b981;
            --amber: #f59e0b;
            --danger: #f43f5e;
            --font-sans: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
            --font-mono: 'JetBrains Mono', monospace;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            background-color: var(--bg);
            color: var(--text);
            font-family: var(--font-sans);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
            -webkit-font-smoothing: antialiased;
        }

        .app-shell {
            width: 100%;
            max-width: 680px;
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        /* HEADER */
        .topbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            gap: 12px;
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 10px;
            font-weight: 700;
            font-size: 0.9rem;
            letter-spacing: 0.04em;
            color: var(--text);
            user-select: none;
        }

        .brand svg {
            width: 20px;
            height: 20px;
            fill: var(--accent);
            flex-shrink: 0;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .btn {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--text-muted);
            height: 34px;
            padding: 0 12px;
            border-radius: 7px;
            font-family: var(--font-sans);
            font-size: 0.8125rem;
            font-weight: 500;
            cursor: pointer;
            transition: background 0.15s, color 0.15s, border-color 0.15s;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 6px;
            white-space: nowrap;
        }

        .btn svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
            flex-shrink: 0;
        }

        .btn:hover {
            background: var(--surface-hover);
            color: var(--text);
            border-color: #32323c;
        }

        .btn-icon {
            width: 34px;
            padding: 0;
        }

        .btn-solid {
            background: var(--primary);
            color: var(--primary-fg);
            border-color: var(--primary);
            font-weight: 600;
        }

        .btn-solid:hover {
            background: #dcdce0;
            color: var(--primary-fg);
            border-color: #dcdce0;
        }

        .btn-solid:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        .user-chip {
            display: flex;
            align-items: center;
            gap: 8px;
            height: 34px;
            padding: 0 10px;
            background: var(--surface-elevated);
            border: 1px solid var(--border);
            border-radius: 7px;
            font-size: 0.8125rem;
            font-weight: 500;
            color: var(--text);
        }

        .user-chip-dot {
            width: 6px;
            height: 6px;
            border-radius: 50%;
            background: var(--accent);
        }

        /* MAIN BOARD */
        .board {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 10px;
            overflow: hidden;
            display: flex;
            flex-direction: column;
        }

        /* HUD */
        .hud {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            border-bottom: 1px solid var(--border);
            background: #0d0d10;
        }

        .hud-cell {
            padding: 10px 14px;
            display: flex;
            flex-direction: column;
            gap: 2px;
            border-right: 1px solid var(--border-subtle);
        }

        .hud-cell:last-child {
            border-right: none;
        }

        .hud-label {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.06em;
        }

        .hud-val {
            font-family: var(--font-mono);
            font-size: 1rem;
            font-weight: 700;
            color: var(--text);
        }

        .hud-val.amber { color: var(--amber); }

        .lives-row {
            display: flex;
            align-items: center;
            gap: 6px;
            height: 20px;
        }

        .life-ship {
            width: 16px;
            height: 12px;
            fill: var(--accent);
            transition: opacity 0.2s;
        }

        .life-ship.lost {
            fill: var(--text-dim);
            opacity: 0.25;
        }

        /* CANVAS */
        .viewport {
            width: 100%;
            aspect-ratio: 4 / 3;
            background: #070709;
            position: relative;
        }

        #gameCanvas {
            display: block;
            width: 100%;
            height: 100%;
            cursor: pointer;
        }

        /* FOOTER BAR */
        .bottombar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 14px;
            border-top: 1px solid var(--border);
            background: var(--surface);
            gap: 12px;
        }

        .shortcuts {
            display: flex;
            align-items: center;
            gap: 14px;
            font-size: 0.75rem;
            color: var(--text-muted);
        }

        .shortcut-item {
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        kbd {
            background: var(--surface-elevated);
            border: 1px solid var(--border);
            border-bottom-width: 2px;
            color: var(--text);
            padding: 1px 6px;
            border-radius: 4px;
            font-family: var(--font-mono);
            font-size: 0.6875rem;
            font-weight: 600;
        }

        /* MODALS */
        .overlay {
            position: fixed;
            inset: 0;
            background: rgba(0, 0, 0, 0.72);
            backdrop-filter: blur(4px);
            -webkit-backdrop-filter: blur(4px);
            display: none;
            align-items: center;
            justify-content: center;
            z-index: 1000;
            padding: 16px;
        }

        .overlay.active {
            display: flex;
        }

        .dialog {
            background: var(--surface);
            border: 1px solid var(--border);
            border-radius: 12px;
            width: 100%;
            max-width: 390px;
            padding: 20px;
            display: flex;
            flex-direction: column;
            gap: 16px;
            box-shadow: 0 20px 50px rgba(0, 0, 0, 0.6);
        }

        .dialog.wide {
            max-width: 440px;
        }

        .dialog-head {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .dialog-title {
            font-size: 0.95rem;
            font-weight: 600;
            color: var(--text);
        }

        .btn-close {
            background: transparent;
            border: none;
            color: var(--text-muted);
            width: 28px;
            height: 28px;
            border-radius: 6px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .btn-close:hover {
            background: var(--surface-hover);
            color: var(--text);
        }

        .btn-close svg {
            width: 16px;
            height: 16px;
            stroke: currentColor;
            stroke-width: 2;
        }

        /* TABS */
        .segmented {
            display: grid;
            grid-template-columns: 1fr 1fr;
            background: #0b0b0e;
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            padding: 3px;
            gap: 3px;
        }

        .seg-btn {
            background: transparent;
            border: none;
            color: var(--text-muted);
            font-family: var(--font-sans);
            font-size: 0.8125rem;
            font-weight: 500;
            padding: 7px 10px;
            border-radius: 6px;
            cursor: pointer;
            transition: all 0.15s;
        }

        .seg-btn.active {
            background: var(--surface-elevated);
            color: var(--text);
            box-shadow: 0 1px 3px rgba(0,0,0,0.4);
        }

        /* FORMS */
        .form-stack {
            display: flex;
            flex-direction: column;
            gap: 12px;
        }

        .field {
            display: flex;
            flex-direction: column;
            gap: 5px;
        }

        .field label {
            font-size: 0.75rem;
            font-weight: 500;
            color: var(--text-muted);
        }

        .control {
            position: relative;
            display: flex;
            align-items: center;
        }

        .input {
            width: 100%;
            height: 38px;
            background: #0b0b0e;
            border: 1px solid var(--border);
            color: var(--text);
            padding: 0 36px 0 12px;
            border-radius: 7px;
            font-family: var(--font-sans);
            font-size: 0.875rem;
            outline: none;
            transition: border-color 0.15s;
        }

        .input:focus {
            border-color: #52525b;
        }

        .eye-btn {
            position: absolute;
            right: 6px;
            background: none;
            border: none;
            color: var(--text-dim);
            width: 28px;
            height: 28px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .eye-btn:hover {
            color: var(--text-muted);
        }

        .eye-btn svg {
            width: 15px;
            height: 15px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .alert {
            padding: 9px 12px;
            border-radius: 7px;
            font-size: 0.8125rem;
            line-height: 1.4;
            display: none;
        }

        .alert-error {
            background: rgba(244, 63, 94, 0.1);
            border: 1px solid rgba(244, 63, 94, 0.25);
            color: #fb7185;
        }

        .alert-error button {
            background: none;
            border: none;
            color: var(--text);
            text-decoration: underline;
            font-weight: 600;
            font-size: 0.8125rem;
            cursor: pointer;
            margin-top: 4px;
            display: inline-block;
        }

        .alert-ok {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.25);
            color: #34d399;
        }

        /* SETTINGS ACCOUNT ROW */
        .account-bar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            padding: 10px 12px;
            background: #0b0b0e;
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
        }

        .account-user {
            display: flex;
            align-items: center;
            gap: 8px;
            font-size: 0.8125rem;
            font-weight: 600;
            color: var(--text);
        }

        .btn-text-danger {
            background: none;
            border: none;
            color: var(--danger);
            font-family: var(--font-sans);
            font-size: 0.75rem;
            font-weight: 500;
            cursor: pointer;
            padding: 4px 6px;
            border-radius: 5px;
        }

        .btn-text-danger:hover {
            background: rgba(244, 63, 94, 0.1);
        }

        .divider {
            height: 1px;
            background: var(--border-subtle);
        }

        .section-caption {
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--text-muted);
        }

        /* SCORE SUMMARY & LEADERBOARD */
        .gameover-summary {
            display: flex;
            align-items: baseline;
            justify-content: space-between;
            padding: 12px 14px;
            background: #0b0b0e;
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
        }

        .gameover-summary span {
            font-size: 0.75rem;
            color: var(--text-muted);
            text-transform: uppercase;
            letter-spacing: 0.05em;
            font-weight: 600;
        }

        .gameover-summary strong {
            font-family: var(--font-mono);
            font-size: 1.25rem;
            color: var(--amber);
        }

        .table-wrap {
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            overflow: hidden;
            background: #0b0b0e;
            max-height: 260px;
            overflow-y: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.8125rem;
        }

        th {
            text-align: left;
            padding: 8px 12px;
            color: var(--text-dim);
            font-weight: 600;
            font-size: 0.6875rem;
            text-transform: uppercase;
            letter-spacing: 0.04em;
            border-bottom: 1px solid var(--border-subtle);
            background: #0e0e12;
        }

        td {
            padding: 8px 12px;
            border-bottom: 1px solid #141418;
            color: var(--text-muted);
        }

        tr:last-child td {
            border-bottom: none;
        }

        tr.is-me td {
            background: rgba(16, 185, 129, 0.06);
            color: var(--text);
        }

        .col-rank {
            width: 42px;
            font-family: var(--font-mono);
            font-size: 0.75rem;
            color: var(--text-dim);
        }

        tr.rank-1 .col-rank, tr.rank-1 .col-player { color: var(--amber); font-weight: 600; }
        tr.rank-2 .col-player, tr.rank-3 .col-player { color: var(--text); font-weight: 500; }

        .col-score {
            text-align: right;
            font-family: var(--font-mono);
            font-weight: 600;
            color: var(--text);
        }

        .col-date {
            text-align: right;
            font-size: 0.75rem;
            color: var(--text-dim);
            width: 115px;
        }

        @media (max-width: 560px) {
            .hud { grid-template-columns: repeat(2, 1fr); }
            .hud-cell:nth-child(2) { border-right: none; }
            .hud-cell:nth-child(1), .hud-cell:nth-child(2) { border-bottom: 1px solid var(--border-subtle); }
            .shortcuts { display: none; }
            .bottombar .btn-solid { width: 100%; }
        }

        /* SECURITY BADGE (TOPBAR) */
        .security-badge {
            display: inline-flex;
            align-items: center;
            gap: 5px;
            height: 34px;
            padding: 0 10px;
            background: rgba(16, 185, 129, 0.08);
            border: 1px solid rgba(16, 185, 129, 0.25);
            border-radius: 7px;
            font-size: 0.75rem;
            font-weight: 600;
            color: var(--accent);
            font-family: var(--font-mono);
            user-select: none;
        }

        .security-badge svg {
            width: 13px;
            height: 13px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        /* PASSWORD STRENGTH & POLICY CHECKLIST */
        .pwd-policy-box {
            display: flex;
            flex-direction: column;
            gap: 8px;
            padding: 10px 12px;
            background: #0b0b0e;
            border: 1px solid var(--border-subtle);
            border-radius: 8px;
            font-size: 0.75rem;
            transition: all 0.2s ease;
        }

        .pwd-meter-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .pwd-meter-title {
            font-size: 0.6875rem;
            font-weight: 600;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.05em;
        }

        .btn-suggest-pwd {
            background: transparent;
            border: 1px solid var(--border);
            color: var(--accent);
            font-family: var(--font-sans);
            font-size: 0.6875rem;
            font-weight: 600;
            padding: 3px 8px;
            border-radius: 5px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 5px;
            transition: background 0.15s, border-color 0.15s;
        }

        .btn-suggest-pwd svg {
            width: 12px;
            height: 12px;
            stroke: currentColor;
            stroke-width: 2;
            fill: none;
        }

        .btn-suggest-pwd:hover {
            background: rgba(16, 185, 129, 0.12);
            border-color: rgba(16, 185, 129, 0.35);
        }

        .pwd-meter-track {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 4px;
            height: 4px;
        }

        .pwd-meter-bar {
            height: 100%;
            background: #24242b;
            border-radius: 2px;
            transition: background 0.2s ease;
        }

        .pwd-meter-bar.active-red {
            background: var(--danger);
        }

        .pwd-meter-bar.active-orange {
            background: #f97316;
        }

        .pwd-meter-bar.active-amber {
            background: var(--amber);
        }

        .pwd-meter-bar.active-green {
            background: var(--accent);
            box-shadow: 0 0 6px rgba(16, 185, 129, 0.4);
        }

        .pwd-meter-info {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 8px;
            font-size: 0.6875rem;
        }

        .pwd-level-tag {
            font-weight: 700;
            font-family: var(--font-mono);
            letter-spacing: 0.02em;
            color: var(--text-dim);
        }

        .pwd-level-tag.red { color: #fb7185; }
        .pwd-level-tag.orange { color: #fb923c; }
        .pwd-level-tag.amber { color: #fcd34d; }
        .pwd-level-tag.green { color: #34d399; }

        .pwd-hint-text {
            color: var(--text-dim);
            font-size: 0.6875rem;
            text-align: right;
        }

        .pwd-rules-list {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 4px 10px;
            padding-top: 4px;
            border-top: 1px solid var(--border-subtle);
        }

        .pwd-rule {
            display: flex;
            align-items: center;
            gap: 6px;
            color: var(--text-dim);
            font-size: 0.6875rem;
            transition: color 0.15s ease;
        }

        .pwd-rule .rule-icon {
            font-family: var(--font-mono);
            font-weight: bold;
            font-size: 0.75rem;
            color: var(--text-dim);
            line-height: 1;
        }

        .pwd-rule.pass {
            color: var(--text);
        }

        .pwd-rule.pass .rule-icon {
            color: var(--accent);
        }
    </style>
</head>
<body>

    <div class="app-shell">
        <header class="topbar">
            <div class="brand">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 4h2v2H6V4zm10 0h2v2h-2V4zM4 6h16v4h2v6h-2v-4h-2v4h-4v2H10v-2H6v-4H4v4H2v-6h2V6zm4 2v2h2V8H8zm6 0v2h2V8h-2z"/>
                </svg>
                <span>SPACE INVADERS</span>
            </div>

            <div class="nav-actions">
                <div class="security-badge" title="Cifrado E2E AES-256 de paquetes activo">
                    <svg viewBox="0 0 24 24"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                    <span>AES-256</span>
                </div>

                <div class="user-chip" id="userBadgeBox" style="display: none;">
                    <span class="user-chip-dot"></span>
                    <span id="displayUsername"></span>
                </div>

                <button type="button" class="btn btn-solid" id="btnLoginOpen" style="display: none;">
                    Entrar
                </button>

                <button type="button" class="btn" id="btnShowScores">
                    <svg viewBox="0 0 24 24"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4zM4 6h3v3a3 3 0 0 1-3-3zm16 0h-3v3a3 3 0 0 0 3-3z"/></svg>
                    <span>Ranking</span>
                </button>

                <button type="button" class="btn" id="btnSettings" title="Ajustes">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Ajustes</span>
                </button>

                <button type="button" class="btn btn-icon" id="btnToggleSound" title="Sonido">
                    <svg id="soundIconOn" viewBox="0 0 24 24"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                    <svg id="soundIconOff" viewBox="0 0 24 24" style="display:none;"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                </button>
            </div>
        </header>

        <main class="board">
            <div class="hud">
                <div class="hud-cell">
                    <span class="hud-label">Puntos</span>
                    <span class="hud-val" id="hudScore">0</span>
                </div>
                <div class="hud-cell">
                    <span class="hud-label">Récord</span>
                    <span class="hud-val amber" id="hudHiScore">0</span>
                </div>
                <div class="hud-cell">
                    <span class="hud-label">Nivel</span>
                    <span class="hud-val" id="hudLevel">1</span>
                </div>
                <div class="hud-cell">
                    <span class="hud-label">Vidas</span>
                    <div class="lives-row" id="hudLives"></div>
                </div>
            </div>

            <div class="viewport">
                <canvas id="gameCanvas" width="640" height="480"></canvas>
            </div>

            <div class="bottombar">
                <div class="shortcuts">
                    <span class="shortcut-item"><kbd>←</kbd><kbd>→</kbd> Mover</span>
                    <span class="shortcut-item"><kbd>Espacio</kbd> Disparar</span>
                    <span class="shortcut-item"><kbd>P</kbd> Pausa</span>
                </div>

                <button type="button" class="btn btn-solid" id="btnPlayGame">
                    Nueva partida
                </button>
            </div>
        </main>
    </div>

    <!-- MODAL: AUTENTICACIÓN -->
    <div class="overlay" id="authModal">
        <div class="dialog">
            <div class="dialog-head">
                <span class="dialog-title" id="authModalTitle">Acceso</span>
                <button type="button" class="btn-close" id="btnCloseAuth" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="segmented">
                <button type="button" class="seg-btn active" id="tabLogin">Iniciar sesión</button>
                <button type="button" class="seg-btn" id="tabRegister">Crear cuenta</button>
            </div>

            <div class="alert alert-error" id="authErrorMsg"></div>

            <form class="form-stack" id="authForm">
                <div class="field">
                    <label for="authUsernameInput">Usuario</label>
                    <input type="text" class="input" id="authUsernameInput" maxlength="25" required autocomplete="username">
                </div>

                <div class="field">
                    <label for="authPasswordInput">Contraseña</label>
                    <div class="control">
                        <input type="password" class="input" id="authPasswordInput" minlength="3" required autocomplete="current-password">
                        <button type="button" class="eye-btn" id="btnTogglePwd" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Medidor de contraseña y checklist de requisitos (Activo en Crear Cuenta) -->
                <div class="pwd-policy-box" id="authPwdPolicyBox" style="display: none;">
                    <div class="pwd-meter-header">
                        <span class="pwd-meter-title">Fortaleza de contraseña</span>
                        <button type="button" class="btn-suggest-pwd" id="btnSuggestAuthPwd" title="Generar una contraseña robusta aleatoria">
                            <svg viewBox="0 0 24 24"><path d="M21 2l-2 2m-6 6l7 7a2 2 0 0 1-2.8 2.8l-7-7m-4-4L2 19l3 3 7-7"/></svg>
                            <span>Sugerir segura</span>
                        </button>
                    </div>
                    <div class="pwd-meter-track">
                        <div class="pwd-meter-bar" id="authMeterBar1"></div>
                        <div class="pwd-meter-bar" id="authMeterBar2"></div>
                        <div class="pwd-meter-bar" id="authMeterBar3"></div>
                        <div class="pwd-meter-bar" id="authMeterBar4"></div>
                    </div>
                    <div class="pwd-meter-info">
                        <span class="pwd-level-tag" id="authLevelTag">Requerida</span>
                        <span class="pwd-hint-text" id="authHintText">Mínimo 8 caracteres combinados</span>
                    </div>
                    <div class="pwd-rules-list">
                        <div class="pwd-rule" id="authRuleLen"><span class="rule-icon">○</span> Mínimo 8 caracteres</div>
                        <div class="pwd-rule" id="authRuleCase"><span class="rule-icon">○</span> Mayúscula y minúscula</div>
                        <div class="pwd-rule" id="authRuleNum"><span class="rule-icon">○</span> Al menos un número</div>
                        <div class="pwd-rule" id="authRuleSym"><span class="rule-icon">○</span> Símbolo (!@#$%...)</div>
                    </div>
                </div>

                <button type="submit" class="btn btn-solid" id="btnAuthSubmit" style="width: 100%; height: 38px; margin-top: 2px;">
                    Continuar
                </button>
            </form>
        </div>
    </div>

    <!-- MODAL: RANKING / FIN DE PARTIDA -->
    <div class="overlay" id="scoreModal">
        <div class="dialog wide">
            <div class="dialog-head">
                <span class="dialog-title" id="modalHeading">Ranking</span>
                <button type="button" class="btn-close" id="btnCloseModal" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="gameover-summary" id="scoreBannerBox" style="display: none;">
                <span>Puntuación</span>
                <strong id="finalScoreVal">0</strong>
            </div>

            <div class="alert alert-ok" id="autoSavedNotice"></div>

            <div class="table-wrap">
                <table>
                    <thead>
                        <tr>
                            <th class="col-rank">#</th>
                            <th>Jugador</th>
                            <th style="text-align: right;">Puntos</th>
                            <th style="text-align: right;">Fecha</th>
                        </tr>
                    </thead>
                    <tbody id="scoresBody">
                        <tr><td colspan="4" style="text-align:center; padding: 20px;">Cargando...</td></tr>
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 8px;">
                <button type="button" class="btn btn-solid" id="btnPlayAgain">
                    Jugar de nuevo
                </button>
            </div>
        </div>
    </div>

    <!-- MODAL: AJUSTES -->
    <div class="overlay" id="settingsModal">
        <div class="dialog">
            <div class="dialog-head">
                <span class="dialog-title">Ajustes</span>
                <button type="button" class="btn-close" id="btnCloseSettings" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="account-bar" id="settingsAccountBar" style="display: none;">
                <div class="account-user">
                    <span class="user-chip-dot"></span>
                    <span id="settingsUsernameLabel"></span>
                </div>
                <button type="button" class="btn-text-danger" id="btnLogout">Cerrar sesión</button>
            </div>

            <span class="section-caption">Cambiar contraseña</span>

            <div class="alert alert-error" id="settingsErrorMsg"></div>
            <div class="alert alert-ok" id="settingsSuccessMsg"></div>

            <form class="form-stack" id="changePwdForm">
                <div class="field" id="settingsUsernameGroup" style="display: none;">
                    <label for="settingsUsernameInput">Usuario</label>
                    <input type="text" class="input" id="settingsUsernameInput" maxlength="25" autocomplete="username">
                </div>

                <div class="field">
                    <label for="currentPasswordInput">Contraseña actual</label>
                    <div class="control">
                        <input type="password" class="input" id="currentPasswordInput" minlength="3" required autocomplete="current-password">
                        <button type="button" class="eye-btn" id="btnToggleCurrentPwd" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="field">
                    <label for="newPasswordInput">Nueva contraseña</label>
                    <div class="control">
                        <input type="password" class="input" id="newPasswordInput" minlength="3" required autocomplete="new-password">
                        <button type="button" class="eye-btn" id="btnToggleNewPwd" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <!-- Medidor de contraseña y checklist de requisitos para Cambio de Contraseña -->
                <div class="pwd-policy-box" id="settingsPwdPolicyBox">
                    <div class="pwd-meter-header">
                        <span class="pwd-meter-title">Fortaleza de contraseña</span>
                        <button type="button" class="btn-suggest-pwd" id="btnSuggestSettingsPwd" title="Generar una contraseña robusta aleatoria">
                            <svg viewBox="0 0 24 24"><path d="M21 2l-2 2m-6 6l7 7a2 2 0 0 1-2.8 2.8l-7-7m-4-4L2 19l3 3 7-7"/></svg>
                            <span>Sugerir segura</span>
                        </button>
                    </div>
                    <div class="pwd-meter-track">
                        <div class="pwd-meter-bar" id="settingsMeterBar1"></div>
                        <div class="pwd-meter-bar" id="settingsMeterBar2"></div>
                        <div class="pwd-meter-bar" id="settingsMeterBar3"></div>
                        <div class="pwd-meter-bar" id="settingsMeterBar4"></div>
                    </div>
                    <div class="pwd-meter-info">
                        <span class="pwd-level-tag" id="settingsLevelTag">Requerida</span>
                        <span class="pwd-hint-text" id="settingsHintText">Mínimo 8 caracteres combinados</span>
                    </div>
                    <div class="pwd-rules-list">
                        <div class="pwd-rule" id="settingsRuleLen"><span class="rule-icon">○</span> Mínimo 8 caracteres</div>
                        <div class="pwd-rule" id="settingsRuleCase"><span class="rule-icon">○</span> Mayúscula y minúscula</div>
                        <div class="pwd-rule" id="settingsRuleNum"><span class="rule-icon">○</span> Al menos un número</div>
                        <div class="pwd-rule" id="settingsRuleSym"><span class="rule-icon">○</span> Símbolo (!@#$%...)</div>
                    </div>
                </div>

                <div class="field">
                    <label for="confirmPasswordInput">Confirmar contraseña</label>
                    <div class="control">
                        <input type="password" class="input" id="confirmPasswordInput" minlength="3" required autocomplete="new-password">
                        <button type="button" class="eye-btn" id="btnToggleConfirmPwd" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-solid" id="btnChangePwdSubmit" style="width: 100%; height: 38px; margin-top: 2px;">
                    Guardar contraseña
                </button>
            </form>
        </div>
    </div>

    <!-- Criptografía Cliente-Servidor (AES-256-CBC + HMAC-SHA256) -->
    <script src="assets/js/crypto-js.min.js"></script>
    <script>
        window.APP_CONFIG = {
            securityKey: <?php echo json_encode(defined('APP_SECURITY_KEY') ? APP_SECURITY_KEY : 'SpaceInvaders2026_SecureKey_Auth_AES'); ?>,
            encryptionEnabled: true
        };
    </script>

    <script>
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
                    osc.frequency.setValueAtTime(760, this.ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(160, this.ctx.currentTime + 0.07);
                    gain.gain.setValueAtTime(0.08, this.ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.07);
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    osc.start();
                    osc.stop(this.ctx.currentTime + 0.07);
                } catch(e){}
            }
            pop() {
                if (this.muted || !this.ctx) return;
                try {
                    const osc = this.ctx.createOscillator();
                    const gain = this.ctx.createGain();
                    osc.type = 'triangle';
                    osc.frequency.setValueAtTime(300, this.ctx.currentTime);
                    osc.frequency.exponentialRampToValueAtTime(55, this.ctx.currentTime + 0.11);
                    gain.gain.setValueAtTime(0.12, this.ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.11);
                    osc.connect(gain);
                    gain.connect(this.ctx.destination);
                    osc.start();
                    osc.stop(this.ctx.currentTime + 0.11);
                } catch(e){}
            }
            boom() {
                if (this.muted || !this.ctx) return;
                try {
                    const dur = 0.24;
                    const buf = this.ctx.createBuffer(1, this.ctx.sampleRate * dur, this.ctx.sampleRate);
                    const d = buf.getChannelData(0);
                    for (let i = 0; i < d.length; i++) d[i] = Math.random() * 2 - 1;
                    const noise = this.ctx.createBufferSource();
                    noise.buffer = buf;
                    const gain = this.ctx.createGain();
                    gain.gain.setValueAtTime(0.15, this.ctx.currentTime);
                    gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + dur);
                    noise.connect(gain);
                    gain.connect(this.ctx.destination);
                    noise.start();
                } catch(e){}
            }
        }

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
                this.state = 'IDLE';
                this.levelClearTimer = 0;

                this.player = {
                    w: 34,
                    h: 16,
                    x: this.width / 2 - 17,
                    y: this.height - 36,
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
                    const tag = e.target.tagName;
                    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
                    if (document.querySelector('.overlay.active')) return;

                    if (['ArrowLeft', 'KeyA'].includes(e.code)) { this.keys.left = true; e.preventDefault(); }
                    if (['ArrowRight', 'KeyD'].includes(e.code)) { this.keys.right = true; e.preventDefault(); }
                    if (['Space', 'ArrowUp', 'KeyW'].includes(e.code)) {
                        this.keys.shoot = true;
                        e.preventDefault();
                        if (this.state === 'IDLE') {
                            if (this.callbacks.onRequestStart) this.callbacks.onRequestStart();
                            else this.start();
                        }
                    }
                    if (e.code === 'KeyP') this.togglePause();
                });

                window.addEventListener('keyup', (e) => {
                    const tag = e.target.tagName;
                    if (tag === 'INPUT' || tag === 'TEXTAREA' || tag === 'SELECT') return;
                    if (document.querySelector('.overlay.active')) return;

                    if (['ArrowLeft', 'KeyA'].includes(e.code)) this.keys.left = false;
                    if (['ArrowRight', 'KeyD'].includes(e.code)) this.keys.right = false;
                    if (['Space', 'ArrowUp', 'KeyW'].includes(e.code)) this.keys.shoot = false;
                });

                this.canvas.addEventListener('click', () => {
                    this.sound.init();
                    if (this.state === 'IDLE') {
                        if (this.callbacks.onRequestStart) this.callbacks.onRequestStart();
                        else this.start();
                    }
                });
            }

            initAliens() {
                this.aliens = [];
                const rows = 4;
                const cols = 8;
                for (let r = 0; r < rows; r++) {
                    for (let c = 0; c < cols; c++) {
                        let type = 'bottom', pts = 10, color = '#a1a1aa';
                        if (r === 0) { type = 'top'; pts = 30; color = '#f43f5e'; }
                        else if (r === 1 || r === 2) { type = 'mid'; pts = 20; color = '#f59e0b'; }

                        this.aliens.push({
                            x: 64 + c * 48,
                            y: 54 + r * 34,
                            w: 26,
                            h: 18,
                            type,
                            pts,
                            color,
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
                    const by = this.height - 92;
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
                this.player.x = this.width / 2 - 17;
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

                if (this.keys.left && this.player.x > 12) this.player.x -= this.player.speed;
                if (this.keys.right && this.player.x < this.width - this.player.w - 12) this.player.x += this.player.speed;

                if (this.shootCooldown > 0) this.shootCooldown--;
                if (this.keys.shoot && this.shootCooldown === 0 && this.bullets.length < 2) {
                    this.bullets.push({
                        x: this.player.x + this.player.w / 2 - 1.5,
                        y: this.player.y - 6,
                        w: 3,
                        h: 10,
                        vy: -8.5
                    });
                    this.shootCooldown = 14;
                    this.sound.laser();
                }

                let allAliensKilled = false;

                for (let i = this.bullets.length - 1; i >= 0; i--) {
                    const b = this.bullets[i];
                    if (!b) continue;

                    b.y += b.vy;
                    if (b.y < -10) {
                        this.bullets.splice(i, 1);
                        continue;
                    }

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

                            if (remaining === 0) allAliensKilled = true;
                            break;
                        }
                    }

                    if (allAliensKilled) break;
                }

                if (allAliensKilled) {
                    this.bullets = [];
                    this.bombs = [];
                    this.state = 'LEVEL_CLEAR';
                    this.levelClearTimer = 45;
                    return;
                }

                let wallHit = false;
                const aliveList = this.aliens.filter(a => a.alive);

                for (const a of aliveList) {
                    a.x += this.alienDir * this.alienSpeed;
                    if (a.x + a.w > this.width - 14 || a.x < 14) wallHit = true;
                    if (a.y + a.h >= this.player.y) {
                        this.gameOver();
                        return;
                    }
                }

                if (wallHit) {
                    this.alienDir *= -1;
                    for (const a of aliveList) a.y += 14;
                }

                this.dropTimer++;
                if (this.dropTimer > Math.max(28, 78 - (this.level * 8)) && aliveList.length > 0) {
                    this.dropTimer = 0;
                    const shooter = aliveList[Math.floor(Math.random() * aliveList.length)];
                    this.bombs.push({
                        x: shooter.x + shooter.w / 2 - 1.5,
                        y: shooter.y + shooter.h,
                        w: 3,
                        h: 9,
                        vy: 3.5 + (this.level * 0.3)
                    });
                }

                for (let i = this.bombs.length - 1; i >= 0; i--) {
                    const bm = this.bombs[i];
                    if (!bm) continue;

                    bm.y += bm.vy;
                    if (bm.y > this.height) {
                        this.bombs.splice(i, 1);
                        continue;
                    }

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
                this.ctx.fillStyle = '#070709';
                this.ctx.fillRect(0, 0, this.width, this.height);

                this.ctx.fillStyle = 'rgba(255, 255, 255, 0.08)';
                for (let s = 0; s < 28; s++) {
                    const sx = (s * 97) % this.width;
                    const sy = (s * 53) % this.height;
                    this.ctx.fillRect(sx, sy, 1.5, 1.5);
                }

                for (const bk of this.bunkers) {
                    const alpha = bk.hp === 3 ? 0.85 : (bk.hp === 2 ? 0.5 : 0.25);
                    this.ctx.fillStyle = `rgba(16, 185, 129, ${alpha})`;
                    this.ctx.fillRect(bk.x, bk.y, bk.w - 1, bk.h - 1);
                }

                const step = Math.floor(Date.now() / 420) % 2;
                for (const a of this.aliens) {
                    if (!a.alive) continue;
                    this.ctx.fillStyle = a.color;

                    if (a.type === 'top') {
                        this.ctx.fillRect(a.x + 5, a.y + 2, a.w - 10, a.h - 7);
                        if (step === 0) {
                            this.ctx.fillRect(a.x + 2, a.y + a.h - 5, 4, 5);
                            this.ctx.fillRect(a.x + a.w - 6, a.y + a.h - 5, 4, 5);
                        } else {
                            this.ctx.fillRect(a.x + 7, a.y + a.h - 5, 4, 5);
                            this.ctx.fillRect(a.x + a.w - 11, a.y + a.h - 5, 4, 5);
                        }
                    } else if (a.type === 'mid') {
                        this.ctx.fillRect(a.x + 4, a.y + 3, a.w - 8, a.h - 6);
                        this.ctx.fillRect(a.x, a.y + (step === 0 ? 5 : 8), 3, 5);
                        this.ctx.fillRect(a.x + a.w - 3, a.y + (step === 0 ? 5 : 8), 3, 5);
                    } else {
                        this.ctx.fillRect(a.x + 4, a.y + 3, a.w - 8, a.h - 6);
                        this.ctx.fillRect(a.x + (step === 0 ? 3 : 6), a.y + a.h - 4, 4, 4);
                        this.ctx.fillRect(a.x + a.w - (step === 0 ? 7 : 10), a.y + a.h - 4, 4, 4);
                    }

                    this.ctx.fillStyle = '#070709';
                    this.ctx.fillRect(a.x + 7, a.y + 6, 3, 3);
                    this.ctx.fillRect(a.x + a.w - 10, a.y + 6, 3, 3);
                }

                this.ctx.fillStyle = '#f4f4f5';
                for (const b of this.bullets) this.ctx.fillRect(b.x, b.y, b.w, b.h);

                this.ctx.fillStyle = '#f43f5e';
                for (const bm of this.bombs) this.ctx.fillRect(bm.x, bm.y, bm.w, bm.h);

                if (!this.player.isHit || Math.floor(Date.now() / 80) % 2 === 0) {
                    this.ctx.fillStyle = '#10b981';
                    const x = this.player.x, y = this.player.y, w = this.player.w, h = this.player.h;
                    this.ctx.fillRect(x, y + 6, w, h - 6);
                    this.ctx.fillRect(x + 6, y + 3, w - 12, 3);
                    this.ctx.fillRect(x + w / 2 - 2, y, 4, 3);
                }

                if (this.state === 'LEVEL_CLEAR') {
                    this.ctx.fillStyle = 'rgba(7, 7, 9, 0.75)';
                    this.ctx.fillRect(0, 0, this.width, this.height);
                    this.ctx.fillStyle = '#10b981';
                    this.ctx.font = '600 18px Inter, sans-serif';
                    this.ctx.textAlign = 'center';
                    this.ctx.fillText(`NIVEL ${this.level} COMPLETADO`, this.width / 2, this.height / 2 - 6);
                    this.ctx.fillStyle = '#8e8e99';
                    this.ctx.font = '500 13px "JetBrains Mono", monospace';
                    this.ctx.fillText('+500 PTS', this.width / 2, this.height / 2 + 18);
                    this.ctx.textAlign = 'left';
                }

                if (this.state === 'PAUSED') {
                    this.ctx.fillStyle = 'rgba(7, 7, 9, 0.78)';
                    this.ctx.fillRect(0, 0, this.width, this.height);
                    this.ctx.fillStyle = '#f4f4f5';
                    this.ctx.font = '600 18px Inter, sans-serif';
                    this.ctx.textAlign = 'center';
                    this.ctx.fillText('PAUSA', this.width / 2, this.height / 2);
                    this.ctx.textAlign = 'left';
                }

                if (this.state === 'IDLE') {
                    this.ctx.fillStyle = 'rgba(7, 7, 9, 0.74)';
                    this.ctx.fillRect(0, 0, this.width, this.height);
                    this.ctx.fillStyle = '#f4f4f5';
                    this.ctx.font = '600 16px Inter, sans-serif';
                    this.ctx.textAlign = 'center';
                    this.ctx.fillText('Presiona Espacio o haz clic para iniciar', this.width / 2, this.height / 2);
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

        /**
         * Cliente de Seguridad Criptográfica
         * Cifrado/Descifrado AES-256-CBC con Vector de Inicialización (IV) aleatorio
         * y Firma de Integridad HMAC-SHA256 para paquetes de entrada y salida.
         */
        const SecurityClient = {
            key: null,
            init() {
                if (this.key) return;
                if (window.CryptoJS && window.APP_CONFIG && window.APP_CONFIG.securityKey) {
                    this.key = CryptoJS.SHA256(window.APP_CONFIG.securityKey);
                }
            },
            encrypt(dataObj) {
                this.init();
                if (!this.key || !window.CryptoJS) return dataObj;
                try {
                    const plain = JSON.stringify(dataObj);
                    const iv = CryptoJS.lib.WordArray.random(16);
                    const enc = CryptoJS.AES.encrypt(plain, this.key, {
                        iv: iv,
                        mode: CryptoJS.mode.CBC,
                        padding: CryptoJS.pad.Pkcs7
                    });
                    const payload = enc.toString();
                    const ivB64 = CryptoJS.enc.Base64.stringify(iv);
                    const signature = CryptoJS.HmacSHA256(ivB64 + ":" + payload, this.key).toString(CryptoJS.enc.Hex);
                    return { encrypted: true, payload, iv: ivB64, signature };
                } catch(e) {
                    console.warn("[SecurityClient] Fallback de cifrado:", e);
                    return dataObj;
                }
            },
            decrypt(pkg) {
                this.init();
                if (!pkg || typeof pkg !== 'object' || !pkg.encrypted || !this.key || !window.CryptoJS) {
                    return pkg;
                }
                try {
                    if (pkg.signature) {
                        const expectedSig = CryptoJS.HmacSHA256(pkg.iv + ":" + pkg.payload, this.key).toString(CryptoJS.enc.Hex);
                        if (pkg.signature !== expectedSig) {
                            throw new Error("Firma criptográfica inválida del paquete recibido");
                        }
                    }
                    const cipherParams = CryptoJS.lib.CipherParams.create({
                        ciphertext: CryptoJS.enc.Base64.parse(pkg.payload)
                    });
                    const dec = CryptoJS.AES.decrypt(cipherParams, this.key, {
                        iv: CryptoJS.enc.Base64.parse(pkg.iv),
                        mode: CryptoJS.mode.CBC,
                        padding: CryptoJS.pad.Pkcs7
                    });
                    const jsonStr = dec.toString(CryptoJS.enc.Utf8);
                    return JSON.parse(jsonStr);
                } catch(e) {
                    console.error("[SecurityClient] Error al descifrar respuesta:", e);
                    return pkg;
                }
            },
            async secureFetch(url, options = {}) {
                this.init();
                options.headers = options.headers || {};
                options.headers['X-Encrypt-Response'] = '1';

                if (options.body && typeof options.body === 'string') {
                    try {
                        const parsed = JSON.parse(options.body);
                        const encPayload = this.encrypt(parsed);
                        options.body = JSON.stringify(encPayload);
                        options.headers['Content-Type'] = 'application/json';
                    } catch(e) {}
                }

                const res = await fetch(url, options);
                const rawJson = await res.json();
                return this.decrypt(rawJson);
            }
        };

        /**
         * Metodología de Contraseña Robusta
         * Evalúa longitud mínima (8+), mayúsculas, minúsculas, dígitos, caracteres especiales,
         * y ausencia del nombre de usuario o patrones vulnerables.
         */
        const PasswordPolicy = {
            rules: {
                len:  (p) => p.length >= 8,
                case: (p) => /[A-Z]/.test(p) && /[a-z]/.test(p),
                num:  (p) => /[0-9]/.test(p),
                sym:  (p) => /[^A-Za-z0-9]/.test(p)
            },
            evaluate(pwd, username = '') {
                const p = pwd || '';
                const results = {
                    len: this.rules.len(p),
                    case: this.rules.case(p),
                    num: this.rules.num(p),
                    sym: this.rules.sym(p)
                };

                let passedCount = (results.len ? 1 : 0) +
                                  (results.case ? 1 : 0) +
                                  (results.num ? 1 : 0) +
                                  (results.sym ? 1 : 0);

                const lower = p.toLowerCase();
                const blacklist = [
                    '12345678', '123456789', '1234567890', 'password', 'password1', 'password123',
                    'qwertyui', 'qwerty123', 'admin123', 'admin2026', 'contrasena', 'contraseña',
                    'spaceinvaders', 'invaders123', 'abcdefgh', 'iloveyou', 'welcome1', '11111111'
                ];
                let isCommon = blacklist.includes(lower) || /^(.)\1{4,}$/.test(p);
                let hasUser = username && username.length >= 3 && lower.includes(username.toLowerCase());

                let level = 0;
                let label = 'Requerida';
                let colorClass = '';
                let hint = 'Mínimo 8 caracteres combinados';

                if (p.length === 0) {
                    level = 0;
                    label = 'Requerida';
                    hint = 'Mínimo 8 caracteres combinados';
                } else if (isCommon) {
                    level = 1;
                    label = 'Muy débil';
                    colorClass = 'red';
                    hint = 'Contraseña común o predecible';
                } else if (hasUser) {
                    level = 1;
                    label = 'Insegura';
                    colorClass = 'red';
                    hint = 'No uses tu nombre de usuario';
                } else if (passedCount <= 1) {
                    level = 1;
                    label = 'Muy débil';
                    colorClass = 'red';
                    hint = 'Agrega mayúsculas, números y símbolos';
                } else if (passedCount === 2) {
                    level = 2;
                    label = 'Débil';
                    colorClass = 'orange';
                    hint = 'Falta combinar más tipos de caracteres';
                } else if (passedCount === 3) {
                    level = 3;
                    label = 'Aceptable';
                    colorClass = 'amber';
                    hint = '¡Casi lista! Cumple con el requisito pendiente';
                } else {
                    level = 4;
                    label = 'Robusta';
                    colorClass = 'green';
                    hint = '¡Excelente! Contraseña segura y aprobada';
                }

                const isValid = results.len && results.case && results.num && results.sym && !isCommon && !hasUser;

                return { results, passedCount, level, label, colorClass, hint, isValid };
            },
            generate(length = 14) {
                const uppers = "ABCDEFGHJKLMNPQRSTUVWXYZ";
                const lowers = "abcdefghijkmnopqrstuvwxyz";
                const numbers = "23456789";
                const symbols = "!@#$%&*?_-";
                const all = uppers + lowers + numbers + symbols;

                let pwd = [
                    uppers[Math.floor(Math.random() * uppers.length)],
                    lowers[Math.floor(Math.random() * lowers.length)],
                    numbers[Math.floor(Math.random() * numbers.length)],
                    symbols[Math.floor(Math.random() * symbols.length)]
                ];
                for (let i = 4; i < length; i++) {
                    pwd.push(all[Math.floor(Math.random() * all.length)]);
                }
                return pwd.sort(() => Math.random() - 0.5).join('');
            },
            bindUI(prefix, inputEl, userEl) {
                const bar1 = document.getElementById(`${prefix}MeterBar1`);
                const bar2 = document.getElementById(`${prefix}MeterBar2`);
                const bar3 = document.getElementById(`${prefix}MeterBar3`);
                const bar4 = document.getElementById(`${prefix}MeterBar4`);
                const levelTag = document.getElementById(`${prefix}LevelTag`);
                const hintText = document.getElementById(`${prefix}HintText`);
                const ruleLen = document.getElementById(`${prefix}RuleLen`);
                const ruleCase = document.getElementById(`${prefix}RuleCase`);
                const ruleNum = document.getElementById(`${prefix}RuleNum`);
                const ruleSym = document.getElementById(`${prefix}RuleSym`);

                const bars = [bar1, bar2, bar3, bar4];

                function update() {
                    const val = inputEl ? inputEl.value : '';
                    const username = userEl ? userEl.value.trim() : '';
                    const evalRes = PasswordPolicy.evaluate(val, username);

                    // Actualizar barras
                    bars.forEach((b, idx) => {
                        if (!b) return;
                        b.className = 'pwd-meter-bar';
                        if (evalRes.level > idx) {
                            if (evalRes.level === 1) b.classList.add('active-red');
                            else if (evalRes.level === 2) b.classList.add('active-orange');
                            else if (evalRes.level === 3) b.classList.add('active-amber');
                            else if (evalRes.level === 4) b.classList.add('active-green');
                        }
                    });

                    // Actualizar etiqueta y consejo
                    if (levelTag) {
                        levelTag.textContent = evalRes.label;
                        levelTag.className = `pwd-level-tag ${evalRes.colorClass}`;
                    }
                    if (hintText) hintText.textContent = evalRes.hint;

                    // Actualizar checklist
                    function setRule(el, passed) {
                        if (!el) return;
                        if (passed) {
                            el.classList.add('pass');
                            const icon = el.querySelector('.rule-icon');
                            if (icon) icon.textContent = '✓';
                        } else {
                            el.classList.remove('pass');
                            const icon = el.querySelector('.rule-icon');
                            if (icon) icon.textContent = '○';
                        }
                    }

                    setRule(ruleLen, evalRes.results.len);
                    setRule(ruleCase, evalRes.results.case);
                    setRule(ruleNum, evalRes.results.num);
                    setRule(ruleSym, evalRes.results.sym);

                    return evalRes;
                }

                if (inputEl) inputEl.addEventListener('input', update);
                if (userEl) userEl.addEventListener('input', update);

                return update;
            }
        };

        document.addEventListener('DOMContentLoaded', () => {
            SecurityClient.init();

            const hudScore       = document.getElementById('hudScore');
            const hudHiScore     = document.getElementById('hudHiScore');
            const hudLives       = document.getElementById('hudLives');
            const hudLevel       = document.getElementById('hudLevel');

            const btnPlayGame    = document.getElementById('btnPlayGame');
            const btnShowScores  = document.getElementById('btnShowScores');
            const btnToggleSound = document.getElementById('btnToggleSound');
            const soundIconOn    = document.getElementById('soundIconOn');
            const soundIconOff   = document.getElementById('soundIconOff');

            const userBadgeBox    = document.getElementById('userBadgeBox');
            const displayUsername = document.getElementById('displayUsername');
            const btnLogout       = document.getElementById('btnLogout');
            const btnLoginOpen    = document.getElementById('btnLoginOpen');

            const authModal          = document.getElementById('authModal');
            const authModalTitle     = document.getElementById('authModalTitle');
            const tabLogin           = document.getElementById('tabLogin');
            const tabRegister        = document.getElementById('tabRegister');
            const authForm           = document.getElementById('authForm');
            const authUsernameInput  = document.getElementById('authUsernameInput');
            const authPasswordInput  = document.getElementById('authPasswordInput');
            const btnAuthSubmit      = document.getElementById('btnAuthSubmit');
            const authErrorMsg       = document.getElementById('authErrorMsg');
            const btnTogglePwd       = document.getElementById('btnTogglePwd');
            const btnCloseAuth       = document.getElementById('btnCloseAuth');
            const authPwdPolicyBox   = document.getElementById('authPwdPolicyBox');
            const btnSuggestAuthPwd  = document.getElementById('btnSuggestAuthPwd');

            const scoreModal      = document.getElementById('scoreModal');
            const modalHeading    = document.getElementById('modalHeading');
            const scoreBannerBox  = document.getElementById('scoreBannerBox');
            const finalScoreVal   = document.getElementById('finalScoreVal');
            const autoSavedNotice = document.getElementById('autoSavedNotice');
            const scoresBody      = document.getElementById('scoresBody');
            const btnPlayAgain    = document.getElementById('btnPlayAgain');
            const btnCloseModal   = document.getElementById('btnCloseModal');

            const btnSettings            = document.getElementById('btnSettings');
            const settingsModal          = document.getElementById('settingsModal');
            const btnCloseSettings       = document.getElementById('btnCloseSettings');
            const settingsAccountBar     = document.getElementById('settingsAccountBar');
            const settingsUsernameLabel  = document.getElementById('settingsUsernameLabel');
            const settingsUsernameGroup  = document.getElementById('settingsUsernameGroup');
            const settingsUsernameInput  = document.getElementById('settingsUsernameInput');
            const changePwdForm          = document.getElementById('changePwdForm');
            const currentPasswordInput   = document.getElementById('currentPasswordInput');
            const newPasswordInput       = document.getElementById('newPasswordInput');
            const confirmPasswordInput   = document.getElementById('confirmPasswordInput');
            const btnToggleCurrentPwd    = document.getElementById('btnToggleCurrentPwd');
            const btnToggleNewPwd        = document.getElementById('btnToggleNewPwd');
            const btnToggleConfirmPwd    = document.getElementById('btnToggleConfirmPwd');
            const btnChangePwdSubmit     = document.getElementById('btnChangePwdSubmit');
            const settingsErrorMsg       = document.getElementById('settingsErrorMsg');
            const settingsSuccessMsg     = document.getElementById('settingsSuccessMsg');
            const btnSuggestSettingsPwd  = document.getElementById('btnSuggestSettingsPwd');

            let authMode = 'login';
            let authToken = localStorage.getItem('si_auth_token') || '';
            let authUsername = localStorage.getItem('si_auth_user') || '';

            let record = parseInt(localStorage.getItem('si_best_score') || '0', 10);
            hudHiScore.textContent = record.toLocaleString();

            // Vincular observadores interactivos de contraseña robusta
            const updateAuthPwdMeter = PasswordPolicy.bindUI('auth', authPasswordInput, authUsernameInput);
            const updateSettingsPwdMeter = PasswordPolicy.bindUI('settings', newPasswordInput, settingsUsernameInput);

            if (btnSuggestAuthPwd) {
                btnSuggestAuthPwd.addEventListener('click', () => {
                    const generated = PasswordPolicy.generate(14);
                    authPasswordInput.value = generated;
                    authPasswordInput.type = 'text';
                    updateAuthPwdMeter();
                    authPasswordInput.focus();
                });
            }

            if (btnSuggestSettingsPwd) {
                btnSuggestSettingsPwd.addEventListener('click', () => {
                    const generated = PasswordPolicy.generate(14);
                    newPasswordInput.value = generated;
                    confirmPasswordInput.value = generated;
                    newPasswordInput.type = 'text';
                    confirmPasswordInput.type = 'text';
                    updateSettingsPwdMeter();
                    newPasswordInput.focus();
                });
            }

            function renderLives(lives) {
                let html = '';
                for (let i = 0; i < 3; i++) {
                    const lost = i >= lives ? 'lost' : '';
                    html += `<svg class="life-ship ${lost}" viewBox="0 0 16 12"><path d="M7 0h2v3h4v3h3v6H0V6h3V3h4V0z"/></svg>`;
                }
                hudLives.innerHTML = html;
            }
            renderLives(3);

            function syncAuthUI() {
                if (authToken && authUsername) {
                    displayUsername.textContent = authUsername;
                    userBadgeBox.style.display = 'flex';
                    btnLoginOpen.style.display = 'none';
                } else {
                    userBadgeBox.style.display = 'none';
                    btnLoginOpen.style.display = 'inline-flex';
                }
            }
            syncAuthUI();

            if (authToken) {
                SecurityClient.secureFetch('api.php?action=verify_token', {
                    method: 'POST',
                    headers: {
                        'Authorization': `Bearer ${authToken}`,
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({ token: authToken })
                })
                .then(res => {
                    if (res && res.success && res.data && res.data.username) {
                        authUsername = res.data.username;
                        localStorage.setItem('si_auth_user', authUsername);
                        syncAuthUI();
                    } else {
                        clearAuth();
                    }
                })
                .catch(() => {});
            }

            function clearAuth() {
                authToken = '';
                authUsername = '';
                localStorage.removeItem('si_auth_token');
                localStorage.removeItem('si_auth_user');
                syncAuthUI();
            }

            function bindPasswordEye(btn, input) {
                if (!btn || !input) return;
                btn.addEventListener('click', () => {
                    input.type = input.type === 'password' ? 'text' : 'password';
                });
            }

            bindPasswordEye(btnTogglePwd, authPasswordInput);
            bindPasswordEye(btnToggleCurrentPwd, currentPasswordInput);
            bindPasswordEye(btnToggleNewPwd, newPasswordInput);
            bindPasswordEye(btnToggleConfirmPwd, confirmPasswordInput);

            function setAuthMode(mode) {
                authMode = mode;
                authErrorMsg.style.display = 'none';
                if (mode === 'login') {
                    tabLogin.classList.add('active');
                    tabRegister.classList.remove('active');
                    authModalTitle.textContent = 'Iniciar sesión';
                    btnAuthSubmit.textContent = 'Entrar';
                    if (authPwdPolicyBox) authPwdPolicyBox.style.display = 'none';
                } else {
                    tabRegister.classList.add('active');
                    tabLogin.classList.remove('active');
                    authModalTitle.textContent = 'Crear cuenta';
                    btnAuthSubmit.textContent = 'Crear cuenta';
                    if (authPwdPolicyBox) {
                        authPwdPolicyBox.style.display = 'flex';
                        updateAuthPwdMeter();
                    }
                }
            }

            tabLogin.addEventListener('click', () => setAuthMode('login'));
            tabRegister.addEventListener('click', () => setAuthMode('register'));

            function openAuthModal(defaultMode = 'login') {
                setAuthMode(defaultMode);
                authUsernameInput.value = authUsername || '';
                authPasswordInput.value = '';
                authModal.classList.add('active');
                setTimeout(() => authUsernameInput.focus(), 80);
            }

            function closeAuthModal() {
                authModal.classList.remove('active');
            }

            authForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                authErrorMsg.style.display = 'none';

                const username = authUsernameInput.value.trim();
                const password = authPasswordInput.value;
                if (!username || !password) return;

                // Validación estricta de contraseña robusta al crear cuenta
                if (authMode === 'register') {
                    const evalRes = PasswordPolicy.evaluate(password, username);
                    if (!evalRes.isValid) {
                        authErrorMsg.innerHTML = '<strong>Contraseña débil rechazada:</strong> Cumple con todos los requisitos de seguridad indicados en el panel (mínimo 8 caracteres, mayúscula, minúscula, número y símbolo).';
                        authErrorMsg.style.display = 'block';
                        authPasswordInput.focus();
                        return;
                    }
                }

                btnAuthSubmit.disabled = true;
                const endpoint = authMode === 'register' ? 'api.php?action=register' : 'api.php?action=login';

                try {
                    // Petición y respuesta cifradas con AES-256-CBC y HMAC
                    const json = await SecurityClient.secureFetch(endpoint, {
                        method: 'POST',
                        body: JSON.stringify({ username, password })
                    });

                    if (json.success && json.data && json.data.token) {
                        authToken = json.data.token;
                        authUsername = json.data.username;
                        localStorage.setItem('si_auth_token', authToken);
                        localStorage.setItem('si_auth_user', authUsername);

                        syncAuthUI();
                        closeAuthModal();

                        if (game.state === 'IDLE') game.start();
                    } else {
                        const errorText = json.message || 'Error de autenticación';
                        authErrorMsg.innerHTML = escapeHtml(errorText);

                        if (json.data && json.data.not_found) {
                            authErrorMsg.innerHTML += `<br><button type="button" id="btnAutoSwitchRegister">Crear cuenta con este usuario</button>`;
                            document.getElementById('btnAutoSwitchRegister').addEventListener('click', () => {
                                setAuthMode('register');
                                authPasswordInput.focus();
                            });
                        } else if (json.data && json.data.user_exists) {
                            authErrorMsg.innerHTML += `<br><button type="button" id="btnAutoSwitchLogin">Iniciar sesión</button>`;
                            document.getElementById('btnAutoSwitchLogin').addEventListener('click', () => {
                                setAuthMode('login');
                                authPasswordInput.focus();
                            });
                        }

                        authErrorMsg.style.display = 'block';
                    }
                } catch (err) {
                    authErrorMsg.textContent = 'Error de conexión segura con el servidor.';
                    authErrorMsg.style.display = 'block';
                } finally {
                    btnAuthSubmit.disabled = false;
                }
            });

            const game = new SpaceInvaders('gameCanvas', {
                onRequestStart: () => {
                    if (!authToken) openAuthModal('login');
                    else game.start();
                },
                onScoreUpdate: (score, lives, level) => {
                    hudScore.textContent = score.toLocaleString();
                    hudLevel.textContent = level;
                    renderLives(lives);

                    if (score > record) {
                        record = score;
                        localStorage.setItem('si_best_score', record);
                        hudHiScore.textContent = record.toLocaleString();
                    }
                },
                onGameOver: async (score) => {
                    await handleGameOver(score);
                }
            });

            async function handleGameOver(score) {
                modalHeading.textContent = 'Partida finalizada';
                scoreBannerBox.style.display = 'flex';
                finalScoreVal.textContent = score.toLocaleString();
                autoSavedNotice.style.display = 'none';

                scoreModal.classList.add('active');

                if (authToken && score > 0) {
                    try {
                        const json = await SecurityClient.secureFetch('api.php?action=submit_score', {
                            method: 'POST',
                            headers: {
                                'Authorization': `Bearer ${authToken}`
                            },
                            body: JSON.stringify({ score })
                        });
                        if (json && json.success) {
                            autoSavedNotice.textContent = `Puntuación registrada de forma segura · Puesto #${json.data.rank}`;
                            autoSavedNotice.style.display = 'block';
                        }
                    } catch (err) {}
                }

                await fetchLeaderboard();
            }

            async function openLeaderboardModal() {
                modalHeading.textContent = 'Ranking';
                scoreBannerBox.style.display = 'none';
                autoSavedNotice.style.display = 'none';
                scoreModal.classList.add('active');
                await fetchLeaderboard();
            }

            function closeScoreModal() {
                scoreModal.classList.remove('active');
            }

            async function fetchLeaderboard() {
                scoresBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 20px;">Cargando...</td></tr>`;

                try {
                    const json = await SecurityClient.secureFetch('api.php?action=leaderboard');

                    if (json && json.success && json.data && json.data.scores) {
                        renderTable(json.data.scores);
                    } else {
                        scoresBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 20px;">Sin registros</td></tr>`;
                    }
                } catch (e) {
                    scoresBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 20px;">Sin conexión</td></tr>`;
                }
            }

            function renderTable(scores) {
                if (!scores.length) {
                    scoresBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding: 20px;">Sin registros</td></tr>`;
                    return;
                }

                let html = '';
                scores.forEach((s) => {
                    const isCurrent = authUsername && s.username.toLowerCase() === authUsername.toLowerCase();
                    const classes = [];
                    if (s.rank <= 3) classes.push(`rank-${s.rank}`);
                    if (isCurrent) classes.push('is-me');

                    html += `
                        <tr class="${classes.join(' ')}">
                            <td class="col-rank">${s.rank}</td>
                            <td class="col-player">${escapeHtml(s.username)}</td>
                            <td class="col-score">${Number(s.score).toLocaleString()}</td>
                            <td class="col-date">${escapeHtml(s.date || '')}</td>
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

            function openSettingsModal() {
                if (game.state === 'PLAYING') game.togglePause();

                settingsErrorMsg.style.display = 'none';
                settingsSuccessMsg.style.display = 'none';
                currentPasswordInput.value = '';
                newPasswordInput.value = '';
                confirmPasswordInput.value = '';
                updateSettingsPwdMeter();

                if (authToken && authUsername) {
                    settingsAccountBar.style.display = 'flex';
                    settingsUsernameLabel.textContent = authUsername;
                    settingsUsernameGroup.style.display = 'none';
                    settingsUsernameInput.required = false;
                } else {
                    settingsAccountBar.style.display = 'none';
                    settingsUsernameGroup.style.display = 'flex';
                    settingsUsernameInput.value = authUsername || '';
                    settingsUsernameInput.required = true;
                }

                settingsModal.classList.add('active');
                setTimeout(() => {
                    if (authToken && authUsername) currentPasswordInput.focus();
                    else settingsUsernameInput.focus();
                }, 80);
            }

            function closeSettingsModal() {
                settingsModal.classList.remove('active');
            }

            changePwdForm.addEventListener('submit', async (e) => {
                e.preventDefault();
                settingsErrorMsg.style.display = 'none';
                settingsSuccessMsg.style.display = 'none';

                const username        = (authToken && authUsername) ? authUsername : settingsUsernameInput.value.trim();
                const currentPassword = currentPasswordInput.value;
                const newPassword     = newPasswordInput.value;
                const confirmPassword = confirmPasswordInput.value;

                if (!currentPassword || !newPassword || !confirmPassword) return;

                if (newPassword !== confirmPassword) {
                    settingsErrorMsg.textContent = 'La nueva contraseña y su confirmación no coinciden.';
                    settingsErrorMsg.style.display = 'block';
                    return;
                }

                if (currentPassword === newPassword) {
                    settingsErrorMsg.textContent = 'La nueva contraseña debe ser distinta a la actual.';
                    settingsErrorMsg.style.display = 'block';
                    return;
                }

                // Validación de contraseña robusta
                const evalRes = PasswordPolicy.evaluate(newPassword, username);
                if (!evalRes.isValid) {
                    settingsErrorMsg.textContent = 'La nueva contraseña no cumple los requisitos de seguridad robusta. Revisa el checklist de requisitos.';
                    settingsErrorMsg.style.display = 'block';
                    newPasswordInput.focus();
                    return;
                }

                btnChangePwdSubmit.disabled = true;

                try {
                    const headers = {};
                    if (authToken) headers['Authorization'] = `Bearer ${authToken}`;

                    const json = await SecurityClient.secureFetch('api.php?action=change_password', {
                        method: 'POST',
                        headers,
                        body: JSON.stringify({
                            username,
                            current_password: currentPassword,
                            new_password: newPassword,
                            confirm_password: confirmPassword
                        })
                    });

                    if (json.success) {
                        if (json.data && json.data.token) {
                            authToken = json.data.token;
                            authUsername = json.data.username || username;
                            localStorage.setItem('si_auth_token', authToken);
                            localStorage.setItem('si_auth_user', authUsername);
                            syncAuthUI();
                            settingsAccountBar.style.display = 'flex';
                            settingsUsernameLabel.textContent = authUsername;
                            settingsUsernameGroup.style.display = 'none';
                        }

                        currentPasswordInput.value = '';
                        newPasswordInput.value = '';
                        confirmPasswordInput.value = '';
                        updateSettingsPwdMeter();

                        settingsSuccessMsg.textContent = 'Contraseña actualizada correctamente con cifrado.';
                        settingsSuccessMsg.style.display = 'block';
                    } else {
                        settingsErrorMsg.textContent = json.message || 'No se pudo actualizar.';
                        settingsErrorMsg.style.display = 'block';
                    }
                } catch (err) {
                    settingsErrorMsg.textContent = 'Error de conexión segura.';
                    settingsErrorMsg.style.display = 'block';
                } finally {
                    btnChangePwdSubmit.disabled = false;
                }
            });

            // Cerrar modales con Escape o clic fuera del diálogo
            window.addEventListener('keydown', (e) => {
                if (e.key === 'Escape') {
                    closeAuthModal();
                    closeScoreModal();
                    closeSettingsModal();
                }
            });

            [authModal, scoreModal, settingsModal].forEach((modal) => {
                modal.addEventListener('mousedown', (e) => {
                    if (e.target === modal) modal.classList.remove('active');
                });
            });

            btnSettings.addEventListener('click', openSettingsModal);
            btnCloseSettings.addEventListener('click', closeSettingsModal);

            btnPlayGame.addEventListener('click', () => {
                if (!authToken) openAuthModal('login');
                else {
                    closeScoreModal();
                    game.start();
                }
            });

            btnPlayAgain.addEventListener('click', () => {
                closeScoreModal();
                game.start();
            });

            btnShowScores.addEventListener('click', openLeaderboardModal);
            btnCloseModal.addEventListener('click', closeScoreModal);
            btnLoginOpen.addEventListener('click', () => openAuthModal('login'));

            btnLogout.addEventListener('click', () => {
                clearAuth();
                closeSettingsModal();
            });

            btnCloseAuth.addEventListener('click', closeAuthModal);

            btnToggleSound.addEventListener('click', () => {
                const active = game.sound.toggle();
                soundIconOn.style.display = active ? 'block' : 'none';
                soundIconOff.style.display = active ? 'none' : 'block';
            });
        });
    </script>
</body>
</html>
