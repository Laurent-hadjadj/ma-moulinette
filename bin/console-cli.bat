@echo off
cls
if "%1"=="" (
    echo Erreur : Aucun paramètre fourni.
    echo Usage : console-cli.bat [parametre]
    echo [parametre] correspond au nom du projet. Ex. ma-moulinette
    exit /b 1
) else (
    set PROJET=%1
)

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
call  %%d:\environnement\lecteur.bat
)

mode con: cols=160 lines=70
rem background : noir, color: blanc
color 0f
CHCP 65001
set VERSION=2024-09-19 v1.12.0
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
echo projet		: [93m%PROJET%[0m
echo env		: [93mdev[0m
echo lecteur		: [93m%LECTEUR_PATH%[0m
echo version		: [93m%VERSION%[0m
echo Symfony		: [93m6.4.7[0m
echo Symfony-cli	: [93m5.8.2[0m
echo php		: [93m8.3.0-NTS[0m
echo git		: [93m2.44.1[0m
echo nodejs		: [93m20.17.0[0m
echo python		: [93m3.12.3[0m
echo maven		: [93m3.8.8[0m
echo jdk		: [93m17[0m
echo posgresql	: [93m15.6[0m
echo sonarqube	: [93m9.9.4-LTS[0m
echo:

set app=%LECTEUR%\environnement
set SCRIPT_PATH=%app%\ma-moulinette\bin\
set SYMFONY_PATH=%app%\0_toolz\symfony-cli\current\
set PHP_PATH=%app%\0_toolz\php-8.3.0-NTS
set NODEJS_PATH=%app%\0_toolz\node-20.17.1
set PYTHON_PATH=%app%\0_toolz\python-3.12.3-embed
set PIP_PATH=%app%\0_toolz\python-3.12.3-embed\Scripts
set JDK_PATH=%app%\0_toolz\jdk17
set MAVEN_PATH=%app%\0_toolz\apache-maven-3.8.8
set POSTGRESQL_PATH=%app%\0_toolz\postgresql-15.6-1
set RABBITMQ_PATH=%app%\0_toolz\rabbitmq-3.13.1
set GIT_PATH=%app%\0_toolz\Git-2.44.0\bin

set HTTP_PROXY=
set HTTPS_PROXY=

echo HTTP_PROXY : %HTTP_PROXY%
echo HTTPS_PROXY : %HTTPS_PROXY%

set JAVA_TOOL_OPTIONS=-Dfile.encoding=UTF8
set JAVA_HOME=%JDK_PATH%

set PATH=%SCRIPT_PATH%;%SYMFONY_PATH%;%GIT_PATH%;%PHP_PATH%;%NODEJS_PATH%;%MAVEN_PATH%\bin;%JAVA_HOME%\bin;%POSTGRESQL_PATH%\bin;%PYTHON_PATH%;%PIP_PATH%;%RABBITMQ_PATH%/sbin;%PATH%

cd %app%\%1

:exit
@rem Laurent HADJADJ - 2022-01-25 v1.0.0
@rem Laurent HADJADJ - 2022-03-28 v1.1.0
@rem Laurent HADJADJ - 2022-03-29 v1.1.1
@rem Laurent HADJADJ - 2022-05-04 v1.1.2
@rem Laurent HADJADJ - 2022-09-07 v1.2.0
@rem Laurent HADJADJ - 2022-12-01 v1.3.0
@rem Laurent HADJADJ - 2023-09-18 v1.4.0
@rem Laurent HADJADJ - 2024-04-12 v1.5.0 - Refactoring des différents scripts : normalisation des variables + maj des programmes
@rem Laurent HADJADJ - 2024-04-13 v1.6.0 - Ajout du logo + call lecteur.bat
@rem Laurent HADJADJ - 2024-05-15 v1.7.0 - Ajout de python
@rem Laurent HADJADJ - 2024-05-23 v1.8.0 - Ajout dans le path des scripts tools
@rem Laurent HADJADJ - 2024-05-24 v1.9.0 - tests du lecteur par défaut
@rem Laurent HADJADJ - 2024-06-16 v1.10.0 - Ajout du path pour rabbitMQ
@rem Laurent HADJADJ - 2024-08-28 v1.11.0 - Ajout du path pour Git
@rem Laurent HADJADJ - 2024-08-28 v1.11.0 - Mise à jour de nodjs en version 20.17.0 LTS
@rem Laurent HADJADJ - 2024-08-28 v1.11.0 - Passage du nom du projet en paramètre
@rem Laurent HADJADJ - 2024-09-09 v1.11.1 - Mise à jour nodejs en 20.17.1 LTS
@rem Laurent HADJADJ - 2024-09-19 v1.12.0 - Vérification de l'existence du projet
