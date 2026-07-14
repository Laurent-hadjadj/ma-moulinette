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

use RuntimeException;

/**
 * [Description LogArchiveException]
 * Exception de base de l'archivage des journaux (App\Service\LogArchive\LogArchiveService).
 * Permet à l'appelant d'intercepter d'un seul bloc toutes les erreurs du service.
 */
class LogArchiveException extends RuntimeException
{
}
