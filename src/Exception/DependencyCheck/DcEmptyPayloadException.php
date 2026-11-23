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

namespace App\Exception\DependencyCheck;

/**
 * [Description DcEmptyPayloadException]
 * MODIF 2026-05-11 : payload de la
 * queue `dc_processing_queue` vide, illisible (stream cassé) ou non-string.
 * Levée par DependencyCheckProcessCommand::processOne().
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
*/
class DcEmptyPayloadException extends DcPayloadException {}
