<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Repository\Traits;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Symfony\Component\Uid\Ulid;
use Doctrine\DBAL\Statement;

/**
 * Fournit des méthodes utilitaires pour gérer les paramètres Doctrine DBAL
 * (booléens, ULID, valeurs nulles...).
 */
trait DoctrineParamHelperTrait
{
    /**
     * Lie un booléen nullable à une requête préparée.
     */
    protected function bindNullableBool(Statement $stmt, string $param, ?bool $value): void
    {
        $stmt->bindValue($param, $value, $value === null ? ParameterType::NULL : ParameterType::BOOLEAN);
    }

    /**
     * Lie un ULID (ou null) à une requête préparée.
     * Convertit automatiquement le ULID en chaîne RFC 4122 pour PostgreSQL.
     */
    protected function bindUlidAsString(Statement $stmt, string $param, Ulid|string|null $value): void
    {
        $stringValue = $value instanceof Ulid ? $value->toRfc4122() : $value;
        $stmt->bindValue($param, $stringValue, $stringValue === null ? ParameterType::NULL : ParameterType::STRING);
    }

    /**
     * Lie une valeur générique nullable, en déduisant automatiquement le type PDO.
     */
    protected function bindNullableValue(Statement $stmt, string $param, mixed $value, ParameterType|ArrayParameterType $defaultType = ParameterType::STRING): void
    {
        if ($value === null) {
            $stmt->bindValue($param, null, ParameterType::NULL);
        } else {
            $stmt->bindValue($param, $value, $defaultType);
        }
    }
}
