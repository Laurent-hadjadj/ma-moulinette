# =============================================================================
#  Ma-Moulinette - Capture des fixtures SonarQube pour les tests Functional
# =============================================================================
#
#  But :
#    Interroger le serveur SonarQube local (version 2026) avec le projet
#    de reference 'tetris:TetrisGame' et enregistrer les reponses JSON dans
#    tests/fixtures/sonarqube/ pour alimenter le MockHttpClient.
#
#  Prerequis :
#    - SonarQube 2026 joignable sur SONAR_URL
#    - Le projet 'tetris:TetrisGame' analyse (existe dans SonarQube)
#    - SONAR_TOKEN valide (scope projet minimum)
#    - Le fichier .env.test.local definit SONAR_URL + SONAR_TOKEN
#
#  Usage :
#    powershell -ExecutionPolicy Bypass -File bin/e2e/capture-sonar-fixtures.ps1
#
#  Recapture :
#    Re-executer le script ecrase les fichiers .json existants.
#
#  Les fixtures suivantes sont produites :
#    tests/fixtures/sonarqube/issues/search.json
#    tests/fixtures/sonarqube/hotspots/search.json
#    tests/fixtures/sonarqube/hotspots/show.json
#    tests/fixtures/sonarqube/project_analyses/search-page1.json
#    tests/fixtures/sonarqube/components/app.json
#    tests/fixtures/sonarqube/measures/component.json
# =============================================================================

$ErrorActionPreference = 'Stop'

# --- Chemin racine du projet (parent de bin/) -------------------------------
$projectRoot = Split-Path -Parent $PSScriptRoot
Set-Location $projectRoot

# --- Chargement des variables d'env depuis .env.test.local -------------------
$envFile = Join-Path $projectRoot '.env.test.local'
if (-not (Test-Path $envFile)) {
    Write-Host "[ERREUR] $envFile introuvable" -ForegroundColor Red
    exit 1
}

$sonarUrl = $null
$sonarToken = $null
foreach ($line in Get-Content $envFile) {
    if ($line -match '^\s*SONAR_URL\s*=\s*(.+?)\s*$')   { $sonarUrl   = $Matches[1].Trim("'", '"') }
    if ($line -match '^\s*SONAR_TOKEN\s*=\s*(.+?)\s*$') { $sonarToken = $Matches[1].Trim("'", '"') }
}

if (-not $sonarUrl -or -not $sonarToken) {
    Write-Host "[ERREUR] SONAR_URL ou SONAR_TOKEN manquant dans $envFile" -ForegroundColor Red
    exit 1
}

$sonarUrl = $sonarUrl.TrimEnd('/')
Write-Host "[INFO] SonarQube : $sonarUrl" -ForegroundColor Cyan

# --- Projet cible ------------------------------------------------------------
$componentKey = 'tetris:TetrisGame'
$outputDir = Join-Path $projectRoot 'tests/fixtures/sonarqube'

# Creation de l'arborescence
$subDirs = @('issues', 'hotspots', 'project_analyses', 'components', 'measures')
foreach ($d in $subDirs) {
    $path = Join-Path $outputDir $d
    New-Item -ItemType Directory -Force -Path $path | Out-Null
}

# --- Auth SonarQube : Basic avec token comme user (convention standard) ------
$authPair = "{0}:" -f $sonarToken
$authB64 = [Convert]::ToBase64String([Text.Encoding]::ASCII.GetBytes($authPair))
$headers = @{ Authorization = "Basic $authB64"; Accept = 'application/json' }

# SSL bypass pour localhost si HTTPS (hérité de la config ma-moulinette)
if ($sonarUrl.StartsWith('https://')) {
    Add-Type @"
using System.Net;
using System.Security.Cryptography.X509Certificates;
public class TrustAllCertsPolicy : ICertificatePolicy {
    public bool CheckValidationResult(ServicePoint sp, X509Certificate cert, WebRequest req, int err) { return true; }
}
"@
    [System.Net.ServicePointManager]::CertificatePolicy = New-Object TrustAllCertsPolicy
    [System.Net.ServicePointManager]::SecurityProtocol = [System.Net.SecurityProtocolType]::Tls12
}

# --- Helper de capture -------------------------------------------------------
function Invoke-SonarCapture {
    param(
        [string]$Endpoint,
        [hashtable]$Query,
        [string]$OutputFile,
        [string]$Label
    )

    $queryString = ($Query.GetEnumerator() | ForEach-Object {
        "{0}={1}" -f $_.Key, [Uri]::EscapeDataString([string]$_.Value)
    }) -join '&'

    $url = "$sonarUrl$Endpoint`?$queryString"
    Write-Host "  -> $Label" -ForegroundColor Yellow
    Write-Host "    GET $url" -ForegroundColor DarkGray

    try {
        $response = Invoke-WebRequest -Uri $url -Headers $headers -Method Get -UseBasicParsing -TimeoutSec 20
        $pretty = $response.Content | ConvertFrom-Json | ConvertTo-Json -Depth 20
        Set-Content -Path $OutputFile -Value $pretty -Encoding UTF8
        $size = '{0:N0}' -f (Get-Item $OutputFile).Length
        Write-Host "    [OK] $OutputFile ($size octets)" -ForegroundColor Green
        return $response.Content | ConvertFrom-Json
    } catch {
        Write-Host "    [FAIL] $($_.Exception.Message)" -ForegroundColor Red
        return $null
    }
}

Write-Host ""
Write-Host "=== Capture des fixtures pour $componentKey ===" -ForegroundColor Cyan

# --- 1. /api/issues/search ---------------------------------------------------
Invoke-SonarCapture `
    -Endpoint '/api/issues/search' `
    -Query @{
        componentKeys = $componentKey
        types         = 'BUG,VULNERABILITY,CODE_SMELL'
        statuses      = 'OPEN,CONFIRMED,REOPENED'
        ps            = '100'
        p             = '1'
    } `
    -OutputFile (Join-Path $outputDir 'issues/search.json') `
    -Label 'issues/search'

# --- 2. /api/hotspots/search -------------------------------------------------
$hotspotsResp = Invoke-SonarCapture `
    -Endpoint '/api/hotspots/search' `
    -Query @{
        projectKey = $componentKey
        status     = 'TO_REVIEW'
        ps         = '100'
        p          = '1'
    } `
    -OutputFile (Join-Path $outputDir 'hotspots/search.json') `
    -Label 'hotspots/search'

# --- 3. /api/hotspots/show ---------------------------------------------------
if ($hotspotsResp -and $hotspotsResp.hotspots -and $hotspotsResp.hotspots.Count -gt 0) {
    $firstHotspotKey = $hotspotsResp.hotspots[0].key
    Invoke-SonarCapture `
        -Endpoint '/api/hotspots/show' `
        -Query @{ hotspot = $firstHotspotKey } `
        -OutputFile (Join-Path $outputDir 'hotspots/show.json') `
        -Label "hotspots/show (hotspot=$firstHotspotKey)"
} else {
    Write-Host "  -> hotspots/show : aucun hotspot trouve - fixture non generee" -ForegroundColor DarkYellow
}

# --- 4. /api/project_analyses/search (page 1) -------------------------------
Invoke-SonarCapture `
    -Endpoint '/api/project_analyses/search' `
    -Query @{
        project = $componentKey
        ps      = '500'
        p       = '1'
    } `
    -OutputFile (Join-Path $outputDir 'project_analyses/search-page1.json') `
    -Label 'project_analyses/search page 1'

# --- 5. /api/components/app --------------------------------------------------
Invoke-SonarCapture `
    -Endpoint '/api/components/app' `
    -Query @{ component = $componentKey } `
    -OutputFile (Join-Path $outputDir 'components/app.json') `
    -Label 'components/app'

# --- 6. /api/measures/component ---------------------------------------------
$metricKeys = @(
    'alert_status','ncloc','lines','files','classes','functions','statements',
    'comment_lines','comment_lines_density',
    'complexity','cognitive_complexity',
    'coverage','branch_coverage','line_coverage','lines_to_cover','conditions_to_cover','uncovered_conditions',
    'tests','test_execution_time','test_errors','test_failures','skipped_tests','test_success_density',
    'duplicated_blocks','duplicated_files','duplicated_lines','duplicated_lines_density',
    'open_issues','reopened_issues','confirmed_issues','false_positive_issues','accepted_issues','high_impact_accepted_issues',
    'violations','blocker_violations','critical_violations','major_violations','minor_violations','info_violations',
    'software_quality_blocker_issues','software_quality_high_issues','software_quality_medium_issues','software_quality_low_issues','software_quality_info_issues',
    'code_smells','sqale_index','sqale_rating','sqale_debt_ratio',
    'bugs','reliability_rating','reliability_remediation_effort',
    'vulnerabilities','security_rating','security_remediation_effort',
    'security_hotspots','security_hotspots_reviewed','security_review_rating'
) -join ','

Invoke-SonarCapture `
    -Endpoint '/api/measures/component' `
    -Query @{
        component  = $componentKey
        metricKeys = $metricKeys
    } `
    -OutputFile (Join-Path $outputDir 'measures/component.json') `
    -Label 'measures/component'

# --- Recap -------------------------------------------------------------------
Write-Host ""
Write-Host "=== Recapitulatif ===" -ForegroundColor Cyan
Get-ChildItem -Path $outputDir -Recurse -File -Filter *.json |
    Sort-Object FullName |
    ForEach-Object {
        $relative = $_.FullName.Substring($projectRoot.Length + 1).Replace('\', '/')
        $size = '{0,8:N0} octets' -f $_.Length
        Write-Host "  $size  $relative" -ForegroundColor DarkGray
    }

Write-Host ""
Write-Host "[OK] Capture terminee. Les fichiers sont commitables (tests/fixtures/sonarqube/)." -ForegroundColor Green
