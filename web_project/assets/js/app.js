/**
 * Controlador principal de Space Invaders
 * Conecta el juego con la API de puntuaciones y el modal de fin de juego.
 */

document.addEventListener('DOMContentLoaded', () => {
    // Elementos del HUD
    const hudScore     = document.getElementById('hudScore');
    const hudHighScore = document.getElementById('hudHighScore');
    const hudLives     = document.getElementById('hudLives');
    const hudLevel     = document.getElementById('hudLevel');

    // Botones
    const btnNewGame     = document.getElementById('btnNewGame');
    const btnViewScores  = document.getElementById('btnViewScores');

    // Modal de Puntuaciones / Fin de Juego
    const scoreModal      = document.getElementById('scoreModal');
    const modalTitle      = document.getElementById('modalTitle');
    const finalScoreBox   = document.getElementById('finalScoreBox');
    const finalScoreVal   = document.getElementById('finalScoreVal');
    const scoreForm       = document.getElementById('scoreForm');
    const inputPlayer     = document.getElementById('inputPlayer');
    const btnSaveScore    = document.getElementById('btnSaveScore');
    const btnPlayAgain    = document.getElementById('btnPlayAgain');
    const btnCloseModal   = document.getElementById('btnCloseModal');
    const leaderboardBody = document.getElementById('leaderboardBody');
    const saveNotice      = document.getElementById('saveNotice');

    // Telemetría en footer
    const dbStatusDot = document.getElementById('dbStatusDot');
    const dbStatusTxt = document.getElementById('dbStatusTxt');

    let currentScore = 0;
    let localHighScore = parseInt(localStorage.getItem('space_invaders_hi') || '0', 10);
    hudHighScore.textContent = localHighScore.toLocaleString();

    // Iniciar el juego
    const game = new window.SpaceInvadersGame('gameCanvas', {
        onScoreUpdate: (score, lives, level) => {
            currentScore = score;
            hudScore.textContent = score.toLocaleString();
            hudLevel.textContent = String(level).padStart(2, '0');

            // Representar vidas con naves o corazones
            let hearts = '';
            for (let i = 0; i < lives; i++) hearts += '🚀 ';
            hudLives.textContent = hearts || '0';

            if (score > localHighScore) {
                localHighScore = score;
                localStorage.setItem('space_invaders_hi', localHighScore);
                hudHighScore.textContent = localHighScore.toLocaleString();
            }
        },
        onGameOver: (finalScore, level) => {
            currentScore = finalScore;
            openScoreModal(true);
        }
    });

    // Abrir Modal de Puntuaciones
    async function openScoreModal(isGameOver = false) {
        saveNotice.style.display = 'none';

        if (isGameOver) {
            modalTitle.textContent = '¡JUEGO TERMINADO!';
            finalScoreBox.style.display = 'block';
            finalScoreVal.textContent = currentScore.toLocaleString();
            scoreForm.style.display = 'flex';
            inputPlayer.value = '';
            inputPlayer.focus();
        } else {
            modalTitle.textContent = 'MEJORES PUNTUACIONES';
            finalScoreBox.style.display = 'none';
            scoreForm.style.display = 'none';
        }

        scoreModal.classList.add('active');
        await loadLeaderboard();
    }

    function closeScoreModal() {
        scoreModal.classList.remove('active');
    }

    // Cargar Leaderboard desde MariaDB
    async function loadLeaderboard() {
        leaderboardBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:16px;">Cargando puntuaciones...</td></tr>`;

        try {
            const res = await fetch('api.php?action=leaderboard');
            const json = await res.json();

            if (json.success && json.data && json.data.scores) {
                renderLeaderboard(json.data.scores);
                if (json.data.db_connected !== undefined) {
                    updateDbStatus(json.data.db_connected);
                }
            } else {
                leaderboardBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:16px;">Sin registros aún</td></tr>`;
            }
        } catch (e) {
            leaderboardBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:16px; color:#e63946;">No se pudo conectar a la base de datos</td></tr>`;
        }
    }

    function renderLeaderboard(scores) {
        if (!scores.length) {
            leaderboardBody.innerHTML = `<tr><td colspan="4" style="text-align:center; padding:16px;">Sé el primero en registrar un récord</td></tr>`;
            return;
        }

        let html = '';
        scores.forEach(s => {
            let rankBadge = `#${s.rank}`;
            let rankClass = '';
            if (s.rank === 1) { rankBadge = '🥇 #1'; rankClass = 'rank-gold'; }
            else if (s.rank === 2) { rankBadge = '🥈 #2'; rankClass = 'rank-silver'; }
            else if (s.rank === 3) { rankBadge = '🥉 #3'; rankClass = 'rank-bronze'; }

            html += `
                <tr class="${rankClass}">
                    <td class="col-rank">${rankBadge}</td>
                    <td class="col-user"><strong>${escapeHtml(s.username)}</strong></td>
                    <td class="col-pts">${Number(s.score).toLocaleString()}</td>
                    <td class="col-date">${escapeHtml(s.date || '')}</td>
                </tr>
            `;
        });
        leaderboardBody.innerHTML = html;
    }

    function escapeHtml(str) {
        const div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    // Guardar Puntuación
    scoreForm.addEventListener('submit', async (e) => {
        e.preventDefault();
        const username = inputPlayer.value.trim();
        if (!username) return;

        btnSaveScore.disabled = true;
        btnSaveScore.textContent = 'Guardando...';

        try {
            const res = await fetch('api.php?action=submit_score', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, score: currentScore })
            });

            const json = await res.json();
            if (json.success) {
                saveNotice.textContent = `¡Puntuación guardada! Rango #${json.data.rank}`;
                saveNotice.style.display = 'block';
                scoreForm.style.display = 'none';
                await loadLeaderboard();
            } else {
                alert(json.message || 'Error al guardar');
            }
        } catch (err) {
            alert('Error de conexión al guardar puntuación');
        } finally {
            btnSaveScore.disabled = false;
            btnSaveScore.textContent = 'Guardar';
        }
    });

    // Botones de acción
    btnNewGame.addEventListener('click', () => {
        closeScoreModal();
        game.start();
    });

    btnPlayAgain.addEventListener('click', () => {
        closeScoreModal();
        game.start();
    });

    btnViewScores.addEventListener('click', () => {
        openScoreModal(false);
    });

    btnCloseModal.addEventListener('click', () => {
        closeScoreModal();
    });

    // Telemetría
    function updateDbStatus(ok) {
        if (ok) {
            dbStatusDot.className = 'status-dot online';
            dbStatusTxt.textContent = 'Conectada';
            dbStatusTxt.style.color = '#2ec4b6';
        } else {
            dbStatusDot.className = 'status-dot offline';
            dbStatusTxt.textContent = 'Desconectada';
            dbStatusTxt.style.color = '#e63946';
        }
    }

    // Comprobar estado inicial del servidor
    fetch('api.php?action=status')
        .then(r => r.json())
        .then(res => {
            if (res.success && res.data) {
                updateDbStatus(res.data.db_connected);
            }
        })
        .catch(() => updateDbStatus(false));
});
