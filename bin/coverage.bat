@echo off
set filename=lecteur.bat
set found=0
for %%d in (C D E F G H I J K L M N O P Q R S T U V W X Y Z) do (
    if exist %%d:\environnement\%filename% (
        set found=1
        goto :found
    )
)

:found
@if %found%==0 (
@echo Le fichier %filename% n'a pas été trouvé sur les disques disponibles.
@goto :exit
) else (
@call  %%d:\environnement\lecteur.bat
)

@mode con: cols=160 lines=70
@color 0f
@CHCP 65001
@set VERSION=2014-05-24 v1.3.0
@title Laurent HADJADJ - version %VERSION%
@cls
@echo ".. __  __             __  __             _              _   _       "
@echo "  |  \/  | __ _      |  \/  | ___  _   _| (_)_ __   ___| |_| |_ ___ "
@echo "  | |\/| |/ _` |_____| |\/| |/ _ \| | | | | | '_ \ / _ \ __| __/ _ \"
@echo "  | |  | | (_| |_____| |  | | (_) | |_| | | | | | |  __/ |_| ||  __/"
@echo "  |_|  |_|\__,_|     |_|  |_|\___/ \__,_|_|_|_| |_|\___|\__|\__\___|"
@echo:
@echo    Laurent HADJADJ
@echo    https://github.com/Laurent-hadjadj/ma-moulinette
@echo    © 2024 - CC BY-SA-NC 4.0
@echo:

@rem  Laurent HADJADJ - 2023-02-13 v1.0.0
@rem  Laurent HADJADJ - 2024-02-11 v1.1.0
@rem  Laurent HADJADJ - 2024-05-13 v1.2.0 - Ajout du lecteur
@rem  Laurent HADJADJ - 2024-05-24 v1.3.0 - tests du lecteur par défaut

@echo:
@echo Env       	: dev
@echo lecteur     : %LECTEUR%
@echo version:    : %VERSION%
@echo Symfony   	: 6.4.7
@echo Symfony-cli : 5.8.2
@echo Php       	: 8.3.0-NTS
@echo nodejs    	: 18.17.1
@echo:

@echo Génére le rapport de couverture des tests unitaires.
@echo Le rapport se trouve dans le dossier ma-moulinette/reports

@set app=%lecteur%\environnement
@set php=%app%\0_toolz\php-8.3.0-NTS\
@set nodejs=%app%\0_toolz\node-18.17.1\

@set HTTP_PROXY=
@set HTTPS_PROXY=

@set PATH=%app%\symfony-cli\current;%php%;%nodejs%;%PATH%

@cd %app%\ma-moulinette

php -dxdebug.mode=coverage bin/phpunit --coverage-clover=reports/phpunit-coverage-result.xml --coverage-html=reports --log-junit=reports/phpunit-execution-result.xml

:exit
