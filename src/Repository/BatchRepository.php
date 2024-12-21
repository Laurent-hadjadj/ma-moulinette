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
   * @param \Doctrine\DBAL\Exception $e
   *
   * @return array
   *
   * Created at: 21/12/2024 20:15:01 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
  {
    if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
      return ['code'=>500, 'erreur' => static::$noDataBase];
    } else {
      return ['code'=>500, 'erreur'=> $e->getMessage()];
    }
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
    $sql = "SELECT statut, titre, responsable, portefeuille, nombre_projet as nombre
            FROM batch
            ORDER BY statut ASC";
      try {
            $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $liste=$stmt->executeQuery()->fetchAllAssociative();
      } catch (\Doctrine\DBAL\Exception $e) {
          return $this->handleDatabaseException($e);
      }
      return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
  }
}
