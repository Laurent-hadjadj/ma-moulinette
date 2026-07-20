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

namespace App\Util;

/**
 * MODIF 2026-05-14 : mode de visualisation
 * partage par toutes les pages agrégées Dependency-Check.
 *
 *   - PROD : dernière version release stable par appli (RSSI / DSI)
 *   - DEV  : dernière version ingérée par appli (SNAPSHOTs inclus)
 *
 * Default = PROD. Le mode est passé via le query param `?vue=prod|dev`
 * (stateless, partageable, no cookie).
 */
final class ScanViewMode
{
    public const PROD    = 'prod';
    public const DEV     = 'dev';
    public const DEFAULT = self::PROD;

    /**
     * [Description for normalize]
     * Convertit un raw query param en mode valide. Default = PROD.
     *
     * @param string|null $raw
     *
     * @return string self::PROD ou self::DEV
     *
     * Created at: 14/05/2026 10:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function normalize(?string $raw): string
    {
        return $raw === self::DEV ? self::DEV : self::PROD;
    }

    /**
     * [Description for flagColumn]
     * Retourne le nom de la colonne SQL a utiliser pour filtrer
     * les scans selon le mode.
     *
     * @param string $mode
     *
     * @return string 'is_latest_overall' ou 'is_latest_release'
     *
     * Created at: 14/05/2026 10:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function flagColumn(string $mode): string
    {
        return $mode === self::DEV ? 'is_latest_overall' : 'is_latest_release';
    }
}
