@echo off
mode con: cols=160 lines=70
color 0f
CHCP 65001
cls

set filename=lecteur.bat
set found=0
for %%d in (C D E F G H I J K L M N O P Q R S T U V W X Y Z) do (
    if exist %%d:\environnement\%filename% (
        set found=1
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

set VERSION=2024-08-01 v1.1.0
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

info:
echo Env         	: dev
echo lecteur         : %LECTEUR%
echo version:        : %VERSION%
echo symfony     	: 6.4
echo symfony-cli 	: 5.8.2
echo php         	: 8.3.0-NTS
echo nodejs      	: 18.17.1
echo:

echo "Création de la base de données de tests"
echo:
php bin/console --env=test doctrine:database:drop --force
php bin/console --env=test doctrine:database:create --if-not-exists
php bin/console --env=test doctrine:schema:update --force
php bin/console --env=test doctrine:migrations:migrate -n

exit:
rem  Laurent HADJADJ - 2024-08-25 v1.0.0 - création du script
rem  Laurent HADJADJ - 2024-08-25 v1.1.0 - Corrections de l'affichage de l'en-tête.
