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
     * [Description for listDistinctTargetUrlsByMavenKey]
     * Liste les target_url distincts connus pour un maven_key, le plus
     * récemment scanné en tête.
     *
     * MODIF 26/10/2026 : un maven_key peut recouvrir plusieurs cibles DAST
     * réelles (ex. tier présentation + tier métier d'une même application,
     * suivis sous la même clé Maven côté SonarQube) — un maven_key ne
     * désigne donc plus forcément une cible DAST unique. La page de
     * restitution affiche désormais une "lane" (KPI + historique propres)
     * par target_url distinct, plutôt que de mélanger leurs scans dans un
     * seul historique (voir findLatestByMavenKey()/listHistoryByMavenKey()
     * ci-dessous, désormais scopées par target_url).
     *
     * @param string $mavenKey
     *
     * @return array<int, string|null>
     *
     * Created at: 26/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listDistinctTargetUrlsByMavenKey(string $mavenKey): array
    {
        $rows = $this->createQueryBuilder('s')
            ->select('s.targetUrl AS targetUrl')
            ->addSelect('MAX(s.generatedAt) AS maxGeneratedAt')
            ->where('s.mavenKey = :mavenKey')
            ->setParameter('mavenKey', $mavenKey)
            ->groupBy('s.targetUrl')
            ->orderBy('maxGeneratedAt', 'DESC')
            ->getQuery()
            ->getResult();

        return array_map(static fn(array $r): ?string => $r['targetUrl'], $rows);
    }

    /**
     * [Description for findLatestByMavenKey]
     * Dernier scan connu pour un projet, **pour une cible (target_url)
     * donnée** — scopé depuis le 26/10/2026 (voir
     * listDistinctTargetUrlsByMavenKey() ci-dessus : un maven_key peut
     * recouvrir plusieurs cibles DAST réelles). `$targetUrl` est requis
     * (pas de valeur par défaut) pour forcer l'appelant à être explicite ;
     * passer `null` filtre sur les scans sans target_url enregistré
     * (`IS NULL`), pas "toutes les cibles confondues". Tri par date de
     * génération du rapport, départagé par id si deux scans partagent le
     * même generatedAt (ex. un rapport ZAP multi-site scindé en plusieurs
     * rapports mono-site avant ingestion : même horodatage, id croissant
     * dans l'ordre d'ingestion). Pas d'équivalent is_latest_overall/release
     * côté ZAP : une cible DAST est une URL, pas une version Maven — un
     * simple tri suffit pour ce premier incrément.
     *
     * @param string $mavenKey
     * @param string|null $targetUrl
     *
     * @return ZapScan|null
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findLatestByMavenKey(string $mavenKey, ?string $targetUrl): ?ZapScan
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.mavenKey = :mavenKey')
            ->setParameter('mavenKey', $mavenKey);

        if ($targetUrl === null) {
            $qb->andWhere('s.targetUrl IS NULL');
        } else {
            $qb->andWhere('s.targetUrl = :targetUrl')->setParameter('targetUrl', $targetUrl);
        }

        return $qb->orderBy('s.generatedAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * [Description for listHistoryByMavenKey]
     * Historique des scans d'un projet (plus récent en premier), **pour une
     * cible (target_url) donnée** — scopé depuis le 26/10/2026, même
     * raison et même convention `$targetUrl` que findLatestByMavenKey()
     * ci-dessus. Pour le tableau Historique et le graphique d'évolution de
     * la page de restitution ZAP. Limité par défaut à 20 points (pas de
     * pagination pour ce premier jet). Départagé par id si deux scans
     * partagent le même generatedAt — même raison que
     * findLatestByMavenKey() ci-dessus.
     *
     * @param string $mavenKey
     * @param string|null $targetUrl
     * @param int $limit
     *
     * @return ZapScan[]
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listHistoryByMavenKey(string $mavenKey, ?string $targetUrl, int $limit = 20): array
    {
        $qb = $this->createQueryBuilder('s')
            ->where('s.mavenKey = :mavenKey')
            ->setParameter('mavenKey', $mavenKey);

        if ($targetUrl === null) {
            $qb->andWhere('s.targetUrl IS NULL');
        } else {
            $qb->andWhere('s.targetUrl = :targetUrl')->setParameter('targetUrl', $targetUrl);
        }

        return $qb->orderBy('s.generatedAt', 'DESC')
            ->addOrderBy('s.id', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }
}
