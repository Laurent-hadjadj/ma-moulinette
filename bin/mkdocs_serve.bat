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
@echo Le fichier %filename% n'a pas été trouvé sur les disques disponibles.
@goto :exit
) else (
@call  %%d:\environnement\lecteur.bat
)

@mode con: cols=160 lines=70
@color 0f
@CHCP 65001
@set VERSION=2024-05-24 v1.1.0
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
@rem Laurent HADJADJ - 2022-01-25 v1.0.0 - Création du script
@rem Laurent HADJADJ - 2024-05-24 v1.1.0 - tests du lecteur par défaut

@echo:
@echo Env           : dev
@echo lecteur       : %LECTEUR%
@echo version:      : %VERSION%
@echo python        : 3.12.3

@echo:
@set app=%LECTEUR%\environnement
@set PYTHON_PATH=%app%\0_toolz\python-3.12.3-embed\
@set PIP_PATH=%app%\0_toolz\python-3.12.3-embed\Scripts\

@set HTTP_PROXY=
@set HTTPS_PROXY=

@echo HTTP_PROXY : %HTTP_PROXY%
@echo HTTPS_PROXY : %HTTPS_PROXY%

@set PATH=%PYTHON_PATH%;%PIP_PATH%;%PATH%

@cd %app%\ma-moulinette\mkDocs
@python -m mkdocs serve

:exit
