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

namespace App\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform;
use Doctrine\DBAL\Types\DateTimeTzImmutableType;

/**
 * Surcharge de DateTimeTzImmutableType pour gérer les microsecondes PostgreSQL.
 *
 * PostgreSQL timestamptz peut retourner "2026-05-14 20:17:08.956633+02",
 * mais DateTimeTzImmutableType attend "Y-m-d H:i:sO" (sans microsecondes).
 * On tronque les microsecondes avant de déléguer au parent.
 */
class DateTimeTzImmutableMicroType extends DateTimeTzImmutableType
{
    /**
     * [Description for convertToPHPValue]
     *
     * @param mixed $value
     * @param AbstractPlatform $platform
     *
     * @return \DateTimeImmutable|null
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateTimeImmutable
    {
        if ($value === null || $value instanceof \DateTimeImmutable) {
            return $value;
        }

        if (!is_string($value)) {
            return parent::convertToPHPValue($value, $platform);
        }

        // Tronque ".956633" entre les secondes et le fuseau horaire
        $value = preg_replace('/(\d{2}:\d{2}:\d{2})\.\d+/', '$1', $value) ?? $value;

        return parent::convertToPHPValue($value, $platform);
    }
}
