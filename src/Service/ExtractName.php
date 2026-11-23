<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Service;

/**
 * [Description ExtractName]
 */
class ExtractName
{

    /**
     * [Description for extractNameFromMavenKey]
     * Extraction du nom du projet depuis une mavenKey
     *
     * @return string
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    public function extractNameFromMavenKey(?string $mavenKey): string
    {
        // Vérifier que la mavenKey n'est pas null avant de la traiter
        if ($mavenKey === null) {
            return '';  // ou une valeur par défaut de ton choix
        }

        /**
         * On récupère le nom de l'application depuis la clé mavenKey
         * [fr.ma-petite-entreprise] : [ma-moulinette]
         */
        $app = explode(":", $mavenKey);
        if (count($app) === 1) {
            /** La clé maven n'est pas conforme, on ne peut pas déduire le nom de l'application */
            $name = $mavenKey;
        } else {
            $name = $app[1];
        }
        return $name;
    }
}
