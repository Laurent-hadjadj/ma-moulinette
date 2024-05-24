echo off
@set filename=lecteur.bat
@set found=0
for %%d in (C D E F G H I J K L M N O P Q R S T U V W X Y Z) do (
    if exist %%d:\environnement\%filename% (
        set found=1
        goto :found
    )
)

:found
@if %found%==0 (
@echo Le fichier %filename% n'a pas été trouvé sur les disques disponibles.
@goto :exit
) else (
@call  %%d:\environnement\lecteur.bat
)

@mode con: cols=160 lines=70
@color 0f
@CHCP 65001
@set VERSION=2014-05-13 v1.8.0
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
@rem  Laurent HADJADJ - 2022-01-25 v1.0.0
@rem  Laurent HADJADJ - 2022-02-20 v1.1.0
@rem  Laurent HADJADJ - 2022-03-28 v1.2.0
@rem  Laurent HADJADJ - 2022-03-29 v1.3.0
@rem  Laurent HADJADJ - 2022-09-07 v1.4.0
@rem  Laurent HADJADJ - 2022-12-01 v1.5.0
@rem  Laurent HADJADJ - 2023-09-18 v1.6.0
@rem  Laurent HADJADJ - 2024-05-13 v1.7.0 - Ajout du lecteur
@rem  Laurent HADJADJ - 2024-05-24 v1.8.0 - tests du lecteur par défaut

@echo:
@echo Env         	: dev
@echo lecteur     : %LECTEUR%
@echo version:    : %VERSION%
@echo symfony     	: 6.4
@echo symfony-cli 	: 5.8.2
@echo php         	: 8.3.0-NTS
@echo nodejs      	: 18.17.1
@echo:

@set app=%lecteur%\environnement
@set php=%app%\0_toolz\php-8.3.0-NTS\
@set nodejs=%app%\0_toolz\node-18.17.1\

@set HTTP_PROXY=
@set HTTPS_PROXY=

@set PATH=%app%\0_toolz\symfony-cli\current;%php%;%PATH%

@cd %app%\ma-moulinette

rem @symfony.exe server:ca:install
rem @https_proxy=http://127.0.0.1:8000 curl https://ma-moulinette.wip
@symfony server:stop
@symfony server:start --no-tls

:exit
