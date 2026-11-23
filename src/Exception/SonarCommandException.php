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

namespace App\Exception;

/**
 * MODIF 2026-05-24 : exception typée pour les erreurs
 * cURL / HTTP dans UpdateSonarqubeTagsCommand::callApi().
 * Remplace \RuntimeException.
 * Levée quand un appel cURL vers SonarQube échoue (erreur réseau ou code HTTP hors 2xx).
 * Hérite de \RuntimeException pour rester compatible avec les blocs catch(\Throwable).
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
*/
class SonarCommandException extends \RuntimeException {}
