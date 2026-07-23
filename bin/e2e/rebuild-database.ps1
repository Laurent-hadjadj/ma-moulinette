# ==============================================================================
# Ma-Moulinette - Rebuild complet de la base PostgreSQL DE TEST (ma_moulinette_test)
# ==============================================================================
# Usage :
#   .\bin\e2e\rebuild-database.ps1                    # defaut: localhost/postgres
#   .\bin\e2e\rebuild-database.ps1 -DbHost "srv-db"
#   .\bin\e2e\rebuild-database.ps1 -Port 5433 -SuperUser "admin"
#
# Prerequis :
#   - psql accessible dans le PATH (ou fournir -PsqlPath)
#   - Un super-utilisateur PostgreSQL capable de DROP/CREATE DATABASE
#   - Le role "db_user" doit deja exister sur le cluster (Crée lors de
#     l'installation de la base "ma_moulinette" réelle) : ce script ne le
#     crée jamais, il le réutilise comme owner de "ma_moulinette_test".
#
# MODIF 2026-07-22 : ce script construisait auparavant "ma_moulinette" (le nom
# de la vraie base de dev/prod), en executant migrations/POSTGRESQL/99_master_install.sql
# tel quel via \ir. c’était dangereux à double titre :
#   1. Le script de drop appelé en premier (00_init/00_drop_database.sql) fait
#      un DROP DATABASE IF EXISTS ma_moulinette WITH (FORCE) — donc DROP de la
#      vraie base de dev locale, avec déconnexion forcée de toute session active
#      (ex. un `symfony serve` en cours) — ET un DROP ROLE IF EXISTS db_user,
#      qui échoue si ce role possède encore des objets dans la vraie base
#      (donc casse la base réelle sans meme réussir à recréer le role après).
#   2. Meme en supposant que ca réussisse, .env.test.local fait pointer
#      APP_ENV=test vers ma_moulinette_test (jamais construite par ce chemin),
#      pas vers ma_moulinette — donc le serveur de test ne se serait jamais
#      connecte a la base fraîchement reconstruite.
#
# Ce script lit désormais les VRAIS fichiers SQL de migrations/POSTGRESQL/ (via
# la liste ordonnée de 99_master_install.sql) et réécrit à la volée, en mémoire,
# toute reference a "ma_moulinette" (base, schema, recherche datname=...) vers
# "ma_moulinette_test" — sans jamais modifier les fichiers sur disque, et sans
# jamais émettre le moindre DROP/CREATE sur la base "ma_moulinette" réelle. La
# creation/suppression du role "db_user" est explicitement exclue (le role est
# partage entre les deux bases et doit deja exister).
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
# ET chcp 65001 pour être certain.
$previousOutputEncoding      = [Console]::OutputEncoding
$previousPSOutputEncoding    = $OutputEncoding
$previousCodePage            = (chcp) -replace '[^0-9]', ''
[Console]::OutputEncoding    = [System.Text.Encoding]::UTF8
$OutputEncoding              = [System.Text.Encoding]::UTF8
chcp 65001 | Out-Null

# Localise le repertoire migrations/POSTGRESQL a partir de ce script
# Script in bin/e2e/ -> remonter 2 niveaux pour atteindre la racine
$scriptDir      = Split-Path -Parent $MyInvocation.MyCommand.Path
$projectRoot    = Split-Path -Parent (Split-Path -Parent $scriptDir)
$migrationsDir  = Join-Path $projectRoot "migrations\POSTGRESQL"
$masterScript   = Join-Path $migrationsDir "99_master_install.sql"

if (-not (Test-Path $masterScript)) {
    Write-Error "Introuvable : $masterScript"
    exit 1
}

Write-Host "================================================================" -ForegroundColor Cyan
Write-Host " Ma-Moulinette - Rebuild PostgreSQL DE TEST (ma_moulinette_test)" -ForegroundColor Cyan
Write-Host "================================================================" -ForegroundColor Cyan
Write-Host " Host       : $DbHost"
Write-Host " Port       : $Port"
Write-Host " SuperUser  : $SuperUser"
Write-Host " Migrations : $migrationsDir"
Write-Host "----------------------------------------------------------------"

# ------------------------------------------------------------------------
# 1. Extrait la liste ordonnée des fichiers \ir de 99_master_install.sql,
#    en excluant explicitement le drop/create de role (00_init/00 et 00_init/01)
#    — la base de test réutilise le role "db_user" existant, jamais recrée ici.
# ------------------------------------------------------------------------
$masterContent = Get-Content -Path $masterScript -Raw -Encoding UTF8
$irMatches     = [regex]::Matches($masterContent, '(?m)^\\ir\s+(\S+)')
$excluded      = @('00_init/00_drop_database.sql', '00_init/01_create_roles.sql')
$relativeFiles = $irMatches | ForEach-Object { $_.Groups[1].Value } | Where-Object { $excluded -notcontains $_ }

Write-Host "[1/3] $($relativeFiles.Count) fichiers SQL a assembler (role db_user exclu, reutilise tel quel)..." -ForegroundColor Yellow

# ------------------------------------------------------------------------
# 2. Concatène le contenu reel de chaque fichier, en réécrivant UNIQUEMENT le
#    NOM DE BASE "ma_moulinette" -> "ma_moulinette_test" (connexions \c,
#    CREATE/DROP/ALTER/COMMENT DATABASE, ALTER ROLE ... IN DATABASE, filtre
#    datname='...' de get_pg_stat_activity()).
#
#    IMPORTANT : le SCHEMA reste nomme "ma_moulinette" (jamais renomme), y
#    compris dans la base de test — vérifie que chaque entité Doctrine porte
#    #[ORM\Table(schema: 'ma_moulinette', ...)] en dur (ex. src/Entity/Historique.php),
#    sans lien avec la variable DATABASE_SCHEMA de .env.test.local (qui n'est
#    en réalité lue nulle part dans le code — grep vide sur config/ et src/).
#    Donc "CREATE SCHEMA ma_moulinette", "ma_moulinette.table" et le
#    "search_path = ma_moulinette, public" ne sont PAS touches ici.
# ------------------------------------------------------------------------
$sb = New-Object System.Text.StringBuilder
[void]$sb.AppendLine("\c postgres;")
[void]$sb.AppendLine("\echo ===== 0. Drop ma_moulinette_test (si existante) =====")
[void]$sb.AppendLine("DROP DATABASE IF EXISTS ma_moulinette_test WITH (FORCE);")

foreach ($rel in $relativeFiles) {
    $path = Join-Path $migrationsDir ($rel -replace '/', '\')
    if (-not (Test-Path $path)) {
        Write-Error "Fichier reference par 99_master_install.sql introuvable : $rel"
        exit 1
    }
    $content = Get-Content -Path $path -Raw -Encoding UTF8
    $content = $content -replace '(?m)^\\c ma_moulinette\b', '\c ma_moulinette_test'
    $content = $content -replace '(?i)\bCREATE DATABASE ma_moulinette\b', 'CREATE DATABASE ma_moulinette_test'
    $content = $content -replace '(?i)\bDROP DATABASE IF EXISTS ma_moulinette\b', 'DROP DATABASE IF EXISTS ma_moulinette_test'
    $content = $content -replace '(?i)\bALTER DATABASE ma_moulinette\b', 'ALTER DATABASE ma_moulinette_test'
    $content = $content -replace '(?i)\bIN DATABASE ma_moulinette\b', 'IN DATABASE ma_moulinette_test'
    $content = $content -replace '(?i)\bCOMMENT ON DATABASE ma_moulinette\b', 'COMMENT ON DATABASE ma_moulinette_test'
    $content = $content -replace "datname = 'ma_moulinette'", "datname = 'ma_moulinette_test'"
    [void]$sb.AppendLine("\echo ===== $rel =====")
    [void]$sb.AppendLine($content)
}

$tempSql = Join-Path $env:TEMP "ma_moulinette_test_install_$([guid]::NewGuid().ToString('N')).sql"
[System.IO.File]::WriteAllText($tempSql, $sb.ToString(), (New-Object System.Text.UTF8Encoding($false)))

Write-Host "[2/3] Script assemble : $tempSql" -ForegroundColor Yellow

# ------------------------------------------------------------------------
# 3. Execute le script assemble.
# ------------------------------------------------------------------------
$psqlArgs = @(
    "-h", $DbHost,
    "-p", $Port,
    "-U", $SuperUser,
    "-v", "ON_ERROR_STOP=1",
    "--set=AUTOCOMMIT=on",
    "-f", $tempSql
)

# Force psql a lire les fichiers en UTF-8 (sinon Windows FR = WIN1252 et les
# emojis dans les commentaires cassent l'import).
$env:PGCLIENTENCODING = "UTF8"

Write-Host "[3/3] Execution psql..." -ForegroundColor Yellow

# NB : psql écrit ses NOTICE (ex. DROP TABLE IF EXISTS sur une table absente)
# sur stderr. En PowerShell 5.1, si la sortie de cette invocation est elle-meme
# redirigée/capturée par l'appelant (pipeline, run_in_background, > fichier),
# chaque ligne stderr est enveloppée en NativeCommandError -> avec
# $ErrorActionPreference = "Stop" (fixe en tête de ce script), un simple NOTICE
# inoffensif devient une exception fatale qui interrompt le rebuild avant meme
# que psql n'ait fini, meme si son exit code final aurait ete 0. On bascule
# donc temporairement en "Continue" le temps de l'appel (meme pattern que
# "symfony serve --daemon" dans run-e2e.ps1), et on juge le succès uniquement
# sur $LASTEXITCODE.
$prevErrorAction       = $ErrorActionPreference
$ErrorActionPreference = "Continue"
try {
    & $PsqlPath @psqlArgs
    $exit = $LASTEXITCODE
}
finally {
    $ErrorActionPreference = $prevErrorAction
    Remove-Item $tempSql -ErrorAction SilentlyContinue
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
Write-Host "[OK] Rebuild termine. Base ma_moulinette_test prête (ma_moulinette réelle jamais touchée)." -ForegroundColor Green
Write-Host "     Utilisateurs fixtures charges :"
Write-Host "       - admin@ma-moulinette.fr (ROLE_INTERNAL, prod)"
Write-Host "       - interne@ma-moulinette.fr (ROLE_INTERNAL, E2E bootstrap)"
Write-Host "       - josh, nathan, sophie, aurelie (ROLE_NONE disabled, E2E)"
