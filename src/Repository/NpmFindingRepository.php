<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Repository;

use App\Entity\NpmFinding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description NpmFindingRepository]
 * Findings = jointure scan x dependency x vulnerability.
 *
 * @extends ServiceEntityRepository<NpmFinding>
 */
class NpmFindingRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NpmFinding::class);
    }

    /**
     * @return array{code: 500, erreur: string}|array{code: 23505, erreur: string}
     */
    public function handleDatabaseException(\Throwable $e): array
    {
        $message = $e->getMessage();

        if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
            $message = self::$noDataBase;
        }

        if ($e instanceof \Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
        }

        return ['code' => 500, 'erreur' => $message];
    }

    /**
     * Vue projet : tous les findings d'un scan, joins dénormalisés.
     *
     * @return array{code: 200, liste: array<int, array<string, mixed>>, erreur: string}|array{code: 500|23505, erreur: string}
     */
    public function listForScan(int $scanId): array
    {
        $sql = <<<SQL
            SELECT
                f.id,
                f.severity_at_scan,
                f.cvss_at_scan,
                f.range,
                f.installed_version,
                f.is_direct,
                f.fix_available,
                f.fix_to_version,
                f.fix_is_semver_major,
                f.is_prod,
                d.name AS dependency_name,
                v.advisory_id,
                v.title,
                v.url,
                v.cwes
            FROM ma_moulinette.npm_finding f
                JOIN ma_moulinette.npm_dependency d    ON f.dependency_id = d.id
                JOIN ma_moulinette.npm_vulnerability v ON f.vulnerability_id = v.id
            WHERE f.scan_id = :scan
            ORDER BY
                CASE f.severity_at_scan
                    WHEN 'critical' THEN 1
                    WHEN 'high'     THEN 2
                    WHEN 'moderate' THEN 3
                    WHEN 'low'      THEN 4
                    WHEN 'info'     THEN 5
                    ELSE 6
                END,
                f.cvss_at_scan DESC NULLS LAST,
                d.name
        SQL;

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, ['scan' => $scanId])
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        $liste = array_map(static fn(array $r) => [
            'id' => (int) $r['id'],
            'severity' => $r['severity_at_scan'],
            'cvss' => $r['cvss_at_scan'] !== null ? (float) $r['cvss_at_scan'] : null,
            'range' => $r['range'],
            'installed_version' => $r['installed_version'],
            'is_direct' => (bool) $r['is_direct'],
            'fix_available' => (bool) $r['fix_available'],
            'fix_to_version' => $r['fix_to_version'],
            'fix_is_semver_major' => $r['fix_is_semver_major'] !== null ? (bool) $r['fix_is_semver_major'] : null,
            'is_prod' => $r['is_prod'] !== null ? (bool) $r['is_prod'] : null,
            'dependency_name' => $r['dependency_name'],
            'advisory_id' => $r['advisory_id'],
            'title' => $r['title'],
            'url' => $r['url'],
            'cwes' => json_decode($r['cwes'] ?? '[]', true) ?: [],
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * Dashboard : répartition des findings d'un scan par origine
     * (Production/Dev/Indéterminé, depuis `is_prod`) x sévérité. Adaptation
     * du plan initial ("prod/dev/optional/peer x sévérité") : `is_prod` est
     * volontairement binaire (true/false/null, voir le commentaire sur la
     * colonne) — npm audit --omit=dev ne permet de distinguer que
     * prod/non-prod, pas optional/peer séparément. La ventilation complète
     * prod/dev/optional/peer existe déjà, mais seulement au niveau du
     * *décompte de dépendances* du scan (tuiles de la page projet), pas par
     * vulnérabilité trouvée.
     *
     * @return array{code: 200, liste: array<int, array<string, mixed>>, erreur: string}|array{code: 500|23505, erreur: string}
     */
    public function countByOriginAndSeverity(int $scanId): array
    {
        $sql = <<<SQL
            SELECT
                CASE WHEN f.is_prod IS NULL THEN 'unknown' WHEN f.is_prod THEN 'prod' ELSE 'dev' END AS group_key,
                f.severity_at_scan AS severity,
                COUNT(*) AS cnt
            FROM ma_moulinette.npm_finding f
            WHERE f.scan_id = :scan
            GROUP BY group_key, f.severity_at_scan
        SQL;

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, ['scan' => $scanId])
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $rows, 'erreur' => ''];
    }

    /**
     * Dashboard : répartition des findings d'un scan par type de dépendance
     * (Direct/Transitif, depuis `is_direct`) x sévérité.
     *
     * @return array{code: 200, liste: array<int, array<string, mixed>>, erreur: string}|array{code: 500|23505, erreur: string}
     */
    public function countByDirectAndSeverity(int $scanId): array
    {
        $sql = <<<SQL
            SELECT
                CASE WHEN f.is_direct THEN 'direct' ELSE 'transitive' END AS group_key,
                f.severity_at_scan AS severity,
                COUNT(*) AS cnt
            FROM ma_moulinette.npm_finding f
            WHERE f.scan_id = :scan
            GROUP BY group_key, f.severity_at_scan
        SQL;

        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery($sql, ['scan' => $scanId])
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $rows, 'erreur' => ''];
    }

    /**
     * Dashboard : top N paquets les plus vulnérables de CE scan (nombre de
     * findings), par opposition à `NpmDependencyRepository::topVulnerableDependencies()`
     * qui agrège tous les scans/projets confondus.
     *
     * @return array{code: 200, liste: array<int, array<string, mixed>>, erreur: string}|array{code: 500|23505, erreur: string}
     */
    public function topVulnerableDependenciesForScan(int $scanId, int $limit = 10): array
    {
        $sql = <<<SQL
            SELECT d.name, COUNT(f.id) AS finding_count
            FROM ma_moulinette.npm_finding f
            JOIN ma_moulinette.npm_dependency d ON f.dependency_id = d.id
            WHERE f.scan_id = :scan
            GROUP BY d.name
            ORDER BY finding_count DESC, d.name ASC
            LIMIT :limit
        SQL;

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            $stmt->bindValue('scan', $scanId, \Doctrine\DBAL\ParameterType::INTEGER);
            $stmt->bindValue('limit', $limit, \Doctrine\DBAL\ParameterType::INTEGER);
            $rows = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $rows, 'erreur' => ''];
    }

    /**
     * Marque isProd=true sur tous les findings d'un scan dont le nom de
     * dépendance apparaît dans la liste fournie (issue du rapport
     * "prodOnly" de l'enveloppe d'upload, croisé par l'ingester). Les
     * findings du scan dont le nom n'apparaît PAS dans la liste sont
     * marqués isProd=false (déterminé : absent de la prod). N'est appelée
     * que si "prodOnly" était présent dans le payload — sinon isProd reste
     * null (indéterminé) sur tous les findings du scan.
     *
     * @param array<int, string> $prodDependencyNames
     */
    public function markIsProdForScan(int $scanId, array $prodDependencyNames): void
    {
        $conn = $this->getEntityManager()->getConnection();

        // MODIF 2026-11-20 : BUG CORRIGÉ — `= ANY(:names)` avec
        // ArrayParameterType::STRING produit `ANY($1, $2, ...)` (Doctrine
        // DBAL expand le tableau pour un IN(), pas pour ANY() qui attend un
        // seul vrai tableau Postgres) -> erreur de syntaxe SQL, confirmée en
        // conditions réelles. `IN (:names)` est la forme que Doctrine DBAL
        // sait expand correctement avec ArrayParameterType.
        if ($prodDependencyNames === []) {
            $conn->executeStatement(
                'UPDATE ma_moulinette.npm_finding SET is_prod = false WHERE scan_id = :scan',
                ['scan' => $scanId],
                ['scan' => \Doctrine\DBAL\ParameterType::INTEGER]
            );
            return;
        }

        $conn->executeStatement(
            'UPDATE ma_moulinette.npm_finding f
             SET is_prod = (d.name IN (:names))
             FROM ma_moulinette.npm_dependency d
             WHERE f.dependency_id = d.id AND f.scan_id = :scan',
            ['names' => $prodDependencyNames, 'scan' => $scanId],
            ['names' => \Doctrine\DBAL\ArrayParameterType::STRING, 'scan' => \Doctrine\DBAL\ParameterType::INTEGER]
        );
    }
}
