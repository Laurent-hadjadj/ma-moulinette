@echo off
@cls
@echo *****************************************
@echo         Environnement Symfony
@echo  Laurent HADJADJ - 2022-01-25 v1.0.0
@echo  Laurent HADJADJ - 2022-03-28 v1.1.0
@echo  Laurent HADJADJ - 2022-03-29 v1.1.1
@echo *****************************************

@echo:
@echo Env       : dev
@echo Script    : v1.1.1
@echo Symfony   : v5.4.2
@echo Php       : v8.1.0

@set app=c:\sonar-dash.dev
@set nodejs=c:\environnement\tools\node-12.20.2

@set PATH=%app%\symfony-cli\current;%app%\composer;%app%\php-8.1.0;%nodejs%;%PATH%
@cd %app%\ma-moulinette
@npm run watch
