# ==============================================================================
# Ma-Moulinette - Rebuild complet de la base PostgreSQL
# ==============================================================================
# Usage :
#   .\bin\e2e\rebuild-database.ps1                    # defaut: localhost/postgres
#   .\bin\e2e\rebuild-database.ps1 -DbHost "srv-db"
#   .\bin\e2e\rebuild-database.ps1 -Port 5433 -SuperUser "admin"
#
# Prerequis :
#   - psql accessible dans le PATH (ou fournir -PsqlPath)
#   - Un super-utilisateur PostgreSQL capable de DROP DATABASE + CREATE ROLE
#
# Ce script drop + recree la base "ma_moulinette" en executant
# migrations/PosgreSQL/99_master_install.sql depuis son repertoire, pour que
# les \ir (include relative) resolvent correctement.
# ==============================================================================

[CmdletBinding()]
param(
    [string] $DbHost    = "localhost",
    [int]    $Port      = 5432,
    [string] $SuperUser = "postgres",
    [string] $PsqlPath  = "psql"
)

$ErrorActionPreference = "Stop"

# Force la console en UTF-8 pour afficher correctement les accents/emojis
# retournes par psql (sinon affichage en CP850 = "Ã©" au lieu de "e").
# En PowerShell 5.1, il faut a la fois [Console]::OutputEncoding ET $OutputEncoding
# ET chcp 65001 pour etre certain.
$previousOutputEncoding      = [Console]::OutputEncoding
$previousPSOutputEncoding    = $OutputEncoding
$previousCodePage            = (chcp) -replace '[^0-9]', ''
[Console]::OutputEncoding    = [System.Text.Encoding]::UTF8
$OutputEncoding              = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

# Localise le repertoire migrations/PosgreSQL a partir de ce script
# Script in bin/e2e/ -> remonter 2 niveaux pour atteindre la racine
$scriptDir      = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot    = Split-Path -Parent (Split-Path -Parent $scriptDir)
$migrationsDir  = Join-Path $projectRoot "migrations\PosgreSQL"
$masterScript   = "99_master_install.sql"

if (-not (Test-Path (Join-Path $migrationsDir $masterScript))) {
    Write-Error "Introuvable : $migrationsDir\$masterScript"
    exit 1
}

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host " Ma-Moulinette - Rebuild PostgreSQL"                              -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host " Host       : $DbHost"
Write-Host " Port       : $Port"
Write-Host " SuperUser  : $SuperUser"
Write-Host " Migrations : $migrationsDir"
Write-Host "----------------------------------------------------------------"

# psql -v ON_ERROR_STOP=1 : arret a la premiere erreur SQL
# --set=AUTOCOMMIT=on     : chaque fichier peut faire des \c (reconnect)

$psqlArgs = @(
    "-h", $DbHost,
    "-p", $Port,
    "-U", $SuperUser,
    "-v", "ON_ERROR_STOP=1",
    "--set=AUTOCOMMIT=on",
    "-f", $masterScript
)

# Force psql a lire les fichiers en UTF-8 (sinon Windows FR = WIN1252 et les
# emojis dans les commentaires cassent l'import).
$env:PGCLIENTENCODING = "UTF8"

Push-Location $migrationsDir
try {
    & $PsqlPath @psqlArgs
    $exit = $LASTEXITCODE
}
finally {
    Pop-Location
    Remove-Item Env:\PGCLIENTENCODING -ErrorAction SilentlyContinue
    [Console]::OutputEncoding = $previousOutputEncoding
    $OutputEncoding = $previousPSOutputEncoding
    if ($previousCodePage) { chcp $previousCodePage | Out-Null }
}

if ($exit -ne 0) {
    Write-Host ""
    Write-Host "[ECHEC] Exit code $exit" -ForegroundColor Red
    exit $exit
}

Write-Host ""
Write-Host "[OK] Rebuild termine. Base ma_moulinette prete." -ForegroundColor Green
Write-Host "     Utilisateurs fixtures charges :"
Write-Host "       - admin@ma-moulinette.fr (ROLE_INTERNAL, prod)"
Write-Host "       - interne@ma-moulinette.fr (ROLE_INTERNAL, E2E bootstrap)"
Write-Host "       - josh, nathan, sophie, aurelie (ROLE_NONE disabled, E2E)"
