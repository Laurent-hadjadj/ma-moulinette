###                                                                                                 ###
### Atention le fichier doit être encodé en UTF-8 avec une séquence de fin de ligne Windows (CRLF). ###
###                                                                                                 ###

@echo off
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
@set VERSION=2014-05-24 v1.1.0
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

@set ROOT=%lecteur%\environnement
@set PHP_PATH=%ROOT%\%app%\0_toolz\php-8.3.0-NTS\
@set PATH=%PHP_PATH%;%PATH%

@echo lecteur     : %LECTEUR%
@echo version:    : %VERSION%
@echo php         : 8.3.0-NTS
@echo phpunit     : 9.6

setlocal DISABLEDELAYEDEXPANSION
SET BIN_TARGET=%~dp0/phpunit
SET COMPOSER_RUNTIME_BIN_DIR=%~dp0
php "%BIN_TARGET%" %*

:exit
