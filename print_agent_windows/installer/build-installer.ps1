param(
  [string]$Runtime = "win-x64",
  [string]$Configuration = "Release"
)

$ErrorActionPreference = "Stop"

$root = Split-Path -Parent $PSScriptRoot
$project = Join-Path $root "SoftmorPrintAgent.csproj"
$publishDir = Join-Path $root "publish\$Runtime"
$issPath = Join-Path $PSScriptRoot "SoftmorPrintAgent.iss"

Write-Host "[1/3] Publicando agente..." -ForegroundColor Cyan
dotnet publish $project -c $Configuration -r $Runtime --self-contained true -p:PublishSingleFile=true -p:IncludeNativeLibrariesForSelfExtract=true -o $publishDir

$inno = "${env:ProgramFiles(x86)}\Inno Setup 6\ISCC.exe"
if (!(Test-Path $inno)) {
  $inno = "${env:ProgramFiles}\Inno Setup 6\ISCC.exe"
}

if (!(Test-Path $inno)) {
  throw "No se encontró Inno Setup. Instálalo desde https://jrsoftware.org/isdl.php"
}

Write-Host "[2/3] Compilando instalador..." -ForegroundColor Cyan
Push-Location $PSScriptRoot
& $inno $issPath
Pop-Location

Write-Host "[3/3] Listo." -ForegroundColor Green
Write-Host "Instalador generado en: $PSScriptRoot\SoftmorPrintAgentSetup.exe"
