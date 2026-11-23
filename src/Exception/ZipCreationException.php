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
 * [Description ZipCreationException]
 * L'archive ZIP n'a pas pu être ouverte en écriture (dossier temporaire
 * inaccessible, disque plein, droits insuffisants…).
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
*/
class ZipCreationException extends LogArchiveException
{
    private string $zipPath;

    public function __construct(string $zipPath, ?\Throwable $previous = null)
    {
        parent::__construct(
            sprintf("Impossible de créer l'archive ZIP %s (dossier temporaire non accessible).", $zipPath),
            0,
            $previous
        );

        $this->zipPath = $zipPath;
    }

    /**
     * [Description for getZipPath]
     *
     * @return string
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function getZipPath(): string
    {
        return $this->zipPath;
    }
}
