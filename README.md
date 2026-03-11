# App – Conciliación Bancaria

Aplicación para la conciliación de facturas (XML CFDI) con movimientos bancarios (Estados de Cuenta).

> **DOCUMENTACIÓN OFICIAL (Forensic Audit)**: Consulta [docs/SOURCE_OF_TRUTH.md](docs/SOURCE_OF_TRUTH.md) para la arquitectura detallada, esquema de base de datos y flujos de negocio auditados.

## Características

- **Multitenancy**: Soporte para múltiples empresas/equipos. Cada usuario pertenece a un equipo (`Team`) y la información se aisla por equipo.
- **Importación**:
    - Facturas: Carga masiva de XML.
    - Movimientos: Carga de estados de cuenta (Soporte inicial para BBVA, estructura extensible).
- **Conciliación**:
    - **Automática**: Algoritmo inteligente que busca coincidencias por monto y fecha (con tolerancia).
    - **Manual**: Interfaz "Workbench" para seleccionar y cruzar facturas con movimientos.
- **Historial**: Registro de todas las conciliaciones realizadas.

## Tech Stack

- **Backend**: Laravel 12 (PHP 8.5)
- **Frontend**: Vue 3 + Inertia.js v2
- **Estilos**: Tailwind CSS 3
- **Database**: MySQL 8.4 (Dockerizado vía Sail)
- **Colas**: Laravel Queues (driver `database`) – colas `imports`, `exports`, `default`
- **Cache/Session**: Database
- **Container**: Laravel Sail (Docker)

## Requisitos Previos

- Docker Desktop corriendo.
- PHP 8.5+ (Opcional si se usa Sail para todo).

## Configuración del Entorno

Copiar `.env.example` a `.env` y ajustar los valores:

```bash
cp .env.example .env
```

El archivo `.env.example` incluye documentación inline de cada variable. Las más importantes:

| Variable | Descripción | Ejemplo Dev |
|---|---|---|
| `DB_CONNECTION` | Driver de BD | `mysql` |
| `DB_HOST` | Host de MySQL | `mysql` (Sail) |
| `QUEUE_CONNECTION` | Driver de colas | `database` |
| `MAIL_MAILER` | Driver de correo | `smtp` (Mailpit en dev) |
| `APP_PORT` | Puerto de la app (Sail) | `8085` |
| `VITE_PORT` | Puerto de Vite HMR | `5174` |

## Instalación (Desarrollo Local)

1. Clonar repositorio y configurar entorno:
    ```bash
    git clone <repo-url> && cd conciliacion
    cp .env.example .env
    ```
2. Instalar dependencias PHP (primera vez sin Sail):
    ```bash
    docker run --rm -v $(pwd):/var/www/html -w /var/www/html laravelsail/php85-composer:latest composer install --ignore-platform-reqs
    ```
3. Iniciar contenedores:
    ```bash
    ./vendor/bin/sail up -d
    ```
4. Generar clave de aplicación:
    ```bash
    ./vendor/bin/sail artisan key:generate
    ```
5. Instalar dependencias Node:
    ```bash
    ./vendor/bin/sail npm install
    ```
6. Ejecutar migraciones:
    ```bash
    ./vendor/bin/sail artisan migrate
    ```
7. Compilar assets (modo desarrollo con HMR):
    ```bash
    ./vendor/bin/sail npm run dev
    ```

La app estará en `http://localhost` (o el `APP_PORT` configurado).

### Servicios Docker incluidos

| Servicio | Puerto por defecto | Configurable con |
|---|---|---|
| App (Laravel) | `80` | `APP_PORT` |
| Vite HMR | `5173` | `VITE_PORT` |
| MySQL 8.4 | `3306` | `FORWARD_DB_PORT` |
| Redis | `6379` | `FORWARD_REDIS_PORT` |
| Mailpit (SMTP) | `1025` | `FORWARD_MAILPIT_PORT` |
| Mailpit (UI) | `8025` | `FORWARD_MAILPIT_DASHBOARD_PORT` |

### Queue Worker (ya incluido en Docker)

El `compose.yaml` incluye un servicio `queue` que ejecuta automáticamente:

```bash
php artisan queue:work --queue=default,imports,exports --sleep=3 --tries=3
```

No necesitas lanzar workers manualmente en desarrollo.

## Despliegue en Producción

### Requisitos del servidor

- PHP 8.5+ con extensiones: `pdo_mysql`, `mbstring`, `xml`, `curl`, `zip`, `gd`, `phpredis` (opcional)
- MySQL 8.0+
- Composer 2+
- Node.js 18+ y npm (solo para build)
- Nginx o Apache
- Supervisor (para queue workers)

### 1. Configuración del entorno

```bash
cp .env.example .env
```

Ajustar valores críticos en `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

DB_HOST=127.0.0.1
DB_DATABASE=conciliacion_prod
DB_USERNAME=tu_usuario
DB_PASSWORD=tu_password_seguro

QUEUE_CONNECTION=database

LOG_LEVEL=error

MAIL_MAILER=smtp
MAIL_HOST=tu-smtp-host.com
MAIL_PORT=465
MAIL_USERNAME=tu-correo@dominio.com
MAIL_PASSWORD=tu-password-smtp
MAIL_ENCRYPTION=ssl
MAIL_FROM_ADDRESS=tu-correo@dominio.com
MAIL_FROM_NAME="${APP_NAME}"
```

### 2. Instalar y compilar

```bash
composer install --no-dev --optimize-autoloader
npm ci && npm run build

php artisan key:generate
php artisan migrate --force
php artisan storage:link
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 3. Configurar Nginx

```nginx
server {
    listen 80;
    server_name tu-dominio.com;
    root /var/www/conciliacion/public;

    index index.php;

    charset utf-8;
    client_max_body_size 50M;  # Para carga de XMLs y estados de cuenta

    location / {
        try_files $uri $uri/ /index.php?$query_string;
    }

    location ~ \.php$ {
        fastcgi_pass unix:/var/run/php/php8.5-fpm.sock;
        fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
        include fastcgi_params;
    }

    location ~ /\.(?!well-known).* {
        deny all;
    }
}
```

### 4. Configurar Queue Workers (Supervisor)

Crear `/etc/supervisor/conf.d/conciliacion-worker.conf`:

```ini
[program:conciliacion-imports]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/conciliacion/artisan queue:work --queue=imports --sleep=3 --tries=3 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=2
redirect_stderr=true
stdout_logfile=/var/www/conciliacion/storage/logs/worker-imports.log

[program:conciliacion-exports]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/conciliacion/artisan queue:work --queue=exports --sleep=3 --tries=3 --timeout=600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/conciliacion/storage/logs/worker-exports.log

[program:conciliacion-default]
process_name=%(program_name)s_%(process_num)02d
command=php /var/www/conciliacion/artisan queue:work --queue=default --sleep=3 --tries=3
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
numprocs=1
redirect_stderr=true
stdout_logfile=/var/www/conciliacion/storage/logs/worker-default.log
```

Activar:

```bash
sudo supervisorctl reread
sudo supervisorctl update
sudo supervisorctl start conciliacion-imports:*
sudo supervisorctl start conciliacion-exports:*
sudo supervisorctl start conciliacion-default:*
```

### 5. Permisos de archivos

```bash
sudo chown -R www-data:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

### 6. Despliegues posteriores

```bash
git pull origin main
composer install --no-dev --optimize-autoloader
npm ci && npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
sudo supervisorctl restart conciliacion-imports:* conciliacion-exports:* conciliacion-default:*
```

## Uso

1. Registrarse (creará un Team por defecto).
2. Ir a "Mesa de Trabajo" (Reconciliation).
3. Subir archivos XML y Estado de Cuenta.
4. Usar "Auto Conciliar" o seleccionar manualmente.
