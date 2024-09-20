@echo off
cls
if "%1"=="" (
    echo Erreur : Aucun paramètre fourni.
    echo Usage : encore.bat [parametre]
    echo [parametre] correspond au nom du projet. Ex. ma-moulinette
    exit /b 1
) else (
    set PROJET=%1
)

mode con: cols=160 lines=70
color 0f
CHCP 65001

set filename=lecteur.bat
set found=0
for %%d in (C D E F G H I J K L M N O P Q R S T U V W X Y Z) do (
    if exist %%d:\environnement\%filename% (
        set found=1
        set LECTEUR_PATH=%%d
        goto :found
    )
)

:found
if %found%==0 (
echo Le fichier %filename% n'a pas été trouvé sur les disques disponibles.
goto :exit
) else (
call  %%d:\environnement\lecteur.bat
)

set VERSION=2024-09-09-v1.7.1
title Laurent HADJADJ - version %VERSION%
echo ".. __  __             __  __             _              _   _       "
echo "  |  \/  | __ _      |  \/  | ___  _   _| (_)_ __   ___| |_| |_ ___ "
echo "  | |\/| |/ _` |_____| |\/| |/ _ \| | | | | | '_ \ / _ \ __| __/ _ \"
echo "  | |  | | (_| |_____| |  | | (_) | |_| | | | | | |  __/ |_| ||  __/"
echo "  |_|  |_|\__,_|     |_|  |_|\___/ \__,_|_|_|_| |_|\___|\__|\__\___|"
echo:
echo    Laurent HADJADJ
echo    https://github.com/Laurent-hadjadj/ma-moulinette
echo    © 2024 - CC BY-SA-NC 4.0
echo:
echo [93m###                                                                         ###[0m
echo [93m### Atention le fichier doit être encodé en UTF-8 avec une séquence         ###[0m
echo [93m### de fin de ligne Windows (CRLF).                                         ###[0m
echo [93m###                                                                         ###[0m
echo:

if exist %LECTEUR_PATH%:\environnement\%PROJET%\ (
    goto :info
    ) else (
        echo [91m    _____ Le projet %PROJET% n’a pas été trouvé... _____[0m
    exit /b 1
)

:info
echo Env         : dev
echo lecteur     : %LECTEUR_PATH%
echo version:    : %VERSION%
echo symfony     : 6.4
echo symfony-cli : 5.8.2
echo php         : 8.3.0-NTS
echo nodejs      : 20.17.1

echo:

@set app=%lecteur%\environnement
@set php=%app%\0_toolz\php-8.3.0-NTS\
@set nodejs=%app%\0_toolz\node-20.17.1\

@set PATH=%app%\0_toolz\symfony-cli\current;%php%;%nodejs%;%PATH%
@cd %app%\%PROJET%
@npm run watch

:exit
@rem Laurent HADJADJ - 2022-01-25 v1.0.0
@rem Laurent HADJADJ - 2022-03-28 v1.1.0
@rem Laurent HADJADJ - 2022-03-29 v1.1.1
@rem Laurent HADJADJ - 2022-05-04 v1.1.2
@rem Laurent HADJADJ - 2022-09-07 v1.2.0
@rem Laurent HADJADJ - 2022-12-01 v1.3.0
@rem Laurent HADJADJ - 2023-09-18 v1.4.0
@rem Laurent HADJADJ - 2024-05-13 v1.5.0 - Ajout du lecteur
@rem Laurent HADJADJ - 2024-05-24 v1.6.0 - Tests du lecteur par défaut
@rem Laurent HADJADJ - 2024-08-01 v1.6.1 - Correction de l'en-tête sur 80 colonnes
@rem Laurent HADJADJ - 2024-08-28 v1.7.0 - Mise à jour de nodejs en version 20.17.0 LTS
@rem Laurent HADJADJ - 2024-08-28 v1.7.0 - Passage du nom du projet en paramètre
@rem Laurent HADJADJ - 2024-09-09 v1.7.1 - Mise à jour nodejs en 20.17.1 LTS
