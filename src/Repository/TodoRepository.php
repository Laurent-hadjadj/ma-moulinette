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

use App\Entity\Todo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description TodoRepository]
 */
class TodoRepository extends ServiceEntityRepository
{

  public static $removeReturnLine = "/\s+/u";
  public static $noDataBase = 'La connexion à la base de données a échoué.';
  public static $mavenKey = ':maven_key';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Todo::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
   * @return array
   *
   * Created at: 06/07/2025 12:11:45 (Europe/Paris)
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
   * [Description for deleteTodoMavenKey]
   * Supprime les T_do pour la version courante (i.e. correspondant à la maven_key)
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 14/03/2024 11:16:47 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteTodoMavenKey($map): array
  {
    $sql = "DELETE
            FROM ma_moulinette.todo
            WHERE maven_key = :maven_key";
    try {
        $this->getEntityManager()->getConnection()->beginTransaction();
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(static::$mavenKey, $map['maven_key']);
            $stmt->executeStatement();
        $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
      $this->getEntityManager()->getConnection()->rollBack();
      return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for selectTodoRuleGroupByRule]
   * Retourne la liste des to.do pour un projet groupé par règle.
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 22/03/2024 11:33:43 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectTodoRuleGroupByRule($map): array
  {
    $sql = "SELECT rule, count(*) as total
            FROM ma_moulinette.todo
            WHERE maven_key = :maven_key
            GROUP BY rule";
    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $stmt->bindValue(static::$mavenKey, $map['maven_key']);
          $liste = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
      return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for selectTodoComponentOrderByRule]
   * On retourne la liste des règle et du détail pour le projet.
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 22/03/2024 11:38:16 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectTodoComponentOrderByRule($map): array
  {
    $sql = "SELECT rule, component, line
            FROM ma_moulinette.todo
            WHERE maven_key = :maven_key
            ORDER BY rule";
    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(static::$mavenKey, $map['maven_key']);
          $liste = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'liste' => $liste, 'erreur'=>''];
  }

  /**
   * [Description for insertTodo]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 31/05/2024 20:21:10 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertTodo($map): array
  {
      $sql = "INSERT INTO ma_moulinette.todo
                  (maven_key, rule, component, line, mode_collecte, utilisateur_collecte, date_enregistrement)
              VALUES
                  (:maven_key, :rule, :component, :line, :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
      try {
            $this->getEntityManager()->getConnection()->beginTransaction();
              foreach($map as $item){
                  $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
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
      } catch (\Throwable $e) {
          $this->getEntityManager()->getConnection()->rollBack();
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'erreur' => ''];
  }
}
