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

use App\Entity\ZapScan;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ZapScanRepository]
 * MODIF 2026-10-13 : 1 scan = 1 rapport ZAP pour (maven_key, project_version,
 * target_url, generated_at).
 *
 * @extends ServiceEntityRepository<ZapScan>
 */
class ZapScanRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZapScan::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array{code: int, erreur: string}
     *
     * Created at: 13/10/2026 21:08:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * [Description for findByUniqueKey]
     * Idempotence : un même rapport ré-ingéré (même clé) retourne le scan
     * existant sans rien refaire.
     *
     * @param string $mavenKey
     * @param string $projectVersion
     * @param string|null $targetUrl
     * @param \DateTimeImmutable $generatedAt
     *
     * @return ZapScan|null
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findByUniqueKey(
        string $mavenKey,
        string $projectVersion,
        ?string $targetUrl,
        \DateTimeImmutable $generatedAt,
    ): ?ZapScan {
        return $this->findOneBy([
            'mavenKey'       => $mavenKey,
            'projectVersion' => $projectVersion,
            'targetUrl'      => $targetUrl,
            'generatedAt'    => $generatedAt,
        ]);
    }

    /**
     * [Description for findLatestByMavenKey]
     * Dernier scan connu pour un projet (tri par date de génération du
     * rapport). Pas d'équivalent is_latest_overall/release côté ZAP : une
     * cible DAST est une URL, pas une version Maven — un simple tri suffit
     * pour ce premier incrément.
     *
     * @param string $mavenKey
     *
     * @return ZapScan|null
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findLatestByMavenKey(string $mavenKey): ?ZapScan
    {
        return $this->createQueryBuilder('s')
            ->where('s.mavenKey = :mavenKey')
            ->setParameter('mavenKey', $mavenKey)
            ->orderBy('s.generatedAt', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * [Description for listHistoryByMavenKey]
     * Historique des scans d'un projet (plus récent en premier), pour le
     * tableau Historique et le graphique d'évolution de la page de
     * restitution ZAP. Limité par défaut à 20 points (pas de pagination
     * pour ce premier jet).
     *
     * @param string $mavenKey
     * @param int $limit
     *
     * @return ZapScan[]
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listHistoryByMavenKey(string $mavenKey, int $limit = 20): array
    {
        return $this->createQueryBuilder('s')
            ->where('s.mavenKey = :mavenKey')
            ->setParameter('mavenKey', $mavenKey)
            ->orderBy('s.generatedAt', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
