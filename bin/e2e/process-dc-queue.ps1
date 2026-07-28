# ==============================================================================
# Ma-Moulinette - Traite la queue d'ingestion DependencyCheck (E2E)
# ==============================================================================
# Usage :
#   .\bin\e2e\process-dc-queue.ps1
#
# Contexte : l'ingestion DependencyCheck est asynchrone en 2 étapes
# (POST /api/secure/dependency-check/upload enqueue, puis un worker séparé
# `app:dependency-check:process` traite la queue). Aucun cron/supervisor
# n'est démarré par la stack e2e (contrairement à la prod, cf. docker/etc/cron) :
# ce script invoque le worker directement, une fois, en APP_ENV=test — même
# convention que le warmup cache dans run-e2e.ps1 (APP_ENV=test + --env=test).
# ==============================================================================

[CmdletBinding()]
param(
    [int] $MaxBatches = 3
)

$ErrorActionPreference = "Stop"

$scriptDir   = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent (Split-Path -Parent $scriptDir)

$prevErrorAction = $ErrorActionPreference
$ErrorActionPreference = "Continue"
$env:APP_ENV = "test"
try {
    & php "$projectRoot\bin\console" app:dependency-check:process --env=test --max-batches=$MaxBatches
    $exit = $LASTEXITCODE
} finally {
    $ErrorActionPreference = $prevErrorAction
    Remove-Item Env:\APP_ENV -ErrorAction SilentlyContinue
}

if ($exit -ne 0) {
    Write-Host "[process-dc-queue] ECHEC (exit $exit)" -ForegroundColor Red
    exit $exit
}
