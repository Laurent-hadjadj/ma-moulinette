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
 * [Description DcPayloadException]
 * MODIF 2026-05-11 : exception parente pour
 * toute défaillance liée au payload d'un job d'ingestion DependencyCheck
 * (worker `app:dependency-check:process`).
 *
 * Permet au worker de catcher une seule famille pour la branche "rapport
 * impossible à traiter, on requeue ou on marque failed", tout en laissant
 * la possibilité d'identifier la cause précise via le type concret
 * (DcEmptyPayloadException, DcGzipDecodeException, etc.).
 *
 * Hérite de \RuntimeException pour rester compatible avec les blocs
 * `catch (\RuntimeException $e)` historiques.
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
*/
abstract class DcPayloadException extends \RuntimeException {}
