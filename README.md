# 🚀 CORE DEFENDER // VOID PROTOCOL
### Portal Táctico Arcade & Sistema de Despliegue Automatizado para Ubuntu 26.04 LTS

Proyecto full-stack de alto rendimiento diseñado con estética **Dark Glassmorphism Táctico** (negro tinta `#0a0a0c`, carmesí `#e63946`, dorado `#ffb703` y cian `#00f5d4`), compuesto por un minijuego arcade en Canvas HTML5, backend en PHP 8.5 (FPM), base de datos MariaDB y automatización DevOps completa para Nginx y phpMyAdmin en Ubuntu 26.04 LTS (`srv2112995`).

---

## 📁 Estructura del Repositorio

```text
SpaceInvaders/
├── deploy/
│   ├── setup_vps_nginx.sh      # Script maestro de aprovisionamiento del VPS
│   └── database.sql            # Copia del esquema SQL para importación desatendida
├── web_project/
│   ├── config/
│   │   └── db.php              # Conexión PDO optimizada a MariaDB
│   ├── assets/
│   │   ├── css/
│   │   │   └── style.css       # Estilos Dark Glassmorphism, HUD y animaciones
│   │   └── js/
│   │       ├── game.js         # Motor Canvas del minijuego + sintetizador Web Audio
│   │       └── app.js          # Integración API REST, Leaderboard y HUD reactivo
│   ├── api.php                 # API RESTful (Leaderboard, envíos y telemetría)
│   ├── database.sql            # Esquema SQL y 5 registros semilla
│   └── index.php               # Portal táctico principal con Server-Side Rendering
└── README.md                   # Manual de operaciones y comandos
```

---

## 🛠️ PASO 1: Probar la Web en Local

Puedes probar la aplicación en tu entorno local mediante cualquiera de los siguientes dos métodos:

### Opción A: Con PHP CLI y MySQL local (Recomendado)
1. Si tienes MySQL o MariaDB instalado localmente:
   ```bash
   mysql -u root -p < web_project/database.sql
   ```
2. Entra a la carpeta del proyecto web e inicia el servidor de desarrollo integrado de PHP:
   ```bash
   cd web_project
   php -S 127.0.0.1:8000
   ```
3. Abre en tu navegador: [http://localhost:8000](http://localhost:8000)

> **Nota de Resiliencia:** El sistema cuenta con modo *fallback* automático. Si no levantas MySQL en local, la web cargará datos simulados y el juego funcionará al 100% indicando el estado `DEGRADADO` en la telemetría del HUD sin romperse.

### Opción B: Con un contenedor Docker rápido (PHP + MariaDB)
Si tienes Docker en tu equipo local:
```bash
docker run -d --name local-mariadb -e MYSQL_ROOT_PASSWORD=rootpass -e MYSQL_DATABASE=game_portal_db -e MYSQL_USER=admin_bd -e MYSQL_PASSWORD=AdminPass2026! -p 3306:3306 mariadb:latest

# Esperar 5 segundos y cargar el esquema:
docker exec -i local-mariadb mysql -uadmin_bd -pAdminPass2026! game_portal_db < web_project/database.sql

# Iniciar servidor PHP local apuntando a la BD:
cd web_project
php -S 127.0.0.1:8000
```

---

## 🖥️ PASO 2: Ejecutar `setup_vps_nginx.sh` en el VPS

El script `deploy/setup_vps_nginx.sh` está diseñado para ejecutarse como `root` en **Ubuntu 26.04 LTS (hostname: `srv2112995`)**. Realiza toda la configuración sin interacción del usuario:

### 1. Subir la carpeta `deploy` a tu VPS
Desde tu máquina local:
```bash
# Reemplaza TU_IP_VPS por la IP real de tu servidor
scp -r deploy root@TU_IP_VPS:/root/
```

*(O si prefieres clonar el repositorio completo vía Git en el VPS):*
```bash
ssh root@TU_IP_VPS
git clone <URL_DE_TU_REPOSITORIO> /root/SpaceInvaders
cd /root/SpaceInvaders/deploy
```

### 2. Conectarte por SSH y ejecutar el script
```bash
ssh root@TU_IP_VPS
cd /root/deploy
chmod +x setup_vps_nginx.sh
./setup_vps_nginx.sh
```

### ¿Qué realiza el script automáticamente?
1. **Actualiza apt** e instala silenciosamente `nginx`, `mariadb-server`, `php-fpm`, extensiones (`php-mysql`, `php-curl`, `php-xml`, `php-gd`, `php-mbstring`, `php-zip`), `phpmyadmin` y `ufw`.
2. **Detecta dinámicamente** la versión de PHP instalada (ej. `8.5`) y localiza el socket FPM (`/run/php/php8.5-fpm.sock`).
3. **Configura `/etc/nginx/sites-available/default`** para:
   - Servir desde `/var/www/html` con `index index.php index.html index.htm;`.
   - Ejecutar archivos `.php` a través del socket FastCGI detectado.
   - Crea el enlace simbólico `ln -sfn /usr/share/phpmyadmin /var/www/html/phpmyadmin`.
4. **Configura el Firewall UFW**:
   - Habilita `OpenSSH` (puerto 22).
   - Habilita `'Nginx Full'` (puertos 80 HTTP y 443 HTTPS).
   - Activa el firewall (`ufw --force enable`).
5. **Configura MariaDB**:
   - Crea la base de datos `game_portal_db` con cotejamiento `utf8mb4_unicode_ci`.
   - Crea el usuario `admin_bd`@`localhost` con contraseña `AdminPass2026!`.
   - Otorga todos los privilegios: `GRANT ALL PRIVILEGES ON *.* TO 'admin_bd'@'localhost' WITH GRANT OPTION;`.
   - Importa automáticamente el archivo `database.sql` si está presente.
6. **Ajusta permisos para SFTP / FileZilla**:
   - Crea el usuario de sistema `usuario_proyecto` con acceso al grupo `www-data`.
   - Establece la propiedad de `/var/www/html` a `www-data:www-data` con permisos `775` y bit SGID.
7. **Reinicia y habilita** `nginx`, `mariadb` y `php8.5-fpm`.

---

## 📂 PASO 3: Qué Carpetas Arrastrar en FileZilla hacia `/var/www/html`

Para desplegar la aplicación web en producción mediante FileZilla:

### 1. Parámetros de Conexión en FileZilla
- **Protocolo:** `SFTP - SSH File Transfer Protocol`
- **Servidor:** `TU_IP_VPS` (o el hostname `srv2112995`)
- **Puerto:** `22`
- **Tipo de autenticación:** `Normal`
- **Usuario:** `usuario_proyecto` (o `root`)
- **Contraseña:** `AdminPass2026!` (o tu clave SSH de root)

### 2. Contenido a Arrastrar
En el panel local (lado izquierdo de FileZilla), abre la carpeta:
`web_project/`

Selecciona **todo el contenido interno** de `web_project/`:
- 📁 `assets/` *(incluyendo `css/` y `js/`)*
- 📁 `config/` *(incluyendo `db.php`)*
- 📄 `api.php`
- 📄 `database.sql`
- 📄 `index.php`

Arrastra estos elementos directamente al panel remoto (lado derecho de FileZilla) dentro de la ruta:
```text
/var/www/html/
```

> **IMPORTANTE:** No arrastres la carpeta contenedora `web_project`, sino sus **archivos y subcarpetas interiores**, de modo que `index.php` quede exactamente en `/var/www/html/index.php`.
>
> *(Nota: El enlace `/var/www/html/phpmyadmin` ya fue creado por el script; déjalo intacto).*

---

## 🌐 URLs de Acceso y Credenciales

| Servicio | URL | Credenciales |
| :--- | :--- | :--- |
| **Portal Web del Videojuego** | `http://<IP_TU_VPS>/` | Acceso libre a comandantes |
| **Panel de phpMyAdmin** | `http://<IP_TU_VPS>/phpmyadmin/` | **Usuario:** `admin_bd`<br>**Contraseña:** `AdminPass2026!` |
| **Conexión SFTP (FileZilla)** | Puerto `22` (SFTP) | **Usuario:** `usuario_proyecto`<br>**Contraseña:** `AdminPass2026!` |
| **Base de Datos MariaDB** | `localhost:3306` | **BD:** `game_portal_db`<br>**Usuario:** `admin_bd`<br>**Pass:** `AdminPass2026!` |

---

## 🔍 Comandos de Verificación Rápida en el VPS

Si necesitas verificar el estado del servidor en cualquier momento:

```bash
# Ver estado de los servicios
systemctl status nginx
systemctl status php8.5-fpm
systemctl status mariadb

# Comprobar la sintaxis de NGINX
nginx -t

# Probar la respuesta HTTP local
curl -I http://localhost/
curl -I http://localhost/phpmyadmin/

# Ver los logs en vivo si hay incidencias
tail -f /var/log/nginx/error.log
```

