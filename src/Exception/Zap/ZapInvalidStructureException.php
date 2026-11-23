<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Exception\Zap;

/**
 * [Description ZapInvalidStructureException]
 * MODIF 2026-10-13 : le JSON est bien formé mais ne respecte pas le schema
 * attendu d'un rapport ZAP (`site` absent/vide, alerte sans `pluginid`,
 * plus d'un site dans le rapport — hors périmètre v1, etc.).
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
*/
class ZapInvalidStructureException extends ZapPayloadException {}
