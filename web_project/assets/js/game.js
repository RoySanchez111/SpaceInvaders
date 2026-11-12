/**
 * Motor del juego Space Invaders (Canvas 2D)
 */
class SpaceInvadersGame {
    constructor(canvasId, callbacks) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.sound = new window.SoundFX();
        this.callbacks = callbacks || {};

        this.width = this.canvas.width;
        this.height = this.canvas.height;

        this.score = 0;
        this.lives = 3;
        this.level = 1;
        this.state = 'IDLE'; // IDLE, PLAYING, PAUSED, LEVEL_CLEAR, GAMEOVER
        this.levelClearTimer = 0;
        this.rafId = null;

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
        this.particles = [];
        this.stars = [];

        this.alienDir = 1;
        this.alienSpeed = 1.0;
        this.dropTimer = 0;
        this.shootCooldown = 0;

        this.keys = { left: false, right: false, shoot: false };

        this.initStars();
        this.initControls();
        this.reset();
        this.render();
    }

    initStars() {
        this.stars = [];
        for (let i = 0; i < 45; i++) {
            this.stars.push({
                x: Math.random() * this.width,
                y: Math.random() * this.height,
                size: Math.random() > 0.75 ? 2 : 1,
                alpha: 0.15 + Math.random() * 0.45,
                speed: 0.15 + Math.random() * 0.35
            });
        }
    }

    isInputBlocked() {
        const gameScreen = document.getElementById('gameScreen');
        if (!gameScreen || gameScreen.style.display === 'none') return true;
        if (document.querySelector('.overlay.active')) return true;
        const activeEl = document.activeElement;
        if (activeEl && ['INPUT', 'TEXTAREA', 'SELECT'].includes(activeEl.tagName)) return true;
        return false;
    }

    initControls() {
        window.addEventListener('keydown', (e) => {
            if (this.isInputBlocked()) return;

            if (['ArrowLeft', 'KeyA'].includes(e.code)) {
                this.keys.left = true;
                e.preventDefault();
            }
            if (['ArrowRight', 'KeyD'].includes(e.code)) {
                this.keys.right = true;
                e.preventDefault();
            }
            if (['Space', 'ArrowUp', 'KeyW'].includes(e.code)) {
                this.keys.shoot = true;
                e.preventDefault();
                if (this.state === 'IDLE') {
                    this.start();
                }
            }
            if (e.code === 'KeyP') {
                this.togglePause();
            }
        });

        window.addEventListener('keyup', (e) => {
            if (['ArrowLeft', 'KeyA'].includes(e.code)) this.keys.left = false;
            if (['ArrowRight', 'KeyD'].includes(e.code)) this.keys.right = false;
            if (['Space', 'ArrowUp', 'KeyW'].includes(e.code)) this.keys.shoot = false;
        });

        this.canvas.addEventListener('click', () => {
            if (this.isInputBlocked()) return;
            this.sound.init();
            if (this.state === 'IDLE') {
                this.start();
            }
        });
    }

    spawnParticles(x, y, color, count = 10) {
        for (let i = 0; i < count; i++) {
            const angle = Math.random() * Math.PI * 2;
            const speed = 1 + Math.random() * 3;
            this.particles.push({
                x,
                y,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed,
                life: 18 + Math.floor(Math.random() * 10),
                maxLife: 28,
                color
            });
        }
    }

    initAliens() {
        this.aliens = [];
        const rows = 4;
        const cols = 8;
        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                let type = 'scout', pts = 10, color = '#22d3ee';
                if (r === 0) { type = 'commander'; pts = 30; color = '#f43f5e'; }
                else if (r === 1 || r === 2) { type = 'fighter'; pts = 20; color = '#f59e0b'; }

                this.aliens.push({
                    x: 68 + c * 48,
                    y: 52 + r * 36,
                    w: 28,
                    h: 20,
                    type,
                    pts,
                    color,
                    alive: true
                });
            }
        }
        this.alienDir = 1;
        this.alienSpeed = 0.85 + (this.level * 0.2);
    }

    initBunkers() {
        this.bunkers = [];
        const count = 3;
        const bWidth = 56;
        const gap = (this.width - (count * bWidth)) / (count + 1);

        for (let i = 0; i < count; i++) {
            const bx = gap + i * (bWidth + gap);
            const by = this.height - 96;
            for (let r = 0; r < 4; r++) {
                for (let c = 0; c < 7; c++) {
                    if (r === 3 && (c >= 2 && c <= 4)) continue;
                    if (r === 0 && (c === 0 || c === 6)) continue;
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
        this.particles = [];
        this.player.x = this.width / 2 - 18;
        this.player.isHit = false;
        this.initBunkers();
        this.initAliens();
        if (this.callbacks.onScoreUpdate) {
            this.callbacks.onScoreUpdate(this.score, this.lives, this.level);
        }
    }

    start() {
        if (this.rafId) cancelAnimationFrame(this.rafId);
        this.sound.init();
        this.reset();
        this.state = 'PLAYING';
        this.loop();
    }

    stop() {
        if (this.rafId) cancelAnimationFrame(this.rafId);
        this.rafId = null;
        this.state = 'IDLE';
        this.reset();
        this.render();
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
        for (const st of this.stars) {
            st.y += st.speed;
            if (st.y > this.height) {
                st.y = 0;
                st.x = Math.random() * this.width;
            }
        }

        for (let i = this.particles.length - 1; i >= 0; i--) {
            const p = this.particles[i];
            p.x += p.vx;
            p.y += p.vy;
            p.life--;
            if (p.life <= 0) this.particles.splice(i, 1);
        }

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
                x: this.player.x + this.player.w / 2 - 2,
                y: this.player.y - 6,
                w: 3,
                h: 11,
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
                    this.sound.hit();
                    this.spawnParticles(a.x + a.w / 2, a.y + a.h / 2, a.color, 12);

                    if (this.callbacks.onScoreUpdate) {
                        this.callbacks.onScoreUpdate(this.score, this.lives, this.level);
                    }

                    const remaining = this.aliens.filter(x => x.alive).length;
                    this.alienSpeed = Math.min(4.5, 0.85 + (this.level * 0.2) + ((32 - remaining) * 0.08));

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
            this.levelClearTimer = 50;
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
                h: 10,
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
                this.spawnParticles(this.player.x + this.player.w / 2, this.player.y + 8, '#10b981', 16);
                this.player.isHit = true;
                this.player.hitTimer = 28;
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
        this.ctx.fillStyle = '#06070b';
        this.ctx.fillRect(0, 0, this.width, this.height);

        // Estrellas
        for (const st of this.stars) {
            this.ctx.fillStyle = `rgba(226, 232, 240, ${st.alpha})`;
            this.ctx.fillRect(st.x, st.y, st.size, st.size);
        }

        // Partículas
        for (const p of this.particles) {
            this.ctx.globalAlpha = Math.max(0, p.life / p.maxLife);
            this.ctx.fillStyle = p.color;
            this.ctx.fillRect(p.x, p.y, 3, 3);
        }
        this.ctx.globalAlpha = 1;

        // Escudos
        for (const bk of this.bunkers) {
            const alpha = bk.hp === 3 ? 0.88 : (bk.hp === 2 ? 0.52 : 0.26);
            this.ctx.fillStyle = `rgba(16, 185, 129, ${alpha})`;
            this.ctx.fillRect(bk.x, bk.y, bk.w - 1, bk.h - 1);
        }

        // Naves enemigas
        const step = Math.floor(Date.now() / 400) % 2;
        for (const a of this.aliens) {
            if (!a.alive) continue;
            this.ctx.fillStyle = a.color;

            if (a.type === 'commander') {
                this.ctx.fillRect(a.x + 6, a.y + 2, a.w - 12, a.h - 7);
                this.ctx.fillRect(a.x + 3, a.y + 6, a.w - 6, a.h - 10);
                if (step === 0) {
                    this.ctx.fillRect(a.x + 2, a.y + a.h - 5, 4, 5);
                    this.ctx.fillRect(a.x + a.w - 6, a.y + a.h - 5, 4, 5);
                } else {
                    this.ctx.fillRect(a.x + 7, a.y + a.h - 5, 4, 5);
                    this.ctx.fillRect(a.x + a.w - 11, a.y + a.h - 5, 4, 5);
                }
            } else if (a.type === 'fighter') {
                this.ctx.fillRect(a.x + 4, a.y + 3, a.w - 8, a.h - 6);
                this.ctx.fillRect(a.x, a.y + (step === 0 ? 5 : 8), 4, 6);
                this.ctx.fillRect(a.x + a.w - 4, a.y + (step === 0 ? 5 : 8), 4, 6);
            } else {
                this.ctx.fillRect(a.x + 4, a.y + 3, a.w - 8, a.h - 6);
                this.ctx.fillRect(a.x + (step === 0 ? 3 : 6), a.y + a.h - 4, 5, 4);
                this.ctx.fillRect(a.x + a.w - (step === 0 ? 8 : 11), a.y + a.h - 4, 5, 4);
            }

            this.ctx.fillStyle = '#06070b';
            this.ctx.fillRect(a.x + 7, a.y + 6, 3, 3);
            this.ctx.fillRect(a.x + a.w - 10, a.y + 6, 3, 3);
        }

        // Disparos jugador
        this.ctx.fillStyle = '#22d3ee';
        for (const b of this.bullets) {
            this.ctx.fillRect(b.x, b.y, b.w, b.h);
        }

        // Proyectiles enemigos
        this.ctx.fillStyle = '#f43f5e';
        for (const bm of this.bombs) {
            this.ctx.fillRect(bm.x, bm.y, bm.w, bm.h);
        }

        // Nave del jugador
        if (!this.player.isHit || Math.floor(Date.now() / 80) % 2 === 0) {
            this.ctx.fillStyle = '#10b981';
            const x = this.player.x, y = this.player.y, w = this.player.w, h = this.player.h;
            this.ctx.fillRect(x, y + 7, w, h - 7);
            this.ctx.fillRect(x + 6, y + 4, w - 12, 3);
            this.ctx.fillRect(x + w / 2 - 2, y, 4, 4);
        }

        // Línea base
        this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.06)';
        this.ctx.beginPath();
        this.ctx.moveTo(12, this.height - 12);
        this.ctx.lineTo(this.width - 12, this.height - 12);
        this.ctx.stroke();

        // Overlays de estado
        if (this.state === 'LEVEL_CLEAR') {
            this.ctx.fillStyle = 'rgba(6, 7, 11, 0.78)';
            this.ctx.fillRect(0, 0, this.width, this.height);
            this.ctx.fillStyle = '#10b981';
            this.ctx.font = '700 20px "Space Grotesk", sans-serif';
            this.ctx.textAlign = 'center';
            this.ctx.fillText(`OLEADA ${this.level} SUPERADA`, this.width / 2, this.height / 2 - 8);
            this.ctx.fillStyle = '#f59e0b';
            this.ctx.font = '600 13px "JetBrains Mono", monospace';
            this.ctx.fillText('+500 PUNTOS EXTRA', this.width / 2, this.height / 2 + 18);
            this.ctx.textAlign = 'left';
        }

        if (this.state === 'PAUSED') {
            this.ctx.fillStyle = 'rgba(6, 7, 11, 0.8)';
            this.ctx.fillRect(0, 0, this.width, this.height);
            this.ctx.fillStyle = '#f8fafc';
            this.ctx.font = '700 22px "Space Grotesk", sans-serif';
            this.ctx.textAlign = 'center';
            this.ctx.fillText('JUEGO EN PAUSA', this.width / 2, this.height / 2 - 6);
            this.ctx.fillStyle = '#64748b';
            this.ctx.font = '500 13px Inter, sans-serif';
            this.ctx.fillText('Presiona P para reanudar', this.width / 2, this.height / 2 + 20);
            this.ctx.textAlign = 'left';
        }

        if (this.state === 'IDLE') {
            this.ctx.fillStyle = 'rgba(6, 7, 11, 0.72)';
            this.ctx.fillRect(0, 0, this.width, this.height);
            this.ctx.fillStyle = '#f8fafc';
            this.ctx.font = '700 20px "Space Grotesk", sans-serif';
            this.ctx.textAlign = 'center';
            this.ctx.fillText('LISTO PARA DESPEGAR', this.width / 2, this.height / 2 - 8);
            this.ctx.fillStyle = '#22d3ee';
            this.ctx.font = '500 13px Inter, sans-serif';
            this.ctx.fillText('Haz clic en la pantalla o presiona Espacio', this.width / 2, this.height / 2 + 18);
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
            this.rafId = requestAnimationFrame(() => this.loop());
        }
    }
}

window.SpaceInvadersGame = SpaceInvadersGame;
