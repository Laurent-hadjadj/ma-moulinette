###                                                                                                 ###
### Atention le fichier doit être encodé en UTF-8 avec une séquence de fin de ligne Windows (CRLF). ###
###                                                                                                 ###

@echo off
set filename=lecteur.bat
set found=0
for %%d in (C D E F G H I J K L M N O P Q R S T U V W X Y Z) do (
    if exist %%d:\environnement\%filename% (
        set found=1
        goto :found
    )
)

:found
@if %found%==0 (
    echo Le fichier %filename% n'a pas été trouvé sur les disques disponibles.
    goto :exit
) else (
    call  %%d:\environnement\lecteur.bat
)

@mode con: cols=160 lines=70
@color 0f
@CHCP 65001
@set VERSION=2024-06-16 v1.10.0
@title Laurent HADJADJ - version %VERSION%
@cls
@echo ".. __  __             __  __             _              _   _       "
@echo "  |  \/  | __ _      |  \/  | ___  _   _| (_)_ __   ___| |_| |_ ___ "
@echo "  | |\/| |/ _` |_____| |\/| |/ _ \| | | | | | '_ \ / _ \ __| __/ _ \"
@echo "  | |  | | (_| |_____| |  | | (_) | |_| | | | | | |  __/ |_| ||  __/"
@echo "  |_|  |_|\__,_|     |_|  |_|\___/ \__,_|_|_|_| |_|\___|\__|\__\___|"
@echo:
@echo    Laurent HADJADJ
@echo    https://github.com/Laurent-hadjadj/ma-moulinette
@echo    © 2024 - CC BY-SA-NC 4.0
@echo:
@rem Laurent HADJADJ - 2022-01-25 v1.0.0
@rem Laurent HADJADJ - 2022-03-28 v1.1.0
@rem Laurent HADJADJ - 2022-03-29 v1.1.1
@rem Laurent HADJADJ - 2022-05-04 v1.1.2
@rem Laurent HADJADJ - 2022-09-07 v1.2.0
@rem Laurent HADJADJ - 2022-12-01 v1.3.0
@rem Laurent HADJADJ - 2023-09-18 v1.4.0
@rem Laurent HADJADJ - 2024-04-12 v1.5.0 - Refactoring des différents scripts : normalisation des variables + maj des programmes
@rem Laurent HADJADJ - 2024-04-13 v1.6.0 - Ajout du logo + call lecteur.bat
@rem Laurent HADJADJ - 2024-05-15 v1.7.0 - Ajout de python
@rem Laurent HADJADJ - 2024-05-23 v1.8.0 - Ajout dans le path des scripts tools
@rem Laurent HADJADJ - 2024-05-24 v1.9.0 - tests du lecteur par défaut
@rem Laurent HADJADJ - 2024-06-16 v1.10.0 - Ajout du path pour rabbitMQ

@echo:
@echo Env         	: dev
@echo lecteur         : %LECTEUR%
@echo version:        : %VERSION%
@echo Symfony     	: 6.4.7
@echo Symfony-cli 	: 5.8.2
@echo php         	: 8.3.0-NTS
@echo nodejs      	: 18.17.1
@echo python   	: 3.12.3
@echo maven       	: 3.8.8
@echo jdk         	: 17
@echo posgresql   	: 15.6
@echo sonarqube   	: 9.9.4-LTS

@echo:
@set app=%LECTEUR%\environnement
@set SCRIPT_PATH=%app%\ma-moulinette\bin\
@set SYMFONY_PATH=%app%\0_toolz\symfony-cli\current\
@set PHP_PATH=%app%\0_toolz\php-8.3.0-NTS
@set NODEJS_PATH=%app%\0_toolz\node-18.17.1
@set PYTHON_PATH=%app%\0_toolz\python-3.12.3-embed
@set PIP_PATH=%app%\0_toolz\python-3.12.3-embed\Scripts
@set JDK_PATH=%app%\0_toolz\jdk17
@set MAVEN_PATH=%app%\0_toolz\apache-maven-3.8.8
@set POSTGRESQL_PATH=%app%\0_toolz\postgresql-15.6-1
@set RABBITMQ_PATH=%app%\0_toolz\rabbitmq-3.13.1

@set HTTP_PROXY=
@set HTTPS_PROXY=

@echo HTTP_PROXY : %HTTP_PROXY%
@echo HTTPS_PROXY : %HTTPS_PROXY%

@set JAVA_TOOL_OPTIONS=-Dfile.encoding=UTF8
@set JAVA_HOME=%JDK_PATH%

@set PATH=%SCRIPT_PATH%;%SYMFONY_PATH%;%PHP_PATH%;%NODEJS_PATH%;%MAVEN_PATH%\bin;%JAVA_HOME%\bin;%POSTGRESQL_PATH%\bin;%PYTHON_PATH%;%PIP_PATH%;%RABBITMQ_PATH%/sbin;%PATH%

@cd %app%\ma-moulinette

:exit
