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

namespace App\Exception\Zap;

/**
 * MODIF 2026-10-13 : le JSON est bien formé mais ne respecte pas le schema
 * attendu d'un rapport ZAP (`site` absent/vide, alerte sans `pluginid`,
 * plus d'un site dans le rapport — hors périmètre v1, etc.).
 */
class ZapInvalidStructureException extends ZapPayloadException
{
}
