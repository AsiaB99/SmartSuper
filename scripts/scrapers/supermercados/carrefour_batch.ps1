param(
    [string]$PostalCode,
    [string]$SalePoint,
    [string]$DeliveryType = "A_DOMICILIO",
    [string]$CookieFile = "scripts/scrapers/supermercados/carrefour_cookie.txt",
    [string]$CategoriesFile = "scripts/scrapers/supermercados/carrefour_categories.txt",
    [string]$OutputDir = "storage/app/scraping/carrefour",
    [int]$PageSize = 24,
    [int]$MaxRetries = 3,
    [int]$RetryDelaySeconds = 3,
    [switch]$ImportToLaravel
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($PostalCode)) {
    throw "Debes indicar -PostalCode."
}

if ([string]::IsNullOrWhiteSpace($SalePoint)) {
    throw "Debes indicar -SalePoint."
}

$projectRoot = Split-Path -Parent (Split-Path -Parent (Split-Path -Parent $PSScriptRoot))
$categoriesPath = Join-Path $projectRoot $CategoriesFile
$cookiePath = Join-Path $projectRoot $CookieFile
$outputPath = Join-Path $projectRoot $OutputDir

if (-not (Test-Path $categoriesPath)) {
    throw "No existe el archivo de categorías: $categoriesPath"
}

if (-not (Test-Path $cookiePath)) {
    throw "No existe el archivo de cookie: $cookiePath"
}

New-Item -ItemType Directory -Force -Path $outputPath | Out-Null

$entries = Get-Content $categoriesPath |
    ForEach-Object { $_.Trim() } |
    Where-Object { $_ -ne "" }

if ($entries.Count -eq 0) {
    throw "No hay categorías válidas en $categoriesPath"
}

$pythonScript = Join-Path $projectRoot "scripts/scrapers/supermercados/carrefour.py"
$artisan = "C:\Users\asiab\.config\herd\bin\php.bat"

$procesadas = 0
$importadas = 0
$fallidas = New-Object System.Collections.Generic.List[string]

foreach ($entry in $entries) {
    $parts = $entry.Split("|", 3)

    if ($parts.Count -ne 3) {
        $fallidas.Add($entry)
        continue
    }

    $categoryId = $parts[0].Trim()
    $apiPath = $parts[1].Trim()
    $refererUrl = $parts[2].Trim()
    $outputFile = Join-Path $outputPath "carrefour-$categoryId-normalizado.json"

    $ok = $false

    for ($attempt = 1; $attempt -le $MaxRetries; $attempt++) {
        Write-Host "Procesando categoría Carrefour $categoryId (intento $attempt/$MaxRetries)..." -ForegroundColor Cyan

        python $pythonScript `
            --category-id $categoryId `
            --api-path $apiPath `
            --postal-code $PostalCode `
            --sale-point $SalePoint `
            --delivery-type $DeliveryType `
            --cookie-file $cookiePath `
            --referer-url $refererUrl `
            --page-size $PageSize `
            --fetch-all-pages `
            --output $outputFile

        if ($LASTEXITCODE -eq 0) {
            $ok = $true
            break
        }

        if ($attempt -lt $MaxRetries) {
            Write-Host "Reintentando $categoryId en $RetryDelaySeconds s..." -ForegroundColor Yellow
            Start-Sleep -Seconds $RetryDelaySeconds
        }
    }

    if (-not $ok) {
        $fallidas.Add($categoryId)
        Write-Host "Falló la categoría $categoryId" -ForegroundColor Red
        continue
    }

    $procesadas++

    if ($ImportToLaravel) {
        & $artisan artisan productos-externos:importar-json $outputFile --fuente=carrefour

        if ($LASTEXITCODE -ne 0) {
            $fallidas.Add("$categoryId (importación)")
            Write-Host "Falló la importación de la categoría $categoryId" -ForegroundColor Red
            continue
        }

        $importadas++
    }
}

Write-Host ""
Write-Host "Resumen:" -ForegroundColor Green
Write-Host "Categorías procesadas: $procesadas"
Write-Host "Categorías importadas: $importadas"
Write-Host "Directorio de salida: $outputPath"

if ($fallidas.Count -gt 0) {
    Write-Host "Fallidas: $($fallidas -join ', ')" -ForegroundColor Yellow
}
