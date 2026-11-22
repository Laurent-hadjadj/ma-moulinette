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

use App\Entity\NpmDependency;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description NpmDependencyRepository]
 *
 * @extends ServiceEntityRepository<NpmDependency>
 */
class NpmDependencyRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NpmDependency::class);
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

    public function findByName(string $name): ?NpmDependency
    {
        return $this->findOneBy(['name' => $name]);
    }

    /**
     * Top N paquets par nombre de findings cumulés (toutes périodes
     * confondues, tous projets) — pour le dashboard "Top paquets vulnérables".
     *
     * @return array<int, array<string, mixed>>
     */
    public function topVulnerableDependencies(int $limit = 10): array
    {
        $sql = "SELECT d.name, COUNT(f.id) AS finding_count
                FROM ma_moulinette.npm_dependency d
                JOIN ma_moulinette.npm_finding f ON f.dependency_id = d.id
                GROUP BY d.name
                ORDER BY finding_count DESC
                LIMIT :limit";

        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
        $stmt->bindValue('limit', $limit, \Doctrine\DBAL\ParameterType::INTEGER);

        return $stmt->executeQuery()->fetchAllAssociative();
    }

    /**
     * Vue org-wide (page `/npm-audit/paquets`, `ROLE_SECURITY_ANALYTICS`) :
     * pour chaque paquet npm ayant au moins un avis de sécurité, nombre de
     * **projets distincts** touchés. Calculé sur le **dernier scan de
     * chaque projet** (CTE `latest_scans`), pas tout l'historique — sinon
     * un projet rescanné 10 fois compterait pour 10, faussant le
     * classement (confirmé en conditions réelles : `angular-demo` a déjà
     * plusieurs scans historiques pour les mêmes versions).
     *
     * `worst_severity_rank` : 5=critical…1=info, reconverti en libellé par
     * l'appelant (évite une 2ᵉ requête juste pour un `CASE` inverse).
     *
     * @return array{code: 200, liste: array<int, array<string, mixed>>, erreur: string}|array{code: 500|23505, erreur: string}
     */
    public function listProjectImpact(int $limit = 200): array
    {
        $sql = <<<SQL
            WITH latest_scans AS (
                SELECT DISTINCT ON (project_group, project_artifact) id, project_group, project_artifact
                FROM ma_moulinette.npm_scan
                ORDER BY project_group, project_artifact, scan_date DESC
            )
            SELECT
                d.name,
                COUNT(DISTINCT (ls.project_group, ls.project_artifact)) AS nb_projects,
                COUNT(f.id) AS nb_findings,
                MAX(CASE f.severity_at_scan
                    WHEN 'critical' THEN 5
                    WHEN 'high'     THEN 4
                    WHEN 'moderate' THEN 3
                    WHEN 'low'      THEN 2
                    ELSE 1
                END) AS worst_severity_rank
            FROM ma_moulinette.npm_finding f
            JOIN ma_moulinette.npm_dependency d ON f.dependency_id = d.id
            JOIN latest_scans ls ON f.scan_id = ls.id
            GROUP BY d.name
            ORDER BY nb_projects DESC, nb_findings DESC, d.name ASC
            LIMIT :limit
        SQL;

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);
            $stmt->bindValue('limit', $limit, \Doctrine\DBAL\ParameterType::INTEGER);
            $rows = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $rows, 'erreur' => ''];
    }
}
