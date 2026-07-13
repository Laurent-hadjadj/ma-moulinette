<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Repository;

use App\Entity\DcFinding;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description DcFindingRepository]
 * MODIF 2026-05-08 : findings = jointure scan x dependency x cve.
 *
 * @extends ServiceEntityRepository<DcFinding>
 */
class DcFindingRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DcFinding::class);
    }

    /**
     * @return array{code: int, erreur: string}
     */
    public function handleDatabaseException(\Throwable $e): array
    {
        $message = $e->getMessage();

        if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
            $message = self::$noDataBase;
        }

        if ($e instanceof \Doctrine\DBAL\Exception\NotNullConstraintViolationException) {
            $message = $e->getMessage();
        }

        if ($e instanceof \Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
        }

        return ['code' => 500, 'erreur' => $message];
    }

    /**
     * [Description for listForScan]
     * Vue projet : tous les findings d'un scan, joins dénormalisés.
     *
     * MODIF 2026-05-15 : @return aligné sur la vraie shape de retour
     * (enveloppe {code, liste, erreur}).
     *
     * @param int $scanId
     *
     * @return array{code: int, liste?: array<int, array<string, mixed>>, erreur?: string}
     *
     * Created at: 08/05/2026 18:32:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listForScan(int $scanId): array
    {
        $sql = <<<SQL
            SELECT
                f.id,
                f.severity_at_scan,
                f.cvss_at_scan,
                d.file_name,
                d.pkg_coordinates,
                d.vendor,
                d.product,
                d.version AS dep_version,
                c.cve_id,
                c.cwes,
                c.description,
                c.cvss_v3_attack_vector
            FROM ma_moulinette.dc_finding f
                JOIN ma_moulinette.dc_dependency d ON f.dependency_id = d.id
                JOIN ma_moulinette.dc_cve c        ON f.cve_id = c.id
            WHERE f.scan_id = :scan
            ORDER BY
                CASE f.severity_at_scan
                    WHEN 'CRITICAL' THEN 1
                    WHEN 'HIGH'     THEN 2
                    WHEN 'MEDIUM'   THEN 3
                    WHEN 'LOW'      THEN 4
                    WHEN 'INFO'     THEN 5
                    ELSE 6
                END,
                f.cvss_at_scan DESC NULLS LAST,
                d.file_name
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
            'file_name' => $r['file_name'],
            'pkg_coordinates' => $r['pkg_coordinates'],
            'vendor' => $r['vendor'],
            'product' => $r['product'],
            'dep_version' => $r['dep_version'],
            'cve_id' => $r['cve_id'],
            'cwes' => json_decode($r['cwes'] ?? '[]', true) ?: [],
            'description' => $r['description'],
            'attack_vector' => $r['cvss_v3_attack_vector'],
        ], $rows);

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }
}
