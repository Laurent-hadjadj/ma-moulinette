<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Repository;

use App\Entity\Anomalie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description AnomalieRepository]
 */
class AnomalieRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $mavenKey = ':maven_key';
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, Anomalie::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Doctrine\DBAL\Exception $e
   *
   * @return array
   *
   * Created at: 21/12/2024 20:17:39 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
  {
      $message = $e->getMessage();

      if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
          $message = static::$noDataBase;
      }

      if ($e->getSqlState() == '23502') {
          $message = $e->getMessage();
      }

      if ($e->getSqlState() == '23505'){
          return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
      }

      return ['code' => 500, 'erreur'=> $message];
  }

  /**
   * [Description for deleteAnomalieMavenKey]
   *  On supprime les anomalies sur le projet
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 13/03/2024 18:01:52 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteAnomalieMavenKey($map): array
  {
    $sql = "DELETE
            FROM ma_moulinette.anomalie
            WHERE maven_key=:maven_key";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(static::$mavenKey, $map['maven_key']);
            $stmt->executeQuery();
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Doctrine\DBAL\Exception $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for selectAnomalieByProjectName]
   * Retourne la liste des anomalies par projet, trié  par ordre alphabétique
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 16/03/2024 21:24:57 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectAnomalieByProjectName(): array
  {
    $sql = "SELECT maven_key as key
            FROM ma_moulinette.anomalie
            GROUP BY maven_key, project_name
            ORDER BY project_name ASC";
    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $liste = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Doctrine\DBAL\Exception $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for selectAnomalie]
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 20/03/2024 16:17:09 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectAnomalie($map): array
  {
    $sql = "SELECT *
            FROM ma_moulinette.anomalie
            WHERE maven_key=:maven_key";
      try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(static::$mavenKey, $map['maven_key']);
            $liste = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Doctrine\DBAL\Exception $e) {
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for insertAnomalie]
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 29/05/2024 18:15:21 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertAnomalie($map): array
  {
    $sql = "INSERT INTO ma_moulinette.anomalie
              (maven_key, project_name, anomalie_total, dette_minute, dette_reliability_minute, dette_vulnerability_minute, dette_code_smell_minute, dette, dette_reliability, dette_vulnerability, dette_code_smell, frontend, backend, autre, blocker, critical, major, info, minor, bug, vulnerability, code_smell, mode_collecte, utilisateur_collecte, date_enregistrement)
            VALUES
              (:maven_key, :project_name, :anomalie_total, :dette_minute, :dette_reliability_minute, :dette_vulnerability_minute, :dette_code_smell_minute, :dette, :dette_reliability, :dette_vulnerability, :dette_code_smell, :frontend, :backend, :autre, :blocker, :critical, :major, :info, :minor, :bug, :vulnerability, :code_smell, :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();

            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(static::$mavenKey, $map['maven_key']);
              $stmt->bindValue(':project_name', $map['project_name']);
              $stmt->bindValue(':anomalie_total', $map['anomalie_total']);
              $stmt->bindValue(':dette', $map['dette']);
              $stmt->bindValue(':dette_reliability', $map['dette_reliability']);
              $stmt->bindValue(':dette_vulnerability', $map['dette_vulnerability']);
              $stmt->bindValue(':dette_code_smell', $map['dette_code_smell']);
              $stmt->bindValue(':dette_minute', $map['dette_minute']);
              $stmt->bindValue(':dette_reliability_minute', $map['dette_reliability_minute']);
              $stmt->bindValue(':dette_vulnerability_minute', $map['dette_vulnerability_minute']);
              $stmt->bindValue(':dette_code_smell_minute', $map['dette_code_smell_minute']);
              $stmt->bindValue(':frontend', $map['frontend']);
              $stmt->bindValue(':backend', $map['backend']);
              $stmt->bindValue(':autre', $map['autre']);
              $stmt->bindValue(':blocker', $map['blocker']);
              $stmt->bindValue(':critical', $map['critical']);
              $stmt->bindValue(':major', $map['major']);
              $stmt->bindValue(':info', $map['info']);
              $stmt->bindValue(':minor', $map['minor']);
              $stmt->bindValue(':bug', $map['bug']);
              $stmt->bindValue(':vulnerability', $map['vulnerability']);
              $stmt->bindValue(':code_smell', $map['code_smell']);
              $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
              $stmt->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);
              /** on formate la date avant de l'enregistrer */
              $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));
              $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
    } catch (\Doctrine\DBAL\Exception $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }

}
