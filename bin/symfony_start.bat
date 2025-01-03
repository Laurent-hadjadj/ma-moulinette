@echo off
cls
if "%1"=="" (
    echo Erreur : Aucun paramètre fourni.
    echo Usage : symfony_start.bat [paramètre]
    echo [paramètre] correspond au nom du projet. Ex. ma-moulinette
    exit /b 1
) else (
    set PROJET=%1
)

mode con: cols=160 lines=9999
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

set VERSION=2024-08-28 v1.9.0
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
echo [93m### Attention le fichier doit être encodé en UTF-8 avec une séquence        ###[0m
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
echo:
echo env         : dev
echo lecteur     : %LECTEUR_PATH%
echo version:    : %VERSION%
echo symfony     : 6.4
echo symfony-cli : 5.8.2
echo php         : 8.3.0-NTS
echo nodejs      : 20.17.0
echo:

set app=%lecteur%\environnement
set php=%app%\0_toolz\php-8.3.0-NTS\

set HTTP_PROXY=
set HTTPS_PROXY=

@set PATH=%app%\0_toolz\symfony-cli\current;%php%;%PATH%

@cd %app%\%PROJET%

@rem symfony.exe server:ca:install
@rem https_proxy=http://127.0.0.1:8000 curl https://ma-moulinette.wip

@symfony server:stop
@symfony server:start --no-tls

:exit
@rem  Laurent HADJADJ - 2022-01-25 v1.0.0
@rem  Laurent HADJADJ - 2022-02-20 v1.1.0
@rem  Laurent HADJADJ - 2022-03-28 v1.2.0
@rem  Laurent HADJADJ - 2022-03-29 v1.3.0
@rem  Laurent HADJADJ - 2022-09-07 v1.4.0
@rem  Laurent HADJADJ - 2022-12-01 v1.5.0
@rem  Laurent HADJADJ - 2023-09-18 v1.6.0
@rem  Laurent HADJADJ - 2024-05-13 v1.7.0 - Ajout du lecteur
@rem  Laurent HADJADJ - 2024-05-24 v1.8.0 - tests du lecteur par défaut
@rem  Laurent HADJADJ - 2024-08-01 v1.8.1 - Correction de l'en-tête sur 80 colonnes
@rem  Laurent HADJADJ - 2024-08-28 v1.9.0 - Mise à jour de nodjs en version 20.17.0 LTS
@rem  Laurent HADJADJ - 2024-08-28 v1.9.0 - Passage du nom du projet en paramètre
