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

use App\Entity\OwaspDast;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MODIF 2026-10-13 : agrégation OWASP Top 10 (2017/2021) issue d'un scan DAST
 * ZAP — complément de la table `owasp` (SAST SonarQube).
 *
 * @extends ServiceEntityRepository<OwaspDast>
 */
class OwaspDastRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OwaspDast::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array{code: int, erreur: string}
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
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
     * [Description for findByScanAndReferential]
     *
     * @param int $scanId
     * @param int $referentialOwasp
     *
     * @return OwaspDast|null
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function findByScanAndReferential(int $scanId, int $referentialOwasp): ?OwaspDast
    {
        return $this->createQueryBuilder('o')
            ->where('o.scan = :scanId')
            ->andWhere('o.referentialOwasp = :referentialOwasp')
            ->setParameter('scanId', $scanId)
            ->setParameter('referentialOwasp', $referentialOwasp)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * [Description for findLatestByMavenKey]
     * Les lignes (une par référentiel, 2017 et/ou 2021) du scan le plus
     * récent pour un projet.
     *
     * @param string $mavenKey
     *
     * @return OwaspDast[]
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    public function findLatestByMavenKey(string $mavenKey): array
    {
        $qb = $this->getEntityManager()->createQueryBuilder();
        $maxGeneratedAt = $qb
            ->select('MAX(s2.generatedAt)')
            ->from(\App\Entity\ZapScan::class, 's2')
            ->where('s2.mavenKey = :mavenKey')
            ->getDQL();

        return $this->createQueryBuilder('o')
            ->join('o.scan', 's')
            ->where('o.mavenKey = :mavenKey')
            ->andWhere("s.generatedAt = ({$maxGeneratedAt})")
            ->setParameter('mavenKey', $mavenKey)
            ->getQuery()
            ->getResult();
    }
}
