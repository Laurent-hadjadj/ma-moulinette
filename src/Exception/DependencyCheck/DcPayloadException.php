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
 */
abstract class DcPayloadException extends \RuntimeException
{
}
