# ==============================================================================
# Ma-Moulinette - Run a single E2E seed SQL file (no password prompt)
# ==============================================================================
# Usage :
#   .\bin\e2e\seed-e2e.ps1 -File 95_e2e\seed-after-spec-02-groupes.sql
#
# Pratique pour replier l'état de fin d'une spec via SQL, plutôt que rejouer
# l'UI (equivalent fixtures Doctrine en integration Symfony).
# ==============================================================================

[CmdletBinding()]
param(
    [Parameter(Mandatory=$true)]
    [string] $File,
    [string] $DbHost     = "localhost",
    [int]    $Port       = 5432,
    [string] $DbUser     = "db_user",
    [string] $DbPassword = "db_password",
    [string] $DbName     = "ma_moulinette_test",
    [string] $PsqlPath   = "psql",
    [switch] $AllowProd,
    [switch] $Quiet
)

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

# GARDE-FOU CRITIQUE : refus explicite de cibler la base de PROD
if ($DbName -eq "ma_moulinette" -and -not $AllowProd) {
    Write-Host "[seed-e2e] REFUS : DbName='ma_moulinette' est la base de PROD." -ForegroundColor Red
    Write-Host "[seed-e2e]        Utilisez -DbName ma_moulinette_test (défaut), ou -AllowProd pour forcer." -ForegroundColor Red
    exit 2
}

$scriptDir     = Split-Path -Parent $MyInvocation.MyCommand.Path
# Script in bin/e2e/ -> remonter 2 niveaux pour atteindre la racine
$projectRoot   = Split-Path -Parent (Split-Path -Parent $scriptDir)
$migrationsDir = Join-Path $projectRoot "migrations\POSTGRESQL"

if (-not (Test-Path (Join-Path $migrationsDir $File))) {
    Write-Error "Introuvable : $migrationsDir\$File"
    exit 1
}

if (-not $Quiet) {
    Write-Host "[seed-e2e] Run SQL '$File' sur '$DbName'" -ForegroundColor Cyan
}

$env:PGPASSWORD       = $DbPassword
$env:PGCLIENTENCODING = "UTF8"

$psqlArgs = @(
    "-h", $DbHost,
    "-p", $Port,
    "-U", $DbUser,
    "-d", $DbName,
    "-v", "ON_ERROR_STOP=1",
    "--set=AUTOCOMMIT=on",
    "-f", $File
)

if ($Quiet) { $psqlArgs += "-q" }

Push-Location $migrationsDir
try {
    if ($Quiet) {
        & $PsqlPath @psqlArgs | Out-Null
    } else {
        & $PsqlPath @psqlArgs
    }
    $exit = $LASTEXITCODE
}
finally {
    Pop-Location
    Remove-Item Env:\PGPASSWORD       -ErrorAction SilentlyContinue
    Remove-Item Env:\PGCLIENTENCODING -ErrorAction SilentlyContinue
}

if ($exit -ne 0) {
    Write-Host "[seed-e2e] ECHEC (exit $exit)" -ForegroundColor Red
    exit $exit
}
if (-not $Quiet) {
    Write-Host "[seed-e2e] OK" -ForegroundColor Green
}
