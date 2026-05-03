# ==============================================================================
# Ma-Moulinette - Audit residus E2E dans une base
# ==============================================================================
# Usage :
#   .\bin\e2e\audit-prod-leak.ps1                       # par defaut : ma_moulinette (PROD)
#   .\bin\e2e\audit-prod-leak.ps1 -DbName ma_moulinette_test  # verifier la base de test
#
# Lit-only (SELECT). Compte les marqueurs E2E typiques attendus uniquement
# dans la base de test :
#   - utilisateurs josh / nathan / sophie / aurelie / interne @ ma-moulinette.fr
#   - projet tetris:TetrisGame, tag tetris-game
#   - groupe fonctionnel tetris-game
#
# Sortie : compte par categorie + exit code 1 si un marqueur trouve en prod.
# ==============================================================================

[CmdletBinding()]
param(
    [string] $DbHost     = "localhost",
    [int]    $Port       = 5432,
    [string] $DbUser     = "db_user",
    [string] $DbPassword = "db_password",
    [string] $DbName     = "ma_moulinette",
    [string] $PsqlPath   = "psql"
)

$ErrorActionPreference = "Stop"
[Console]::OutputEncoding = [System.Text.Encoding]::UTF8

Write-Host "[audit] Cible : $DbName sur $DbHost`:$Port" -ForegroundColor Cyan

$env:PGPASSWORD       = $DbPassword
$env:PGCLIENTENCODING = "UTF8"

# Une requete par marqueur pour faciliter la lecture du rapport.
$queries = @(
    @{ Name = "users E2E (josh/nathan/sophie/aurelie/interne)"; Sql = @"
SELECT COUNT(*) FROM utilisateur
WHERE courriel IN (
  'interne@ma-moulinette.fr',
  'josh.liberman@ma-moulinette.fr',
  'nathan.jones@ma-moulinette.fr',
  'sophie.martin@ma-moulinette.fr',
  'aurelie.petit-coeur@ma-moulinette.fr'
);
"@ },
    @{ Name = "projet tetris:TetrisGame"; Sql = "SELECT COUNT(*) FROM liste_projet WHERE maven_key = 'tetris:TetrisGame';" },
    @{ Name = "groupe fonctionnel 'tetris-game'"; Sql = "SELECT COUNT(*) FROM groupe_fonctionnel WHERE titre = 'tetris-game';" },
    @{ Name = "users portant tag tetris-game (liste_groupe_fonctionnel)"; Sql = "SELECT COUNT(*) FROM utilisateur WHERE liste_groupe_fonctionnel::text LIKE '%tetris-game%';" }
)

$totalLeak = 0
$report = @()

try {
    foreach ($q in $queries) {
        $output = & $PsqlPath -h $DbHost -p $Port -U $DbUser -d $DbName -t -A -c $q.Sql 2>&1
        if ($LASTEXITCODE -ne 0) {
            Write-Host "[audit] ECHEC requete : $($q.Name)" -ForegroundColor Red
            Write-Host $output -ForegroundColor Red
            exit 3
        }
        $count = [int]($output | Select-Object -First 1)
        $totalLeak += $count
        $report += [PSCustomObject]@{ Marker = $q.Name; Count = $count }
    }
}
finally {
    Remove-Item Env:\PGPASSWORD       -ErrorAction SilentlyContinue
    Remove-Item Env:\PGCLIENTENCODING -ErrorAction SilentlyContinue
}

Write-Host ""
$report | Format-Table -AutoSize -Wrap

if ($DbName -eq "ma_moulinette" -and $totalLeak -gt 0) {
    Write-Host "[audit] FUITE : $totalLeak marqueur(s) E2E detecte(s) en PROD." -ForegroundColor Red
    Write-Host "[audit] Nettoyer manuellement (les marqueurs au-dessus indiquent quoi)." -ForegroundColor Yellow
    exit 1
}

if ($totalLeak -eq 0) {
    Write-Host "[audit] OK : aucun marqueur E2E dans '$DbName'." -ForegroundColor Green
} else {
    Write-Host "[audit] $totalLeak marqueur(s) detecte(s) (normal sur '$DbName' si base de test)." -ForegroundColor Cyan
}
exit 0
