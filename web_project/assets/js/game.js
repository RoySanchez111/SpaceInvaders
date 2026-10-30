/**
 * SPACE INVADERS CLÁSICO
 * Motor de juego en HTML5 Canvas con Web Audio API para efectos retro.
 */

class RetroAudio {
    constructor() {
        this.ctx = null;
        this.enabled = true;
    }

    init() {
        if (!this.ctx && (window.AudioContext || window.webkitAudioContext)) {
            const AudioCtx = window.AudioContext || window.webkitAudioContext;
            this.ctx = new AudioCtx();
        }
        if (this.ctx && this.ctx.state === 'suspended') {
            this.ctx.resume();
        }
    }

    shoot() {
        if (!this.enabled || !this.ctx) return;
        try {
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = 'square';
            osc.frequency.setValueAtTime(800, this.ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(150, this.ctx.currentTime + 0.1);
            gain.gain.setValueAtTime(0.12, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.1);
            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start();
            osc.stop(this.ctx.currentTime + 0.1);
        } catch (e) {}
    }

    invaderKill() {
        if (!this.enabled || !this.ctx) return;
        try {
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(300, this.ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(50, this.ctx.currentTime + 0.15);
            gain.gain.setValueAtTime(0.18, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.15);
            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start();
            osc.stop(this.ctx.currentTime + 0.15);
        } catch (e) {}
    }

    playerHit() {
        if (!this.enabled || !this.ctx) return;
        try {
            const dur = 0.3;
            const bufferSize = this.ctx.sampleRate * dur;
            const buffer = this.ctx.createBuffer(1, bufferSize, this.ctx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) data[i] = Math.random() * 2 - 1;
            const noise = this.ctx.createBufferSource();
            noise.buffer = buffer;
            const gain = this.ctx.createGain();
            gain.gain.setValueAtTime(0.25, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + dur);
            noise.connect(gain);
            gain.connect(this.ctx.destination);
            noise.start();
        } catch (e) {}
    }
}

class SpaceInvadersGame {
    constructor(canvasId, callbacks = {}) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.audio = new RetroAudio();
        this.onGameOver = callbacks.onGameOver || (() => {});
        this.onScoreUpdate = callbacks.onScoreUpdate || (() => {});

        this.width = this.canvas.width;
        this.height = this.canvas.height;

        this.score = 0;
        this.lives = 3;
        this.level = 1;
        this.state = 'IDLE'; // IDLE, PLAYING, GAMEOVER, PAUSED

        // Nave del Jugador
        this.player = {
            width: 36,
            height: 20,
            x: this.width / 2 - 18,
            y: this.height - 40,
            speed: 5,
            isHit: false,
            hitTimer: 0
        };

        // Proyectiles y Bombas
        this.bullets = [];
        this.alienBombs = [];
        this.canShoot = true;
        this.shootCooldown = 0;

        // Invasores
        this.aliens = [];
        this.alienDir = 1;
        this.alienSpeed = 1.0;
        this.alienStepDown = 14;
        this.alienDropTimer = 0;

        // Bunkers de protección
        this.bunkers = [];

        // Teclado
        this.keys = { left: false, right: false, shoot: false };

        this.initControls();
        this.resetGame();
        this.drawScreen();
    }

    initControls() {
        window.addEventListener('keydown', (e) => {
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
                if (this.state === 'IDLE') this.start();
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
            this.audio.init();
            if (this.state === 'IDLE') this.start();
        });
    }

    initBunkers() {
        this.bunkers = [];
        const count = 3;
        const bWidth = 56;
        const bHeight = 32;
        const gap = (this.width - (count * bWidth)) / (count + 1);

        for (let i = 0; i < count; i++) {
            const bx = gap + i * (bWidth + gap);
            const by = this.height - 100;
            // Estructurar el bunker en pequeños bloques para que se vaya destruyendo
            for (let r = 0; r < 4; r++) {
                for (let c = 0; c < 7; c++) {
                    // Hueco inferior del arco
                    if (r === 3 && (c === 2 || c === 3 || c === 4)) continue;
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

    initAliens() {
        this.aliens = [];
        const rows = 4;
        const cols = 8;
        const aWidth = 28;
        const aHeight = 20;
        const xOffset = 60;
        const yOffset = 50;

        for (let r = 0; r < rows; r++) {
            for (let c = 0; c < cols; c++) {
                let type = 'bottom';
                let pts = 10;
                let color = '#00f5d4';

                if (r === 0) {
                    type = 'top';
                    pts = 30;
                    color = '#e63946';
                } else if (r === 1 || r === 2) {
                    type = 'mid';
                    pts = 20;
                    color = '#ffb703';
                }

                this.aliens.push({
                    x: xOffset + c * 48,
                    y: yOffset + r * 34,
                    w: aWidth,
                    h: aHeight,
                    type,
                    pts,
                    color,
                    alive: true
                });
            }
        }
        this.alienDir = 1;
        this.alienSpeed = 0.8 + (this.level * 0.25);
    }

    resetGame() {
        this.score = 0;
        this.lives = 3;
        this.level = 1;
        this.bullets = [];
        this.alienBombs = [];
        this.player.x = this.width / 2 - 18;
        this.initBunkers();
        this.initAliens();
        this.onScoreUpdate(this.score, this.lives, this.level);
    }

    start() {
        this.audio.init();
        this.resetGame();
        this.state = 'PLAYING';
        this.loop();
    }

    togglePause() {
        if (this.state === 'PLAYING') {
            this.state = 'PAUSED';
            this.drawScreen();
        } else if (this.state === 'PAUSED') {
            this.state = 'PLAYING';
            this.loop();
        }
    }

    update() {
        if (this.state !== 'PLAYING') return;

        // 1. Movimiento del jugador
        if (this.keys.left && this.player.x > 10) {
            this.player.x -= this.player.speed;
        }
        if (this.keys.right && this.player.x < this.width - this.player.width - 10) {
            this.player.x += this.player.speed;
        }

        // 2. Disparo del jugador
        if (this.shootCooldown > 0) this.shootCooldown--;
        if (this.keys.shoot && this.shootCooldown === 0 && this.bullets.length < 2) {
            this.bullets.push({
                x: this.player.x + this.player.width / 2 - 2,
                y: this.player.y - 4,
                w: 4,
                h: 12,
                vy: -8
            });
            this.shootCooldown = 15;
            this.audio.shoot();
        }

        // 3. Mover proyectiles del jugador
        for (let i = this.bullets.length - 1; i >= 0; i--) {
            const b = this.bullets[i];
            b.y += b.vy;
            if (b.y < -10) {
                this.bullets.splice(i, 1);
                continue;
            }

            // Colisión con bunkers
            let bulletHit = false;
            for (let j = this.bunkers.length - 1; j >= 0; j--) {
                const bk = this.bunkers[j];
                if (b.x < bk.x + bk.w && b.x + b.w > bk.x && b.y < bk.y + bk.h && b.y + b.h > bk.y) {
                    bk.hp--;
                    if (bk.hp <= 0) this.bunkers.splice(j, 1);
                    this.bullets.splice(i, 1);
                    bulletHit = true;
                    break;
                }
            }
            if (bulletHit) continue;

            // Colisión con invasores
            for (let k = 0; k < this.aliens.length; k++) {
                const a = this.aliens[k];
                if (a.alive && b.x < a.x + a.w && b.x + b.w > a.x && b.y < a.y + a.h && b.y + b.h > a.y) {
                    a.alive = false;
                    this.bullets.splice(i, 1);
                    this.score += a.pts;
                    this.audio.invaderKill();
                    this.onScoreUpdate(this.score, this.lives, this.level);

                    // Aumentar velocidad conforme quedan menos
                    const aliveCount = this.aliens.filter(x => x.alive).length;
                    this.alienSpeed = Math.min(4.5, 0.8 + (this.level * 0.25) + ((32 - aliveCount) * 0.08));

                    // ¿Limpió toda la pantalla?
                    if (aliveCount === 0) {
                        this.level++;
                        this.score += 500; // Bonus por limpiar pantalla
                        this.bullets = [];
                        this.alienBombs = [];
                        this.initAliens();
                        this.initBunkers();
                        this.onScoreUpdate(this.score, this.lives, this.level);
                    }
                    break;
                }
            }
        }

        // 4. Mover invasores
        let hitWall = false;
        const aliveAliens = this.aliens.filter(a => a.alive);

        for (const a of aliveAliens) {
            a.x += this.alienDir * this.alienSpeed;
            if (a.x + a.w > this.width - 15 || a.x < 15) {
                hitWall = true;
            }
            // Si llegan a la línea del jugador -> Game Over
            if (a.y + a.h >= this.player.y) {
                this.triggerGameOver();
                return;
            }
        }

        if (hitWall) {
            this.alienDir *= -1;
            for (const a of aliveAliens) {
                a.y += this.alienStepDown;
            }
        }

        // 5. Bombas de los invasores (aleatorias desde la fila más baja viva)
        this.alienDropTimer++;
        if (this.alienDropTimer > Math.max(30, 80 - (this.level * 8)) && aliveAliens.length > 0) {
            this.alienDropTimer = 0;
            const shooter = aliveAliens[Math.floor(Math.random() * aliveAliens.length)];
            this.alienBombs.push({
                x: shooter.x + shooter.w / 2 - 2,
                y: shooter.y + shooter.h,
                w: 4,
                h: 10,
                vy: 3.5 + (this.level * 0.3)
            });
        }

        // 6. Mover bombas de invasores
        for (let i = this.alienBombs.length - 1; i >= 0; i--) {
            const ab = this.alienBombs[i];
            ab.y += ab.vy;

            if (ab.y > this.height) {
                this.alienBombs.splice(i, 1);
                continue;
            }

            // Colisión con bunkers
            let bombHit = false;
            for (let j = this.bunkers.length - 1; j >= 0; j--) {
                const bk = this.bunkers[j];
                if (ab.x < bk.x + bk.w && ab.x + ab.w > bk.x && ab.y < bk.y + bk.h && ab.y + ab.h > bk.y) {
                    bk.hp--;
                    if (bk.hp <= 0) this.bunkers.splice(j, 1);
                    this.alienBombs.splice(i, 1);
                    bombHit = true;
                    break;
                }
            }
            if (bombHit) continue;

            // Colisión con el jugador
            if (ab.x < this.player.x + this.player.width &&
                ab.x + ab.w > this.player.x &&
                ab.y < this.player.y + this.player.height &&
                ab.y + ab.h > this.player.y) {
                
                this.alienBombs.splice(i, 1);
                this.lives--;
                this.audio.playerHit();
                this.player.isHit = true;
                this.player.hitTimer = 25;
                this.onScoreUpdate(this.score, this.lives, this.level);

                if (this.lives <= 0) {
                    this.triggerGameOver();
                    return;
                }
            }
        }

        if (this.player.hitTimer > 0) {
            this.player.hitTimer--;
            if (this.player.hitTimer === 0) this.player.isHit = false;
        }
    }

    drawScreen() {
        this.ctx.fillStyle = '#0a0a0c';
        this.ctx.fillRect(0, 0, this.width, this.height);

        // Estrellas de fondo
        this.ctx.fillStyle = 'rgba(255, 255, 255, 0.2)';
        for (let s = 0; s < 30; s++) {
            const sx = (s * 87) % this.width;
            const sy = (s * 43) % this.height;
            this.ctx.fillRect(sx, sy, 2, 2);
        }

        // Bunkers
        for (const bk of this.bunkers) {
            const alpha = bk.hp === 3 ? 1.0 : (bk.hp === 2 ? 0.7 : 0.4);
            this.ctx.fillStyle = `rgba(0, 245, 212, ${alpha})`;
            this.ctx.fillRect(bk.x, bk.y, bk.w, bk.h);
        }

        // Invasores (pixelados y con diseño clásico)
        for (const a of this.aliens) {
            if (!a.alive) continue;
            this.ctx.fillStyle = a.color;
            this.drawAlienShape(a.x, a.y, a.w, a.h, a.type);
        }

        // Proyectiles del jugador
        this.ctx.fillStyle = '#00f5d4';
        this.ctx.shadowColor = '#00f5d4';
        this.ctx.shadowBlur = 6;
        for (const b of this.bullets) {
            this.ctx.fillRect(b.x, b.y, b.w, b.h);
        }
        this.ctx.shadowBlur = 0;

        // Bombas de los aliens
        this.ctx.fillStyle = '#e63946';
        this.ctx.shadowColor = '#e63946';
        this.ctx.shadowBlur = 6;
        for (const ab of this.alienBombs) {
            this.ctx.fillRect(ab.x, ab.y, ab.w, ab.h);
        }
        this.ctx.shadowBlur = 0;

        // Nave del jugador
        if (!this.player.isHit || Math.floor(Date.now() / 80) % 2 === 0) {
            this.ctx.fillStyle = '#ffb703';
            this.ctx.shadowColor = '#ffb703';
            this.ctx.shadowBlur = 8;
            this.drawPlayerShip(this.player.x, this.player.y, this.player.width, this.player.height);
            this.ctx.shadowBlur = 0;
        }

        // Línea verde inferior de defensa
        this.ctx.strokeStyle = 'rgba(0, 245, 212, 0.3)';
        this.ctx.lineWidth = 1;
        this.ctx.beginPath();
        this.ctx.moveTo(10, this.height - 15);
        this.ctx.lineTo(this.width - 10, this.height - 15);
        this.ctx.stroke();

        // Pantalla de Pausa
        if (this.state === 'PAUSED') {
            this.ctx.fillStyle = 'rgba(0, 0, 0, 0.65)';
            this.ctx.fillRect(0, 0, this.width, this.height);
            this.ctx.fillStyle = '#ffb703';
            this.ctx.font = 'bold 24px monospace';
            this.ctx.textAlign = 'center';
            this.ctx.fillText('JUEGO EN PAUSA', this.width / 2, this.height / 2);
            this.ctx.font = '14px monospace';
            this.ctx.fillStyle = '#fff';
            this.ctx.fillText('Presiona P para continuar', this.width / 2, this.height / 2 + 30);
            this.ctx.textAlign = 'left';
        }

        // Pantalla Inicial
        if (this.state === 'IDLE') {
            this.ctx.fillStyle = 'rgba(0, 0, 0, 0.7)';
            this.ctx.fillRect(0, 0, this.width, this.height);
            this.ctx.fillStyle = '#e63946';
            this.ctx.font = 'bold 28px monospace';
            this.ctx.textAlign = 'center';
            this.ctx.fillText('SPACE INVADERS', this.width / 2, this.height / 2 - 30);

            this.ctx.fillStyle = '#ffb703';
            this.ctx.font = '16px monospace';
            this.ctx.fillText('CLIC O ESPACIO PARA INICIAR', this.width / 2, this.height / 2 + 15);

            this.ctx.fillStyle = '#94a3b8';
            this.ctx.font = '13px monospace';
            this.ctx.fillText('← / → o A / D para moverte  •  Espacio para disparar', this.width / 2, this.height / 2 + 50);
            this.ctx.textAlign = 'left';
        }
    }

    drawPlayerShip(x, y, w, h) {
        // Silueta clásica de cañón defensor
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
    }

    drawAlienShape(x, y, w, h, type) {
        // Dibujo simplificado pixel-art según el tipo
        const step = Math.floor(Date.now() / 400) % 2; // animación clásica de 2 pasos
        this.ctx.beginPath();

        if (type === 'top') { // Pulpo / Squid
            this.ctx.rect(x + 6, y + 2, w - 12, h - 8);
            if (step === 0) {
                this.ctx.rect(x + 2, y + h - 6, 4, 6);
                this.ctx.rect(x + w - 6, y + h - 6, 4, 6);
            } else {
                this.ctx.rect(x + 8, y + h - 6, 4, 6);
                this.ctx.rect(x + w - 12, y + h - 6, 4, 6);
            }
        } else if (type === 'mid') { // Cangrejo / Crab
            this.ctx.rect(x + 4, y + 3, w - 8, h - 7);
            this.ctx.rect(x, y + (step === 0 ? 6 : 9), 4, 6);
            this.ctx.rect(x + w - 4, y + (step === 0 ? 6 : 9), 4, 6);
        } else { // Calamar / Medusa
            this.ctx.rect(x + 5, y + 3, w - 10, h - 6);
            this.ctx.rect(x + (step === 0 ? 3 : 6), y + h - 5, 5, 5);
            this.ctx.rect(x + w - (step === 0 ? 8 : 11), y + h - 5, 5, 5);
        }
        this.ctx.fill();

        // Ojos negros de los invasores
        this.ctx.fillStyle = '#0a0a0c';
        this.ctx.fillRect(x + 8, y + 6, 3, 3);
        this.ctx.fillRect(x + w - 11, y + 6, 3, 3);
    }

    triggerGameOver() {
        this.state = 'GAMEOVER';
        this.audio.playerHit();
        this.drawScreen();

        if (this.onGameOver) {
            this.onGameOver(this.score, this.level);
        }
    }

    loop() {
        if (this.state === 'PLAYING') {
            this.update();
            this.drawScreen();
            requestAnimationFrame(() => this.loop());
        }
    }
}

window.SpaceInvadersGame = SpaceInvadersGame;
