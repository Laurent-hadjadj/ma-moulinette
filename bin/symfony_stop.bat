@echo off
@cls
@echo *****************************************
@echo         Environnement Symfony
@echo  Laurent HADJADJ - 2022-01-25 v1.0.0
@echo  Laurent HADJADJ - 2022-03-28 v1.1.0
@echo  Laurent HADJADJ - 2022-03-29 v1.2.0
@echo *****************************************

@echo:
@echo Env       : dev
@echo Script    : v1.2.0
@echo Symfony   : v5.4.2
@echo Php       : v8.1.0

@set app=c:\sonar-dash.dev

@set PATH=%app%\symfony-cli\current;%app%\php-8.1.0;%PATH%
@cd %app%\ma-moulinette

@symfony server:stop
