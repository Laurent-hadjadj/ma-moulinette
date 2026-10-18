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
    [int] $MaxBatches = 3,
    # Port Postgres cible — 5432 (natif) par défaut. La stack Docker e2e
    # (docker/e2e/docker-compose.e2e.yml) expose son postgres sur 5434 :
    # passer -DbPort 5434 quand on cible Docker (même convention que
    # E2E_DB_PORT cote tests/e2e/helpers/db.ts).
    [int] $DbPort = 5432
)

$ErrorActionPreference = "Stop"

$scriptDir   = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot = Split-Path -Parent (Split-Path -Parent $scriptDir)

$prevErrorAction = $ErrorActionPreference
$ErrorActionPreference = "Continue"
$env:APP_ENV = "test"
# DATABASE_URL de .env.test.local vise localhost:5432 (natif) ; une variable
# d'environnement reelle prime sur le fichier .env (comportement Symfony
# standard) — on la surcharge seulement si on vise un port different.
if ($DbPort -ne 5432) {
    $env:DATABASE_URL = "postgresql://db_user:db_password@localhost:$DbPort/ma_moulinette_test?serverVersion=18&charset=utf8"
}
try {
    & php "$projectRoot\bin\console" app:dependency-check:process --env=test --max-batches=$MaxBatches
    $exit = $LASTEXITCODE
} finally {
    $ErrorActionPreference = $prevErrorAction
    Remove-Item Env:\APP_ENV -ErrorAction SilentlyContinue
    Remove-Item Env:\DATABASE_URL -ErrorAction SilentlyContinue
}

if ($exit -ne 0) {
    Write-Host "[process-dc-queue] ECHEC (exit $exit)" -ForegroundColor Red
    exit $exit
}
