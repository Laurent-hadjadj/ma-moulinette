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

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\DBAL\ParameterType;
use App\Entity\BatchTraitement;
use App\Repository\Traits\DoctrineParamHelperTrait;

/**
 * @extends ServiceEntityRepository<BatchTraitement>
 */
class BatchTraitementRepository extends ServiceEntityRepository
{
  use DoctrineParamHelperTrait;

  private static $removeReturnLine = "/\s+/u";
  private static $noDataBase = 'La connexion à la base de données a échoué.';
  private static $traitementId = ':traitement_id';
  private static $pending = ':pending';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, BatchTraitement::class);
  }

/**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
   * @return array
   *
   * Created at: 29/10/2025 22:36:06 (Europe/Paris)
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
   * [Description for selectBatchTraitementAutomatiqueListe]
   *
   * @return array
   *
   * Created at: 06/11/2025 17:51:11 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectBatchTraitementAutomatiqueListe(): array
  {
      $sql = "SELECT titre as nom_traitement, portefeuille,
                      nombre_projet, traitement_id
              FROM ma_moulinette.batch_traitement
              WHERE mode_collecte = 'TRAITEMENT AUTOMATIQUE'
                AND in_progress = false
                AND activated = true
              ORDER BY date_enregistrement";

      $conn = $this->getEntityManager()->getConnection();

      try {
            $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $liste = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
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
    } catch (\Throwable $e) {
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
                    debut_traitement as debut, fin_traitement as fin,
                    traitement_id
            FROM ma_moulinette.batch_traitement
            WHERE date(date_enregistrement)= :date_short
            GROUP BY mode_collecte,
                      success, in_progress,
                      titre, portefeuille, nombre_projet,
                      responsable, responsable_short,
                      debut_traitement, fin_traitement,
                      traitement_id
            ORDER BY responsable ASC, mode_collecte ASC";

      $conn = $this->getEntityManager()->getConnection();

      try {
            $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(':date_short', $dateShort);
            $liste = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
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
    $sql = "SELECT id, mode_collecte, titre, portefeuille, nombre_projet, traitement_id
            FROM ma_moulinette.batch_traitement
            WHERE titre = :titre";
    $conn = $this->getEntityManager()->getConnection();

    try {
          $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                  $stmt->bindValue(':titre', $map['titre_portefeuille']);
          $liste = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
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
    $sql = "SELECT
              SUM(CASE WHEN pending IS TRUE THEN 1 ELSE 0 END) AS pending_count,
              SUM(CASE WHEN in_progress IS TRUE THEN 1 ELSE 0 END) AS in_progress_count,
              SUM(CASE WHEN pending IS NULL THEN 1 ELSE 0 END) AS pending_null_count,
              SUM(CASE WHEN in_progress IS NULL THEN 1 ELSE 0 END) AS in_progress_null_count
            FROM ma_moulinette.batch_traitement
            WHERE mode_collecte = 'TRAITEMENT MANUEL'
            AND activated = true";

    $conn = $this->getEntityManager()->getConnection();

    try {
            $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $exec = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }

      return [
          'code' => 200,
          'pending' => $exec[0]['pending_count'],
          'progress' => $exec[0]['in_progress_count'],
          'pending_null' => $exec[0]['pending_null_count'],
          'in_progress_null' => $exec[0]['in_progress_null_count'],
          'erreur' => ''
    ];
  }

  /**
   * [Description for updateBatchTraitementPending]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 02/11/2025 21:30:02 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function updateBatchTraitementPending($map): array
  {
    $sql = "UPDATE ma_moulinette.batch_traitement
            SET pending = :pending
            WHERE traitement_id = :traitement_id";

    $conn = $this->getEntityManager()->getConnection();

    try {
          $conn->beginTransaction();
          $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                  $this->bindNullableBool($stmt, static::$pending, $map['pending']);
                  $this->bindUlidAsString($stmt, static::$traitementId, $map['traitement_id']);
          $stmt->executeStatement();
          $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            return $this->handleDatabaseException($e);
        }

        /** on prépare la réponse */
        return ['code' => 200, 'erreur' => ''];
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
            SET debut_traitement = :debut_traitement,
                fin_traitement = :fin_traitement,
                success = :success,
                pending = :pending,
                in_progress = :in_progress
            WHERE traitement_id = :traitement_id";

    $conn = $this->getEntityManager()->getConnection();

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $stmt->bindValue(':debut_traitement', $map['debut_traitement']);
          $stmt->bindValue(':fin_traitement', $map['fin_traitement']);

          // Gestion du type ULID pour PostgreSQL
          $this->bindNullableBool($stmt, ':success', $map['success']);
          $this->bindNullableBool($stmt, static::$pending, $map['pending']);
          $this->bindNullableBool($stmt, ':in_progress', $map['in_progress']);
          $this->bindUlidAsString($stmt, static::$traitementId, $map['traitement_id']);

        // Exécution
        $stmt->executeStatement();
        $conn->commit();
      } catch (\Throwable $e) {
        $conn->rollBack();
        return $this->handleDatabaseException($e);
      }

      return [ 'code' => 200, 'erreur' => '' ];
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
  public function insertBatchTraitement(array $map): array
  {
      $sql = "INSERT INTO ma_moulinette.batch_traitement
              (activated, mode_collecte, success, in_progress, pending, titre, portefeuille, nombre_projet, responsable, responsable_short, traitement_id, date_enregistrement)
              VALUES (:activated, :mode_collecte, :success, :in_progress, :pending, :titre, :portefeuille, :nombre_projet, :responsable, :responsable_short, :traitement_id, :date_enregistrement)";

      $conn = $this->getEntityManager()->getConnection();

      // Nettoyage des chaînes vides pour les booléens nullable
      foreach (['success', 'pending', 'in_progress'] as $key) {
          if ($map[$key] === '') {
              $map[$key] = null;
          }
      }

      try {
          $conn->beginTransaction();
          $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));

                  // Paramètres texte / int
                  $stmt->bindValue(':mode_collecte', $map['mode_collecte'], ParameterType::STRING);
                  $stmt->bindValue(':titre', $map['titre'], ParameterType::STRING);
                  $stmt->bindValue(':portefeuille', $map['portefeuille'], ParameterType::STRING);
                  $stmt->bindValue(':nombre_projet', $map['nombre_projet'], ParameterType::INTEGER);
                  $stmt->bindValue(':responsable', $map['responsable'], ParameterType::STRING);
                  $stmt->bindValue(':responsable_short', $map['responsable_short'], ParameterType::STRING);
                  $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));

                  $this->bindNullableBool($stmt, ':activated', $map['activated']);
                  $this->bindNullableBool($stmt, ':success', $map['success']);
                  $this->bindNullableBool($stmt, static::$pending, $map['pending']);
                  $this->bindNullableBool($stmt, ':in_progress', $map['in_progress']);
                  $this->bindUlidAsString($stmt, static::$traitementId, $map['traitement_id']);

          // Exécution
          $stmt->executeStatement();
          $conn->commit();
      } catch (\Throwable $e) {
          $conn->rollBack();
          return $this->handleDatabaseException($e);
      }

      return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for deleteTraitement]
   *
   * @param mixed $traitement_id
   *
   * @return array
   *
   * Created at: 29/10/2025 21:40:32 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteTraitement($traitement_id): array
  {
    $sql = "DELETE
            FROM ma_moulinette.batch_traitement
            WHERE traitement_id = :traitement_id";

    $conn = $this->getEntityManager()->getConnection();

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $this->bindUlidAsString($stmt, static::$traitementId, $traitement_id);
          $stmt->executeStatement();
        $conn->commit();
    } catch (\Throwable $e) {
        $conn->rollBack();
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
    $conn = $this->getEntityManager()->getConnection();

    try {
          $conn->beginTransaction();
          $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                  $stmt->bindValue(':portefeuille', $map['portefeuille']);
                  $stmt->bindValue(':nombre_projet', $map['nombre_projet']);
                  $stmt->executeStatement();
          $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            return $this->handleDatabaseException($e);
        }

        /** on prépare la réponse */
        return ['code' => 200, 'erreur' => ''];
  }


}
