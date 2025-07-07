<?php

namespace App\Repository;

use App\Entity\Actuator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Actuator>
 *
 * @method Actuator|null find($id, $lockMode = null, $lockVersion = null)
 * @method Actuator|null findOneBy(array $criteria, array $orderBy = null)
 * @method Actuator[]    findAll()
 * @method Actuator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActuatorRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, Actuator::class);
  }


  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
   * @return array
   *
   * Created at: 07/07/2025 09:36:59 (Europe/Paris)
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
   * [Description for deleteActuatorUrl]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 23/06/2024 14:56:24 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteActuatorUrl($map): array
  {
    $sql = "DELETE
            FROM actuator
            WHERE url = :url";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(':url', $map['url']);
              $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
        $this->getEntityManager()->getConnection()->rollback();
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for findActuatorOrderBy]
   *
   * @param mixed $sortColumn
   * @param mixed $sortDirection
   *
   * @return array
   *
   * Created at: 23/06/2024 14:56:20 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function findActuatorOrderBy($sortColumn, $sortDirection): array
  {
    $sql = "SELECT *
            FROM ma_moulinette.actuator
            ORDER BY ".$sortColumn." ".$sortDirection;

    try {
          $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $paginator=$stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'paginator_query' => $paginator, 'erreur' => ''];
  }

  /**
   * [Description for findActuatorOrderByDate]
   *
   * @param mixed $sortDirection
   *
   * @return array
   *
   * Created at: 23/06/2024 14:56:13 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function findActuatorOrderByDate($sortDirection): array
  {
    $sql = "SELECT * FROM ma_moulinette.actuator
            ORDER BY
                CASE
                    WHEN date_modification IS NOT NULL THEN date_modification
                    ELSE date_enregistrement
                END ".$sortDirection;
    try {
          $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $paginator=$stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'paginator_query' => $paginator, 'erreur' => ''];
  }

  /**
   * [Description for findActuatorMavenKey]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 26/06/2024 18:57:59 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function findActuatorMavenKey($map): array
  {
    $sql = "SELECT id, url, actuator_user, actuator_password
            FROM ma_moulinette.actuator
            WHERE maven_key = :maven_key";
    try {
          $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(':maven_key', $map['maven_key']);
            $liste=$stmt->executeQuery()->fetchAllAssociative();
            if (empty($liste)) {
                return ['code' => 404, 'message' => "Le projet n'a pas de point d'accès défini"];
            }
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }

      $id=$liste[0]['id'];
      $url=$liste[0]['url'];
      $user=$liste[0]['actuator_user'] ?? null;
      $password=$liste[0]['actuator_password'] ?? null;

      return ['code' => 200, 'erreur' => ''] + compact('url', 'user', 'password', 'id');
  }
}
