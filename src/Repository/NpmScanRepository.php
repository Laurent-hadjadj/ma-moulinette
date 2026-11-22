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

use App\Entity\NpmScan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description NpmScanRepository]
 *
 * @extends ServiceEntityRepository<NpmScan>
 */
class NpmScanRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NpmScan::class);
    }

    /**
     * @return array<int|string, mixed>
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
     * Idempotence métier : un scan existe déjà pour ce triplet projet à
     * cette date exacte — ne pas ré-ingérer.
     */
    public function findByProjectVersionDate(
        string $group,
        string $artifact,
        string $version,
        \DateTimeImmutable $scanDate
    ): ?NpmScan {
        return $this->findOneBy([
            'projectGroup'    => $group,
            'projectArtifact' => $artifact,
            'projectVersion'  => $version,
            'scanDate'        => $scanDate,
        ]);
    }

    /**
     * Dernier scan (toutes versions confondues) pour un triplet projet —
     * utilisé par l'ingestion du rapport "prod-only" pour retrouver le
     * scan "full" sur lequel croiser isProd.
     */
    public function findLatestForProject(string $group, string $artifact, string $version): ?NpmScan
    {
        return $this->createQueryBuilder('s')
            ->where('s.projectGroup = :group')
            ->andWhere('s.projectArtifact = :artifact')
            ->andWhere('s.projectVersion = :version')
            ->setParameter('group', $group)
            ->setParameter('artifact', $artifact)
            ->setParameter('version', $version)
            ->orderBy('s.scanDate', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Historique des scans d'un projet (plus récent en premier), toutes
     * versions confondues — pour la page "Tendance" du rapport PDF. Pas de
     * filtre par version : npm n'a pas de socle de version stable (les devs
     * modifient librement `package.json`), la seule dimension temporelle
     * cohérente est le projet (group:artifact) dans son ensemble.
     *
     * @return array<int, NpmScan>
     */
    public function listHistoryByProject(string $group, string $artifact, int $limit = 10): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.projectGroup = :group')
            ->andWhere('s.projectArtifact = :artifact')
            ->setParameter('group', $group)
            ->setParameter('artifact', $artifact)
            ->orderBy('s.scanDate', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * 1 ligne par (project_group, project_artifact) — le scan le plus
     * récent, pour la page d'index (liste des paquets scannés).
     *
     * @return array<int, array<string, mixed>>
     */
    public function listLatestPerProject(): array
    {
        $sql = "SELECT DISTINCT ON (project_group, project_artifact) *
                FROM ma_moulinette.npm_scan
                ORDER BY project_group, project_artifact, scan_date DESC";

        return $this->getEntityManager()->getConnection()
            ->executeQuery($sql)
            ->fetchAllAssociative();
    }
}
