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
@echo  Laurent HADJADJ - 2024-04-12 v1.5.0
@echo *****************************************

@echo:
@echo Env         	: dev
@echo Script      	: 1.5.0
@echo Symfony     	: 6.4
@echo Symfony-cli 	: 5.8.2
@echo Php         	: 8.3.0-NTS
@echo nodejs      	: 18.17.1
@echo maven       	: 3.8.8
@echo jdk         	: 17
@echo posgresql   	: 15.6
@echo sonarqube   	: 9.9.4-LTS

@echo:

@set lecteur=c:
@set app=%lecteur%\environnement\projet\ma-moulinette
@set PHP_PATH=%app%\0_toolz\php-8.3.0-NTS\
@set NODEJS_PATH=%app%\0_toolz\node-18.17.1\
@set JDK_PATH=%app%\0_toolz\jdk17
@set MAVEN_PATH=%app%\0_toolz\apache-maven-3.8.8
@set POSTGRESQL_PATH=%app%\0_toolz\postgresql-15.6-1\

@set HTTP_PROXY=
@set HTTPS_PROXY=

@echo HTTP_PROXY : %HTTP_PROXY%
@echo HTTPS_PROXY : %HTTPS_PROXY%

@set JAVA_TOOL_OPTIONS=-Dfile.encoding=UTF8
@set JAVA_HOME=%JDK_PATH%

@set PATH=%app%\0_toolz\symfony-cli\current;%PHP_PATH%;%NODEJS_PATH%;%MAVEN_PATH%\bin;%JAVA_HOME%\bin;%POSTGRESQL_PATH%\bin;%PATH%

@cd %app%\ma-moulinette
