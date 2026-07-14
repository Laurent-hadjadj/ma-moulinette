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

namespace App\Service\DependencyCheck;

use App\Util\LatestVersionResolver;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;

/**
 * MODIF 2026-05-14 : service de maintenance
 * des flags `is_latest_overall` / `is_latest_release` sur `dc_scan`.
 *
 * Appel :
 *   - apres chaque ingestion réussie d'un scan (hook dans DependencyCheckIngester)
 *   - par la commande filet `php bin/console dc:recompute-latest` (backfill)
 *
 * Invariant maintenu pour chaque (project_group, project_artifact) :
 *   - exactement 1 ligne avec is_latest_overall = TRUE  (si scans existent)
 *   - 0 ou 1 ligne avec is_latest_release = TRUE        (0 si aucune release ingérée)
 *
 * Tout est en transaction : reset des flags du couple puis SET TRUE sur
 * les 1-2 gagnants. Atomique vis-a-vis d'une ingestion concurrente sur
 * le meme couple (PG verrouille les lignes touchées).
 */
class DcLatestVersionUpdater
{
    public function __construct(
        private readonly EntityManagerInterface $em,
    ) {
    }

    /**
     * [Description for recomputeFor]
     * Recalcule les flags pour tous les scans de (group, artifact).
     *
     * @param string $group
     * @param string $artifact
     *
     * @return array{overall_id: int|null, release_id: int|null}
     *
     * Created at: 14/05/2026 09:30:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function recomputeFor(string $group, string $artifact): array
    {
        /** @var Connection $conn */
        $conn = $this->em->getConnection();

        $conn->beginTransaction();
        try {
            $rows = $conn->fetchAllAssociative(
                'SELECT id, project_version
                    FROM ma_moulinette.dc_scan
                    WHERE project_group = :g AND project_artifact = :a',
                ['g' => $group, 'a' => $artifact]
            );

            if ($rows === []) {
                $conn->commit();
                return ['overall_id' => null, 'release_id' => null];
            }

            // Map version -> id (1ere occurrence en cas d'egalite stricte de version)
            $versions = [];
            $idByVersion = [];
            foreach ($rows as $r) {
                $v = (string) $r['project_version'];
                $versions[] = $v;
                if (!isset($idByVersion[$v])) {
                    $idByVersion[$v] = (int) $r['id'];
                }
            }

            $overallVersion = LatestVersionResolver::pickLatest($versions, false);
            $releaseVersion = LatestVersionResolver::pickLatest($versions, true);

            $overallId = $overallVersion !== null ? $idByVersion[$overallVersion] : null;
            $releaseId = $releaseVersion !== null ? $idByVersion[$releaseVersion] : null;

            // Reset des flags pour ce couple
            $conn->executeStatement(
                'UPDATE ma_moulinette.dc_scan
                    SET is_latest_overall = FALSE, is_latest_release = FALSE
                    WHERE project_group = :g AND project_artifact = :a',
                ['g' => $group, 'a' => $artifact]
            );

            if ($overallId !== null) {
                $conn->executeStatement(
                    'UPDATE ma_moulinette.dc_scan
                        SET is_latest_overall = TRUE
                        WHERE id = :id',
                    ['id' => $overallId]
                );
            }
            if ($releaseId !== null) {
                $conn->executeStatement(
                    'UPDATE ma_moulinette.dc_scan
                        SET is_latest_release = TRUE
                        WHERE id = :id',
                    ['id' => $releaseId]
                );
            }

            $conn->commit();

            return [
                'overall_id' => $overallId,
                'release_id' => $releaseId,
            ];
        } catch (\Throwable $e) {
            $conn->rollBack();
            throw $e;
        }
    }

    /**
     * [Description for recomputeAll]
     * Recalcule les flags pour TOUS les couples (project_group, project_artifact)
     * distincts presents dans `dc_scan`. Utilise par la commande backfill.
     *
     * @return int Nombre de couples traites
     *
     * Created at: 14/05/2026 09:30:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function recomputeAll(): int
    {
        $conn = $this->em->getConnection();
        $pairs = $conn->fetchAllAssociative(
            'SELECT DISTINCT project_group, project_artifact
                FROM ma_moulinette.dc_scan
                ORDER BY project_group ASC, project_artifact ASC'
        );

        foreach ($pairs as $p) {
            $this->recomputeFor((string) $p['project_group'], (string) $p['project_artifact']);
        }

        return count($pairs);
    }
}
