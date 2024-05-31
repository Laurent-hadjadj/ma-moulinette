<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
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

  public static $removeReturnline = "/\s+/u";

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Todo::class);
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
  public function deleteTodoMavenKey($map):array
  {
    try {
      $this->getEntityManager()->getConnection()->beginTransaction();
        $sql = "DELETE
                FROM todo
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            $conn->bindValue(':maven_key', $map['maven_key']);
            $conn->executeStatement();
      $this->getEntityManager()->getConnection()->commit();
    } catch (\Doctrine\DBAL\Exception $e) {
      $this->getEntityManager()->getConnection()->rollBack();
      return ['code'=>500, 'erreur'=> $e->getMessage()];
    }
    return ['code'=>200, 'erreur'=>''];
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
  public function selectTodoRuleGroupByRule($map):array
  {
    try {
        $sql = "SELECT rule, count(*) as total
                FROM todo
                WHERE maven_key=:maven_key
                GROUP BY rule";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
          $exec=$conn->executeQuery();
          $liste=$exec->fetchAllAssociative();
    } catch (\Doctrine\DBAL\Exception $e) {
      return ['code'=>500, 'erreur'=> $e->getMessage()];
    }
    return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
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
  public function selectTodoComponentOrderByRule($map):array
  {
    try {
            $sql = "SELECT rule, component, line
                    FROM todo
                    WHERE maven_key=:maven_key
                    ORDER BY rule";
            $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            $conn->bindValue(':maven_key', $map['maven_key']);
            $exec=$conn->executeQuery();
            $liste=$exec->fetchAllAssociative();
    } catch (\Doctrine\DBAL\Exception $e) {
      return ['code'=>500, 'erreur'=> $e->getMessage()];
    }
    return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
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
  public function insertTodo($map):array
  {
      $sql = "INSERT INTO todo
                  (maven_key, rule, component, line, date_enregistrement)
              VALUES
                  (:maven_key, :rule, :component, :line, :date_enregistrement)";
      try {
              $this->getEntityManager()->getConnection()->beginTransaction();
                  foreach($map as $item){
                      $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                          $stmt->bindValue(':maven_key', $item['maven_key']);
                          $stmt->bindValue(':rule', $item['rule']);
                          $stmt->bindValue(':component', $item['component']);
                          $stmt->bindValue(':line', $item['line']);
                          $stmt->bindValue(':date_enregistrement', $item['date_enregistrement']->format('Y-m-d H:i:sO'));
                          $stmt->executeStatement();
              }
              $this->getEntityManager()->getConnection()->commit();
      } catch (\Doctrine\DBAL\Exception $e) {
          $this->getEntityManager()->getConnection()->rollBack();
          return ['code'=>500, 'erreur'=> $e->getMessage()];
      }
      return ['code'=>200, 'erreur'=>''];
  }
}
