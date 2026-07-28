# ==============================================================================
# Ma-Moulinette - Génère var/admin-stats.json + migrations/admin-stats.json (E2E)
# ==============================================================================
# Usage :
#   .\bin\e2e\refresh-admin-stats.ps1
#
# Contexte : /statistiques/dashboard lit var/admin-stats.json (gitignoré) puis
# migrations/admin-stats.json en repli — les deux sont absents par défaut, la
# page affiche alors des statistiques figées. Ce script invoque
# `app:admin:refresh-stats` (cloc + phpunit --list-tests) pour que le spec 16
# puisse aussi exercer le chemin "vraies données" du dashboard.
# ==============================================================================

$ErrorActionPreference = "Stop"

$scriptDir   = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent (Split-Path -Parent $scriptDir)

$prevErrorAction = $ErrorActionPreference
$ErrorActionPreference = "Continue"
$env:APP_ENV = "test"
try {
    & php "$projectRoot\bin\console" app:admin:refresh-stats --env=test
    $exit = $LASTEXITCODE
} finally {
    $ErrorActionPreference = $prevErrorAction
    Remove-Item Env:\APP_ENV -ErrorAction SilentlyContinue
}

if ($exit -ne 0) {
    Write-Host "[refresh-admin-stats] ECHEC (exit $exit)" -ForegroundColor Red
    exit $exit
}
