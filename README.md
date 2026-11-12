# Space Invaders Web

Juego online **Space Invaders** en HTML5 Canvas con backend modular en **PHP**, **MariaDB** y **NGINX**.

## Estructura del Proyecto

```text
SpaceInvaders/
├── deploy/
│   ├── setup_vps_nginx.sh        # Configuración automática del servidor
│   └── database.sql              # Esquema inicial de base de datos
├── web_project/
│   ├── index.php                 # Estructura de pantallas (Acceso y Juego)
│   ├── api.php                   # Enrutador de peticiones API
│   ├── database.sql              # Esquema SQL de usuarios y puntuaciones
│   ├── config/
│   │   └── db.php                # Conexión PDO a MariaDB
│   ├── includes/
│   │   ├── auth_service.php      # Lógica de sesión, cuentas y puntuaciones
│   │   ├── crypto.php            # Transporte de paquetes cliente-servidor
│   │   └── validator.php         # Reglas de validación de entradas
│   └── assets/
│       ├── css/
│       │   └── style.css         # Estilos de pantalla de acceso y tablero
│       └── js/
│           ├── app.js            # Controlador de vistas, modales y sesión
│           ├── game.js           # Motor Canvas de Space Invaders
│           ├── sound.js          # Sintetizador de audio Web Audio API
│           ├── api-client.js     # Cliente HTTP y gestión de sesión
│           └── crypto-js.min.js  # Librería auxiliar de transporte
└── README.md
```

## Despliegue / Actualización en VPS

Desde la terminal del servidor VPS (`root@srv2112995:~#`):

```bash
rm -rf /tmp/SpaceInvaders && git clone https://github.com/RoySanchez111/SpaceInvaders.git /tmp/SpaceInvaders && cp -rf /tmp/SpaceInvaders/web_project/* /var/www/html/ && chown -R www-data:www-data /var/www/html
```
