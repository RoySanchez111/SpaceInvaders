#!/usr/bin/env bash
# ==============================================================================
# Script de Despliegue y Configuración Automatizada para Ubuntu 26.04 LTS
# Hostname de Referencia: srv2112995
# Stack: NGINX + PHP 8.5 (FPM) + MariaDB + phpMyAdmin + UFW Firewall
# ==============================================================================

set -euo pipefail

# ------------------------------------------------------------------------------
# Configuración de Colores y Estilos
# ------------------------------------------------------------------------------
RED='\033[0;31m'
GREEN='\033[0;32m'
YELLOW='\033[1;33m'
BLUE='\033[0;34m'
MAGENTA='\033[0;35m'
CYAN='\033[0;36m'
BOLD='\033[1m'
NC='\033[0m' # No Color

# ------------------------------------------------------------------------------
# Parámetros del Sistema y Credenciales
# ------------------------------------------------------------------------------
TARGET_HOSTNAME="srv2112995"
DB_NAME="game_portal_db"
DB_USER="admin_bd"
DB_PASS="AdminPass2026!"
SFTP_USER="usuario_proyecto"
WEB_ROOT="/var/www/html"
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"

# ------------------------------------------------------------------------------
# Funciones Utilitarias de Logging
# ------------------------------------------------------------------------------
log_info() {
    echo -e "${BLUE}[INFO]${NC} $1"
}

log_success() {
    echo -e "${GREEN}[OK]${NC} $1"
}

log_warning() {
    echo -e "${YELLOW}[WARN]${NC} $1"
}

log_error() {
    echo -e "${RED}[ERROR]${NC} $1" >&2
}

log_header() {
    echo -e "\n${BOLD}${CYAN}==============================================================================${NC}"
    echo -e "${BOLD}${CYAN} $1${NC}"
    echo -e "${BOLD}${CYAN}==============================================================================${NC}"
}

# ------------------------------------------------------------------------------
# 0. Verificaciones Previas (Root y Sistema Operativo)
# ------------------------------------------------------------------------------
log_header "0. VERIFICACIONES DE ENTORNO Y PRIVILEGIOS"

if [[ $EUID -ne 0 ]]; then
    log_error "Este script debe ejecutarse con privilegios de superusuario (root)."
    echo "Ejecute: sudo bash $0"
    exit 1
fi

log_success "Privilegios de root verificados (EUID=0)."

# Opcional: Notificar si el hostname no coincide
CURRENT_HOSTNAME=$(hostname 2>/dev/null || cat /etc/hostname 2>/dev/null || echo "unknown")
log_info "Hostname actual: ${BOLD}${CURRENT_HOSTNAME}${NC} (Objetivo: ${TARGET_HOSTNAME})"

# Desactivar interacción en apt y auto-reiniciar servicios si aplica
export DEBIAN_FRONTEND=noninteractive
export NEEDRESTART_MODE=a

# ------------------------------------------------------------------------------
# 1. Instalación de Paquetes (Nginx, MariaDB, PHP-FPM, phpMyAdmin)
# ------------------------------------------------------------------------------
log_header "1. ACTUALIZACIÓN E INSTALACIÓN DE PAQUETES"

log_info "Actualizando repositorios apt..."
apt-get update -y

log_info "Preconfigurando debconf para instalación desatendida de phpMyAdmin..."
# Evitamos que phpmyadmin intente configurar apache2 o pregunte por dbconfig
echo "phpmyadmin phpmyadmin/dbconfig-install boolean false" | debconf-set-selections || true
echo "phpmyadmin phpmyadmin/reconfigure-webserver multiselect none" | debconf-set-selections || true

PACKAGES=(
    nginx
    mariadb-server
    php-fpm
    php-mysql
    php-curl
    php-xml
    php-gd
    php-mbstring
    php-zip
    phpmyadmin
    ufw
    curl
    unzip
)

log_info "Instalando paquetes: ${PACKAGES[*]}..."
apt-get install -y --no-install-recommends "${PACKAGES[@]}"

log_success "Paquetes esenciales instalados con éxito."

# ------------------------------------------------------------------------------
# 2. Detección Dinámica de Versión de PHP y Socket PHP-FPM
# ------------------------------------------------------------------------------
log_header "2. DETECCIÓN DINÁMICA DE PHP Y CONFIGURACIÓN DE FPM"

# Intentar detectar mediante CLI
PHP_CLI_VER=$(php -r 'echo PHP_MAJOR_VERSION.".".PHP_MINOR_VERSION;' 2>/dev/null || true)

if [[ -n "$PHP_CLI_VER" ]]; then
    PHP_VER="$PHP_CLI_VER"
else
    # Buscar en directorios /etc/php/*
    PHP_VER=$(find /etc/php/ -mindepth 1 -maxdepth 1 -type d -exec basename {} \; 2>/dev/null | grep -E '^[0-9]+\.[0-9]+$' | sort -V | tail -n1 || true)
fi

# Fallback si no fue detectada (predeterminada de la solicitud: 8.5)
if [[ -z "$PHP_VER" ]]; then
    PHP_VER="8.5"
    log_warning "No se pudo detectar automáticamente la versión; asumiendo PHP ${PHP_VER}."
else
    log_success "Versión de PHP detectada: ${BOLD}${PHP_VER}${NC}"
fi

PHP_FPM_SERVICE="php${PHP_VER}-fpm"
PHP_FPM_SOCK="/run/php/php${PHP_VER}-fpm.sock"

# Asegurar que el servicio FPM esté iniciado para generar el socket
systemctl enable "${PHP_FPM_SERVICE}" || true
systemctl restart "${PHP_FPM_SERVICE}" || true

# Esperar unos momentos por el socket
SOCKET_WAIT=0
while [[ ! -S "$PHP_FPM_SOCK" && $SOCKET_WAIT -lt 5 ]]; do
    sleep 1
    ((SOCKET_WAIT++))
done

if [[ -S "$PHP_FPM_SOCK" ]]; then
    log_success "Socket PHP-FPM activo en: ${PHP_FPM_SOCK}"
else
    # Si no existe con la versión exacta, buscar cualquier socket php-fpm en /run/php
    ALTERNATIVE_SOCK=$(ls /run/php/php*-fpm.sock 2>/dev/null | head -n1 || true)
    if [[ -n "$ALTERNATIVE_SOCK" ]]; then
        PHP_FPM_SOCK="$ALTERNATIVE_SOCK"
        log_warning "Usando socket alternativo encontrado: ${PHP_FPM_SOCK}"
    else
        log_warning "Socket aún no visible en /run/php; se utilizará la ruta estándar: ${PHP_FPM_SOCK}"
    fi
fi

# ------------------------------------------------------------------------------
# 3. Configuración de NGINX (/etc/nginx/sites-available/default)
# ------------------------------------------------------------------------------
log_header "3. CONFIGURACIÓN DEL VIRTUAL HOST DE NGINX"

NGINX_DEFAULT_CONF="/etc/nginx/sites-available/default"

# Respaldar configuración original si no existe respaldo previo
if [[ -f "$NGINX_DEFAULT_CONF" && ! -f "${NGINX_DEFAULT_CONF}.original.bak" ]]; then
    cp "$NGINX_DEFAULT_CONF" "${NGINX_DEFAULT_CONF}.original.bak"
    log_info "Copia de respaldo creada en ${NGINX_DEFAULT_CONF}.original.bak"
fi

log_info "Generando configuración NGINX para PHP ${PHP_VER} y phpMyAdmin..."

cat <<'EOF' > "$NGINX_DEFAULT_CONF"
# Configuración del Servidor Web Nginx
# Generado automáticamente por deploy/setup_vps_nginx.sh

server {
    listen 80 default_server;
    listen [::]:80 default_server;

    # Directorio raíz del proyecto
    root /var/www/html;

    # Orden de búsqueda de índices
    index index.php index.html index.htm;

    server_name _;

    # Límite de carga para archivos / dumps SQL
    client_max_body_size 64M;

    # Cabeceras de seguridad básicas
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;

    # Enrutamiento principal
    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    # Bloque de ejecución PHP vía FastCGI
    location ~ \.php$ {
        include snippets/fastcgi-php.conf;
        fastcgi_pass unix:__PHP_FPM_SOCK__;
        fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
        include fastcgi_params;
        fastcgi_read_timeout 300;
        fastcgi_buffer_size 128k;
        fastcgi_buffers 256 16k;
        fastcgi_busy_buffers_size 256k;
        fastcgi_temp_file_write_size 256k;
    }

    # Bloque específico para phpMyAdmin (optimizado y seguro)
    location /phpmyadmin {
        root /usr/share/;
        index index.php index.html index.htm;

        location ~ ^/phpmyadmin/(.+\.php)$ {
            try_files $uri =404;
            root /usr/share/;
            fastcgi_pass unix:__PHP_FPM_SOCK__;
            fastcgi_index index.php;
            fastcgi_param SCRIPT_FILENAME $document_root$fastcgi_script_name;
            include fastcgi_params;
            fastcgi_read_timeout 300;
            fastcgi_buffer_size 128k;
            fastcgi_buffers 256 16k;
        }

        location ~* ^/phpmyadmin/(.+\.(jpg|jpeg|gif|css|png|js|ico|html|xml|txt))$ {
            root /usr/share/;
            expires 30d;
            add_header Cache-Control "public, no-transform";
        }
    }

    # Denegar acceso a archivos sensibles y ocultos (.git, .env, .htaccess, etc.)
    location ~ /\. {
        deny all;
        access_log off;
        log_not_found off;
    }

    # Caché para activos estáticos del frontend
    location ~* \.(css|js|jpg|jpeg|png|gif|ico|svg|webp|woff|woff2|ttf|eot)$ {
        expires 30d;
        add_header Cache-Control "public, no-transform";
    }
}
EOF

# Reemplazar el marcador del socket PHP-FPM con la ruta real detectada
sed -i "s|__PHP_FPM_SOCK__|${PHP_FPM_SOCK}|g" "$NGINX_DEFAULT_CONF"

# Asegurar enlace en sites-enabled si no existe
if [[ ! -L /etc/nginx/sites-enabled/default && ! -f /etc/nginx/sites-enabled/default ]]; then
    ln -s /etc/nginx/sites-available/default /etc/nginx/sites-enabled/default
fi

# Crear enlace simbólico de phpMyAdmin en /var/www/html (requisito explícito)
mkdir -p "$WEB_ROOT"
log_info "Creando enlace simbólico: ln -sfn /usr/share/phpmyadmin ${WEB_ROOT}/phpmyadmin"
ln -sfn /usr/share/phpmyadmin "${WEB_ROOT}/phpmyadmin"

# Validar sintaxis de NGINX
log_info "Validando configuración de NGINX (nginx -t)..."
nginx -t

log_success "Virtual host de NGINX configurado y validado correctamente."

# ------------------------------------------------------------------------------
# 4. Configuración del Firewall UFW
# ------------------------------------------------------------------------------
log_header "4. CONFIGURACIÓN DEL FIREWALL UFW"

log_info "Permitiendo OpenSSH (Puerto 22)..."
ufw allow OpenSSH || ufw allow 22/tcp

log_info "Permitiendo 'Nginx Full' (Puertos 80 HTTP y 443 HTTPS)..."
ufw allow 'Nginx Full'

log_info "Habilitando UFW..."
ufw --force enable

log_success "Reglas de Firewall UFW aplicadas con éxito."

# ------------------------------------------------------------------------------
# 5. Configuración de MariaDB y Creación de Credenciales
# ------------------------------------------------------------------------------
log_header "5. CONFIGURACIÓN DE MARIADB Y BASE DE DATOS"

log_info "Asegurando inicio de servicio MariaDB..."
systemctl start mariadb
systemctl enable mariadb

log_info "Creando base de datos '${DB_NAME}' y usuario '${DB_USER}'..."
mariadb -u root <<EOF
-- Crear la base de datos con soporte UTF-8 completo
CREATE DATABASE IF NOT EXISTS \`${DB_NAME}\` 
  CHARACTER SET utf8mb4 
  COLLATE utf8mb4_unicode_ci;

-- Crear o actualizar usuario con contraseña especificada
CREATE USER IF NOT EXISTS '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';
ALTER USER '${DB_USER}'@'localhost' IDENTIFIED BY '${DB_PASS}';

-- Otorgar todos los privilegios con opción de concesión (para phpMyAdmin)
GRANT ALL PRIVILEGES ON *.* TO '${DB_USER}'@'localhost' WITH GRANT OPTION;
FLUSH PRIVILEGES;
EOF

log_success "Base de datos y usuario '${DB_USER}' configurados en MariaDB."

# ------------------------------------------------------------------------------
# Importación Automática del Esquema SQL si está presente
# ------------------------------------------------------------------------------
SQL_CANDIDATES=(
    "${1:-}"                                     # Argumento pasado por CLI
    "${SCRIPT_DIR}/../web_project/database.sql" # Ruta relativa si se corre desde repo
    "${SCRIPT_DIR}/database.sql"                 # Mismo directorio que el script
    "${WEB_ROOT}/database.sql"                   # Si ya fue subido a /var/www/html
)

SQL_IMPORTED=false
for SQL_FILE in "${SQL_CANDIDATES[@]}"; do
    if [[ -n "$SQL_FILE" && -f "$SQL_FILE" ]]; then
        log_info "Importando esquema SQL desde: ${SQL_FILE}..."
        mariadb -u root "${DB_NAME}" < "$SQL_FILE"
        log_success "Esquema '${SQL_FILE}' importado satisfactoriamente en '${DB_NAME}'."
        SQL_IMPORTED=true
        break
    fi
done

if [[ "$SQL_IMPORTED" = false ]]; then
    log_warning "No se encontró ningún archivo database.sql en las rutas automáticas."
    log_info "Puede importarlo más tarde desde phpMyAdmin o ejecutando: mariadb -u root ${DB_NAME} < database.sql"
fi

# ------------------------------------------------------------------------------
# 6. Gestión de Usuario SFTP y Ajuste de Permisos
# ------------------------------------------------------------------------------
log_header "6. PERMISOS DE ARCHIVOS Y USUARIO PARA FILEZILLA (SFTP)"

# Verificar si el usuario_proyecto existe; si no, crearlo
if id -u "${SFTP_USER}" >/dev/null 2>&1; then
    log_info "El usuario '${SFTP_USER}' ya existe en el sistema."
else
    log_info "Creando usuario de sistema '${SFTP_USER}'..."
    useradd -m -s /bin/bash "${SFTP_USER}"
    # Establecer contraseña temporal o la misma del proyecto si no tiene
    echo "${SFTP_USER}:${DB_PASS}" | chpasswd
    log_success "Usuario '${SFTP_USER}' creado con contraseña provisional: ${DB_PASS}"
fi

# Agregar usuario_proyecto al grupo www-data
usermod -aG www-data "${SFTP_USER}"
log_success "Usuario '${SFTP_USER}' agregado al grupo www-data."

# Ajustar propiedad y permisos en /var/www/html
log_info "Aplicando permisos a ${WEB_ROOT}..."
chown -R www-data:www-data "${WEB_ROOT}"
chmod -R 775 "${WEB_ROOT}"

# Activar bit setgid para que los nuevos archivos mantengan el grupo www-data
find "${WEB_ROOT}" -type d -exec chmod g+s {} + 2>/dev/null || true

log_success "Permisos aplicados: propietario www-data:www-data con chmod 775 (SGID activo)."

# ------------------------------------------------------------------------------
# 7. Reinicio y Activación de Servicios
# ------------------------------------------------------------------------------
log_header "7. REINICIO Y VERIFICACIÓN DE SERVICIOS"

log_info "Reiniciando MariaDB..."
systemctl restart mariadb
systemctl enable mariadb

log_info "Reiniciando PHP-FPM (${PHP_FPM_SERVICE})..."
systemctl restart "${PHP_FPM_SERVICE}"
systemctl enable "${PHP_FPM_SERVICE}"

log_info "Reiniciando Nginx..."
systemctl restart nginx
systemctl enable nginx

# ------------------------------------------------------------------------------
# 8. Resumen Final del Despliegue
# ------------------------------------------------------------------------------
SERVER_IP=$(hostname -I 2>/dev/null | awk '{print $1}' || echo "IP_PUBLICA")

log_header "DESPLIEGUE FINALIZADO CON ÉXITO"
echo -e "${GREEN}${BOLD}¡El entorno NGINX + PHP ${PHP_VER} + MariaDB está listo para producción!${NC}\n"
echo -e "${BOLD}1. Acceso Web Principal:${NC}"
echo -e "   -> http://${SERVER_IP}/"
echo -e "   -> http://localhost/ (desde el servidor)\n"
echo -e "${BOLD}2. Acceso a phpMyAdmin:${NC}"
echo -e "   -> http://${SERVER_IP}/phpmyadmin/"
echo -e "   -> Usuario BD:   ${CYAN}${DB_USER}${NC}"
echo -e "   -> Password BD:  ${CYAN}${DB_PASS}${NC}\n"
echo -e "${BOLD}3. Base de Datos del Juego:${NC}"
echo -e "   -> Nombre de BD: ${CYAN}${DB_NAME}${NC}\n"
echo -e "${BOLD}4. Conexión SFTP para FileZilla:${NC}"
echo -e "   -> Protocolo:    SFTP (SSH File Transfer Protocol)"
echo -e "   -> Servidor:     ${SERVER_IP}"
echo -e "   -> Puerto:       22"
echo -e "   -> Usuario:      ${CYAN}${SFTP_USER}${NC} (o root)"
echo -e "   -> Ruta destino: ${CYAN}${WEB_ROOT}/${NC}\n"
echo -e "${BOLD}5. Estado de Servicios:${NC}"
systemctl is-active nginx >/dev/null && echo -e "   -> Nginx:    ${GREEN}ACTIVO (RUNNING)${NC}" || echo -e "   -> Nginx:    ${RED}INACTIVO${NC}"
systemctl is-active "${PHP_FPM_SERVICE}" >/dev/null && echo -e "   -> PHP-FPM:  ${GREEN}ACTIVO (RUNNING)${NC}" || echo -e "   -> PHP-FPM:  ${RED}INACTIVO${NC}"
systemctl is-active mariadb >/dev/null && echo -e "   -> MariaDB:  ${GREEN}ACTIVO (RUNNING)${NC}" || echo -e "   -> MariaDB:  ${RED}INACTIVO${NC}"
echo -e "${CYAN}==============================================================================${NC}\n"

