<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Exception\DependencyCheck;

/**
 * MODIF 2026-05-11 : le payload
 * décompressé n'est pas du JSON valide (json_decode a levé JsonException).
 * Levée par DependencyCheckProcessCommand::processOne().
 */
class DcInvalidJsonException extends DcPayloadException
{
}
