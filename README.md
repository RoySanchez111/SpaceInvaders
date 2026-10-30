# 👾 Space Invaders Web & Despliegue VPS

Juego clásico de **Space Invaders** en HTML5 Canvas con backend en **PHP 8.5 (FPM)**, **MariaDB** y servidor web **NGINX**. Al terminar o perder la partida, muestra inmediatamente la tabla de mejores puntuaciones y permite registrar tu récord.

---

## 📁 Estructura del Proyecto

```text
SpaceInvaders/
├── deploy/
│   ├── setup_vps_nginx.sh      # Script para configurar automáticamente el VPS
│   └── database.sql            # Base de datos y tabla de puntuaciones
├── web_project/
│   ├── config/
│   │   └── db.php              # Conexión PDO a MariaDB
│   ├── assets/
│   │   ├── css/style.css       # Estilos oscuros minimalistas
│   │   └── js/
│   │       ├── game.js         # Juego Space Invaders (Canvas + sonido retro)
│   │       └── app.js          # Lógica de puntuaciones y modal
│   ├── api.php                 # API simple (guardar y leer puntuaciones)
│   ├── database.sql            # Script SQL de la base de datos
│   └── index.php               # Pantalla principal del juego
└── README.md
```

---

## 1. Probar la web en local

Abre tu terminal en la carpeta del proyecto y ejecuta:

```bash
cd web_project
php -S 127.0.0.1:8000
```

Abre tu navegador en: **http://localhost:8000**

> *Nota: Si aún no tienes MariaDB configurado en tu máquina local, el juego funcionará perfectamente en modo demostración con puntuaciones de ejemplo.*

---

## 2. Ejecutar el script `setup_vps_nginx.sh` en tu VPS (Ubuntu 26.04)

El script configura de forma 100% desatendida NGINX, PHP 8.5 FPM, MariaDB, phpMyAdmin y el firewall UFW.

### Paso 1: Subir la carpeta `deploy` al VPS
Desde tu máquina local:
```bash
scp -r deploy root@TU_IP_VPS:/root/
```

### Paso 2: Conectarte por SSH y ejecutarlo
```bash
ssh root@TU_IP_VPS
cd /root/deploy
chmod +x setup_vps_nginx.sh
./setup_vps_nginx.sh
```

El script dejará listos los servicios:
- **Web:** `http://TU_IP_VPS/`
- **phpMyAdmin:** `http://TU_IP_VPS/phpmyadmin/` (Usuario: `admin_bd` | Contraseña: `AdminPass2026!`)

---

## 3. Qué carpetas arrastrar en FileZilla hacia `/var/www/html`

1. Abre **FileZilla** y conéctate por **SFTP** (puerto `22`):
   - **Servidor:** `TU_IP_VPS`
   - **Usuario:** `usuario_proyecto` (o `root`)
   - **Contraseña:** `AdminPass2026!`
2. En tu equipo (panel izquierdo), entra a la carpeta `web_project/`.
3. Selecciona **todo su contenido interior**:
   - 📁 `assets`
   - 📁 `config`
   - 📄 `api.php`
   - 📄 `database.sql`
   - 📄 `index.php`
4. Arrástralo al panel derecho dentro de:
   ```text
   /var/www/html/
   ```

*(Nota: Asegúrate de arrastrar los archivos dentro de `web_project`, de modo que `index.php` quede en `/var/www/html/index.php`).*
