@echo off
@cls
@echo *****************************************
@echo         Environnement Symfony
@echo  Laurent HADJADJ - 2023-02-13 v1.0.0
@echo  Laurent HADJADJ - 2024-02-11 v1.1.0
@echo *****************************************

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

@set lecteur=c:
@set app=%lecteur%\environnement
@set php=%app%\0_toolz\php-8.3.0-NTS\
@set nodejs=%app%\0_toolz\node-18.17.1\

@set HTTP_PROXY=
@set HTTPS_PROXY=

@set PATH=%app%\symfony-cli\current;%php%;%nodejs%;%PATH%

@cd %app%\ma-moulinette

php -dxdebug.mode=coverage bin/phpunit --coverage-clover=reports/phpunit-coverage-result.xml --coverage-html=reports --log-junit=reports/phpunit-execution-result.xml
