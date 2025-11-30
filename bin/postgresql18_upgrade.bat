@echo off
SETLOCAL ENABLEEXTENSIONS ENABLEDELAYEDEXPANSION

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Détection du lecteur (script lecteur.bat obligatoire)
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

set filename=lecteur.bat
for %%d in (C D E F G H I J K L M N O P Q R S T U V W X Y Z) do (
    if exist %%d:\environnement\%filename% (
        call %%d:\environnement\lecteur.bat
        goto :detected
    )
)

echo Le fichier %filename% n'a pas été trouvé sur les disques disponibles.
goto :exit

:detected
@mode con: cols=160 lines=70
@color 0f
@CHCP 65001

set VERSION=2025-11-30 v1.0.0
title Upgrade PostgreSQL 15.6 → 18.1  - %VERSION%

echo ".. __  __             __  __             _              _   _       "
echo "  |  \/  | __ _      |  \/  | ___  _   _| (_)_ __   ___| |_| |_ ___ "
echo "  | |\/| |/ _` |_____| |\/| |/ _ \| | | | | | '_ \ / _ \ __| __/ _ \"
echo "  | |  | | (_| |_____| |  | | (_) | |_| | | | | | |  __/ |_| ||  __/"
echo "  |_|  |_|\__,_|     |_|  |_|\___/ \__,_|_|_|_| |_|\___|\__|\__\___|"
echo:
echo    Laurent HADJADJ
echo    https://github.com/Laurent-hadjadj/ma-moulinette
echo    © 2024-2025 - CC BY-SA-NC 4.0
echo:
echo [93m###                                                                         ###[0m
echo [93m### Attention le fichier doit être encodé en UTF-8 avec BOM et              ###[0m
echo [93m### une séquence  de fin de ligne Windows (CRLF).                           ###[0m
echo [93m###                                                                         ###[0m
echo:
@cls

@echo *****************************************
@echo  Upgrade database to PostgreSQL 18.1
@echo  Laurent HADJADJ - %VERSION%
@echo *****************************************
@echo:

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: Définition des chemins
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

@set app=%LECTEUR%\environnement
@set OLD_PostgreSQL=PostgreSQL-15.6-1
@set NEW_PostgreSQL=PostgreSQL-18.1

@set OLD_BIN=%app%\0_toolz\%OLD_PostgreSQL%\bin
@set OLD_DATA=%app%\0_toolz\%OLD_PostgreSQL%\data
@set NEW_BIN=%app%\0_toolz\%NEW_PostgreSQL%\bin
@set NEW_DATA=%app%\0_toolz\%NEW_PostgreSQL%\data

set "OLD_HBA=%OLD_DATA%\pg_hba.conf"
set "OLD_HBA_BAK=%OLD_DATA%\pg_hba.conf.bak"

set "NEW_HBA=%NEW_DATA%\pg_hba.conf"
set "NEW_HBA_BAK=%NEW_DATA%\pg_hba.conf.bak"

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: DEBUG
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

echo --- DEBUG PATHS ---
echo OLD_BIN=[%OLD_BIN%]
echo OLD_DATA=[%OLD_DATA%]
echo OLD_HBA=[%OLD_HBA%]
echo NEW_BIN=[%NEW_BIN%]
echo NEW_DATA=[%NEW_DATA%]
echo NEW_HBA=[%NEW_HBA%]
echo --------------------
echo.

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: INITDB PG18 SI PAS DEJA INITIALISE
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

if not exist "%NEW_DATA%" (
  echo Creation dossier data PG18...
  mkdir "%NEW_DATA%"
)

if exist "%NEW_DATA%\PG_VERSION" goto INIT18_DONE

echo Nettoyage dossier PG18...
rmdir /S /Q "%NEW_DATA%" >nul 2>&1
mkdir "%NEW_DATA%"

echo Initialisation cluster PG18...
"%NEW_BIN%\initdb.exe" -D "%NEW_DATA%" -U postgres -A scram-sha-256 -E UTF8 --pwfile="%APP%\pwd.txt"
if errorlevel 1 goto INIT18_ERR
goto INIT18_DONE

:INIT18_ERR
echo [ERREUR] initdb 18.1 a echoue.
goto EXIT_SCRIPT

:INIT18_DONE
echo [PG18] initdb OK.
echo.

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: PATCH PG15 : TRUST TEMPORAIRE
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

echo [PG15] Sauvegarde pg_hba.conf...
copy "%OLD_HBA%" "%OLD_HBA_BAK%" >nul
if errorlevel 1 goto HBA15_BACKUP_ERR

echo [PG15] Ajout TRUST temporaire...
(
  echo # TEMP TRUST FOR PG_UPGRADE
  echo local   all   all                 trust
  echo host    all   all   127.0.0.1/32  trust
  echo host    all   all   ::1/128       trust
  type "%OLD_HBA%"
) > "%OLD_HBA%.tmp"

move /Y "%OLD_HBA%.tmp" "%OLD_HBA%" >nul
if errorlevel 1 goto HBA15_PATCH_ERR

goto HBA15_OK

:HBA15_BACKUP_ERR
echo [ERREUR] Impossible de sauvegarder pg_hba.conf PG15.
goto EXIT_SCRIPT

:HBA15_PATCH_ERR
echo [ERREUR] Impossible de patcher pg_hba.conf PG15.
goto EXIT_SCRIPT

:HBA15_OK
echo [PG15] pg_hba.conf patched OK.
echo.

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: PATCH PG18 : TRUST TEMPORAIRE
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

echo [PG18] Sauvegarde pg_hba.conf...
copy "%NEW_HBA%" "%NEW_HBA_BAK%" >nul
if errorlevel 1 goto HBA18_BACKUP_ERR

echo [PG18] Ajout TRUST temporaire...
(
  echo # TEMP TRUST FOR PG_UPGRADE
  echo local   all   all                 trust
  echo host    all   all   127.0.0.1/32  trust
  echo host    all   all   ::1/128       trust
  type "%NEW_HBA%"
) > "%NEW_HBA%.tmp"

move /Y "%NEW_HBA%.tmp" "%NEW_HBA%" >nul
if errorlevel 1 goto HBA18_PATCH_ERR

goto HBA18_OK

:HBA18_BACKUP_ERR
echo [ERREUR] Impossible de sauvegarder pg_hba.conf PG18.
goto EXIT_SCRIPT

:HBA18_PATCH_ERR
echo [ERREUR] Impossible de patcher pg_hba.conf PG18.
goto EXIT_SCRIPT

:HBA18_OK
echo [PG18] pg_hba.conf patched OK.
echo.

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: STOP PG15 + VERIFICATION CHECKSUMS
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

echo [PG15] Arret du serveur...
"%OLD_BIN%\pg_ctl.exe" -D "%OLD_DATA%" stop -m fast >nul 2>&1

echo [PG15] Verification checksums...
"%OLD_BIN%\pg_checksums.exe" --check --pgdata="%OLD_DATA%" >nul 2>&1
set RC=%ERRORLEVEL%

if "%RC%"=="0" goto CHECKSUM_OK
if "%RC%"=="1" goto CHECKSUM_ENABLE
goto CHECKSUM_ERR

:CHECKSUM_ENABLE
echo [PG15] Activation des checksums...
"%OLD_BIN%\pg_checksums.exe" --enable --pgdata="%OLD_DATA%"
if errorlevel 1 goto CHECKSUM_ACT_ERR
goto CHECKSUM_OK

:CHECKSUM_OK
echo [PG15] Checksums OK.
goto DO_UPGRADE

:CHECKSUM_ERR
echo [ERREUR] Impossible de lire l'état des checksums.
goto RESTORE_HBAS

:CHECKSUM_ACT_ERR
echo [ERREUR] Echec d'activation des checksums.
goto RESTORE_HBAS

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: UPGRADE 15 → 18
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:DO_UPGRADE
echo ----- Upgrade PostgreSQL 15 → 18 -----
"%NEW_BIN%\pg_upgrade.exe" ^
  --old-bindir="%OLD_BIN%" ^
  --new-bindir="%NEW_BIN%" ^
  --old-datadir="%OLD_DATA%" ^
  --new-datadir="%NEW_DATA%" ^
  -U postgres

if errorlevel 1 goto UPGRADE_ERR

echo.
echo [OK] Upgrade complete !
goto RESTORE_HBAS

:UPGRADE_ERR
echo [ERREUR] pg_upgrade a echoue.
goto RESTORE_HBAS

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: RESTAURATION DES PG_HBA.CONF
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:RESTORE_HBAS
echo [PG15] Restauration pg_hba.conf...
copy /Y "%OLD_HBA_BAK%" "%OLD_HBA%" >nul
echo [PG18] Restauration pg_hba.conf...
copy /Y "%NEW_HBA_BAK%" "%NEW_HBA%" >nul

echo.
echo [OK] Fichiers pg_hba.conf restaurés.
echo.
goto EXIT_SCRIPT

::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::
:: FIN
::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::::

:EXIT_SCRIPT
ENDLOCAL
exit /b
