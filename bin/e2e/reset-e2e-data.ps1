# ==============================================================================
# Ma-Moulinette - Reset rapide de l'etat E2E (sans prompt password)
# ==============================================================================
# Usage :
#   .\bin\e2e\reset-e2e-data.ps1
#   .\bin\e2e\reset-e2e-data.ps1 -Quiet      # silencieux (pour beforeAll Playwright)
#
# Avantage vs rebuild-database.ps1 :
#   - Pas de DROP DATABASE (5x plus rapide ~3s vs ~30s)
#   - Pas de prompt password (utilise db_user / db_password de DATABASE_URL)
#   - Equivalent du reset entre tests d'integration Symfony
#
# Conserve : referentiels OWASP, versions ma_moulinette, admin, groupes defaut
# Wipe + reload : 5 utilisateurs E2E, donnees projet, groupes custom
# ==============================================================================

[CmdletBinding()]
param(
    [string] $DbHost     = "localhost",
    [int]    $Port       = 5432,
    [string] $DbUser     = "db_user",
    [string] $DbPassword = "db_password",
    [string] $PsqlPath   = "psql",
    [switch] $Quiet
)

$ErrorActionPreference = "Stop"

# Force UTF-8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$scriptDir     = Split-Path -Parent $MyInvocation.MyCommand.Path
# Script in bin/e2e/ -> remonter 2 niveaux pour atteindre la racine
$projectRoot   = Split-Path -Parent (Split-Path -Parent $scriptDir)
$migrationsDir = Join-Path $projectRoot "migrations\PosgreSQL"
$resetScript   = "95_e2e\reset-e2e-data.sql"

if (-not (Test-Path (Join-Path $migrationsDir $resetScript))) {
    Write-Error "Introuvable : $migrationsDir\$resetScript"
    exit 1
}

if (-not $Quiet) {
    Write-Host "[reset-e2e] Reset DB E2E (db_user, sans prompt)..." -ForegroundColor Cyan
}

# PGPASSWORD evite tout prompt
$env:PGPASSWORD       = $DbPassword
$env:PGCLIENTENCODING = "UTF8"

$psqlArgs = @(
    "-h", $DbHost,
    "-p", $Port,
    "-U", $DbUser,
    "-d", "ma_moulinette",
    "-v", "ON_ERROR_STOP=1",
    "--set=AUTOCOMMIT=on",
    "-f", $resetScript
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
    Write-Host "[reset-e2e] ECHEC (exit $exit)" -ForegroundColor Red
    exit $exit
}

if (-not $Quiet) {
    Write-Host "[reset-e2e] OK" -ForegroundColor Green
}
