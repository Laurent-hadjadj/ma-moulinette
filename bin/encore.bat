@echo off
@cls
@echo ***************************************** 
@echo         Environnement Symfony 
@echo  Laurent HADJADJ - 2022-01-25 v1.0
@echo *****************************************
@echo:
@echo Script    : v1.0
@echo Symfony   : v5.4.2
@echo Php       : v8.1.0
@set PATH=c:\sonar-dash\symfony-cli\current;c:\sonar-dash\composer;c:\sonar-dash\php-8.1.0;c:\environnement\tools\node-12.20.2;%PATH%
@cd c:\sonar-dash\ma-moulinette

@npm run watch

rem http://localhost:8000/api/projet/anomalies/ajout?maven_key=fr.franceagrimer:aval&type=CODE_SMELL&setup=2#trace-box-2