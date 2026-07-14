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
 * [Description LogDirectoryNotFoundException]
 * Le dossier des journaux configuré est introuvable ou illisible.
 */
class LogDirectoryNotFoundException extends LogArchiveException
{
    private string $logDir;

    public function __construct(string $logDir, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf("Dossier de logs introuvable : %s", $logDir),
            0,
            $previous
        );

        $this->logDir = $logDir;
    }

    public function getLogDir(): string
    {
        return $this->logDir;
    }
}
