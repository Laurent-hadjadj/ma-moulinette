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

class DateTools
{
    public function __construct()
    {
        /** Vide */
    }

    /**
     * [Description for date_to_minute]
     * Fonction pour convertir une date au format xxd aah xxmin en minutes
     *
     * @param string $str
     *
     * return ($jour * 24 * 60) + ($heure * 60) + intval($minute)
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    public function dateToMinute(string $str): int
    {
        $jour = $heure = $minute = 0;
        //[2d1h1min]-- >[2] [1h1min]
        $j = explode('d', $str);
        $h = [];
        if (count($j) == 1) {
            $h = explode('h', $j[0]);
        }
        if (count($j) == 2) {
            $jour = (int) $j[0];
            $h = explode('h', $j[1]);
        }

        //heure [1], [1min]
        $m = [];
        if (count($h) == 1) {
            $m = explode('min', $h[0]);
        }
        if (count($h) == 2) {
            $heure = (int) $h[0];
            $m = explode('min', $h[1]);
        }

        //minute
        if (count($m) == 1) {
            $m = explode('min', $j[0]);
        }
        if (count($m) == 2) {
            $mm = explode('min', $m[0]);
            $minute = $mm[0];
        }

        return ($jour * 24 * 60) + ($heure * 60) + intval($minute);
    }

    /**
     * [Description for minutesTo]
     * Converti les minutes en jours, heures et minutes
     *
     * @param int $minutes
     *
     * @return string
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    /* MODIF 2026-05-04 : signature `string $minutes` → `int $minutes`.
     * Tous les callers passent un int (effortTotal typé int dans Owasp.php),
     * et la fonction faisait des opérations arithmétiques (/, -, %) sur le paramètre. */
    public function minutesTo(int $minutes): string
    {
        $j = (int)($minutes / 1440);
        $h = (int)(($minutes - ($j * 1440)) / 60);
        $m = round($minutes % 60);
        if (empty($h)) {
            $h = 0;
        }
        if ($j > 0) {
            return $j . "d, " . $h . "h:" . $m . "min";
        } else {
            return $h . "h:" . $m . "min";
        }
    }

    /**
     * [Description for minutesToString]
     *
     * @param int $minutes
     *
     * @return string
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    public function minutesToString(int $minutes): string
    {
        $j = (int)($minutes / 1440);
        $h = (int)(($minutes - ($j * 1440)) / 60);
        $m = round($minutes % 60, 0);

        // Pour 0 jours, éviter l'espace avant 'd'
        if ($j == 0) {
            return sprintf('0d, %dh:%dm', $h, $m); // Pas d'espace avant 'd' pour 0 jours
        }

        return sprintf('%dd, %dh:%dm', $j, $h, $m); // Pas d'espace entre les jours et 'd'
    }
}
