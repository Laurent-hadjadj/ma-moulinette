<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
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
 * @param mixed $mavenKey
 *
 * @return string
 *
 * Created at: 20/05/2024 16:33:15 (Europe/Paris)
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
 */
public function extractNameFromMavenKey($mavenKey): string
    {
        /**
         * On récupère le nom de l'application depuis la clé mavenKey
         * [fr.ma-petite-entreprise] : [ma-moulinette]
         */
        $app = explode(":", $mavenKey);
        if (count($app)===1) {
            /** La clé maven n'est pas conforme, on ne peut pas déduire le nom de l'application */
            $name=$mavenKey;
        } else {
            $name=$app[1];
        }
        return $name;
    }
}
