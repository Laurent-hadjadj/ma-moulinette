<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Exception;

/**
 * [Description LogDirectoryNotFoundException]
 * Le dossier des journaux configuré est introuvable ou illisible.
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
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
