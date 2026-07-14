<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Exception;

/**
 * [Description EmptyLogSelectionException]
 * Aucun journal exploitable dans la demande d'archivage : sélection vide,
 * ou aucun des fichiers demandés n'est un journal valide du dossier de logs.
 */
class EmptyLogSelectionException extends LogArchiveException
{
}
