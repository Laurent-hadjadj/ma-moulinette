@echo off
@cls
@echo *****************************************
@echo         Environnement Symfony
@echo  Laurent HADJADJ - 2022-01-25 v1.0.0
@echo  Laurent HADJADJ - 2022-03-28 v1.1.0
@echo  Laurent HADJADJ - 2022-03-29 v1.1.1
@echo  Laurent HADJADJ - 2022-05-04 v1.1.2
@echo  Laurent HADJADJ - 2022-09-07 v1.2.0
@echo *****************************************

@echo:
@echo Env       : dev
@echo Script    : v1.2.0
@echo Symfony   : v6.1.3
@echo Php       : v8.1.10

@set app=c:\sonar-dash.dev
@set php=%app%\php-8.1.10
@set nodejs=%app%\node-12.22.12

@set PATH=%app%\symfony-cli\current;%app%\composer;%php%;%nodejs%;%PATH%
@cd %app%\ma-moulinette
@npm run watch
