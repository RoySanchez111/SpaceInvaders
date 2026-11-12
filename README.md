# Space Invaders Web

Juego online **Space Invaders** en HTML5 Canvas con backend modular en **PHP**, **MariaDB**, **Python OpenCV** y **NGINX**.

## Estructura de Carpetas

```text
SpaceInvaders/
├── deploy/
│   ├── setup_vps_nginx.sh        # Instalación automática en Ubuntu VPS
│   └── database.sql              # Esquema SQL de respaldo
├── web_project/                  # Raíz de la aplicación web (/var/www/html)
│   ├── index.php                 # ÚNICO archivo principal de la web (Acceso, Juego y Modales)
│   ├── database.sql              # Esquema de tablas (users y players_scores)
│   ├── config/
│   │   └── db.php                # Conexión PDO a MariaDB
│   ├── api/                      # Backend PHP + Motor Biométrico Python OpenCV
│   │   ├── auth.php              # Endpoint central de API (login, registro, cambio de clave con biometría, ranking)
│   │   ├── biometric_cv.py       # Motor Python + OpenCV para verificación facial en cambios sensibles
│   │   ├── validator.php         # Reglas de contraseña robusta
│   │   └── crypto.php            # Transporte cliente-servidor
│   └── assets/                   # Frontend (CSS + JS)
│       ├── css/
│       │   └── style.css         # Estilos de la aplicación
│       └── js/
│           ├── app.js            # Controlador de vistas, cámara biométrica, contraseña robusta y clasificación
│           ├── game.js           # Motor Canvas de Space Invaders
│           ├── sound.js          # Sintetizador de audio Web Audio API
│           ├── api-client.js     # Cliente conectado con api/auth.php
│           └── crypto-js.min.js  # Soporte criptográfico de transporte
└── README.md
```

## Actualizar en tu VPS (`root@srv2112995:~#`)

```bash
apt-get update && apt-get install -y python3 python3-opencv python3-numpy && rm -rf /tmp/SpaceInvaders && git clone https://github.com/RoySanchez111/SpaceInvaders.git /tmp/SpaceInvaders && rm -rf /var/www/html/* && cp -rf /tmp/SpaceInvaders/web_project/* /var/www/html/ && chown -R www-data:www-data /var/www/html
```
