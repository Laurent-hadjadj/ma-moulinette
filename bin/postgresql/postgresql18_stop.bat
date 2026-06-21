@echo off

SETLOCAL ENABLEEXTENSIONS

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

@set VERSION=2025-11-30 v1.0.0
@title PostgreSQL 18.1 - stop - %VERSION%
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

set "APP=%~dp0..\environnement"
set "PG_NAME=PostgreSQL-18.1"

set "PG_BIN=%APP%\0_toolz\%PG_NAME%\bin"
set "PG_DATA=%APP%\0_toolz\%PG_NAME%\data"

echo Arrêt PostgreSQL 18.1...
"%PG_BIN%\pg_ctl.exe" -D "%PG_DATA%" stop -m fast

if errorlevel 1 goto ERR
echo PostgreSQL 18.1 arrêté proprement.
goto END

:ERR
echo [ERREUR] Impossible d'arrêter PostgreSQL 18.1.
goto END

:END
ENDLOCAL
exit /b
