/**
 * Controlador principal de vistas, contraseña robusta, biometría en ajustes y clasificación
 */
document.addEventListener('DOMContentLoaded', () => {
    const authScreen = document.getElementById('authScreen');
    const gameScreen = document.getElementById('gameScreen');

    // Elementos de Autenticación
    const tabLogin          = document.getElementById('tabLogin');
    const tabRegister       = document.getElementById('tabRegister');
    const authForm          = document.getElementById('authForm');
    const authUsernameInput = document.getElementById('authUsernameInput');
    const authPasswordInput = document.getElementById('authPasswordInput');
    const btnTogglePwd      = document.getElementById('btnTogglePwd');
    const authRulesBox      = document.getElementById('authRulesBox');
    const authErrorMsg      = document.getElementById('authErrorMsg');
    const btnAuthSubmit     = document.getElementById('btnAuthSubmit');
    const authSwitchPrompt  = document.getElementById('authSwitchPrompt');

    // HUD y Controles del Juego
    const displayUsername = document.getElementById('displayUsername');
    const hudScore        = document.getElementById('hudScore');
    const hudHiScore      = document.getElementById('hudHiScore');
    const hudLevel        = document.getElementById('hudLevel');
    const hudLives        = document.getElementById('hudLives');
    const btnPlayGame     = document.getElementById('btnPlayGame');
    const btnShowScores   = document.getElementById('btnShowScores');
    const btnSettings     = document.getElementById('btnSettings');
    const btnToggleSound  = document.getElementById('btnToggleSound');
    const btnHeaderLogout = document.getElementById('btnHeaderLogout');
    const soundIconOn     = document.getElementById('soundIconOn');
    const soundIconOff    = document.getElementById('soundIconOff');

    // Modal de Clasificación
    const scoreModal      = document.getElementById('scoreModal');
    const modalHeading    = document.getElementById('modalHeading');
    const scoreBannerBox  = document.getElementById('scoreBannerBox');
    const finalScoreVal   = document.getElementById('finalScoreVal');
    const autoSavedNotice = document.getElementById('autoSavedNotice');
    const scoresBody      = document.getElementById('scoresBody');
    const btnPlayAgain    = document.getElementById('btnPlayAgain');
    const btnCloseModal   = document.getElementById('btnCloseModal');

    // Modal de Ajustes (Cambio Sensible de Contraseña con Biometría)
    const settingsModal         = document.getElementById('settingsModal');
    const btnCloseSettings      = document.getElementById('btnCloseSettings');
    const settingsUsernameLabel = document.getElementById('settingsUsernameLabel');
    const btnLogout             = document.getElementById('btnLogout');
    const changePwdForm         = document.getElementById('changePwdForm');
    const currentPasswordInput  = document.getElementById('currentPasswordInput');
    const newPasswordInput      = document.getElementById('newPasswordInput');
    const confirmPasswordInput  = document.getElementById('confirmPasswordInput');
    const btnToggleCurrentPwd   = document.getElementById('btnToggleCurrentPwd');
    const btnToggleNewPwd       = document.getElementById('btnToggleNewPwd');
    const btnToggleConfirmPwd   = document.getElementById('btnToggleConfirmPwd');
    const btnChangePwdSubmit    = document.getElementById('btnChangePwdSubmit');
    const settingsErrorMsg      = document.getElementById('settingsErrorMsg');
    const settingsSuccessMsg    = document.getElementById('settingsSuccessMsg');

    // Elementos de Captura Biométrica Facial
    const bioVideo       = document.getElementById('bioVideo');
    const bioPreview     = document.getElementById('bioPreview');
    const bioPlaceholder = document.getElementById('bioPlaceholder');
    const bioCanvas      = document.getElementById('bioCanvas');
    const bioStatusBadge = document.getElementById('bioStatusBadge');
    const btnBioStartCam = document.getElementById('btnBioStartCam');
    const btnBioSnap     = document.getElementById('btnBioSnap');
    const bioFileInput   = document.getElementById('bioFileInput');

    let authMode = 'login';
    let base64Face = '';
    let activeStream = null;
    let record = parseInt(localStorage.getItem('si_best_score') || '0', 10);
    hudHiScore.textContent = record.toLocaleString();

    const game = new window.SpaceInvadersGame('gameCanvas', {
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

    function renderLives(lives) {
        let html = '';
        for (let i = 0; i < 3; i++) {
            const lost = i >= lives ? 'lost' : '';
            html += `<svg class="life-ship ${lost}" viewBox="0 0 16 12"><path d="M7 0h2v3h4v3h3v6H0V6h3V3h4V0z"/></svg>`;
        }
        hudLives.innerHTML = html;
    }
    renderLives(3);

    function escapeHtml(text) {
        const el = document.createElement('div');
        el.textContent = text;
        return el.innerHTML;
    }

    // =========================================================================
    // 1. EVALUADOR DE CONTRASEÑA ROBUSTA (Crear Cuenta y Cambiar Contraseña)
    // =========================================================================
    function checkPwdCriteria(pwd, username = '') {
        const p = pwd || '';
        const len    = p.length >= 8;
        const mix    = /[A-Z]/.test(p) && /[a-z]/.test(p);
        const num    = /[0-9]/.test(p);
        const sym    = /[^A-Za-z0-9]/.test(p);
        const noUser = !username || username.length < 3 || !p.toLowerCase().includes(username.toLowerCase());
        const count  = (len ? 1 : 0) + (mix ? 1 : 0) + (num ? 1 : 0) + (sym ? 1 : 0);

        let level = 0;
        let label = 'Requerida';
        if (p.length > 0) {
            if (!noUser || count <= 1) { level = 1; label = 'Débil'; }
            else if (count === 2)      { level = 2; label = 'Regular'; }
            else if (count === 3)      { level = 3; label = 'Aceptable'; }
            else                       { level = 4; label = 'Robusta'; }
        }

        return { len, mix, num, sym, noUser, level, label, valid: len && mix && num && sym && noUser };
    }

    function bindPasswordMeter(inputEl, prefix, getUsernameFn) {
        const elLen   = document.getElementById(`${prefix}RuleLen`);
        const elMix   = document.getElementById(`${prefix}RuleMix`);
        const elNum   = document.getElementById(`${prefix}RuleNum`);
        const elSym   = document.getElementById(`${prefix}RuleSym`);
        const elLabel = document.getElementById(`${prefix}StrengthLabel`);
        const bars    = [
            document.getElementById(`${prefix}Bar1`),
            document.getElementById(`${prefix}Bar2`),
            document.getElementById(`${prefix}Bar3`),
            document.getElementById(`${prefix}Bar4`)
        ];

        const update = () => {
            const c = checkPwdCriteria(inputEl.value, getUsernameFn ? getUsernameFn() : '');
            if (elLen) elLen.classList.toggle('done', c.len);
            if (elMix) elMix.classList.toggle('done', c.mix);
            if (elNum) elNum.classList.toggle('done', c.num);
            if (elSym) elSym.classList.toggle('done', c.sym);

            if (elLabel) {
                elLabel.textContent = c.label;
                elLabel.className = c.level > 0 ? `lvl-${c.level}` : '';
            }

            bars.forEach((b, idx) => {
                if (!b) return;
                b.className = 's-bar';
                if (c.level > idx) {
                    b.classList.add(`lvl-${c.level}`);
                }
            });

            return c;
        };

        inputEl.addEventListener('input', update);
        return update;
    }

    const updateAuthRules = bindPasswordMeter(authPasswordInput, 'auth', () => authUsernameInput.value.trim());
    const updateSettingsRules = bindPasswordMeter(newPasswordInput, 'set', () => window.ApiClient.getUsername());
    authUsernameInput.addEventListener('input', updateAuthRules);

    // =========================================================================
    // 2. CÁMARA Y CAPTURA BIOMÉTRICA (Para Cambio Sensible de Contraseña)
    // =========================================================================
    function stopCameraStream() {
        if (activeStream) {
            activeStream.getTracks().forEach(track => track.stop());
            activeStream = null;
        }
        bioVideo.srcObject = null;
        bioVideo.style.display = 'none';
        btnBioSnap.style.display = 'none';
    }

    function resetBiometricScanner() {
        stopCameraStream();
        base64Face = '';
        bioPreview.src = '';
        bioPreview.style.display = 'none';
        bioPlaceholder.style.display = 'flex';
        bioStatusBadge.textContent = 'Pendiente';
        bioStatusBadge.classList.remove('ready');
        bioFileInput.value = '';
    }

    function setCapturedFace(dataUrl) {
        stopCameraStream();
        base64Face = dataUrl;
        bioPreview.src = dataUrl;
        bioPlaceholder.style.display = 'none';
        bioPreview.style.display = 'block';
        bioStatusBadge.textContent = 'Rostro listo';
        bioStatusBadge.classList.add('ready');
    }

    btnBioStartCam.addEventListener('click', async () => {
        settingsErrorMsg.style.display = 'none';
        try {
            stopCameraStream();
            activeStream = await navigator.mediaDevices.getUserMedia({
                video: { facingMode: 'user', width: { ideal: 480 }, height: { ideal: 480 } }
            });
            bioVideo.srcObject = activeStream;
            bioVideo.style.display = 'block';
            bioPreview.style.display = 'none';
            bioPlaceholder.style.display = 'none';
            btnBioSnap.style.display = 'inline-flex';
        } catch (err) {
            settingsErrorMsg.textContent = 'El navegador requiere HTTPS para cámara en vivo. Usa el botón "Subir / Tomar foto".';
            settingsErrorMsg.style.display = 'block';
        }
    });

    btnBioSnap.addEventListener('click', () => {
        const ctx = bioCanvas.getContext('2d');
        bioCanvas.width = bioVideo.videoWidth || 400;
        bioCanvas.height = bioVideo.videoHeight || 400;
        ctx.drawImage(bioVideo, 0, 0, bioCanvas.width, bioCanvas.height);
        setCapturedFace(bioCanvas.toDataURL('image/jpeg', 0.85));
    });

    bioFileInput.addEventListener('change', (e) => {
        const file = e.target.files[0];
        if (!file) return;
        const reader = new FileReader();
        reader.onload = (ev) => {
            setCapturedFace(ev.target.result);
        };
        reader.readAsDataURL(file);
    });

    // =========================================================================
    // 3. FLUJO DE PANTALLAS (ACCESO OBLIGATORIO ANTES DEL JUEGO)
    // =========================================================================
    function enterGameScreen(username, bestScore = null) {
        displayUsername.textContent = username;
        settingsUsernameLabel.textContent = username;

        if (bestScore !== null && bestScore > record) {
            record = bestScore;
            localStorage.setItem('si_best_score', record);
            hudHiScore.textContent = record.toLocaleString();
        }

        authScreen.style.display = 'none';
        gameScreen.style.display = 'flex';
        game.stop();
    }

    function returnToAuthScreen(defaultMode = 'login') {
        game.stop();
        stopCameraStream();
        scoreModal.classList.remove('active');
        settingsModal.classList.remove('active');
        gameScreen.style.display = 'none';
        authScreen.style.display = 'flex';
        setAuthMode(defaultMode);
        authPasswordInput.value = '';
        setTimeout(() => authUsernameInput.focus(), 60);
    }

    async function initSessionGate() {
        const token = window.ApiClient.getToken();
        if (!token) {
            returnToAuthScreen('login');
            return;
        }

        try {
            const res = await window.ApiClient.request('verify_token', { token });
            if (res && res.success && res.data && res.data.username) {
                window.ApiClient.setSession(token, res.data.username);
                enterGameScreen(res.data.username, res.data.best_score || 0);
            } else {
                window.ApiClient.clearSession();
                returnToAuthScreen('login');
            }
        } catch (e) {
            returnToAuthScreen('login');
        }
    }

    function setAuthMode(mode) {
        authMode = mode;
        authErrorMsg.style.display = 'none';

        if (mode === 'login') {
            tabLogin.classList.add('active');
            tabRegister.classList.remove('active');
            authRulesBox.style.display = 'none';
            btnAuthSubmit.textContent = 'Entrar al juego';
            authSwitchPrompt.innerHTML = '¿Eres nuevo jugador? <button type="button" id="btnSwitchMode">Crear una cuenta</button>';
        } else {
            tabRegister.classList.add('active');
            tabLogin.classList.remove('active');
            authRulesBox.style.display = 'flex';
            updateAuthRules();
            btnAuthSubmit.textContent = 'Registrarse y jugar';
            authSwitchPrompt.innerHTML = '¿Ya tienes cuenta? <button type="button" id="btnSwitchMode">Iniciar sesión</button>';
        }

        const btnSwitch = document.getElementById('btnSwitchMode');
        if (btnSwitch) {
            btnSwitch.addEventListener('click', () => {
                setAuthMode(authMode === 'login' ? 'register' : 'login');
                authPasswordInput.focus();
            });
        }
    }

    tabLogin.addEventListener('click', () => setAuthMode('login'));
    tabRegister.addEventListener('click', () => setAuthMode('register'));

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

    // Envío de Login / Registro
    authForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        authErrorMsg.style.display = 'none';

        const username = authUsernameInput.value.trim();
        const password = authPasswordInput.value;
        if (!username || !password) return;

        if (authMode === 'register') {
            const check = checkPwdCriteria(password, username);
            if (!check.valid) {
                authErrorMsg.textContent = 'Cumple con los 4 requisitos de contraseña robusta para crear tu cuenta.';
                authErrorMsg.style.display = 'block';
                authPasswordInput.focus();
                return;
            }
        }

        btnAuthSubmit.disabled = true;
        const action = authMode === 'register' ? 'register' : 'login';

        try {
            const res = await window.ApiClient.request(action, { username, password });

            if (res && res.success && res.data && res.data.token) {
                window.ApiClient.setSession(res.data.token, res.data.username);
                enterGameScreen(res.data.username);
            } else {
                const msg = (res && res.message) ? res.message : 'No se pudo iniciar sesión.';
                authErrorMsg.innerHTML = escapeHtml(msg);

                if (res && res.data && res.data.not_found) {
                    authErrorMsg.innerHTML += ` <button type="button" id="btnQuickRegister">Crear cuenta ahora</button>`;
                    document.getElementById('btnQuickRegister').addEventListener('click', () => {
                        setAuthMode('register');
                        authPasswordInput.focus();
                    });
                } else if (res && res.data && res.data.user_exists) {
                    authErrorMsg.innerHTML += ` <button type="button" id="btnQuickLogin">Iniciar sesión</button>`;
                    document.getElementById('btnQuickLogin').addEventListener('click', () => {
                        setAuthMode('login');
                        authPasswordInput.focus();
                    });
                }

                authErrorMsg.style.display = 'block';
            }
        } catch (err) {
            authErrorMsg.textContent = err && err.message && err.message.includes('estado')
                ? `No se pudo conectar con el servidor (${err.message}).`
                : 'No se pudo conectar con el servidor.';
            authErrorMsg.style.display = 'block';
        } finally {
            btnAuthSubmit.disabled = false;
        }
    });

    async function performLogout() {
        try {
            await window.ApiClient.request('logout', {});
        } catch (e) {}
        window.ApiClient.clearSession();
        returnToAuthScreen('login');
    }

    btnLogout.addEventListener('click', performLogout);
    btnHeaderLogout.addEventListener('click', performLogout);

    // =========================================================================
    // 4. CLASIFICACIÓN Y FIN DE PARTIDA
    // =========================================================================
    async function handleGameOver(score) {
        modalHeading.textContent = 'Fin de la partida';
        scoreBannerBox.style.display = 'flex';
        finalScoreVal.textContent = score.toLocaleString();
        autoSavedNotice.style.display = 'none';

        scoreModal.classList.add('active');

        if (score > 0 && window.ApiClient.getToken()) {
            try {
                const res = await window.ApiClient.request('submit_score', { score });
                if (res && res.success && res.data) {
                    autoSavedNotice.textContent = `Nuevo registro guardado · Posición #${res.data.rank}`;
                    autoSavedNotice.style.display = 'block';
                }
            } catch (e) {}
        }

        await fetchLeaderboard();
    }

    async function openLeaderboardModal() {
        if (game.state === 'PLAYING') game.togglePause();
        modalHeading.textContent = 'Clasificación general';
        scoreBannerBox.style.display = 'none';
        autoSavedNotice.style.display = 'none';
        scoreModal.classList.add('active');
        await fetchLeaderboard();
    }

    function closeScoreModal() {
        scoreModal.classList.remove('active');
    }

    async function fetchLeaderboard() {
        scoresBody.innerHTML = `<tr><td colspan="4" class="table-empty">Cargando clasificación...</td></tr>`;

        try {
            const res = await window.ApiClient.request('leaderboard', null, 'GET');
            if (res && res.success && res.data && res.data.scores) {
                renderTable(res.data.scores);
            } else {
                scoresBody.innerHTML = `<tr><td colspan="4" class="table-empty">Aún no hay récords registrados</td></tr>`;
            }
        } catch (e) {
            scoresBody.innerHTML = `<tr><td colspan="4" class="table-empty">No se pudo cargar la tabla</td></tr>`;
        }
    }

    function renderTable(scores) {
        if (!scores.length) {
            scoresBody.innerHTML = `<tr><td colspan="4" class="table-empty">Sé el primero en registrar una puntuación</td></tr>`;
            return;
        }

        const currentUser = window.ApiClient.getUsername().toLowerCase();
        let html = '';

        scores.forEach((s) => {
            const isMe = currentUser && s.username.toLowerCase() === currentUser;
            const classes = [];
            if (s.rank <= 3) classes.push(`rank-${s.rank}`);
            if (isMe) classes.push('is-me');

            html += `
                <tr class="${classes.join(' ')}">
                    <td class="col-rank">#${s.rank}</td>
                    <td class="col-player">${escapeHtml(s.username)}</td>
                    <td class="col-score">${Number(s.score).toLocaleString()}</td>
                    <td class="col-date">${escapeHtml(s.date || '')}</td>
                </tr>
            `;
        });

        scoresBody.innerHTML = html;
    }

    // =========================================================================
    // 5. MODAL DE AJUSTES (CAMBIO DE CONTRASEÑA CON CONTRASEÑA ROBUSTA + BIOMETRÍA)
    // =========================================================================
    function openSettingsModal() {
        if (game.state === 'PLAYING') game.togglePause();

        settingsErrorMsg.style.display = 'none';
        settingsSuccessMsg.style.display = 'none';
        currentPasswordInput.value = '';
        newPasswordInput.value = '';
        confirmPasswordInput.value = '';
        updateSettingsRules();
        resetBiometricScanner();
        settingsUsernameLabel.textContent = window.ApiClient.getUsername();

        settingsModal.classList.add('active');
        setTimeout(() => currentPasswordInput.focus(), 60);
    }

    function closeSettingsModal() {
        stopCameraStream();
        settingsModal.classList.remove('active');
    }

    changePwdForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        settingsErrorMsg.style.display = 'none';
        settingsSuccessMsg.style.display = 'none';

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
            settingsErrorMsg.textContent = 'La nueva contraseña debe ser diferente a la actual.';
            settingsErrorMsg.style.display = 'block';
            return;
        }

        const check = checkPwdCriteria(newPassword, window.ApiClient.getUsername());
        if (!check.valid) {
            settingsErrorMsg.textContent = 'Cumple con los 4 requisitos de contraseña robusta para la nueva contraseña.';
            settingsErrorMsg.style.display = 'block';
            newPasswordInput.focus();
            return;
        }

        if (!base64Face) {
            settingsErrorMsg.textContent = 'Captura o sube tu fotografía facial para autorizar el cambio de contraseña.';
            settingsErrorMsg.style.display = 'block';
            return;
        }

        btnChangePwdSubmit.disabled = true;
        btnChangePwdSubmit.textContent = 'Verificando biometría...';

        try {
            const res = await window.ApiClient.request('change_password', {
                current_password: currentPassword,
                new_password: newPassword,
                confirm_password: confirmPassword,
                face_image: base64Face
            });

            if (res && res.success) {
                if (res.data && res.data.token) {
                    window.ApiClient.setSession(res.data.token, res.data.username || window.ApiClient.getUsername());
                }
                currentPasswordInput.value = '';
                newPasswordInput.value = '';
                confirmPasswordInput.value = '';
                updateSettingsRules();
                resetBiometricScanner();

                const conf = (res.data && res.data.biometric_confidence) ? ` · Biometría: ${res.data.biometric_confidence}` : '';
                settingsSuccessMsg.textContent = `Contraseña actualizada correctamente${conf}`;
                settingsSuccessMsg.style.display = 'block';
            } else {
                settingsErrorMsg.textContent = (res && res.message) ? res.message : 'No se pudo cambiar la contraseña.';
                settingsErrorMsg.style.display = 'block';
            }
        } catch (err) {
            settingsErrorMsg.textContent = 'Error de conexión con el servidor.';
            settingsErrorMsg.style.display = 'block';
        } finally {
            btnChangePwdSubmit.disabled = false;
            btnChangePwdSubmit.textContent = 'Verificar rostro y actualizar contraseña';
        }
    });

    // Eventos generales
    btnPlayGame.addEventListener('click', () => {
        closeScoreModal();
        game.start();
    });

    btnPlayAgain.addEventListener('click', () => {
        closeScoreModal();
        game.start();
    });

    btnShowScores.addEventListener('click', openLeaderboardModal);
    btnCloseModal.addEventListener('click', closeScoreModal);
    btnSettings.addEventListener('click', openSettingsModal);
    btnCloseSettings.addEventListener('click', closeSettingsModal);

    btnToggleSound.addEventListener('click', () => {
        const active = game.sound.toggle();
        soundIconOn.style.display = active ? 'block' : 'none';
        soundIconOff.style.display = active ? 'none' : 'block';
    });

    window.addEventListener('keydown', (e) => {
        if (e.key === 'Escape') {
            closeScoreModal();
            closeSettingsModal();
        }
    });

    [scoreModal, settingsModal].forEach((modal) => {
        modal.addEventListener('mousedown', (e) => {
            if (e.target === modal) {
                if (modal === settingsModal) closeSettingsModal();
                else modal.classList.remove('active');
            }
        });
    });

    initSessionGate();
});
