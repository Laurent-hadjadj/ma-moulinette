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

use App\Entity\NoSonar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NoSonarRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $mavenKey = ':maven_key';
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, NoSonar::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Doctrine\DBAL\Exception $e
   *
   * @return array
   *
   * Created at: 18/12/2024 15:25:17 (Europe/Paris)
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
   * [Description for deleteNoSonarMavenKey]
   * Supprime les données de la version courante (i.e. correspondant à la maven_key)
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 11/03/2024 17:36:11 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteNoSonarMavenKey($map): array
  {
    $sql = "DELETE
            FROM ma_moulinette.no_sonar
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
   * [Description for selectNoSonarRuleGroupByRule]
   * Retourne la liste des règles pour un projet groupé par règle.
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 22/03/2024 11:08:28 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectNoSonarRuleGroupByRule($map): array
  {
    $sql = "SELECT rule, count(*) as total
            FROM ma_moulinette.no_sonar
            WHERE maven_key=:maven_key
            GROUP BY rule";
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
   * [Description for insertNoSonar]
   * Ajout d'un noSonar ou d'un suppressWarning
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 07/05/2024 13:19:22 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertNoSonar($map): array
  {
    $sql = "INSERT INTO ma_moulinette.no_sonar
                (maven_key, rule, component, line, mode_collecte, utilisateur_collecte, date_enregistrement)
            VALUES
                (:maven_key, :rule, :component, :line, :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            foreach($map as $item){
              $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ",$sql));
                  $stmt->bindValue(static::$mavenKey, $item['maven_key']);
                  $stmt->bindValue(':rule', $item['rule']);
                  $stmt->bindValue(':component', $item['component']);
                  $stmt->bindValue(':line', $item['line']);
                  $stmt->bindValue(':mode_collecte', $item['mode_collecte']);
                  $stmt->bindValue(':utilisateur_collecte', $item['utilisateur_collecte']);
                  $stmt->bindValue(':date_enregistrement', $item['date_enregistrement']->format('Y-m-d H:i:sO'));
                  $stmt->executeStatement();
            }
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Doctrine\DBAL\Exception $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
    }
    return ['code'=>200, 'erreur'=>''];
  }
}
