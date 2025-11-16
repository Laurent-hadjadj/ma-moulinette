<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2025.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Repository;

use App\Entity\BatchProfiling;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description BatchProfilingRepository]
 */
class BatchProfilingRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, BatchProfiling::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
   * @return array
   *
   * Created at: 26/10/2025 12:19:01 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function handleDatabaseException(\Throwable $e): array
  {
    $message = $e->getMessage();

    // message = 'SQLSTATE[08006]'
    if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
      $message = static::$noDataBase;
    }

    // state = '23502'
    if ($e instanceof \Doctrine\DBAL\Exception\NotNullConstraintViolationException) {
      $message = $e->getMessage();
    }

    // state = '23505'
    if ($e instanceof \Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
      return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
    }

    return ['code' => 500, 'erreur' => $message];
  }

  /**
   * [Description for getGlobalKpi]
   *
   * @return array
   *
   * Created at: 16/11/2025 15:21:45 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function getGlobalKpi(): array
  {
    $sql = "
            SELECT
              ROUND(AVG(temps_total_moyen_s), 2) AS average_time_total,
              ROUND(AVG(temps_moyen_projet_s), 2) AS average_time_projet,
              ROUND(AVG(memoire_peak_moyenne_mo), 2) AS average_memory_peak,
              ROUND(AVG(memoire_moyenne_mo), 2) AS average_memory,
              ROUND(AVG(memoire_peak_max_mo), 2) AS average_memory_peak_max,
              MAX(derniere_execution) AS last_execution
            FROM ma_moulinette.vw_batch_profiling_stats";

    $conn = $this->getEntityManager()->getConnection();

    try {
        $stmt = $conn->prepare(preg_replace(self::$removeReturnLine, ' ', $sql));
        $result = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
      return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'summary' => $result, 'erreur' => ''];
  }

  /**
   * [Description for findGlobalSummary]
   *
   * @param mixed $indicateur
   *
   * @return array
   *
   * Created at: 16/11/2025 21:00:01 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function findGlobalSummary($indicateur): array
  {
    // Liste des colonnes autorisées (à adapter selon les colonnes que tu veux autoriser)
    $validIndicators = ['utilisateur', 'portefeuille', 'granularite',
            'periode', 'nb_projets', 'nb_exec', 'derniere_execution'];

    // Vérifie que l'indicateur fourni est valide
    if (!in_array($indicateur, $validIndicators)) {
        throw new \InvalidArgumentException("Indicateur invalide : {$indicateur}");
    }

    // Construire la requête avec l'indicateur en dur
    $sql = "SELECT {$indicateur},
                  ROUND(AVG(temps_total_moyen_s), 2) AS average_time,
                  ROUND(AVG(memoire_peak_moyenne_mo), 2) AS average_memory
            FROM ma_moulinette.vw_batch_profiling_summary
            GROUP BY {$indicateur}
            ORDER BY {$indicateur} DESC
            LIMIT 10";

    $conn = $this->getEntityManager()->getConnection();

    try {
        $stmt = $conn->prepare(preg_replace(self::$removeReturnLine, ' ', $sql));
        $result = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
      return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'indicateur' => $result ?? [], 'erreur' => ''];
  }


  /**
   * [Description for findLatest]
   *
   * @param int $limit
   *
   * @return array
   *
   * Created at: 16/11/2025 15:10:36 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function findLatest(int $limit = 10): array
  {
      return $this->createQueryBuilder('b')
          ->orderBy('b.dateExecution', 'DESC')
          ->setMaxResults($limit)
          ->getQuery()
          ->getArrayResult();
  }

  /**
   * [Description for findStatsByPortefeuille]
   *
   * @return array
   *
   * Created at: 16/11/2025 15:10:29 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function findStatsByPortefeuille(): array
  {
    return $this->getEntityManager()
        ->getConnection()
        ->executeQuery('SELECT
            portefeuille,
              AVG(temps_moyen) AS average_time,
              AVG(memoire_moyenne) AS average_memory
            FROM batch_profiling
            GROUP BY portefeuille
            ORDER BY portefeuille')
        ->fetchAllAssociative();
  }

  /**
   * [Description for findWeeklyStats]
   *
   * @return array
   *
   * Created at: 16/11/2025 15:10:26 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function findWeeklyStats(): array
  {
    return $this->getEntityManager()
        ->getConnection()
        ->executeQuery('SELECT
                semaine,
                portefeuille,
                temps_total_moyen_s AS average_time,
                memoire_peak_moyenne_mo AS average_memory
            FROM ma_moulinette.vw_batch_profiling_weekly
            ORDER BY semaine ASC, portefeuille ASC')
        ->fetchAllAssociative();
  }

  /**
   * [Description for findMonthlyStats]
   *
   * @return array
   *
   * Created at: 16/11/2025 15:10:22 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function findMonthlyStats(): array
  {
    return $this->getEntityManager()
        ->getConnection()
        ->executeQuery('SELECT
                mois,
                portefeuille,
                temps_total_moyen_s AS average_time,
                memoire_peak_moyenne_mo AS average_memory
            FROM ma_moulinette.vw_batch_profiling_monthly
            ORDER BY mois ASC')
        ->fetchAllAssociative();
  }

  /**
   * [Description for findUsersStats]
   *
   * @return array
   *
   * Created at: 16/11/2025 15:10:18 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function findUsersStats(): array
  {
      return $this->getEntityManager()
          ->getConnection()
          ->executeQuery('SELECT
                utilisateur,
                temps_total_moyen_s AS average_time,
                memoire_peak_moyenne_mo AS average_memory
            FROM ma_moulinette.vw_batch_profiling_global
            ORDER BY utilisateur ASC')
          ->fetchAllAssociative();
  }
}
