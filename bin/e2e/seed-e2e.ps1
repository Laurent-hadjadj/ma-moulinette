# ==============================================================================
# Ma-Moulinette - Run a single E2E seed SQL file (no password prompt)
# ==============================================================================
# Usage :
#   .\bin\e2e\seed-e2e.ps1 -File 95_e2e\seed-after-spec-02-groupes.sql
#
# Pratique pour replicer l'etat de fin d'une spec via SQL, plutot que rejouer
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
    [string] $PsqlPath   = "psql",
    [switch] $Quiet
)

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

$scriptDir     = Split-Path -Parent $MyInvocation.MyCommand.Path
# Script in bin/e2e/ -> remonter 2 niveaux pour atteindre la racine
$projectRoot   = Split-Path -Parent (Split-Path -Parent $scriptDir)
$migrationsDir = Join-Path $projectRoot "migrations\PosgreSQL"

if (-not (Test-Path (Join-Path $migrationsDir $File))) {
    Write-Error "Introuvable : $migrationsDir\$File"
    exit 1
}

if (-not $Quiet) {
    Write-Host "[seed-e2e] Run SQL : $File" -ForegroundColor Cyan
}

$env:PGPASSWORD       = $DbPassword
$env:PGCLIENTENCODING = "UTF8"

$psqlArgs = @(
    "-h", $DbHost,
    "-p", $Port,
    "-U", $DbUser,
    "-d", "ma_moulinette",
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
