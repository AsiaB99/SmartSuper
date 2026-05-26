# SmartSuper

SmartSuper es mi proyecto final de DAW. Se trata de una aplicación web monolítica desarrollada con Laravel para gestionar listas de la compra, despensas compartidas y un catálogo centralizado de productos, con una capa de recomendación orientada a ayudar al usuario a decidir en qué supermercado le conviene comprar.

## Autora

- Asia Bosch Dwiyanti

## Resumen del proyecto

La aplicación cubre tres necesidades principales del hogar:

- organizar listas de la compra compartidas entre varios usuarios
- controlar el stock disponible en despensas
- consultar un catálogo global de productos y su relación con supermercados y precios

Como valor diferencial, SmartSuper incorpora un motor de recomendación que compara alternativas de compra a partir del contenido de una lista y del contexto disponible.

## Objetivos funcionales

- Crear y gestionar listas de la compra.
- Compartir listas y despensas entre usuarios con distintos permisos.
- Gestionar despensas y stock doméstico.
- Mantener un catálogo centralizado de productos y secciones.
- Registrar supermercados, cadenas y precios por producto.
- Importar catálogos externos para enriquecer productos y precios.
- Recomendar la opción de compra más conveniente para una lista.

## Características principales

- Gestión de usuarios autenticados.
- CRUD de listas, despensas, productos y supermercados.
- Relaciones de colaboración entre usuarios y recursos compartidos.
- Catálogo global reutilizable por toda la aplicación.
- Integración con importaciones externas de catálogos.
- Base preparada para cálculo de recomendación según cesta y contexto.

## Tecnologías utilizadas

- PHP 8.3
- Laravel 12
- Blade
- Vite
- JavaScript modular
- Tailwind CSS
- MySQL 8 como base de datos objetivo
- Composer
- npm

## Arquitectura del proyecto

- `app/Http/Controllers`: capa HTTP, autorización, orquestación y respuestas.
- `app/Http/Requests`: validación de formularios y peticiones.
- `app/Models`: entidades de dominio y tablas pivote.
- `app/Services`: lógica de negocio, cálculos e importaciones.
- `database/migrations`: definición del esquema.
- `database/seeders`: datos iniciales y apoyo a demostración.
- `resources/views`: interfaz Blade.
- `resources/js`: assets frontend gestionados con Vite.
- `routes/web.php`: rutas web.
- `routes/console.php`: comandos Artisan y scheduler.

## Módulos del dominio

- `Lista`: gestión de listas de la compra y flujo de finalización.
- `Despensa`: control de stock y productos disponibles en casa.
- `Producto`: catálogo global y clasificación por secciones.
- `Supermercado`: gestión de supermercados y cadenas.
- `Venden`: relación entre productos, supermercados y precios.
- `ProductoExterno`: entrada y mapeo de catálogos importados.

## Requisitos

### Entorno de desarrollo

- PHP 8.3 o superior
- Composer
- Node.js y npm
- MySQL 8 o SQLite para pruebas locales
- Laravel Herd o entorno equivalente

### Entorno de producción

- PHP 8.3 o superior con extensiones requeridas por Laravel
- Servidor web apuntando a `public/`
- MySQL 8
- Acceso CLI a `php`
- Permisos de escritura en `storage/` y `bootstrap/cache/`
- Cron del sistema para ejecutar tareas programadas

## Puesta en marcha rápida

Si solo quieres dejar el proyecto funcionando para evaluarlo localmente:

```bash
composer run setup
php artisan db:seed
composer run dev
```

Después abre la URL local del proyecto y accede con alguno de los usuarios de demostración:

- `admin@example.com` / `password`
- `test@example.com` / `password`

## Instalación paso a paso

### 1. Instalar dependencias

```bash
composer install
npm install
```

### 2. Crear el entorno

En Windows:

```bash
copy .env.example .env
php artisan key:generate
```

En macOS o Linux:

```bash
cp .env.example .env
php artisan key:generate
```

### 3. Revisar configuración básica

Ajusta al menos estas variables en `.env`:

- `APP_NAME`
- `APP_ENV`
- `APP_URL`
- `APP_DEBUG`
- `DB_CONNECTION`
- `DB_HOST`
- `DB_PORT`
- `DB_DATABASE`
- `DB_USERNAME`
- `DB_PASSWORD`

Si utilizas sesiones, caché y colas en base de datos, revisa también:

- `SESSION_DRIVER`
- `CACHE_STORE`
- `QUEUE_CONNECTION`

### 4. Preparar la base de datos

```bash
php artisan migrate
php artisan db:seed
```

### 5. Compilar assets

```bash
npm run build
```

### 6. Levantar el entorno de desarrollo

```bash
composer run dev
```

## Cómo probar la aplicación

Un recorrido básico de validación funcional sería:

1. Iniciar sesión con un usuario de demostración.
2. Crear una lista de la compra.
3. Añadir productos del catálogo a la lista.
4. Crear o revisar una despensa.
5. Consultar supermercados y precios registrados.
6. Probar el flujo de recomendación o finalización de lista si los datos cargados lo permiten.

## Datos de demostración

El seeder crea usuarios base y secciones del catálogo. Los precios y productos externos no se generan como demo cerrada, sino que se alimentan desde importaciones y validación manual.

Credenciales disponibles tras ejecutar `php artisan db:seed`:

- Usuario administrador: `admin@example.com` / `password`
- Usuario de prueba: `test@example.com` / `password`

## Scripts útiles

- `composer run setup`: instala dependencias, crea `.env` si no existe, genera clave, ejecuta migraciones y compila assets.
- `composer run dev`: arranca servidor local, cola, visor de logs y Vite en paralelo.
- `composer run test`: limpia configuración y ejecuta la suite de tests.
- `npm run dev`: arranca Vite en modo desarrollo.
- `npm run build`: genera el build de producción.

## Testing

Para ejecutar la suite:

```bash
composer run test
```

También puedes usar directamente:

```bash
php artisan test
```

En el entorno local con Herd también es habitual:

```bash
C:\Users\asiab\.config\herd\bin\php.bat artisan test
```

## Recomendación de compra

El proyecto está orientado a recomendar la mejor opción de compra a partir de una lista. La idea funcional del sistema consiste en:

- calcular el coste de la cesta en cada supermercado disponible
- considerar información complementaria del contexto de compra
- devolver la alternativa más conveniente para el usuario

Esta parte constituye uno de los ejes funcionales del proyecto y justifica el componente “smart” de SmartSuper.

## Importación de catálogo externo

La actualización del catálogo externo se orquesta mediante:

```bash
php artisan catalogo:actualizar
```

Ejemplos de uso:

```bash
php artisan catalogo:actualizar --fuente=mercadona
php artisan catalogo:actualizar --fuente=consum
php artisan catalogo:actualizar --fuente=carrefour
php artisan catalogo:actualizar --solo-importar
php artisan catalogo:actualizar --solo-scraping
```

Variables relevantes en `.env`:

- `CATALOGO_EXTERNO_SCHEDULER_ENABLED`
- `CATALOGO_EXTERNO_POWERSHELL_BINARY`
- `CATALOGO_EXTERNO_PROCESS_TIMEOUT`
- variables específicas de `mercadona`
- variables específicas de `consum`
- variables específicas de `carrefour`

Prerrequisitos operativos:

- PHP CLI funcional
- PowerShell 7 para el orquestador actual
- Python para los scrapers
- permisos de escritura en `storage/app/scraping`
- configuración válida de cada fuente habilitada

## Despliegue en producción

La rama de referencia para despliegue es `main`.

Flujo recomendado:

1. Desplegar el código de `main`.
2. Instalar dependencias PHP sin paquetes de desarrollo.
3. Instalar dependencias frontend y generar assets, o publicar un build ya generado.
4. Configurar el archivo `.env` de producción.
5. Ejecutar migraciones con `--force`.
6. Generar cachés de Laravel.
7. Configurar el scheduler.
8. Verificar acceso web, assets y tareas programadas.

Comandos habituales:

```bash
composer install --no-dev --optimize-autoloader
npm install
npm run build
php artisan migrate --force
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

Si necesitas limpiar cachés antes:

```bash
php artisan optimize:clear
```

Variables mínimas recomendadas en producción:

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://tu-dominio`
- configuración real de base de datos
- `SESSION_DRIVER`
- `CACHE_STORE`
- `QUEUE_CONNECTION`
- `CONTACTO_EMAIL`

Si usas cookies seguras de sesión, revisa también:

- `SESSION_SECURE_COOKIE=true`
- `SESSION_DOMAIN`

## Scheduler de Laravel

SmartSuper registra tareas programadas en `routes/console.php`. En producción debe ejecutarse el scheduler cada minuto:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

## Verificación técnica

Comandos útiles de comprobación:

```bash
php artisan schedule:list
php artisan catalogo:actualizar --fuente=mercadona
php artisan catalogo:actualizar --fuente=consum
php artisan catalogo:actualizar --fuente=carrefour --solo-importar
```

Conviene revisar:

- que los comandos terminan sin errores
- que los JSON se generan o consumen en `storage/app/scraping`
- que `productos_externos` actualiza `fecha_importacion`
- que el remapeo de productos externos se ejecuta correctamente

## Estado de entrega

Este repositorio corresponde a la rama principal de entrega del proyecto. El README está orientado a facilitar:

- la evaluación funcional del sistema
- la puesta en marcha local
- la comprensión de la arquitectura general
- la validación técnica básica del proyecto

## Notas finales

- La aplicación usa `@vite(...)`, por lo que `public/build/manifest.json` debe existir en producción.
- `storage/` y `bootstrap/cache/` deben ser escribibles por el servidor web.
- Si el servidor no compila frontend, el build debe formar parte del artefacto desplegado.
