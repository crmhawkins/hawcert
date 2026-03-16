# Script para empaquetar la extensión de Chrome
# Esto crea un archivo .zip que puedes usar para distribuir la extensión

Write-Host "📦 Empaquetando extensión HawCert Auto-Fill..." -ForegroundColor Cyan

# Obtener la ruta del script
$scriptPath = Split-Path -Parent $MyInvocation.MyCommand.Path
$extensionPath = $scriptPath
$outputFile = Join-Path (Split-Path -Parent $extensionPath) "hawcert-extension-v1.0.0.zip"

# Archivos a incluir (excluir documentación y scripts)
$filesToInclude = @(
    "manifest.json",
    "background.js",
    "content.js",
    "popup.html",
    "popup.js",
    "options.html",
    "options.js"
)

# Crear archivo ZIP temporal
$tempZip = Join-Path $env:TEMP "hawcert-temp.zip"

# Eliminar ZIP temporal si existe
if (Test-Path $tempZip) {
    Remove-Item $tempZip -Force
}

# Eliminar ZIP de salida si existe
if (Test-Path $outputFile) {
    Remove-Item $outputFile -Force
}

Write-Host "Agregando archivos al ZIP..." -ForegroundColor Yellow

# Crear ZIP usando .NET
Add-Type -AssemblyName System.IO.Compression.FileSystem
$zip = [System.IO.Compression.ZipFile]::Open($tempZip, [System.IO.Compression.ZipArchiveMode]::Create)

foreach ($file in $filesToInclude) {
    $filePath = Join-Path $extensionPath $file
    if (Test-Path $filePath) {
        [System.IO.Compression.ZipFileExtensions]::CreateEntryFromFile($zip, $filePath, $file) | Out-Null
        Write-Host "  ✓ $file" -ForegroundColor Green
    } else {
        Write-Host "  ✗ $file (no encontrado)" -ForegroundColor Red
    }
}

$zip.Dispose()

# Mover el ZIP temporal al destino final
Move-Item $tempZip $outputFile -Force

Write-Host ""
Write-Host "✅ Extensión empaquetada exitosamente!" -ForegroundColor Green
Write-Host "📁 Archivo creado: $outputFile" -ForegroundColor Cyan
Write-Host ""
Write-Host "Para instalar en Chrome:" -ForegroundColor Yellow
Write-Host "1. Ve a chrome://extensions/" -ForegroundColor White
Write-Host "2. Activa 'Modo de desarrollador'" -ForegroundColor White
Write-Host "3. Haz clic en 'Cargar extensión sin empaquetar'" -ForegroundColor White
Write-Host "4. Selecciona la carpeta chrome-extension (NO el ZIP)" -ForegroundColor White
Write-Host ""
Write-Host "Nota: Para modo desarrollador, NO uses el ZIP, carga la carpeta directamente." -ForegroundColor Gray
