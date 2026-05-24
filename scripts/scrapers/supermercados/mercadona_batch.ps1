param(
    [string]$PostalCode,
    [string]$WarehouseId,
    [string]$Cookie,
    [string]$CustomerDeviceId,
    [string]$XVersion,
    [string]$IdsFile = "scripts/scrapers/supermercados/mercadona_category_ids.txt",
    [string]$OutputDir = "storage/app/scraping/mercadona",
    [switch]$ImportToLaravel
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($PostalCode)) {
    throw "Debes indicar -PostalCode."
}

if ([string]::IsNullOrWhiteSpace($WarehouseId)) {
    throw "Debes indicar -WarehouseId."
}

if ([string]::IsNullOrWhiteSpace($Cookie)) {
    throw "Debes indicar -Cookie."
}

if ([string]::IsNullOrWhiteSpace($CustomerDeviceId)) {
    throw "Debes indicar -CustomerDeviceId."
}

if ([string]::IsNullOrWhiteSpace($XVersion)) {
    throw "Debes indicar -XVersion."
}

$projectRoot = Split-Path -Parent (Split-Path -Parent (Split-Path -Parent $PSScriptRoot))
$idsPath = Join-Path $projectRoot $IdsFile
$outputPath = Join-Path $projectRoot $OutputDir

if (-not (Test-Path $idsPath)) {
    throw "No existe el archivo de IDs: $idsPath"
}

New-Item -ItemType Directory -Force -Path $outputPath | Out-Null

$ids = Get-Content $idsPath |
    ForEach-Object { $_.Trim() } |
    Where-Object { $_ -match '^\d+$' }

if ($ids.Count -eq 0) {
    throw "No hay IDs válidos en $idsPath"
}

$pythonScript = Join-Path $projectRoot "scripts/scrapers/supermercados/mercadona.py"
$artisan = "C:\Users\asiab\.config\herd\bin\php.bat"

$procesadas = 0
$importadas = 0
$fallidas = New-Object System.Collections.Generic.List[string]

foreach ($id in $ids) {
    $categoryUrl = "https://tienda.mercadona.es/categories/$id"
    $outputFile = Join-Path $outputPath "mercadona-$id-normalizado.json"

    Write-Host "Procesando categoría $id..." -ForegroundColor Cyan

    python $pythonScript `
        --category-url $categoryUrl `
        --postal-code $PostalCode `
        --warehouse-id $WarehouseId `
        --cookie $Cookie `
        --customer-device-id $CustomerDeviceId `
        --x-version $XVersion `
        --output $outputFile

    if ($LASTEXITCODE -ne 0) {
        $fallidas.Add($id)
        Write-Host "Falló la categoría $id" -ForegroundColor Red
        continue
    }

    $procesadas++

    if ($ImportToLaravel) {
        & $artisan artisan productos-externos:importar-json $outputFile --fuente=mercadona

        if ($LASTEXITCODE -ne 0) {
            $fallidas.Add("$id (importación)")
            Write-Host "Falló la importación de la categoría $id" -ForegroundColor Red
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
