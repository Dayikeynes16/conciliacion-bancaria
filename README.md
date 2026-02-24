# Conciliación Bancaria

Aplicación para la conciliación de facturas XML (CFDI) con movimientos bancarios (estados de cuenta), con soporte multiempresa.

> Documentación técnica completa: [docs/SOURCE_OF_TRUTH.md](docs/SOURCE_OF_TRUTH.md)

---

## Requisitos

- [Docker Desktop](https://www.docker.com/products/docker-desktop/) corriendo

---

## Configuración inicial

```bash
# 1. Copiar variables de entorno
cp .env.example .env

# 2. Instalar dependencias PHP (usa Docker, no requiere PHP local)
docker run --rm -v "$(pwd):/var/www/html" -w /var/www/html composer:latest composer install --ignore-platform-reqs --no-interaction

# 3. Levantar todos los contenedores
./vendor/bin/sail up -d

# 4. Generar clave de aplicación
./vendor/bin/sail artisan key:generate

# 5. Ejecutar migraciones
./vendor/bin/sail artisan migrate

# 6. Compilar assets del frontend
./vendor/bin/sail npm install && ./vendor/bin/sail npm run build
```

La app estará disponible en **http://localhost**

---

## Servicios incluidos

| Servicio        | Descripción                              | Puerto |
| --------------- | ---------------------------------------- | ------ |
| `laravel.test`  | App principal (PHP 8.5)                  | 80     |
| `mysql`         | Base de datos MySQL 8.4                  | 3306   |
| `redis`         | Cache y colas                            | 6379   |
| `mailpit`       | Captura de emails (desarrollo)           | 8025   |
| `queue-imports` | Worker: procesa XMLs y estados de cuenta | —      |
| `queue-exports` | Worker: genera reportes Excel/PDF        | —      |

> Los workers de cola se inician **automáticamente** con `sail up`.

---

## Comandos útiles

```bash
# Detener contenedores
./vendor/bin/sail stop

# Ver logs de los workers
docker logs conciliacion-bancaria-queue-imports-1 -f
docker logs conciliacion-bancaria-queue-exports-1 -f

# Acceder al shell del contenedor
./vendor/bin/sail shell

# Correr tests
./vendor/bin/sail artisan test --compact
```

---

## Deploy en producción

Antes de hacer deploy, revisa y ajusta en el `.env`:

```env
APP_ENV=production
APP_DEBUG=false
APP_URL=https://tu-dominio.com

# Base de datos
DB_HOST=tu-host-db
DB_DATABASE=nombre_db
DB_USERNAME=usuario
DB_PASSWORD=contraseña_segura

# Redis
REDIS_HOST=tu-host-redis

# Mail
MAIL_MAILER=smtp
MAIL_HOST=...
```

Luego:

```bash
./vendor/bin/sail up -d
./vendor/bin/sail artisan migrate --force
./vendor/bin/sail npm run build
./vendor/bin/sail artisan config:cache
./vendor/bin/sail artisan route:cache
./vendor/bin/sail artisan view:cache
```

> **Importante:** Los workers `queue-imports` y `queue-exports` deben estar corriendo para que la importación de archivos y la generación de reportes funcionen. Con `sail up -d` se inician automáticamente.

---

## Primera vez en la app

1. Registrarse (se crea un equipo por defecto).
2. Ir a **Formatos Bancarios** y crear el formato de tu banco (ej. BBVA).
3. Ir a **Mesa de Trabajo** → cargar XMLs y estados de cuenta.
4. Usar **Auto Conciliar** o seleccionar manualmente.
