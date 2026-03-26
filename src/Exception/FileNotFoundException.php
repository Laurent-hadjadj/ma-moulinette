<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2024-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Exception;

/**
 * [Description FileNotFoundException]
 */
class FileNotFoundException extends \RuntimeException
{
    public function __construct(string $file, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Fichier introuvable : %s', $file), $code, $previous);
    }
}
