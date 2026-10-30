/**
 * =============================================================================
 * PORTAL TÁCTICO // CONTROLADOR MAESTRO DE APLICACIÓN (app.js)
 * Conexión Asíncrona con api.php, Leaderboard en Tiempo Real y HUD
 * =============================================================================
 */

document.addEventListener('DOMContentLoaded', () => {
    // Instanciar Juego de Combate
    const game = new window.CoreDefenderGame('defenseCanvas');

    // Elementos del DOM - Controles del Juego
    const btnStartGame       = document.getElementById('btnStartGame');
    const btnPauseGame       = document.getElementById('btnPauseGame');
    const btnEmpPulse        = document.getElementById('btnEmpPulse');
    const btnAudioToggle     = document.getElementById('btnAudioToggle');
    const audioIcon          = document.getElementById('audioIcon');
    const audioLabel         = document.getElementById('audioLabel');
    const canvasOverlay      = document.getElementById('canvasOverlay');
    const btnOverlayAction   = document.getElementById('btnOverlayAction');

    // Elementos del DOM - Leaderboard & Búsqueda
    const leaderboardRows    = document.getElementById('leaderboardRows');
    const leaderboardSearch  = document.getElementById('leaderboardSearch');
    const btnClearSearch     = document.getElementById('btnClearSearch');
    const btnRefresh         = document.getElementById('btnRefreshLeaderboard');
    const refreshIcon        = document.getElementById('refreshIcon');

    // Elementos del DOM - Formulario de Registro
    const scoreSubmitForm    = document.getElementById('scoreSubmitForm');
    const inputCallsign      = document.getElementById('inputCallsign');
    const selectFaction      = document.getElementById('selectFaction');
    const inputScore         = document.getElementById('inputScore');
    const inputWave          = document.getElementById('inputWave');
    const btnSubmitScore     = document.getElementById('btnSubmitScore');

    // Elementos del DOM - Modal Debriefing (Game Over)
    const debriefModal       = document.getElementById('debriefModal');
    const debriefScore       = document.getElementById('debriefScore');
    const debriefWave        = document.getElementById('debriefWave');
    const debriefKills       = document.getElementById('debriefKills');
    const debriefAccuracy    = document.getElementById('debriefAccuracy');
    const btnModalSaveScore  = document.getElementById('btnModalSaveScore');
    const btnModalRetry      = document.getElementById('btnModalRetry');

    // Telemetría de Servidor en Footer
    const dbPulse            = document.getElementById('dbPulse');
    const dbStatusLabel      = document.getElementById('dbStatusLabel');
    const pingVal            = document.getElementById('pingVal');

    // =========================================================================
    // 1. GESTIÓN DE NOTIFICACIONES TOAST TÁCTICAS
    // =========================================================================
    function showToast(message, type = 'crimson') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `tactical-toast toast-${type}`;
        
        let icon = '⚡';
        if (type === 'success') icon = '✓';
        if (type === 'gold') icon = '★';

        toast.innerHTML = `
            <span class="toast-icon">${icon}</span>
            <span class="toast-msg">${message}</span>
        `;

        container.appendChild(toast);

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            toast.style.transition = 'all 0.3s ease';
            setTimeout(() => toast.remove(), 300);
        }, 4000);
    }

    // =========================================================================
    // 2. CONTROL DEL JUEGO Y BOTONES
    // =========================================================================
    function startGameSession() {
        canvasOverlay.classList.remove('active');
        btnStartGame.textContent = '↻ REINICIAR COMBATE';
        btnPauseGame.disabled = false;
        btnPauseGame.textContent = '⏸ PAUSAR';
        game.start();
    }

    btnOverlayAction.addEventListener('click', startGameSession);
    btnStartGame.addEventListener('click', startGameSession);

    btnPauseGame.addEventListener('click', () => {
        game.pause();
        if (game.isPaused) {
            btnPauseGame.textContent = '▶ REANUDAR';
            showToast('SISTEMA EN PAUSA TÁCTICA', 'gold');
        } else {
            btnPauseGame.textContent = '⏸ PAUSAR';
        }
    });

    btnEmpPulse.addEventListener('click', () => {
        game.triggerEmpPulse();
    });

    btnAudioToggle.addEventListener('click', () => {
        const isMuted = !game.sound.toggle();
        if (isMuted) {
            audioIcon.textContent = '🔇';
            audioLabel.textContent = 'AUDIO OFF';
            showToast('CANAL DE AUDIO SILENCIADO', 'crimson');
        } else {
            audioIcon.textContent = '🔊';
            audioLabel.textContent = 'AUDIO ON';
            showToast('SÍNTESIS DE AUDIO ACTIVADA', 'gold');
        }
    });

    // =========================================================================
    // 3. CAPTURA DEL EVENTO GAME OVER Y DEBRIEFING
    // =========================================================================
    window.addEventListener('coreDefender:gameOver', (e) => {
        const { score, wave, kills, accuracy } = e.detail;

        debriefScore.textContent = score.toLocaleString();
        debriefWave.textContent = `OLEADA ${wave.toString().padStart(2, '0')}`;
        debriefKills.textContent = kills;
        debriefAccuracy.textContent = `${accuracy}%`;

        // Pre-cargar valores en el formulario de la derecha
        inputScore.value = score;
        inputWave.value = wave;

        btnPauseGame.disabled = true;
        debriefModal.classList.add('active');
    });

    btnModalRetry.addEventListener('click', () => {
        debriefModal.classList.remove('active');
        startGameSession();
    });

    btnModalSaveScore.addEventListener('click', () => {
        debriefModal.classList.remove('active');
        // Llevar el foco suavemente al formulario de registro
        scoreSubmitForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
        inputCallsign.focus();
        showToast('CONFIRMA TU CALLSIGN Y ENVÍA TU RÉCORD', 'gold');
    });

    // =========================================================================
    // 4. LEADERBOARD // CONSULTA Y FILTRADO ASÍNCRONO (fetch API)
    // =========================================================================
    async function loadLeaderboard(searchTerm = '') {
        refreshIcon.classList.add('rotating');

        try {
            const url = `api.php?action=leaderboard&search=${encodeURIComponent(searchTerm)}&limit=15`;
            const response = await fetch(url, { headers: { 'Accept': 'application/json' } });
            
            if (!response.ok) {
                throw new Error(`HTTP Error ${response.status}`);
            }

            const result = await response.json();

            if (result.success && result.data && Array.isArray(result.data.scores)) {
                renderLeaderboard(result.data.scores);
                
                // Actualizar estado de BD si el endpoint lo reporta
                if (result.data.db_connected !== undefined) {
                    updateDatabaseIndicator(result.data.db_connected);
                }
            } else {
                leaderboardRows.innerHTML = `
                    <div style="padding: 24px; text-align: center; color: var(--text-muted); font-family: var(--font-mono);">
                        NO SE REGISTRARON EXPEDIENTES
                    </div>
                `;
            }
        } catch (err) {
            console.error('Error cargando leaderboard:', err);
            showToast('Falla al conectar con MariaDB', 'crimson');
        } finally {
            refreshIcon.classList.remove('rotating');
        }
    }

    function renderLeaderboard(scores) {
        if (scores.length === 0) {
            leaderboardRows.innerHTML = `
                <div style="padding: 24px; text-align: center; color: var(--text-muted); font-family: var(--font-mono);">
                    NO SE ENCONTRARON COMANDANTES CON ESTE CRITERIO
                </div>
            `;
            return;
        }

        let html = '';
        scores.forEach((item, index) => {
            const rank = index + 1;
            let rankClass = 'rank-default';
            if (rank === 1) rankClass = 'rank-gold';
            else if (rank === 2) rankClass = 'rank-silver';
            else if (rank === 3) rankClass = 'rank-bronze';

            const faction = item.faction || 'Solar Vanguard';
            const factionSlug = faction.toLowerCase().replace(/\s+/g, '-');
            const waveFormatted = `W-${String(item.wave_reached || 1).padStart(2, '0')}`;
            const scoreFormatted = Number(item.score || 0).toLocaleString();

            html += `
                <div class="leaderboard-row ${rankClass}">
                    <div class="col-rank">
                        <span class="rank-badge ${rankClass}">#${rank}</span>
                    </div>
                    <div class="col-player">
                        <div class="player-callsign" title="${escapeHtml(item.username)}">${escapeHtml(item.username)}</div>
                        <div class="player-faction-tag faction-${factionSlug}">${escapeHtml(faction)}</div>
                    </div>
                    <div class="col-wave">
                        <span class="wave-pill">${waveFormatted}</span>
                    </div>
                    <div class="col-score">
                        <span class="score-number">${scoreFormatted}</span>
                    </div>
                </div>
            `;
        });

        leaderboardRows.innerHTML = html;
    }

    function escapeHtml(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    // Buscador en tiempo real con debounce
    let searchDebounceTimer = null;
    leaderboardSearch.addEventListener('input', (e) => {
        const val = e.target.value.trim();
        btnClearSearch.style.display = val.length > 0 ? 'block' : 'none';

        clearTimeout(searchDebounceTimer);
        searchDebounceTimer = setTimeout(() => {
            loadLeaderboard(val);
        }, 220);
    });

    btnClearSearch.addEventListener('click', () => {
        leaderboardSearch.value = '';
        btnClearSearch.style.display = 'none';
        loadLeaderboard('');
        leaderboardSearch.focus();
    });

    btnRefresh.addEventListener('click', () => {
        loadLeaderboard(leaderboardSearch.value.trim());
        showToast('LEADERBOARD SINCRONIZADO', 'gold');
    });

    // =========================================================================
    // 5. TRANSMISIÓN DE PUNTAJES A MARIADB (fetch POST)
    // =========================================================================
    scoreSubmitForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const username = inputCallsign.value.trim();
        const faction = selectFaction.value;
        const score = parseInt(inputScore.value, 10) || 0;
        const waveReached = parseInt(inputWave.value, 10) || 1;

        if (!username) {
            showToast('DEBES INGRESAR UN CALLSIGN VÁLIDO', 'crimson');
            inputCallsign.focus();
            return;
        }

        btnSubmitScore.disabled = true;
        btnSubmitScore.innerHTML = '<span class="btn-glow"></span> TRANSMITIENDO...';

        try {
            const response = await fetch('api.php?action=submit_score', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    username: username,
                    faction: faction,
                    score: score,
                    wave_reached: waveReached
                })
            });

            const result = await response.json();

            if (response.ok && result.success) {
                const rankAchieved = result.data?.rank ? `RANGO #${result.data.rank}` : 'REGISTRADO';
                showToast(`¡PUNTAJE TRANSMITIDO CON ÉXITO! (${rankAchieved})`, 'success');
                
                // Recargar Leaderboard para reflejar la posición de inmediato
                leaderboardSearch.value = '';
                btnClearSearch.style.display = 'none';
                await loadLeaderboard('');
            } else {
                throw new Error(result.message || 'Error en la transmisión');
            }
        } catch (err) {
            console.error('Error enviando puntuación:', err);
            showToast(`ERROR: ${err.message}`, 'crimson');
        } finally {
            btnSubmitScore.disabled = false;
            btnSubmitScore.innerHTML = '<span class="btn-glow"></span> TRANSMITIR PUNTUACIÓN AL CUARTEL GENERAL';
        }
    });

    // =========================================================================
    // 6. TELEMETRÍA Y PING AL SERVIDOR
    // =========================================================================
    function updateDatabaseIndicator(isConnected) {
        if (!dbPulse || !dbStatusLabel) return;
        if (isConnected) {
            dbPulse.className = 'pulse-indicator online';
            dbStatusLabel.className = 'telemetry-val text-green';
            dbStatusLabel.textContent = 'ONLINE';
        } else {
            dbPulse.className = 'pulse-indicator degraded';
            dbStatusLabel.className = 'telemetry-val text-amber';
            dbStatusLabel.textContent = 'DEGRADADO';
        }
    }

    async function checkServerStatus() {
        const startTime = performance.now();
        try {
            const res = await fetch('api.php?action=status');
            const latency = Math.round(performance.now() - startTime);

            if (pingVal) {
                pingVal.textContent = `${latency} ms`;
            }

            if (res.ok) {
                const data = await res.json();
                if (data.success && data.data) {
                    updateDatabaseIndicator(data.data.db_connected);
                }
            }
        } catch (e) {
            if (pingVal) pingVal.textContent = 'ERR';
            updateDatabaseIndicator(false);
        }
    }

    // Ping inicial y chequeo periódico cada 30s
    checkServerStatus();
    setInterval(checkServerStatus, 30000);
});

