@echo off
@call lecteur.bat
@mode con: cols=160 lines=70
@color 0f
@CHCP 65001
@set VERSION=2014-05-13 v1.2.0
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
@rem  Laurent HADJADJ - 2024-05-13 v1.2.0


@echo:
@echo Env       	: dev
@echo Script    	: 1.1.0
@echo Symfony   	: 6.4
@echo Symfony-cli 	: 5.8.2
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
