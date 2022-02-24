@echo off
@cls
@echo ***************************************** 
@echo         Environnement Symfony 
@echo  Laurent HADJADJ - 2022-01-25 v1.0
@echo  Laurent HADJADJ - 2022-02-20 v1.1
@echo *****************************************
@echo:
@echo Script    : v1.1
@echo Symfony   : v5.4.2
@echo Php       : v8.1.0 
@set PATH=c:\sonar-dash\symfony-cli\current\;c:\sonar-dash\php-8.1.0\;%PATH%
@cd c:\sonar-dash\ma-moulinette
@symfony server:stop
@symfony server:start --no-tls
REM symfony.exe server:ca:install