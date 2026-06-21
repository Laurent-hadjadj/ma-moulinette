@echo off
mode con: cols=160 lines=70
color 0f
CHCP 65001
cls
echo:
echo [93m###                                                                         ###[0m
echo [93m### Attention le fichier doit être encodé en UTF-8 avec BOM et              ###[0m
echo [93m### une séquence  de fin de ligne Windows (CRLF).                           ###[0m
echo [93m###                                                                         ###[0m
echo:

REM Lecteur par défaut c:
set lecteur=c:
