# Scrapers

Scripts auxiliares de ingesta de precios para SmartSuper.

## Estructura

- `supermercados/mercadona.py`: scraper base de categoría para Mercadona.

## Flujo recomendado

1. Abrir la categoría en el navegador después de seleccionar el código postal.
2. Abrir `Network` y localizar la llamada `api/categories/...`.
3. Copiar la respuesta JSON o llamar al endpoint con el contexto de sesión.
4. Ejecutar el script y producir JSON normalizado.
5. Importar ese JSON desde Laravel.

## Ejemplo con JSON guardado

```bash
python scripts/scrapers/supermercados/mercadona.py ^
  --json-file storage/app/scraping/mercadona-category-112.json ^
  --postal-code 04720 ^
  --warehouse-id 4410
```

## Ejemplo contra la API de Mercadona

```bash
python scripts/scrapers/supermercados/mercadona.py ^
  --category-url https://tienda.mercadona.es/categories/112 ^
  --postal-code 04720 ^
  --warehouse-id 4410 ^
  --cookie "__mo_da={\"warehouse\":\"4410\",\"postalCode\":\"04720\"}; __mo_ui={\"language\":\"es\"}" ^
  --customer-device-id "cebd17f1-1803-4f58-8205-72aa88dc2f89R" ^
  --x-version "v8851"
```

## Lote de categorías de Mercadona

Puedes procesar muchas categorías de una vez con:

```powershell
.\scripts\scrapers\supermercados\mercadona_batch.ps1 `
  -PostalCode 04720 `
  -WarehouseId 4410 `
  -Cookie '__mo_da={"warehouse":"4410","postalCode":"04720"}; __mo_ui={"language":"es"}' `
  -CustomerDeviceId 'cebd17f1-1803-4f58-8205-72aa88dc2f89R' `
  -XVersion 'v8851' `
  -ImportToLaravel
```

Por defecto lee los IDs desde `scripts/scrapers/supermercados/mercadona_category_ids.txt`
y guarda los JSON en `storage/app/scraping/mercadona/`.

## Consum

Ejemplo con una categoría de Consum:

```powershell
python scripts\scrapers\supermercados\consum.py `
  --category-id 2811 `
  --postal-code 46001 `
  --fetch-all-pages `
  --output storage\app\scraping\consum-2811-normalizado.json
```

Si necesitas replicar exactamente el contexto visto en DevTools, también puedes ajustar:
- `--referer-url`
- `--x-tol-zone`
- `--x-tol-channel`
- `--x-tol-locale`
- `--x-tol-app`
- `--x-tol-shipping-zone`
- `--x-tol-currency`

## Lote de categorías de Consum

Puedes procesar varias categorías de Consum con:

```powershell
.\scripts\scrapers\supermercados\consum_batch.ps1 `
  -PostalCode 46001 `
  -ImportToLaravel
```

Por defecto lee las categorías desde `scripts/scrapers/supermercados/consum_categories.txt`
y guarda los JSON en `storage/app/scraping/consum/`.

## Carrefour

Ejemplo con una categoría de Carrefour:

```powershell
python scripts\scrapers\supermercados\carrefour.py `
  --category-id cat20018 `
  --api-path 'supermercado/frescos/carne/cat20018/c' `
  --postal-code 28232 `
  --sale-point 005290 `
  --delivery-type A_DOMICILIO `
  --cookie-file scripts\scrapers\supermercados\carrefour_cookie.txt `
  --referer-url 'https://www.carrefour.es/supermercado/frescos/carne/cat20018/c' `
  --output storage\app\scraping\carrefour-cat20018-normalizado.json
```

Notas para Carrefour:
- Copia la cabecera `cookie:` desde DevTools y pégala en `scripts/scrapers/supermercados/carrefour_cookie.txt`.
- Tienes una plantilla en `scripts/scrapers/supermercados/carrefour_cookie.txt.example`.
- Si una categoría no es de carne, cambia `--api-path` y `--referer-url` por la ruta real vista en `Network`.
- Si el endpoint tiene paginación por `offset`, añade `--fetch-all-pages`.

## Lote de categorías de Carrefour

Puedes procesar varias categorías de Carrefour con:

```powershell
.\scripts\scrapers\supermercados\carrefour_batch.ps1 `
  -PostalCode 28232 `
  -SalePoint 005290 `
  -ImportToLaravel
```

Por defecto lee las categorías desde `scripts/scrapers/supermercados/carrefour_categories.txt`
y la cookie desde `scripts/scrapers/supermercados/carrefour_cookie.txt`.

## Importar JSON ya generados

Si ya has generado los JSON y no quieres volver a scrapear, puedes importarlos por directorio:

```powershell
.\scripts\scrapers\import_existing_jsons.ps1 `
  -InputDir storage\app\scraping\carrefour
```

Si quieres forzar la fuente durante esa importación:

```powershell
.\scripts\scrapers\import_existing_jsons.ps1 `
  -InputDir storage\app\scraping\consum `
  -Fuente consum
```

## Orquestación diaria desde Laravel

El flujo completo de scraping + importación + remapeo ya puede ejecutarse desde Laravel con:

```powershell
php artisan catalogo:actualizar
```

Opciones útiles:

```powershell
php artisan catalogo:actualizar --fuente=mercadona
php artisan catalogo:actualizar --solo-importar
php artisan catalogo:actualizar --solo-scraping
```

Configuración:
- Los parámetros por cadena viven en `.env` bajo el prefijo `CATALOGO_EXTERNO_*`.
- El scheduler diario se activa con `CATALOGO_EXTERNO_SCHEDULER_ENABLED=true`.
- Cada cadena usa un único contexto en esta primera versión.

Diagnóstico básico:
- Si falla una cadena, el comando sigue con las demás y lo refleja en el resumen final.
- Los logs quedan bajo el canal estándar con eventos `catalogo_externo.*` y `productos_externos.importacion.resumen`.

## Scheduler diario en producción

Una vez validadas manualmente las fuentes y activado `CATALOGO_EXTERNO_SCHEDULER_ENABLED=true`, el servidor debe ejecutar el scheduler de Laravel cada minuto.

Linux cron:

```cron
* * * * * cd /ruta/al/proyecto && php artisan schedule:run >> /dev/null 2>&1
```

Verificación:

```bash
php artisan schedule:list
```

La salida esperada incluye:

```text
0 0 * * *  php artisan catalogo:actualizar
```

## Ubicaciones de supermercados desde OpenStreetMap

Para generar un JSON normalizado de supermercados de España:

```powershell
python scripts\scrapers\supermercados\osm_supermercados.py `
  --area España `
  --output storage\app\scraping\osm-supermercados-espana.json
```

Después importa el archivo en Laravel:

```powershell
php artisan supermercados:importar-ubicaciones storage\app\scraping\osm-supermercados-espana.json --fuente=osm
```

Notas:
- OSM usa `shop=supermarket` para supermercados.

