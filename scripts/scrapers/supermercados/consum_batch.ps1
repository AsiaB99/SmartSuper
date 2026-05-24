param(
    [string]$PostalCode,
    [string]$CategoriesFile = "scripts/scrapers/supermercados/consum_categories.txt",
    [string]$OutputDir = "storage/app/scraping/consum",
    [int]$Limit = 20,
    [int]$OrderById = 5,
    [string]$XTolZone = "0",
    [string]$XTolChannel = "1",
    [string]$XTolLocale = "es",
    [string]$XTolApp = "shop-front",
    [string]$XTolShippingZone = "0D",
    [string]$XTolCurrency = "EUR",
    [switch]$ImportToLaravel
)

$ErrorActionPreference = "Stop"

$projectRoot = Split-Path -Parent (Split-Path -Parent (Split-Path -Parent $PSScriptRoot))
$categoriesPath = Join-Path $projectRoot $CategoriesFile
$outputPath = Join-Path $projectRoot $OutputDir

if (-not (Test-Path $categoriesPath)) {
    throw "No existe el archivo de categorías: $categoriesPath"
}

New-Item -ItemType Directory -Force -Path $outputPath | Out-Null

$entries = Get-Content $categoriesPath |
    ForEach-Object { $_.Trim() } |
    Where-Object { $_ -ne "" }

if ($entries.Count -eq 0) {
    throw "No hay categorías válidas en $categoriesPath"
}

$pythonScript = Join-Path $projectRoot "scripts/scrapers/supermercados/consum.py"
$artisan = "C:\Users\asiab\.config\herd\bin\php.bat"

$procesadas = 0
$importadas = 0
$fallidas = New-Object System.Collections.Generic.List[string]

foreach ($entry in $entries) {
    $parts = $entry.Split("|", 2)

    if ($parts.Count -ne 2) {
        $fallidas.Add($entry)
        continue
    }

    $categoryId = $parts[0].Trim()
    $refererUrl = $parts[1].Trim()
    $outputFile = Join-Path $outputPath "consum-$categoryId-normalizado.json"

    Write-Host "Procesando categoría Consum $categoryId..." -ForegroundColor Cyan

    $command = @(
        $pythonScript,
        "--category-id", $categoryId,
        "--referer-url", $refererUrl,
        "--limit", $Limit,
        "--order-by-id", $OrderById,
        "--x-tol-zone", $XTolZone,
        "--x-tol-channel", $XTolChannel,
        "--x-tol-locale", $XTolLocale,
        "--x-tol-app", $XTolApp,
        "--x-tol-shipping-zone", $XTolShippingZone,
        "--x-tol-currency", $XTolCurrency,
        "--fetch-all-pages",
        "--output", $outputFile
    )

    if (-not [string]::IsNullOrWhiteSpace($PostalCode)) {
        $command += @("--postal-code", $PostalCode)
    }

    python @command

    if ($LASTEXITCODE -ne 0) {
        $fallidas.Add($categoryId)
        Write-Host "Falló la categoría $categoryId" -ForegroundColor Red
        continue
    }

    $procesadas++

    if ($ImportToLaravel) {
        & $artisan artisan productos-externos:importar-json $outputFile --fuente=consum

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
