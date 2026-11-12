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
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=JetBrains+Mono:wght@500;700&family=Space+Grotesk:wght@600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- =====================================================================
         1. PANTALLA DE ACCESO (OBLIGATORIA ANTES DE VER EL JUEGO)
         ===================================================================== -->
    <section class="auth-screen" id="authScreen">
        <div class="auth-card">
            <div class="auth-brand">
                <div class="auth-logo-mark">
                    <svg viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M6 4h2v2H6V4zm10 0h2v2h-2V4zM4 6h16v4h2v6h-2v-4h-2v4h-4v2H10v-2H6v-4H4v4H2v-6h2V6zm4 2v2h2V8H8zm6 0v2h2V8h-2z"/>
                    </svg>
                </div>
                <h1>SPACE INVADERS</h1>
            </div>

            <div class="segmented">
                <button type="button" class="seg-btn active" id="tabLogin">Iniciar sesión</button>
                <button type="button" class="seg-btn" id="tabRegister">Crear cuenta</button>
            </div>

            <div class="alert alert-error" id="authErrorMsg"></div>

            <form class="form-stack" id="authForm">
                <div class="field">
                    <label for="authUsernameInput">Nombre de jugador</label>
                    <input type="text" class="input" id="authUsernameInput" maxlength="25" required autocomplete="username">
                </div>

                <div class="field">
                    <label for="authPasswordInput">Contraseña</label>
                    <div class="control">
                        <input type="password" class="input" id="authPasswordInput" minlength="3" required autocomplete="current-password">
                        <button type="button" class="eye-btn" id="btnTogglePwd" tabindex="-1" aria-label="Mostrar contraseña">
                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="rules-grid" id="authRulesBox" style="display: none;">
                    <span class="rule-pill" id="authRuleLen">8+ caracteres</span>
                    <span class="rule-pill" id="authRuleMix">Mayús y minús</span>
                    <span class="rule-pill" id="authRuleNum">Al menos 1 número</span>
                    <span class="rule-pill" id="authRuleSym">Al menos 1 símbolo</span>
                </div>

                <button type="submit" class="btn btn-primary" id="btnAuthSubmit" style="width: 100%; height: 40px;">
                    Entrar al juego
                </button>

                <div class="auth-switch" id="authSwitchPrompt"></div>
            </form>
        </div>
    </section>

    <!-- =====================================================================
         2. APLICACIÓN DEL JUEGO (OCULTA HASTA INICIAR SESIÓN)
         ===================================================================== -->
    <div class="game-screen" id="gameScreen" style="display: none;">
        <header class="topbar">
            <div class="brand">
                <svg viewBox="0 0 24 24" aria-hidden="true">
                    <path d="M6 4h2v2H6V4zm10 0h2v2h-2V4zM4 6h16v4h2v6h-2v-4h-2v4h-4v2H10v-2H6v-4H4v4H2v-6h2V6zm4 2v2h2V8H8zm6 0v2h2V8h-2z"/>
                </svg>
                <span>SPACE INVADERS</span>
            </div>

            <div class="nav-actions">
                <div class="user-chip">
                    <span class="user-chip-dot"></span>
                    <span id="displayUsername"></span>
                </div>

                <button type="button" class="btn" id="btnShowScores">
                    <svg viewBox="0 0 24 24"><path d="M8 21h8M12 17v4M7 4h10v5a5 5 0 0 1-10 0V4zM4 6h3v3a3 3 0 0 1-3-3zm16 0h-3v3a3 3 0 0 0 3-3z"/></svg>
                    <span>Clasificación</span>
                </button>

                <button type="button" class="btn" id="btnSettings">
                    <svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 1 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 1 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 1 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9c.26.604.852.997 1.51 1H21a2 2 0 1 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                    <span>Ajustes</span>
                </button>

                <button type="button" class="btn btn-icon" id="btnToggleSound" title="Sonido">
                    <svg id="soundIconOn" viewBox="0 0 24 24"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><path d="M15.54 8.46a5 5 0 0 1 0 7.07M19.07 4.93a10 10 0 0 1 0 14.14"/></svg>
                    <svg id="soundIconOff" viewBox="0 0 24 24" style="display:none;"><polygon points="11 5 6 9 2 9 2 15 6 15 11 19 11 5"/><line x1="23" y1="9" x2="17" y2="15"/><line x1="17" y1="9" x2="23" y2="15"/></svg>
                </button>

                <button type="button" class="btn btn-icon" id="btnHeaderLogout" title="Cerrar sesión">
                    <svg viewBox="0 0 24 24"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
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
                    <span class="hud-label">Mejor marca</span>
                    <span class="hud-val amber" id="hudHiScore">0</span>
                </div>
                <div class="hud-cell">
                    <span class="hud-label">Oleada</span>
                    <span class="hud-val cyan" id="hudLevel">1</span>
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

                <button type="button" class="btn btn-primary" id="btnPlayGame">
                    Nueva partida
                </button>
            </div>
        </main>
    </div>

    <!-- =====================================================================
         MODAL: CLASIFICACIÓN Y FIN DE PARTIDA
         ===================================================================== -->
    <div class="overlay" id="scoreModal">
        <div class="dialog wide">
            <div class="dialog-head">
                <span class="dialog-title" id="modalHeading">Clasificación general</span>
                <button type="button" class="btn-close" id="btnCloseModal" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="gameover-summary" id="scoreBannerBox" style="display: none;">
                <span>Puntuación final</span>
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
                    <tbody id="scoresBody"></tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn btn-primary" id="btnPlayAgain">
                    Jugar de nuevo
                </button>
            </div>
        </div>
    </div>

    <!-- =====================================================================
         MODAL: AJUSTES DE CUENTA
         ===================================================================== -->
    <div class="overlay" id="settingsModal">
        <div class="dialog">
            <div class="dialog-head">
                <span class="dialog-title">Ajustes</span>
                <button type="button" class="btn-close" id="btnCloseSettings" aria-label="Cerrar">
                    <svg viewBox="0 0 24 24"><line x1="18" y1="6" x2="6" y2="18"/><line x1="6" y1="6" x2="18" y2="18"/></svg>
                </button>
            </div>

            <div class="account-bar">
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
                        <input type="password" class="input" id="newPasswordInput" minlength="8" required autocomplete="new-password">
                        <button type="button" class="eye-btn" id="btnToggleNewPwd" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div class="rules-grid">
                    <span class="rule-pill" id="setRuleLen">8+ caracteres</span>
                    <span class="rule-pill" id="setRuleMix">Mayús y minús</span>
                    <span class="rule-pill" id="setRuleNum">Al menos 1 número</span>
                    <span class="rule-pill" id="setRuleSym">Al menos 1 símbolo</span>
                </div>

                <div class="field">
                    <label for="confirmPasswordInput">Confirmar nueva contraseña</label>
                    <div class="control">
                        <input type="password" class="input" id="confirmPasswordInput" minlength="8" required autocomplete="new-password">
                        <button type="button" class="eye-btn" id="btnToggleConfirmPwd" tabindex="-1">
                            <svg viewBox="0 0 24 24"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <button type="submit" class="btn btn-primary" id="btnChangePwdSubmit" style="width: 100%; height: 40px;">
                    Actualizar contraseña
                </button>
            </form>
        </div>
    </div>

    <script>
        window.APP_KEY = <?php echo json_encode(defined('APP_SECURITY_KEY') ? APP_SECURITY_KEY : 'SpaceInvaders2026_SecureKey_Auth_AES'); ?>;
    </script>
    <script src="assets/js/crypto-js.min.js"></script>
    <script src="assets/js/api-client.js"></script>
    <script src="assets/js/sound.js"></script>
    <script src="assets/js/game.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
