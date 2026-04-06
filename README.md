# SmartSuper

Aplicación web monolítica en Laravel para gestionar listas de la compra y despensas compartidas, con objetivo de incorporar un motor de recomendación de supermercado por coste total (precio + distancia).

## Estado actual del proyecto

Actualmente ya está implementado:

- Modelos del dominio:
  - `Lista`, `Despensa`, `Producto`, `Seccion`, `Supermercado`
  - pivotes: `Hacen`, `Tienen`, `Formadas`, `Almacena`, `Venden`
- Relaciones Eloquent explícitas con claves no estándar del dominio.
- CRUD base de listas de compra:
  - listado, creación, edición y borrado
  - validación con Form Requests
- Rutas web para listas mediante resource routes.
- Base de vistas Blade para el módulo de listas.

## Stack

- Backend: Laravel 12
- PHP: 8.3+ (probado en 8.4 con Herd)
- Base de datos objetivo: MySQL 8
- Frontend: Blade + Vite + Tailwind CSS


## Estructura relevante

- `app/Models`: entidades de dominio y pivotes
- `app/Http/Controllers/ListaController.php`: acciones HTTP del CRUD de listas
- `app/Http/Requests`: validaciones de creación/edición de listas
- `resources/views/listas`: vistas del módulo de listas
- `routes/web.php`: rutas web
- `database/migrations`: esquema de base de datos


## Requisitos previos

- PHP 8.3+
- Composer
- Node.js y npm
- MySQL

## Puesta en marcha

### 1) Instalar dependencias

```bash
composer install
npm install
```

### 2) Configurar entorno

Crear `.env` (si no existe) a partir de `.env.example` y ajustar:

- `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- `SESSION_DRIVER` (si MySQL no está disponible, usar temporalmente `file`)

### 3) Clave de aplicación y migraciones

```bash
php artisan key:generate
php artisan migrate
```

### 4) Assets frontend

```bash
npm run build
```

Esto genera `public/build/manifest.json`, requerido por `@vite(...)` en las vistas.

## Scripts útiles

### Composer

- `composer run setup`: instalación completa inicial (deps, .env, key, migrate, assets)
- `composer run dev`: stack local de desarrollo

### npm

- `npm run dev`: servidor Vite para desarrollo
- `npm run build`: compilación de producción de assets
