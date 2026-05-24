param(
    [string]$InputDir,
    [string]$Pattern = "*.json",
    [string]$Fuente
)

$ErrorActionPreference = "Stop"

if ([string]::IsNullOrWhiteSpace($InputDir)) {
    throw "Debes indicar -InputDir."
}

$projectRoot = Split-Path -Parent $PSScriptRoot
$resolvedInputDir = if ([System.IO.Path]::IsPathRooted($InputDir)) {
    $InputDir
} else {
    Join-Path $projectRoot $InputDir
}

if (-not (Test-Path $resolvedInputDir)) {
    throw "No existe el directorio: $resolvedInputDir"
}

$artisan = "C:\Users\asiab\.config\herd\bin\php.bat"
$files = Get-ChildItem -Path $resolvedInputDir -Filter $Pattern | Sort-Object Name

if ($files.Count -eq 0) {
    throw "No se encontraron archivos con patrón '$Pattern' en $resolvedInputDir"
}

$importados = 0
$fallidos = New-Object System.Collections.Generic.List[string]

foreach ($file in $files) {
    Write-Host "Importando $($file.Name)..." -ForegroundColor Cyan
    $argumentos = @("artisan", "productos-externos:importar-json", $file.FullName)

    if (-not [string]::IsNullOrWhiteSpace($Fuente)) {
        $argumentos += "--fuente=$Fuente"
    }

    & $artisan @argumentos

    if ($LASTEXITCODE -ne 0) {
        $fallidos.Add($file.Name)
        Write-Host "Falló $($file.Name)" -ForegroundColor Red
        continue
    }

    $importados++
}

Write-Host ""
Write-Host "Resumen:" -ForegroundColor Green
Write-Host "Archivos importados: $importados"
Write-Host "Directorio: $resolvedInputDir"

if ($fallidos.Count -gt 0) {
    Write-Host "Fallidos: $($fallidos -join ', ')" -ForegroundColor Yellow
}
