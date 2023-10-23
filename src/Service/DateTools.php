<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
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
     * @param mixed $str
     *
     * return ($jour * 24 * 60) + ($heure * 60) + intval($minute)
     *
     * Created at: 15/12/2022, 21:25:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function dateToMinute($str)
    {
        $jour = $heure = $minute = 0;
        //[2d1h1min]-- >[2] [1h1min]
        $j = explode('d', $str);
        if (count($j) == 1) {
            $h = explode('h', $j[0]);
        }
        if (count($j) == 2) {
            $jour = $j[0];
            $h = explode('h', $j[1]);
        }

        //heure [1], [1min]
        if (count($h) == 1) {
            $m = explode('min', $h[0]);
        }
        if (count($h) == 2) {
            $heure = $h[0];
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
     * @param mixed $minutes
     *
     * @return string
     *
     * Created at: 15/12/2022, 21:26:17 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function minutesTo($minutes): string
    {
        $j = (int)($minutes / 1440);
        $h = (int)(($minutes - ($j * 1440)) / 60);
        $m = round($minutes % 60);
        if (empty($h) || is_null($h)) {
            $h = 0;
        }
        if ($j > 0) {
            return $j . "d, " . $h . "h:" . $m . "min";
        } else {
            return $h . "h:" . $m . "min";
        }
    }

}
