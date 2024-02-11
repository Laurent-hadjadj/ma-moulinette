@echo off
@cls
@echo *****************************************
@echo         Environnement Symfony
@echo  Laurent HADJADJ - 2022-01-25 v1.0.0
@echo  Laurent HADJADJ - 2022-03-28 v1.1.0
@echo  Laurent HADJADJ - 2022-03-29 v1.1.1
@echo  Laurent HADJADJ - 2022-05-04 v1.1.2
@echo  Laurent HADJADJ - 2022-09-07 v1.2.0
@echo  Laurent HADJADJ - 2022-12-01 v1.3.0
@echo  Laurent HADJADJ - 2023-09-18 v1.4.0
@echo *****************************************

@echo:
@echo Env       : dev
@echo Script    : v1.4.0
@echo Symfony   : v6.4.3
@echo Php       : v8.3.0-NTS
@echo nodejs    : 18.17.1
@echo:

@set lecteur=c:
@set app=%lecteur%\environnement\projet\ma-moulinette
@set php=%app%\php-8.3.0-NTS\
@set nodejs=%app%\node-18.17.1\

@set HTTP_PROXY=
@set HTTPS_PROXY=

@echo HTTP_PROXY : %HTTP_PROXY%
@echo HTTPS_PROXY : %HTTPS_PROXY%

@set PATH=%app%\symfony-cli\current;%app%\composer\;%php%;%nodejs%;%PATH%

@cd %app%\ma-moulinette
