@echo off
@cls
@echo *****************************************
@echo         Environnement Symfony
@echo  Laurent HADJADJ - 2023-02-13 v1.0.0
@echo *****************************************

@echo:
@echo Env       : dev
@echo Script    : v1.0.0
@echo Symfony   : v6.1.6
@echo Php       : v8.1.10

@set app=c:\sonar-dash.dev
@set php=%app%\php-8.1.10
@set nodejs=%app%\node-12.22.12

@set nodejs=c:\%app%\node-12.22.12
@set HTTP_PROXY=
@set HTTPS_PROXY=

@set PATH=%app%\symfony-cli\current;%app%\composer;%php%;%nodejs%;

@cd %app%\ma-moulinette

php -dxdebug.mode=coverage bin/phpunit --coverage-clover=reports/phpunit-coverage-result.xml --coverage-html=reports --log-junit=reports/phpunit-execution-result.xml