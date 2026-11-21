<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  https://www.gnu.org/licenses/agpl-3.0.html
*/

namespace App\Repository;

use App\Entity\BatchExecution;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Traits\DoctrineParamHelperTrait;
use Symfony\Component\Uid\Ulid;

/**
 * @extends ServiceEntityRepository<BatchExecution>
 */
class BatchExecutionRepository extends ServiceEntityRepository
{
  use DoctrineParamHelperTrait;

    private static string $removeReturnLine = "/\s+/u";
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, BatchExecution::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
     * @return array<int|string, mixed>
   *
   * Created at: 07/07/2025 09:36:06 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
   */
  public function handleDatabaseException(\Throwable $e): array
  {
    $message = $e->getMessage();

    // message = 'SQLSTATE[08006]'
    if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
        $message = self::$noDataBase;
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
   * [Description for deleteTraitement]
   * $transaction_id peut être un Ulid ou une chaîne de caractères.
   *
     * @return array<int|string, mixed>
   *
   * Created at: 18/11/2026 19:53:22 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
   */
    public function deleteTraitement(Ulid|string $traitement_id): array
  {
    $sql = "DELETE
            FROM ma_moulinette.batch_execution
            WHERE traitement_id = :traitement_id";

    $conn = $this->getEntityManager()->getConnection();

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
          $this->bindUlidAsString($stmt, ':traitement_id', $traitement_id);
          $stmt->executeStatement();
        $conn->commit();
    } catch (\Throwable $e) {
        $conn->rollBack();
        return $this->handleDatabaseException($e);
    }

    return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for selectBatchExecutionTraitementId]
   *
   *
     * @return array<int|string, mixed>
   *
   * Created at: 18/11/2026 20:40:08 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
   */
    public function selectBatchExecutionLastTraitementId(Ulid|string $traitement_id): array
  {
      $sql = "SELECT id
              FROM ma_moulinette.batch_execution
              WHERE traitement_id = :traitement_id
              ORDER BY date_enregistrement DESC
              limit 1";
      $conn = $this->getEntityManager()->getConnection();

      try {
            $stmt = $conn->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
              $this->bindUlidAsString($stmt, ':traitement_id', $traitement_id);
            $find = $stmt->executeQuery()->fetchAssociative();
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }

      return ['code' => 200, 'id' => $find['id'] ?? [], 'erreur' => ''];
  }

}
