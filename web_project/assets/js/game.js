/**
 * =============================================================================
 * CORE DEFENDER: VOID PROTOCOL
 * Minijuego Arcade Táctico en HTML5 Canvas
 * =============================================================================
 * Mecánica: Arena Survival / Core Defense.
 * Defiende el reactor central contra incursiones del Vacío Espacial.
 * Incluye sintetizador de audio procedimental con Web Audio API (cero dependencias).
 */

class SoundSynthesizer {
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

    toggle() {
        this.enabled = !this.enabled;
        return this.enabled;
    }

    playLaser() {
        if (!this.enabled || !this.ctx) return;
        try {
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = 'sawtooth';
            osc.frequency.setValueAtTime(880, this.ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(110, this.ctx.currentTime + 0.12);

            gain.gain.setValueAtTime(0.15, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.12);

            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start();
            osc.stop(this.ctx.currentTime + 0.12);
        } catch (e) {
            // Audio policy silently caught
        }
    }

    playExplosion(isHeavy = false) {
        if (!this.enabled || !this.ctx) return;
        try {
            const dur = isHeavy ? 0.35 : 0.2;
            const bufferSize = this.ctx.sampleRate * dur;
            const buffer = this.ctx.createBuffer(1, bufferSize, this.ctx.sampleRate);
            const data = buffer.getChannelData(0);
            for (let i = 0; i < bufferSize; i++) {
                data[i] = Math.random() * 2 - 1;
            }

            const noise = this.ctx.createBufferSource();
            noise.buffer = buffer;

            const filter = this.ctx.createBiquadFilter();
            filter.type = 'lowpass';
            filter.frequency.setValueAtTime(isHeavy ? 260 : 450, this.ctx.currentTime);
            filter.frequency.exponentialRampToValueAtTime(40, this.ctx.currentTime + dur);

            const gain = this.ctx.createGain();
            gain.gain.setValueAtTime(isHeavy ? 0.35 : 0.22, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + dur);

            noise.connect(filter);
            filter.connect(gain);
            gain.connect(this.ctx.destination);

            noise.start();
        } catch (e) {}
    }

    playEmpPulse() {
        if (!this.enabled || !this.ctx) return;
        try {
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = 'sine';
            osc.frequency.setValueAtTime(200, this.ctx.currentTime);
            osc.frequency.exponentialRampToValueAtTime(1200, this.ctx.currentTime + 0.25);
            osc.frequency.exponentialRampToValueAtTime(60, this.ctx.currentTime + 0.6);

            gain.gain.setValueAtTime(0.4, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.6);

            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start();
            osc.stop(this.ctx.currentTime + 0.6);
        } catch (e) {}
    }

    playCoreDamage() {
        if (!this.enabled || !this.ctx) return;
        try {
            const osc = this.ctx.createOscillator();
            const gain = this.ctx.createGain();
            osc.type = 'square';
            osc.frequency.setValueAtTime(140, this.ctx.currentTime);
            osc.frequency.setValueAtTime(90, this.ctx.currentTime + 0.1);

            gain.gain.setValueAtTime(0.25, this.ctx.currentTime);
            gain.gain.exponentialRampToValueAtTime(0.01, this.ctx.currentTime + 0.25);

            osc.connect(gain);
            gain.connect(this.ctx.destination);
            osc.start();
            osc.stop(this.ctx.currentTime + 0.25);
        } catch (e) {}
    }

    playWaveStart() {
        if (!this.enabled || !this.ctx) return;
        try {
            const now = this.ctx.currentTime;
            [330, 440, 550, 880].forEach((freq, i) => {
                const osc = this.ctx.createOscillator();
                const gain = this.ctx.createGain();
                osc.type = 'triangle';
                osc.frequency.value = freq;
                gain.gain.setValueAtTime(0.12, now + i * 0.08);
                gain.gain.exponentialRampToValueAtTime(0.001, now + (i + 1) * 0.15);
                osc.connect(gain);
                gain.connect(this.ctx.destination);
                osc.start(now + i * 0.08);
                osc.stop(now + (i + 1) * 0.15);
            });
        } catch (e) {}
    }
}

class CoreDefenderGame {
    constructor(canvasId) {
        this.canvas = document.getElementById(canvasId);
        this.ctx = this.canvas.getContext('2d');
        this.sound = new SoundSynthesizer();

        // Dimensiones virtuales
        this.width = this.canvas.width;
        this.height = this.canvas.height;
        this.centerX = this.width / 2;
        this.centerY = this.height / 2;

        // Estado del Juego
        this.isRunning = false;
        this.isPaused = false;
        this.score = 0;
        this.wave = 1;
        this.multiplier = 1.0;
        this.shotsFired = 0;
        this.shotsHit = 0;
        this.kills = 0;

        // Núcleo Central
        this.core = {
            x: this.centerX,
            y: this.centerY,
            radius: 28,
            maxHealth: 100,
            health: 100,
            pulseTimer: 0,
            shieldAngle: 0
        };

        // Nave Defensora Orbital
        this.player = {
            orbitRadius: 95,
            angle: 0,
            x: this.centerX + 95,
            y: this.centerY,
            speed: 0.05,
            size: 14,
            aimAngle: 0,
            fireCooldown: 0,
            fireRate: 8 // ticks entre disparos
        };

        // Capacidad Especial EMP
        this.emp = {
            cooldown: 0,
            maxCooldown: 400, // ~6.5 segundos a 60fps
            radius: 0,
            maxRadius: 280,
            active: false
        };

        // Entidades y Partículas
        this.bullets = [];
        this.enemies = [];
        this.particles = [];
        this.combatTexts = [];

        // Oleadas y Spawns
        this.waveEnemiesLeft = 12;
        this.spawnTimer = 0;
        this.spawnInterval = 70;

        // Inputs
        this.mouse = { x: this.centerX, y: this.centerY, isDown: false };
        this.keys = { w: false, a: false, s: false, d: false, space: false };

        this.setupEventListeners();
        this.renderInitialFrame();
    }

    setupEventListeners() {
        // Seguimiento de Puntero sobre el Canvas
        const updateMousePos = (e) => {
            const rect = this.canvas.getBoundingClientRect();
            const scaleX = this.canvas.width / rect.width;
            const scaleY = this.canvas.height / rect.height;
            this.mouse.x = (e.clientX - rect.left) * scaleX;
            this.mouse.y = (e.clientY - rect.top) * scaleY;
        };

        this.canvas.addEventListener('mousemove', updateMousePos);
        this.canvas.addEventListener('mousedown', (e) => {
            this.sound.init();
            if (e.button === 0) {
                this.mouse.isDown = true;
            } else if (e.button === 2) {
                e.preventDefault();
                this.triggerEmpPulse();
            }
        });
        this.canvas.addEventListener('mouseup', (e) => {
            if (e.button === 0) this.mouse.isDown = false;
        });
        this.canvas.addEventListener('contextmenu', (e) => e.preventDefault());

        // Teclado
        window.addEventListener('keydown', (e) => {
            if (['KeyW', 'ArrowUp'].includes(e.code)) this.keys.w = true;
            if (['KeyS', 'ArrowDown'].includes(e.code)) this.keys.s = true;
            if (['KeyA', 'ArrowLeft'].includes(e.code)) this.keys.a = true;
            if (['KeyD', 'ArrowRight'].includes(e.code)) this.keys.d = true;
            if (e.code === 'Space') {
                e.preventDefault();
                this.triggerEmpPulse();
            }
        });

        window.addEventListener('keyup', (e) => {
            if (['KeyW', 'ArrowUp'].includes(e.code)) this.keys.w = false;
            if (['KeyS', 'ArrowDown'].includes(e.code)) this.keys.s = false;
            if (['KeyA', 'ArrowLeft'].includes(e.code)) this.keys.a = false;
            if (['KeyD', 'ArrowRight'].includes(e.code)) this.keys.d = false;
        });
    }

    start() {
        this.sound.init();
        this.reset();
        this.isRunning = true;
        this.isPaused = false;
        this.sound.playWaveStart();
        this.showCombatText(this.centerX, this.centerY - 50, "DEFENSA ACTIVADA // OLEADA 01", "#00f5d4");
        this.gameLoop();
    }

    pause() {
        if (!this.isRunning) return;
        this.isPaused = !this.isPaused;
        if (!this.isPaused) {
            this.gameLoop();
        }
    }

    reset() {
        this.score = 0;
        this.wave = 1;
        this.multiplier = 1.0;
        this.shotsFired = 0;
        this.shotsHit = 0;
        this.kills = 0;
        this.core.health = 100;
        this.bullets = [];
        this.enemies = [];
        this.particles = [];
        this.combatTexts = [];
        this.emp.cooldown = 0;
        this.emp.active = false;
        this.waveEnemiesLeft = 14;
        this.spawnInterval = 65;
        this.updateHUD();
    }

    triggerEmpPulse() {
        if (!this.isRunning || this.isPaused) return;
        if (this.emp.cooldown <= 0) {
            this.emp.active = true;
            this.emp.radius = 10;
            this.emp.cooldown = this.emp.maxCooldown;
            this.sound.playEmpPulse();

            // Destruir proyectiles y dañar enemigos masivamente
            this.enemies.forEach(enemy => {
                const dist = Math.hypot(enemy.x - this.centerX, enemy.y - this.centerY);
                if (dist < this.emp.maxRadius) {
                    this.createExplosion(enemy.x, enemy.y, '#ffb703', 18);
                    this.score += 80 * this.wave;
                    this.kills++;
                }
            });
            this.enemies = this.enemies.filter(e => Math.hypot(e.x - this.centerX, e.y - this.centerY) >= this.emp.maxRadius);
            this.showCombatText(this.centerX, this.centerY - 70, "PULSO EMP DESCARGADO", "#ffb703");
            this.updateHUD();
        }
    }

    spawnEnemy() {
        // Seleccionar borde de origen
        let x, y;
        const edge = Math.floor(Math.random() * 4);
        const margin = 30;

        if (edge === 0) { x = Math.random() * this.width; y = -margin; }
        else if (edge === 1) { x = this.width + margin; y = Math.random() * this.height; }
        else if (edge === 2) { x = Math.random() * this.width; y = this.height + margin; }
        else { x = -margin; y = Math.random() * this.height; }

        // Tipos de enemigos según la oleada
        const rand = Math.random();
        let type = 'scout';
        let hp = 1;
        let speed = 1.3 + (this.wave * 0.12);
        let color = '#e63946';
        let size = 9;

        if (rand > 0.75 && this.wave >= 2) {
            type = 'dreadnought';
            hp = 3 + Math.floor(this.wave / 3);
            speed = 0.75 + (this.wave * 0.05);
            color = '#ffb703';
            size = 14;
        } else if (rand > 0.5 && this.wave >= 3) {
            type = 'interceptor';
            hp = 2;
            speed = 1.9 + (this.wave * 0.1);
            color = '#a855f7';
            size = 8;
        }

        this.enemies.push({
            x, y,
            type,
            hp,
            maxHp: hp,
            speed,
            color,
            size,
            angle: 0,
            wiggle: Math.random() * Math.PI * 2
        });
    }

    createExplosion(x, y, color = '#e63946', count = 12) {
        for (let i = 0; i < count; i++) {
            const angle = Math.random() * Math.PI * 2;
            const speed = 1.5 + Math.random() * 4;
            this.particles.push({
                x, y,
                vx: Math.cos(angle) * speed,
                vy: Math.sin(angle) * speed,
                life: 1.0,
                decay: 0.02 + Math.random() * 0.03,
                color,
                size: 2 + Math.random() * 3
            });
        }
    }

    showCombatText(x, y, text, color = '#ffb703') {
        this.combatTexts.push({
            x, y,
            text,
            color,
            life: 1.0,
            vy: -1.2
        });
    }

    update() {
        // --- 1. Movimiento del Defensor Orbital ---
        if (this.keys.a) this.player.angle -= this.player.speed;
        if (this.keys.d) this.player.angle += this.player.speed;
        if (this.keys.w && this.player.orbitRadius < 150) this.player.orbitRadius += 2;
        if (this.keys.s && this.player.orbitRadius > 65) this.player.orbitRadius -= 2;

        this.player.x = this.centerX + Math.cos(this.player.angle) * this.player.orbitRadius;
        this.player.y = this.centerY + Math.sin(this.player.angle) * this.player.orbitRadius;

        // Ángulo de mira hacia el ratón
        this.player.aimAngle = Math.atan2(this.mouse.y - this.player.y, this.mouse.x - this.player.x);

        // Disparo continuo al mantener el clic
        if (this.mouse.isDown && this.player.fireCooldown <= 0) {
            this.bullets.push({
                x: this.player.x,
                y: this.player.y,
                vx: Math.cos(this.player.aimAngle) * 9,
                vy: Math.sin(this.player.aimAngle) * 9,
                life: 80
            });
            this.shotsFired++;
            this.player.fireCooldown = this.player.fireRate;
            this.sound.playLaser();
        }

        if (this.player.fireCooldown > 0) {
            this.player.fireCooldown--;
        }

        // --- 2. Actualización de EMP ---
        if (this.emp.cooldown > 0) {
            this.emp.cooldown--;
        }
        if (this.emp.active) {
            this.emp.radius += 14;
            if (this.emp.radius >= this.emp.maxRadius) {
                this.emp.active = false;
            }
        }

        // --- 3. Actualización de Proyectiles ---
        for (let i = this.bullets.length - 1; i >= 0; i--) {
            const b = this.bullets[i];
            b.x += b.vx;
            b.y += b.vy;
            b.life--;

            if (b.life <= 0 || b.x < 0 || b.x > this.width || b.y < 0 || b.y > this.height) {
                this.bullets.splice(i, 1);
                continue;
            }

            // Colisión con enemigos
            for (let j = this.enemies.length - 1; j >= 0; j--) {
                const e = this.enemies[j];
                const dist = Math.hypot(b.x - e.x, b.y - e.y);
                if (dist < e.size + 4) {
                    this.shotsHit++;
                    e.hp--;
                    this.createExplosion(b.x, b.y, '#00f5d4', 4);
                    this.bullets.splice(i, 1);

                    if (e.hp <= 0) {
                        this.sound.playExplosion(e.type === 'dreadnought');
                        this.createExplosion(e.x, e.y, e.color, e.type === 'dreadnought' ? 24 : 14);
                        const points = (e.type === 'dreadnought' ? 350 : 100) * this.wave;
                        this.score += Math.round(points * this.multiplier);
                        this.kills++;
                        this.showCombatText(e.x, e.y, `+${points}`, e.color);
                        this.enemies.splice(j, 1);
                    }
                    break;
                }
            }
        }

        // --- 4. Actualización y Generación de Enemigos ---
        this.spawnTimer++;
        if (this.spawnTimer >= this.spawnInterval && this.waveEnemiesLeft > 0) {
            this.spawnEnemy();
            this.waveEnemiesLeft--;
            this.spawnTimer = 0;
        }

        // Progresión de Oleada
        if (this.waveEnemiesLeft <= 0 && this.enemies.length === 0) {
            this.wave++;
            this.multiplier = +(this.multiplier + 0.2).toFixed(1);
            this.waveEnemiesLeft = 12 + (this.wave * 4);
            this.spawnInterval = Math.max(25, 70 - (this.wave * 5));
            this.sound.playWaveStart();
            this.showCombatText(this.centerX, this.centerY - 50, `OLEADA ${this.wave.toString().padStart(2, '0')} // AMENAZA AUMENTADA`, "#00f5d4");
            // Regeneración parcial del núcleo al superar la oleada
            this.core.health = Math.min(100, this.core.health + 15);
            this.updateHUD();
        }

        for (let i = this.enemies.length - 1; i >= 0; i--) {
            const e = this.enemies[i];
            const targetX = this.centerX;
            const targetY = this.centerY;
            const angle = Math.atan2(targetY - e.y, targetX - e.x);

            // Trayectoria recta o en zig-zag si es interceptor
            if (e.type === 'interceptor') {
                e.wiggle += 0.08;
                const perpX = -Math.sin(angle);
                const perpY = Math.cos(angle);
                e.x += Math.cos(angle) * e.speed + perpX * Math.sin(e.wiggle) * 1.5;
                e.y += Math.sin(angle) * e.speed + perpY * Math.sin(e.wiggle) * 1.5;
            } else {
                e.x += Math.cos(angle) * e.speed;
                e.y += Math.sin(angle) * e.speed;
            }

            e.angle = angle;

            // Colisión con el Núcleo Central
            const distToCore = Math.hypot(e.x - this.centerX, e.y - this.centerY);
            if (distToCore < this.core.radius + e.size) {
                const damage = e.type === 'dreadnought' ? 25 : 10;
                this.core.health -= damage;
                this.createExplosion(e.x, e.y, '#e63946', 20);
                this.sound.playCoreDamage();
                this.showCombatText(this.centerX, this.centerY, `-${damage}% INTEGRIDAD`, '#e63946');
                this.enemies.splice(i, 1);
                this.updateHUD();

                if (this.core.health <= 0) {
                    this.core.health = 0;
                    this.gameOver();
                    return;
                }
            }
        }

        // --- 5. Partículas y Textos de Combate ---
        for (let i = this.particles.length - 1; i >= 0; i--) {
            const p = this.particles[i];
            p.x += p.vx;
            p.y += p.vy;
            p.life -= p.decay;
            if (p.life <= 0) this.particles.splice(i, 1);
        }

        for (let i = this.combatTexts.length - 1; i >= 0; i--) {
            const ct = this.combatTexts[i];
            ct.y += ct.vy;
            ct.life -= 0.025;
            if (ct.life <= 0) this.combatTexts.splice(i, 1);
        }

        this.core.pulseTimer += 0.05;
        this.core.shieldAngle += 0.02;
    }

    draw() {
        this.ctx.clearRect(0, 0, this.width, this.height);

        // 1. Rejilla Holográfica Local del Radar
        this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.03)';
        this.ctx.lineWidth = 1;
        for (let r = 50; r <= 360; r += 70) {
            this.ctx.beginPath();
            this.ctx.arc(this.centerX, this.centerY, r, 0, Math.PI * 2);
            this.ctx.stroke();
        }

        // 2. Onda de Pulso EMP Activa
        if (this.emp.active) {
            this.ctx.strokeStyle = 'rgba(255, 183, 3, 0.7)';
            this.ctx.lineWidth = 4;
            this.ctx.beginPath();
            this.ctx.arc(this.centerX, this.centerY, this.emp.radius, 0, Math.PI * 2);
            this.ctx.stroke();

            this.ctx.fillStyle = 'rgba(255, 183, 3, 0.12)';
            this.ctx.beginPath();
            this.ctx.arc(this.centerX, this.centerY, this.emp.radius, 0, Math.PI * 2);
            this.ctx.fill();
        }

        // 3. Núcleo Central
        const pulse = Math.sin(this.core.pulseTimer) * 3;
        const coreGradient = this.ctx.createRadialGradient(
            this.centerX, this.centerY, 4,
            this.centerX, this.centerY, this.core.radius + pulse
        );

        if (this.core.health > 40) {
            coreGradient.addColorStop(0, '#ffffff');
            coreGradient.addColorStop(0.5, '#00f5d4');
            coreGradient.addColorStop(1, 'rgba(0, 245, 212, 0)');
        } else {
            coreGradient.addColorStop(0, '#ffffff');
            coreGradient.addColorStop(0.5, '#e63946');
            coreGradient.addColorStop(1, 'rgba(230, 57, 70, 0)');
        }

        this.ctx.fillStyle = coreGradient;
        this.ctx.beginPath();
        this.ctx.arc(this.centerX, this.centerY, this.core.radius + pulse, 0, Math.PI * 2);
        this.ctx.fill();

        // Anillo de Escudo Táctico del Núcleo
        this.ctx.strokeStyle = this.core.health > 40 ? 'rgba(0, 245, 212, 0.6)' : 'rgba(230, 57, 70, 0.8)';
        this.ctx.lineWidth = 2;
        this.ctx.setLineDash([12, 8]);
        this.ctx.beginPath();
        this.ctx.arc(this.centerX, this.centerY, this.core.radius + 10, this.core.shieldAngle, this.core.shieldAngle + Math.PI * 2);
        this.ctx.stroke();
        this.ctx.setLineDash([]);

        // Órbita Guía de la Nave
        this.ctx.strokeStyle = 'rgba(255, 255, 255, 0.08)';
        this.ctx.beginPath();
        this.ctx.arc(this.centerX, this.centerY, this.player.orbitRadius, 0, Math.PI * 2);
        this.ctx.stroke();

        // 4. Proyectiles
        this.ctx.fillStyle = '#00f5d4';
        this.ctx.shadowColor = '#00f5d4';
        this.ctx.shadowBlur = 8;
        this.bullets.forEach(b => {
            this.ctx.beginPath();
            this.ctx.arc(b.x, b.y, 3, 0, Math.PI * 2);
            this.ctx.fill();
        });
        this.ctx.shadowBlur = 0;

        // 5. Enemigos
        this.enemies.forEach(e => {
            this.ctx.save();
            this.ctx.translate(e.x, e.y);
            this.ctx.rotate(e.angle);

            this.ctx.fillStyle = e.color;
            this.ctx.shadowColor = e.color;
            this.ctx.shadowBlur = 8;

            this.ctx.beginPath();
            if (e.type === 'dreadnought') {
                // Nave pesada en forma de hexágono
                this.ctx.moveTo(e.size, 0);
                this.ctx.lineTo(-e.size, -e.size);
                this.ctx.lineTo(-e.size / 2, 0);
                this.ctx.lineTo(-e.size, e.size);
            } else if (e.type === 'interceptor') {
                // Interceptor con alas afiladas
                this.ctx.moveTo(e.size, 0);
                this.ctx.lineTo(-e.size, -e.size * 0.7);
                this.ctx.lineTo(-e.size * 0.4, 0);
                this.ctx.lineTo(-e.size, e.size * 0.7);
            } else {
                // Drone básico en triángulo agresivo
                this.ctx.moveTo(e.size, 0);
                this.ctx.lineTo(-e.size, -e.size * 0.6);
                this.ctx.lineTo(-e.size, e.size * 0.6);
            }
            this.ctx.closePath();
            this.ctx.fill();
            this.ctx.restore();
        });

        // 6. Nave Orbital del Jugador
        this.ctx.save();
        this.ctx.translate(this.player.x, this.player.y);
        this.ctx.rotate(this.player.aimAngle);

        this.ctx.fillStyle = '#ffb703';
        this.ctx.shadowColor = '#ffb703';
        this.ctx.shadowBlur = 10;

        this.ctx.beginPath();
        this.ctx.moveTo(this.player.size + 4, 0);
        this.ctx.lineTo(-this.player.size, -this.player.size * 0.65);
        this.ctx.lineTo(-this.player.size * 0.4, 0);
        this.ctx.lineTo(-this.player.size, this.player.size * 0.65);
        this.ctx.closePath();
        this.ctx.fill();
        this.ctx.restore();

        // 7. Partículas
        this.particles.forEach(p => {
            this.ctx.fillStyle = p.color;
            this.ctx.globalAlpha = Math.max(0, p.life);
            this.ctx.beginPath();
            this.ctx.arc(p.x, p.y, p.size, 0, Math.PI * 2);
            this.ctx.fill();
        });
        this.ctx.globalAlpha = 1.0;

        // 8. Textos de Combate Flotantes
        this.ctx.font = 'bold 12px "Orbitron", monospace';
        this.combatTexts.forEach(ct => {
            this.ctx.fillStyle = ct.color;
            this.ctx.globalAlpha = Math.max(0, ct.life);
            this.ctx.fillText(ct.text, ct.x, ct.y);
        });
        this.ctx.globalAlpha = 1.0;
    }

    renderInitialFrame() {
        this.draw();
    }

    gameLoop() {
        if (!this.isRunning || this.isPaused) return;

        this.update();
        this.draw();

        // Actualizar HUD cada frame
        this.updateScoreDisplay();

        requestAnimationFrame(() => this.gameLoop());
    }

    updateScoreDisplay() {
        const hudScore = document.getElementById('hudScore');
        if (hudScore) {
            hudScore.textContent = this.score.toString().padStart(6, '0');
        }
    }

    updateHUD() {
        const hudScore = document.getElementById('hudScore');
        const hudWave = document.getElementById('hudWave');
        const hudMultiplier = document.getElementById('hudMultiplier');
        const hudCoreHealthBar = document.getElementById('hudCoreHealthBar');
        const hudCoreHealthText = document.getElementById('hudCoreHealthText');
        const empCooldownText = document.getElementById('empCooldownText');

        if (hudScore) hudScore.textContent = this.score.toString().padStart(6, '0');
        if (hudWave) hudWave.textContent = this.wave.toString().padStart(2, '0');
        if (hudMultiplier) hudMultiplier.textContent = `x${this.multiplier.toFixed(1)}`;
        
        if (hudCoreHealthBar && hudCoreHealthText) {
            const h = Math.max(0, Math.round(this.core.health));
            hudCoreHealthBar.style.width = `${h}%`;
            hudCoreHealthText.textContent = `${h}%`;

            hudCoreHealthBar.classList.remove('warning', 'critical');
            if (h <= 25) {
                hudCoreHealthBar.classList.add('critical');
            } else if (h <= 55) {
                hudCoreHealthBar.classList.add('warning');
            }
        }

        if (empCooldownText) {
            if (this.emp.cooldown <= 0) {
                empCooldownText.textContent = 'LISTO';
                empCooldownText.style.color = '#ffb703';
            } else {
                const secs = (this.emp.cooldown / 60).toFixed(1);
                empCooldownText.textContent = `${secs}s`;
                empCooldownText.style.color = '#64748b';
            }
        }
    }

    gameOver() {
        this.isRunning = false;
        this.sound.playCoreDamage();
        this.createExplosion(this.centerX, this.centerY, '#e63946', 60);
        this.draw();

        // Calcular precisión
        const accuracy = this.shotsFired > 0 
            ? Math.round((this.shotsHit / this.shotsFired) * 100) 
            : 0;

        // Disparar evento personalizado con los resultados
        window.dispatchEvent(new CustomEvent('coreDefender:gameOver', {
            detail: {
                score: this.score,
                wave: this.wave,
                kills: this.kills,
                accuracy: accuracy
            }
        }));
    }
}

// Exportar globalmente
window.CoreDefenderGame = CoreDefenderGame;

