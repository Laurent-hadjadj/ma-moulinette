@echo off

mode con: cols=160 lines=1000
color 0f
CHCP 65001

echo ###                                                                            ###
echo ### Attention le fichier doit être encodé en UTF-8 avec une séquence de fin de ###
echo ### ligne Windows (CRLF).                                                      ###
echo ###                                                                            ###
echo:

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
    @call  %%d:\environnement\lecteur.bat
)

set VERSION=2025-01-02 v1.0.0
title Laurent HADJADJ - version %VERSION%
cls
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

set ROOT=%lecteur%\environnement

@echo lecteur     : %LECTEUR_PATH%
@echo version:    : %VERSION%

setlocal enabledelayedexpansion

:: Configuration des paramètres RabbitMQ
set RABBITMQ_HOST=localhost
set RABBITMQ_PORT=15672
set RABBITMQ_USER=guest
set RABBITMQ_PASS=guest
set QUEUE_FILE=queues.txt

:: Vérification de l'existence du fichier des queues
if not exist %QUEUE_FILE% (
    echo Le fichier %QUEUE_FILE% est introuvable.
    exit /b 1
)

:: Lecture et suppression des queues
for /f "tokens=*" %%Q in (%QUEUE_FILE%) do (
    echo Suppression de la queue : %%Q
    curl -u %RABBITMQ_USER%:%RABBITMQ_PASS% -X DELETE http://%RABBITMQ_HOST%:%RABBITMQ_PORT%/api/queues/%%2f/%%Q
)

echo Toutes les queues ont été supprimées.

:exit
