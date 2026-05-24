<?php

declare(strict_types=1);

return [
    'scheduler_enabled' => (bool) env('CATALOGO_EXTERNO_SCHEDULER_ENABLED', false),
    'powershell_binary' => env('CATALOGO_EXTERNO_POWERSHELL_BINARY', 'C:\Program Files\PowerShell\7\pwsh.exe'),
    'process_timeout' => (int) env('CATALOGO_EXTERNO_PROCESS_TIMEOUT', 3600),
    'sources' => [
        'mercadona' => [
            'enabled' => (bool) env('CATALOGO_EXTERNO_MERCADONA_ENABLED', false),
            'script' => 'scripts/scrapers/supermercados/mercadona_batch.ps1',
            'output_dir' => env('CATALOGO_EXTERNO_MERCADONA_OUTPUT_DIR', 'storage/app/scraping/mercadona'),
            'ids_file' => env('CATALOGO_EXTERNO_MERCADONA_IDS_FILE', 'scripts/scrapers/supermercados/mercadona_category_ids.txt'),
            'arguments' => [
                'PostalCode' => env('CATALOGO_EXTERNO_MERCADONA_POSTAL_CODE'),
                'WarehouseId' => env('CATALOGO_EXTERNO_MERCADONA_WAREHOUSE_ID'),
                'Cookie' => env('CATALOGO_EXTERNO_MERCADONA_COOKIE'),
                'CustomerDeviceId' => env('CATALOGO_EXTERNO_MERCADONA_CUSTOMER_DEVICE_ID'),
                'XVersion' => env('CATALOGO_EXTERNO_MERCADONA_X_VERSION'),
                'IdsFile' => env('CATALOGO_EXTERNO_MERCADONA_IDS_FILE', 'scripts/scrapers/supermercados/mercadona_category_ids.txt'),
                'OutputDir' => env('CATALOGO_EXTERNO_MERCADONA_OUTPUT_DIR', 'storage/app/scraping/mercadona'),
            ],
        ],
        'consum' => [
            'enabled' => (bool) env('CATALOGO_EXTERNO_CONSUM_ENABLED', false),
            'script' => 'scripts/scrapers/supermercados/consum_batch.ps1',
            'output_dir' => env('CATALOGO_EXTERNO_CONSUM_OUTPUT_DIR', 'storage/app/scraping/consum'),
            'categories_file' => env('CATALOGO_EXTERNO_CONSUM_CATEGORIES_FILE', 'scripts/scrapers/supermercados/consum_categories.txt'),
            'arguments' => [
                'PostalCode' => env('CATALOGO_EXTERNO_CONSUM_POSTAL_CODE'),
                'WarehouseId' => env('CATALOGO_EXTERNO_CONSUM_WAREHOUSE_ID'),
                'CategoriesFile' => env('CATALOGO_EXTERNO_CONSUM_CATEGORIES_FILE', 'scripts/scrapers/supermercados/consum_categories.txt'),
                'OutputDir' => env('CATALOGO_EXTERNO_CONSUM_OUTPUT_DIR', 'storage/app/scraping/consum'),
                'Limit' => (int) env('CATALOGO_EXTERNO_CONSUM_LIMIT', 20),
                'OrderById' => (int) env('CATALOGO_EXTERNO_CONSUM_ORDER_BY_ID', 5),
                'XTolZone' => env('CATALOGO_EXTERNO_CONSUM_X_TOL_ZONE', '0'),
                'XTolChannel' => env('CATALOGO_EXTERNO_CONSUM_X_TOL_CHANNEL', '1'),
                'XTolLocale' => env('CATALOGO_EXTERNO_CONSUM_X_TOL_LOCALE', 'es'),
                'XTolApp' => env('CATALOGO_EXTERNO_CONSUM_X_TOL_APP', 'shop-front'),
                'XTolShippingZone' => env('CATALOGO_EXTERNO_CONSUM_X_TOL_SHIPPING_ZONE', '0D'),
                'XTolCurrency' => env('CATALOGO_EXTERNO_CONSUM_X_TOL_CURRENCY', 'EUR'),
            ],
        ],
        'carrefour' => [
            'enabled' => (bool) env('CATALOGO_EXTERNO_CARREFOUR_ENABLED', false),
            'script' => 'scripts/scrapers/supermercados/carrefour_batch.ps1',
            'output_dir' => env('CATALOGO_EXTERNO_CARREFOUR_OUTPUT_DIR', 'storage/app/scraping/carrefour'),
            'categories_file' => env('CATALOGO_EXTERNO_CARREFOUR_CATEGORIES_FILE', 'scripts/scrapers/supermercados/carrefour_categories.txt'),
            'cookie_file' => env('CATALOGO_EXTERNO_CARREFOUR_COOKIE_FILE', 'scripts/scrapers/supermercados/carrefour_cookie.txt'),
            'arguments' => [
                'PostalCode' => env('CATALOGO_EXTERNO_CARREFOUR_POSTAL_CODE'),
                'SalePoint' => env('CATALOGO_EXTERNO_CARREFOUR_SALE_POINT'),
                'DeliveryType' => env('CATALOGO_EXTERNO_CARREFOUR_DELIVERY_TYPE', 'A_DOMICILIO'),
                'CookieFile' => env('CATALOGO_EXTERNO_CARREFOUR_COOKIE_FILE', 'scripts/scrapers/supermercados/carrefour_cookie.txt'),
                'CategoriesFile' => env('CATALOGO_EXTERNO_CARREFOUR_CATEGORIES_FILE', 'scripts/scrapers/supermercados/carrefour_categories.txt'),
                'OutputDir' => env('CATALOGO_EXTERNO_CARREFOUR_OUTPUT_DIR', 'storage/app/scraping/carrefour'),
                'PageSize' => (int) env('CATALOGO_EXTERNO_CARREFOUR_PAGE_SIZE', 24),
                'MaxRetries' => (int) env('CATALOGO_EXTERNO_CARREFOUR_MAX_RETRIES', 3),
                'RetryDelaySeconds' => (int) env('CATALOGO_EXTERNO_CARREFOUR_RETRY_DELAY_SECONDS', 3),
            ],
        ],
    ],
];
