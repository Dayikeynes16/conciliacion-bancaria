# App – Conciliación Bancaria

Aplicación para la conciliación de facturas (XML CFDI) con movimientos bancarios (Estados de Cuenta).

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

- **Backend**: Laravel 12
- **Frontend**: Vue 3 + Inertia.js
- **Estilos**: Tailwind CSS 4
- **Database**: MySQL / PostgreSQL (Dockerizado vía Sail)
- **Container**: Laravel Sail (Docker)

## Requisitos Previos

- Docker Desktop corriendo.
- PHP 8.2+ (Opcional si se usa Sail para todo).

## Instalación

1. Clonar repositorio.
2. Iniciar contenedores:
    ```bash
    ./vendor/bin/sail up -d
    ```
3. Instalar dependencias:
    ```bash
    ./vendor/bin/sail composer install
    ./vendor/bin/sail npm install
    ```
4. Ejecutar migraciones:
    ```bash
    ./vendor/bin/sail artisan migrate
    ```
5. Compilar assets:
    ```bash
    ./vendor/bin/sail npm run dev
    ```

## Uso

1. Registrarse (creará un Team por defecto).
2. Ir a "Mesa de Trabajo" (Reconciliation).
3. Subir archivos XML y Estado de Cuenta.
4. Usar "Auto Conciliar" o seleccionar manualmente.
