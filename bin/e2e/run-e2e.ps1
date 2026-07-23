# ==============================================================================
# Ma-Moulinette - Lancement complet de la suite E2E Playwright (Phase K)
# ==============================================================================
# Usage :
#   .\bin\e2e\run-e2e.ps1                       # tous les specs, headless
#   .\bin\e2e\run-e2e.ps1 -Headed               # avec navigateur visible
#   .\bin\e2e\run-e2e.ps1 -Spec "01-smoke"      # un spec en particulier (substring match)
#   .\bin\e2e\run-e2e.ps1 -SkipRebuild          # garder la DB existante (debug)
#   .\bin\e2e\run-e2e.ps1 -SkipServer           # ne pas demarrer Symfony serve (deja up ailleurs)
#   .\bin\e2e\run-e2e.ps1 -Port 8080            # port custom (defaut 8000)
#
# Workflow :
#   1. Rebuild DB ma_moulinette (sauf -SkipRebuild)
#   2. set APP_ENV=test puis demarre Symfony serve en background
#   3. Attend que le serveur reponde sur /login
#   4. Lance npx playwright test depuis tests/e2e/
#   5. Stoppe Symfony serve a la fin (meme en cas d'echec)
# ==============================================================================

[CmdletBinding()]
param(
    [string] $Spec        = "",
    [int]    $Port        = 8000,
    [switch] $Headed,
    [switch] $SkipRebuild,
    [switch] $SkipServer,
    [int]    $ServerWaitSec = 30
)

$ErrorActionPreference = "Stop"

# Force la console en UTF-8
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8
$OutputEncoding           = [System.Text.Encoding]::UTF8

# Script in bin/e2e/ -> remonter 2 niveaux pour atteindre la racine du projet
$projectRoot = Split-Path -Parent (Split-Path -Parent $PSScriptRoot)
Push-Location $projectRoot

$script:StartedDaemon = $false
$exitCode             = 0

try {
    Write-Host "================================================================" -ForegroundColor Cyan
    Write-Host " Ma-Moulinette - Suite E2E Playwright"                            -ForegroundColor Cyan
    Write-Host "================================================================" -ForegroundColor Cyan
    Write-Host " Spec        : $(if ($Spec) { $Spec } else { '(tous)' })"
    Write-Host " Port        : $Port"
    Write-Host " Headed      : $Headed"
    Write-Host " SkipRebuild : $SkipRebuild"
    Write-Host " SkipServer  : $SkipServer"
    Write-Host "----------------------------------------------------------------"

    # ----------------------------------------------------------------------------
    # 1. Rebuild DB
    # ----------------------------------------------------------------------------
    if (-not $SkipRebuild) {
        Write-Host ""
        Write-Host "[1/4] Rebuild DB ma_moulinette..." -ForegroundColor Yellow
        & "$projectRoot\bin\e2e\rebuild-database.ps1"
        if ($LASTEXITCODE -ne 0) { throw "Rebuild DB en echec (exit $LASTEXITCODE)" }
    } else {
        Write-Host ""
        Write-Host "[1/4] Rebuild DB skip (-SkipRebuild)" -ForegroundColor DarkGray
    }

    # ----------------------------------------------------------------------------
    # 2. Demarrer Symfony serve si necessaire (sinon reutiliser celui qui tourne)
    # ----------------------------------------------------------------------------
    $baseUrl = "http://localhost:$Port"

    function Test-PortOpen {
        param([int]$Port)
        $tcp = New-Object System.Net.Sockets.TcpClient
        try {
            $iar = $tcp.BeginConnect("127.0.0.1", $Port, $null, $null)
            $ok  = $iar.AsyncWaitHandle.WaitOne(500, $false)
            return ($ok -and $tcp.Connected)
        } catch {
            return $false
        } finally {
            $tcp.Close()
        }
    }

    # Symfony CLI verrouille UN SEUL daemon PAR PROJET (dossier), pas par port :
    # si un daemon tourne deja sur un autre port pour ce meme dossier,
    # `symfony serve --daemon --port=X` réussit silencieusement (exit 0) sans
    # rien démarrer sur X. Sans detection en amont, ca dégénère en un timeout
    # de ServerWaitSec sans aucune explication (vérifie en pratique le
    # 22/07/2026 : dev server deja up sur 8000, tentative --port=8001 rendue
    # muette, "Symfony serve injoignable... apres 30s").
    function Get-SymfonyRunningPort {
        $prevErrorAction        = $ErrorActionPreference
        $ErrorActionPreference  = "Continue"
        try {
            $statusOutput = & symfony server:status 2>&1
        } finally {
            $ErrorActionPreference = $prevErrorAction
        }
        $listening = $statusOutput | Select-String -Pattern 'Listening on https?://[^:]+:(\d+)'
        if ($listening) {
            return [int] $listening.Matches[0].Groups[1].Value
        }
        return $null
    }

    if (-not $SkipServer) {
        Write-Host ""

        # Si un serveur tourne deja sur le port, on le reutilise (cas frequent
        # en dev : tu as deja symfony serve dans un autre terminal).
        if (Test-PortOpen -Port $Port) {
            Write-Host "[2/4] Serveur deja up sur port $Port -> on le reutilise." -ForegroundColor Green
            Write-Host "      ATTENTION : verifie qu'il tourne en APP_ENV=test (sinon SonarFixtureClientService inactif)." -ForegroundColor DarkYellow
        } else {
            $runningPort = Get-SymfonyRunningPort
            if ($runningPort -and $runningPort -ne $Port) {
                throw @"
[2/4] Un daemon Symfony CLI tourne deja pour CE projet sur le port $runningPort (probablement votre serveur de dev habituel) alors que le port $Port est demande pour l'E2E.

Symfony CLI n'autorise qu'UN SEUL daemon par projet, quel que soit le port demandé : relancer 'symfony serve --daemon --port=$Port' réussirait silencieusement (exit 0) sans rien démarrer sur $Port, d'ou un timeout de ${ServerWaitSec}s sans explication.

Options :
    - Réutiliser le serveur existant : relancer avec -Port $runningPort (verifier au préalable qu'il tourne en APP_ENV=test, sinon SonarFixtureClientService est inactif)
    - Arrêter le serveur existant si vous n'en avez pas besoin : symfony server:stop
    - Gérer vous-même un serveur de test sur $Port (ex. php -S <host>:$Port) puis relancer avec -SkipServer
"@
            }

            Write-Host "[2/4] Demarrage Symfony serve sur $baseUrl (APP_ENV=test, daemon)..." -ForegroundColor Yellow

            # Nettoyage des php-cgi orphelins d'une session precedente. Sur Windows,
            # quand on tue brutalement symfony serve, son worker php-cgi peut survivre
            # et garder le fichier .symfony5/log/<hash>.log verrouille -> daemon refuse
            # de demarrer ("file is being used by another process").
            $orphans = Get-Process php-cgi -ErrorAction SilentlyContinue
            if ($orphans) {
                Write-Host "      Nettoyage de $($orphans.Count) php-cgi orphelin(s)..." -ForegroundColor DarkYellow
                $orphans | Stop-Process -Force -ErrorAction SilentlyContinue
                Start-Sleep -Milliseconds 500
            }

            # symfony serve --daemon = mode daemon gere par Symfony CLI lui-meme.
            # On capture la sortie pour pouvoir la dump en cas d'echec.
            # NB : PS 5.1 wrap stderr en ErrorRecord avec 2>&1 + ErrorActionPreference=Stop
            #      -> on bascule en Continue le temps de la capture.
            $prevErrorAction        = $ErrorActionPreference
            $ErrorActionPreference  = "Continue"
            $env:APP_ENV            = "test"
            try {
                $serveOutput = & symfony serve --daemon --no-tls --port=$Port 2>&1
                $exitServe   = $LASTEXITCODE
            } finally {
                $ErrorActionPreference = $prevErrorAction
                Remove-Item Env:\APP_ENV -ErrorAction SilentlyContinue
            }

            if ($exitServe -ne 0) {
                Write-Host "      Output symfony :" -ForegroundColor Red
                if ($serveOutput) {
                    $serveOutput | ForEach-Object { Write-Host "        $_" -ForegroundColor DarkRed }
                } else {
                    Write-Host "        (aucune sortie)" -ForegroundColor DarkRed
                }
                throw "symfony serve --daemon a echoue (exit $exitServe)"
            }

            # Marqueur pour stopper le daemon en finally (uniquement si on l'a demarre)
            $script:StartedDaemon = $true

            # Attente que le port soit ouvert
            Write-Host "      Attente du serveur (max ${ServerWaitSec}s)..." -ForegroundColor DarkGray
            $deadline = (Get-Date).AddSeconds($ServerWaitSec)
            $ready    = $false
            while ((Get-Date) -lt $deadline) {
                if (Test-PortOpen -Port $Port) { $ready = $true; break }
                Start-Sleep -Milliseconds 500
            }

            if (-not $ready) {
                throw "Symfony serve injoignable sur port $Port apres ${ServerWaitSec}s"
            }
            Write-Host "      OK serveur pret." -ForegroundColor Green
        }

        # Warmup cache test : evite les 502 sur les assets (PNG, font) au 1er hit
        # (PHP-FPM rame quand il rebuilde le cache + sert les assets en parallele).
        Write-Host "      Warmup cache test..." -ForegroundColor DarkGray
        $env:APP_ENV = "test"
        try {
            & php "$projectRoot\bin\console" cache:warmup --env=test --quiet 2>&1 | Out-Null
            Write-Host "      OK cache pret." -ForegroundColor Green
        } catch {
            Write-Host "      (warning : warmup KO, pas bloquant)" -ForegroundColor DarkYellow
        } finally {
            Remove-Item Env:\APP_ENV -ErrorAction SilentlyContinue
        }
    } else {
        Write-Host ""
        Write-Host "[2/4] Symfony serve skip (-SkipServer). Assurez-vous qu'il tourne deja sur $baseUrl avec APP_ENV=test." -ForegroundColor DarkGray
    }

    # ----------------------------------------------------------------------------
    # 3. Lancer Playwright
    # ----------------------------------------------------------------------------
    Write-Host ""
    Write-Host "[3/4] Lancement Playwright..." -ForegroundColor Yellow

    $playwrightArgs = @("playwright", "test")
    if ($Spec)   { $playwrightArgs += $Spec }
    if ($Headed) { $playwrightArgs += "--headed" }

    Push-Location "$projectRoot\tests\e2e"
    try {
        # E2E_BASE_URL : surcharge le baseURL de playwright.config.ts si port custom
        $env:E2E_BASE_URL = $baseUrl
        & npx @playwrightArgs
        $exitCode = $LASTEXITCODE
    } finally {
        Remove-Item Env:\E2E_BASE_URL -ErrorAction SilentlyContinue
        Pop-Location
    }

    Write-Host ""
    if ($exitCode -eq 0) {
        Write-Host "[4/4] Suite E2E reussie." -ForegroundColor Green
    } else {
        Write-Host "[4/4] Suite E2E en echec (exit $exitCode)." -ForegroundColor Red
        Write-Host "      Voir tests\e2e\playwright-report\index.html" -ForegroundColor DarkGray
    }
}
catch {
    Write-Host ""
    $msg = if ($_.Exception.Message) { $_.Exception.Message } else { "$_" }
    Write-Host "[ERREUR] $msg" -ForegroundColor Red
    if ($_.ScriptStackTrace) {
        Write-Host "         $($_.ScriptStackTrace)" -ForegroundColor DarkGray
    }
    $exitCode = 1
}
finally {
    # ----------------------------------------------------------------------------
    # Cleanup : arret du daemon Symfony seulement si on l'a demarre.
    # Si on a reutilise un serveur existant, on le laisse tourner.
    # ----------------------------------------------------------------------------
    if ($script:StartedDaemon) {
        Write-Host ""
        Write-Host "Arret Symfony serve daemon..." -ForegroundColor DarkGray
        try {
            & symfony server:stop 2>&1 | Out-Null
        } catch {
            Write-Host "  (warning : symfony server:stop a echoue)" -ForegroundColor DarkYellow
        }
    }
    Pop-Location
}

exit $exitCode
