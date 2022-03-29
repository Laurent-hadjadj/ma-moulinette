@echo off
@cls
@echo *****************************************
@echo         Environnement Symfony
@echo  Laurent HADJADJ - 2022-01-25 v1.0.0
@echo  Laurent HADJADJ - 2022-02-20 v1.1.0
@echo  Laurent HADJADJ - 2022-03-28 v1.2.0
@echo  Laurent HADJADJ - 2022-03-29 v1.3.0
@echo *****************************************

@echo:
@echo Env       : dev
@echo Script    : v1.3.0
@echo Symfony   : v5.4.2
@echo Php       : v8.1.0

@set app=c:\sonar-dash.dev
@set HTTP_PROXY=
@set HTTPS_PROXY=

@set PATH=%app%\symfony-cli\current;%app%\php-8.1.0;%PATH%
@cd %app%\ma-moulinette

rem @symfony.exe server:ca:install
rem @https_proxy=http://127.0.0.1:8000 curl https://sonar-dash.wip
@symfony server:stop
@symfony server:start --no-tls
