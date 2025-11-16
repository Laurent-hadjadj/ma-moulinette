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

use App\Entity\Batch;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description BatchRepository]
 */
class BatchRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, Batch::class);
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
   * [Description for selectBatchByStatut]
   *
   * @return array
   *
   * Created at: 22/05/2024 18:04:17 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectBatchByStatut(): array
  {
    $sql = "SELECT activated, titre, responsable, portefeuille,
                  nombre_projet as nombre
            FROM batch
            ORDER BY activated ASC";
      try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $liste = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for updatePortefeuille]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 26/10/2025 13:05:57 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function updatePortefeuille($map): array
  {
    $sql = "UPDATE ma_moulinette.batch
            SET nombre_projet = :nombre_projet
            WHERE portefeuille = :portefeuille";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(':portefeuille', $map['portefeuille']);
              $stmt->bindValue(':nombre_projet', $map['nombre_projet']);
            $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'erreur' => ''];
  }
}
