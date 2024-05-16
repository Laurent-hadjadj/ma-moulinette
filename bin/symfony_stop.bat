@echo off
@call lecteur.bat
@mode con: cols=160 lines=70
@color 0f
@CHCP 65001
@set VERSION=2014-05-13 v1.6.0
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
@rem  Laurent HADJADJ - 2022-03-28 v1.1.0
@rem  Laurent HADJADJ - 2022-03-29 v1.2.0
@rem  Laurent HADJADJ - 2022-09-07 v1.3.0
@rem  Laurent HADJADJ - 2022-12-01 v1.4.0
@rem  Laurent HADJADJ - 2023-09-18 v1.5.0
@rem  Laurent HADJADJ - 2024-05-13 v1.6.0

@echo:
@echo Env       	: dev
@echo Script    	: 1.5.0
@echo Symfony   	: 6.4
@echo Symfony-cli 	: 5.8.2
@echo Php       	: php-8.3.0-NTS
@echo nodejs    	: 18.17.1
@echo:

@set app=%lecteur%\environnement
@set php=%app%\0_toolz\php-8.3.0-NTS\
@set nodejs=%app%\0_toolz\node-18.17.1\

@set PATH=%app%\0_toolz\symfony-cli\current;%php%;%PATH%
@cd %app%\ma-moulinette

@symfony server:stop
