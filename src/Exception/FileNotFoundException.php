<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2024-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Exception;

/**
 * [Description FileNotFoundException]
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
*/
class FileNotFoundException extends \RuntimeException
{
    public function __construct(string $file, int $code = 0, ?\Throwable $previous = null)
    {
        parent::__construct(sprintf('Fichier introuvable : %s', $file), $code, $previous);
    }
}
