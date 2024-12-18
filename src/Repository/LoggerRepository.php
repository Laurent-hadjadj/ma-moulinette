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

use App\Entity\Logger;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description LoggerRepository]
 */
class LoggerRepository extends ServiceEntityRepository
{

  public static $removeReturnLine = "/\s+/u";
  public static $mavenKey = ':maven_key';
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Logger::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Doctrine\DBAL\Exception $e
   *
   * @return array
   *
   * Created at: 18/12/2024 15:44:27 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
  {
      if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
          return ['code' => 500, 'erreur' => static::$noDataBase];
      } else {
          return ['code' => 500, 'erreur' => $e->getMessage()];
      }
  }

  /**
   * [Description for deleteLoggerMavenKey]
   * Supprime les Logger pour la version courante (i.e. correspondant à la maven_key)
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 10/07/2024 20:16:47 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteLoggerMavenKey($map): array
  {
    $sql = "DELETE
            FROM ma_moulinette.logger
            WHERE maven_key=:maven_key";
    try {
        $this->getEntityManager()->getConnection()->beginTransaction();
          $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(static::$mavenKey, $map['maven_key']);
            $stmt->executeStatement();
      $this->getEntityManager()->getConnection()->commit();
    } catch (\Doctrine\DBAL\Exception $e) {
      $this->getEntityManager()->getConnection()->rollBack();
      return $this->handleDatabaseException($e);
    }
    return ['code'=>200, 'erreur'=>''];
  }

  /**
   * [Description for selectLogger]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 12/07/2024 08:18:20 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectLogger($map): array
  {
    $sql = "SELECT logger_info, logger_warn, logger_error, logger_debug
            FROM ma_moulinette.logger
            WHERE maven_key=:maven_key";
      try {
            $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(static::$mavenKey, $map['maven_key']);
            $liste=$stmt->executeQuery()->fetchAllAssociative();
      } catch (\Doctrine\DBAL\Exception $e) {
        return $this->handleDatabaseException($e);
      }
      return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
  }


  /**
   * [Description for insertLogger]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 10/07/2024 19:29:10 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertLogger($map): array
  {
      $sql = "INSERT INTO ma_moulinette.logger
                  (maven_key, logger_info, logger_warn, logger_error, logger_debug, mode_collecte, utilisateur_collecte, date_enregistrement)
              VALUES
                  (:maven_key, :logger_info, :logger_warn, :logger_error, :logger_debug, :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
      try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                  $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                  $stmt->bindValue(':logger_info', $map['logger_info']);
                  $stmt->bindValue(':logger_warn', $map['logger_warn']);
                  $stmt->bindValue(':logger_error', $map['logger_error']);
                  $stmt->bindValue(':logger_debug', $map['logger_debug']);
                  $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
                  $stmt->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);
                  $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));
                  $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
      } catch (\Doctrine\DBAL\Exception $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
      }
      return ['code'=>200, 'erreur'=>''];
  }
}
