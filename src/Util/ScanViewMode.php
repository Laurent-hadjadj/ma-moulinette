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
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
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
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public static function flagColumn(string $mode): string
    {
        return $mode === self::DEV ? 'is_latest_overall' : 'is_latest_release';
    }
}
