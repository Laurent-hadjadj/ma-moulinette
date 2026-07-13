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

namespace App\Exception;

/**
 * MODIF 2026-05-24 : exception typée pour les erreurs
 * cURL / HTTP dans UpdateSonarqubeTagsCommand::callApi().
 * Remplace \RuntimeException.
 * Levée quand un appel cURL vers SonarQube échoue (erreur réseau ou code HTTP hors 2xx).
 * Hérite de \RuntimeException pour rester compatible avec les blocs catch(\Throwable).
 */
class SonarCommandException extends \RuntimeException
{
}
