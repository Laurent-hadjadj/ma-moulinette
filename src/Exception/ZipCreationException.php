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
 * [Description ZipCreationException]
 * L'archive ZIP n'a pas pu être ouverte en écriture (dossier temporaire
 * inaccessible, disque plein, droits insuffisants…).
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

    public function getZipPath(): string
    {
        return $this->zipPath;
    }
}
