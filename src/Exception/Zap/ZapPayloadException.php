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
 * MODIF 2026-10-13 : exception parente pour toute défaillance liée au
 * payload d'un job d'ingestion ZAP (worker `app:zap:process`), sur le
 * gabarit DcPayloadException.
 *
 * Permet au worker de catcher une seule famille pour la branche "rapport
 * impossible à traiter, on requeue ou on marque failed", tout en laissant
 * la possibilité d'identifier la cause précise via le type concret.
 *
 * Hérite de \RuntimeException pour rester compatible avec les blocs
 * `catch (\RuntimeException $e)` génériques.
 */
abstract class ZapPayloadException extends \RuntimeException
{
}
