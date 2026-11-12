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
│   │       ├── app.js          # Lógica de puntuaciones y modal
│   │       └── crypto-js.min.js# Criptografía AES-256 y HMAC para paquetes
│   ├── api.php                 # API REST segura con cifrado E2E y BCRYPT
│   ├── database.sql            # Script SQL de la base de datos
│   └── index.php               # Interfaz con medidor de contraseñas y juego
└── README.md
```

---

## 🔒 Mecanismos de Seguridad y Autenticación Avanzada

El aplicativo integra una arquitectura de seguridad integral compuesta por 3 pilares:

1. **Cifrado E2E de Paquetes de Entrada y Salida (AES-256-CBC + HMAC-SHA256)**:
   - Los datos sensibles ya no viajan en texto plano por la red (`username`, `password`, `token`, puntuaciones).
   - El cliente JavaScript cifra cada paquete con AES-256-CBC usando un IV criptográfico único de 16 bytes y adjunta una firma digital HMAC-SHA256 para prevenir alteraciones o ataques de intermediario (Man-In-The-Middle).
   - El servidor PHP descifra la petición, valida la integridad y responde con un nuevo paquete cifrado antes de transmitirlo al navegador.

2. **Metodología de Contraseñas Robustas (Rechazo de Contraseñas Débiles)**:
   - Validación forzosa tanto en el backend (`api.php`) como en el frontend (`index.php`).
   - Requisitos obligatorios:
     - Mínimo 8 caracteres (máximo 64).
     - Al menos una letra mayúscula (`A-Z`).
     - Al menos una letra minúscula (`a-z`).
     - Al menos un dígito numérico (`0-9`).
     - Al menos un símbolo o carácter especial (`!@#$%^&*` etc.).
     - Rechazo de contraseñas que incluyan el nombre de usuario o secuencias de repetición.
     - Bloqueo de contraseñas comunes de diccionario (`password`, `12345678`, etc.).

3. **Asistente Interactivo en Tiempo Real y Protección contra Fuerza Bruta**:
   - **Medidor visual interactivo**: Barra de fortaleza con 4 niveles de seguridad dinámicos (Débil, Regular, Aceptable, Robusta).
   - **Checklist dinámico en vivo**: Se marcan en verde instantáneamente los requisitos conforme el usuario escribe.
   - **Generador de contraseñas robustas**: Botón con un solo clic que sugiere una clave segura de alta entropía.
   - **Protección contra fuerza bruta (Rate Limiting)**: Bloqueo temporal automático de la cuenta por 5 minutos al acumular 5 intentos fallidos consecutivos de inicio de sesión.

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
