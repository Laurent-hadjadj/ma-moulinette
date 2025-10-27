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

use App\Entity\BatchTraitement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BatchTraitement>
 */
class BatchTraitementRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, BatchTraitement::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Doctrine\DBAL\Exception $e
   *
   * @return array
   *
   * Created at: 21/12/2024 20:06:26 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
  {
    if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
      return ['code' => 500, 'erreur' => static::$noDataBase];
    } else {
      return ['code' => 500, 'erreur'=> $e->getMessage()];
    }
  }

  /**
   * [Description for selectBatchTraitementDateEnregistrementAutomatiqueLast]
   * On récupère la dernière date de programmation du batch automatique
   *
   *
   * @return array
   *
   * Created at: 10/04/2024 09:10:49 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectBatchTraitementDateEnregistrementAutomatiqueLast(): array
  {
    $sql = "SELECT date_enregistrement as date
            FROM ma_moulinette.batch_traitement
            WHERE mode_collecte = 'TRAITEMENT AUTOMATIQUE'
            ORDER BY date_enregistrement DESC limit 1";
      try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $liste = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Doctrine\DBAL\Exception $e) {
        return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for selectBatchTraitementDateEnregistrementLast]
   * On récupère la dernière date du batch exécuté
   *
   * @return array
   *
   * Created at: 10/04/2024 09:35:40 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectBatchTraitementDateEnregistrementLast(): array
  {
    $sql = "SELECT date_enregistrement as date
            FROM ma_moulinette.batch_traitement
            ORDER BY date_enregistrement DESC limit 1";
    try {
          $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $liste = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for selectBatchTraitementLast]
   * On récupère la liste des derniers traitements,
   * groupé par titre et ordonné par responsable
   *
   * @param string $date
   *
   * @return array
   *
   * Created at: 10/04/2024 09:38:41 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectBatchTraitementLast($dateShort): array
  {
    $sql = "SELECT  mode_collecte, success, in_progress, titre, portefeuille,
                    nombre_projet as projet, responsable, responsable_short,
                    debut_traitement as debut, fin_traitement as fin
            FROM ma_moulinette.batch_traitement
            WHERE date(date_enregistrement)= :date_short
            GROUP BY mode_collecte, success, in_progress, titre, portefeuille, nombre_projet, responsable, responsable_short, debut_traitement, fin_traitement
            ORDER BY responsable ASC, mode_collecte ASC";
      try {
            $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(':date_short', $dateShort);
            $liste = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Doctrine\DBAL\Exception $e) {
        return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for selectBatchTraitement]
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 09/06/2024 21:55:58 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectBatchTraitement($map): array
  {
    $sql = "SELECT id, mode_collecte, titre, portefeuille, nombre_projet as projet
            FROM ma_moulinette.batch_traitement
            WHERE titre = :titre";
    try {
          $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(':titre', $map['titre_portefeuille']);
          $liste = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Doctrine\DBAL\Exception $e) {
        return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for updateBatchTraitement]
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 22/05/2024 17:56:41 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function updateBatchTraitement($map): array
  {
    $sql = "UPDATE ma_moulinette.batch_traitement
            SET debut_traitement = :debut_traitement, fin_traitement = :fin_traitement, success = :success, pending = :pending, in_progress = :in_progress
            WHERE id = :id";

    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(':success', $map['success']);
              $stmt->bindValue(':pending', $map['pending']);
              $stmt->bindValue(':in_progress', $map['in_progress']);
              $stmt->bindValue(':debut_traitement', $map['debut_traitement']);
              $stmt->bindValue(':fin_traitement', $map['fin_traitement']);
              $stmt->bindValue(':id', $map['id']);
              $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
      } catch (\Doctrine\DBAL\Exception $e) {
          $this->getEntityManager()->getConnection()->rollBack();
          return $this->handleDatabaseException($e);
      }
      return [ 'code' => 200, 'erreur' => '' ];
  }

  /**
   * [Description for countBatchTraitementPendingAndProgress]
   *
   *
   * @return array
   *
   * Created at: 27/10/2025 19:41:15 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function countBatchTraitementPendingAndProgress(): array
  {
    $sql = "SELECT count(pending) as pending, count(in_progress) as progress
            FROM ma_moulinette.batch_traitement
            WHERE mode_collecte = 'TRAITEMENT_MANUEL'
            AND pending = true
            AND in_progress = true";
    try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $exec = $stmt->executeQuery()->fetchAllAssociative();;
      } catch (\Doctrine\DBAL\Exception $e) {
          return $this->handleDatabaseException($e);
      }

      return [
          'code' => 200,
          'pending' => $exec[0]['pending'],
          'progress' => $exec[0]['progress'],
          'erreur' => ''
    ];
  }

  /**
   * [Description for insertBatchTraitement]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 07/06/2024 11:54:14 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertBatchTraitement($map): array
  {
    $sql = "INSERT INTO ma_moulinette.batch_traitement
                (mode_collecte, success, in_progress, pending, titre, portefeuille, nombre_projet, responsable, date_enregistrement)
            VALUES (:mode_collecte, :success, :in_progress, :pending, :titre, :portefeuille, :nombre_projet, :responsable, :date_enregistrement)";

      try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
              $stmt->bindValue(':success', $map['success']);
              $stmt->bindValue(':in_progress', $map['in_progress']);
              $stmt->bindValue(':pending', $map['pending']);
              $stmt->bindValue(':titre', $map['titre']);
              $stmt->bindValue(':portefeuille', $map['portefeuille']);
              $stmt->bindValue(':nombre_projet', $map['nombre_projet']);
              $stmt->bindValue(':responsable', $map['responsable']);
              $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));
              $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
      } catch (\Doctrine\DBAL\Exception $e) {
          $this->getEntityManager()->getConnection()->rollBack();
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for updatePortefeuille]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 26/10/2025 15:29:27 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function updatePortefeuille($map): array
  {
    $sql = "UPDATE ma_moulinette.batch_traitement
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
