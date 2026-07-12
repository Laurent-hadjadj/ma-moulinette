<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
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
    public function convertToPHPValue(mixed $value, AbstractPlatform $platform): ?\DateTimeImmutable
    {
        if ($value === null || $value instanceof \DateTimeImmutable) {
            return $value;
        }

        // Tronque ".956633" entre les secondes et le fuseau horaire
        $value = (string) preg_replace('/(\d{2}:\d{2}:\d{2})\.\d+/', '$1', $value);

        return parent::convertToPHPValue($value, $platform);
    }
}
