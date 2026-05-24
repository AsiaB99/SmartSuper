# SmartSuper

Aplicación web monolítica en Laravel para gestionar listas de la compra y despensas compartidas, con objetivo de incorporar un motor de recomendación de supermercado por coste total (precio + distancia).

## Estado actual del proyecto

Estado verificado el 2026-05-03:

- Dominio implementado:
  - entidades: `Lista`, `Despensa`, `Producto`, `Seccion`, `Supermercado`
  - pivotes: `Hacen`, `Tienen`, `Formadas`, `Almacena`, `Venden`
- Flujos funcionales disponibles:
  - CRUD de `listas` y `despensas`
  - gestión de stock en despensas (alta, actualización y baja de producto)
  - finalización de lista y vista de recomendación
  - CRUD admin de `productos`, `supermercados` y `precios` (`venden`)
- Seguridad/autorización:
  - policies registradas para `Lista` y `Despensa`
  - protección `auth` en rutas de aplicación
  - restricción de acceso admin en controladores de catálogo/precios
- Pruebas existentes:
  - feature tests de autorización para listas/despensas y módulo admin
  - tests de recomendación (feature + unit)

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

## Automatización de catálogo externo

El catálogo externo de precios queda orquestado por Laravel con:

```bash
php artisan catalogo:actualizar
```

Opciones disponibles:

```bash
php artisan catalogo:actualizar --fuente=mercadona
php artisan catalogo:actualizar --fuente=consum
php artisan catalogo:actualizar --fuente=carrefour
php artisan catalogo:actualizar --solo-importar
php artisan catalogo:actualizar --solo-scraping
```

### Configuración en `.env`

La automatización usa variables `CATALOGO_EXTERNO_*`:

- activación del scheduler: `CATALOGO_EXTERNO_SCHEDULER_ENABLED`
- binario de PowerShell: `CATALOGO_EXTERNO_POWERSHELL_BINARY`
- timeout de procesos: `CATALOGO_EXTERNO_PROCESS_TIMEOUT`
- parámetros de `mercadona`, `consum` y `carrefour`

La primera versión opera con un único contexto por cadena.

### Despliegue en servidor

Para que la actualización diaria se ejecute automáticamente en producción hacen falta estos prerrequisitos:

- PHP CLI operativo
- PowerShell 7 disponible en el servidor
- Python disponible para los scrapers
- permisos de escritura en `storage/app/scraping`
- `.env` de producción con `CATALOGO_EXTERNO_SCHEDULER_ENABLED=true`

Laravel ya registra el comando diario en su scheduler. Solo falta que el servidor ejecute el scheduler de Laravel cada minuto:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

En Windows Task Scheduler, el equivalente es lanzar cada minuto:

```powershell
php artisan schedule:run
```

desde la raíz del proyecto.

### Verificación postdespliegue

Comandos útiles:

```bash
php artisan schedule:list
php artisan catalogo:actualizar --fuente=mercadona
php artisan catalogo:actualizar --fuente=consum
php artisan catalogo:actualizar --fuente=carrefour --solo-importar
```

Qué revisar:

- salida del comando sin errores
- tabla `productos_externos` con `fecha_importacion` actualizada
- productos marcados como `disponible = false` cuando desaparecen del lote
- panel admin de productos externos con sugerencias regeneradas
