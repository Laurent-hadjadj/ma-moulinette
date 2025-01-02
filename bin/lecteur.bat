@echo off
mode con: cols=160 lines=70
color 0f
CHCP 65001
cls
echo:
echo [93m###                                                                         ###[0m
echo [93m### Attention le fichier doit être encodé en UTF-8 avec une séquence         ###[0m
echo [93m### de fin de ligne Windows (CRLF).                                         ###[0m
echo [93m###                                                                         ###[0m
echo:                                                                          ###
echo:
REM Lecteur par défaut c:
set lecteur=c:
