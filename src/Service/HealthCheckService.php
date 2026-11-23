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

use Doctrine\DBAL\Connection;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * [Description HealthCheckService]
 */
class HealthCheckService
{
    public function __construct(
        private Connection $connection,
        private LoggerInterface $logger,
        private CacheInterface $cache
    ) {}

    /**
     * [Description for check]
     *
     * @return array<int|string, mixed>
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function check(): array
    {
        return $this->cache->get('healthcheck_result', function (ItemInterface $item) {

            // ⏱️ TTL court (5 secondes) pour éviter de cacher un résultat obsolète trop longtemps
            $item->expiresAfter(5);

            $errors = [];

            // DB check
            try {
                $this->connection->fetchOne('SELECT 1');
            } catch (\Throwable $e) {
                $this->logger->error('[HealthCheck] ❌ La base de données est indisponible', [
                    'exception' => $e->getMessage()
                ]);
                return ['[HealthCheck] ❌ La base de données est indisponible'];
            }

            // Table check
            try {
                $schemaManager = $this->connection->createSchemaManager();

                if (!$schemaManager->tablesExist(['ma_moulinette'])) {
                    $this->logger->warning('[HealthCheck] ⚠️ La table \'ma_moulinette\' n\'existe pas');
                    $errors[] = '[HealthCheck] ⚠️ La table \'ma_moulinette\' n\'existe pas';
                }
            } catch (\Throwable $e) {
                $this->logger->error('[HealthCheck] ❌ La vérification du schéma a échoué', [
                    'exception' => $e->getMessage()
                ]);
                $errors[] = '[HealthCheck] ❌ La vérification du schéma a échoué';
            }

            return $errors;
        });
    }
}
