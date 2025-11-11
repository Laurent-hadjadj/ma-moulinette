<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2025.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Repository;

use App\Entity\BatchExecutionJournal;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use App\Repository\Traits\DoctrineParamHelperTrait;

/**
 * [Description BatchExecutionJournalRepository]
 */
class BatchExecutionJournalRepository extends ServiceEntityRepository
{
  use DoctrineParamHelperTrait;

  public static $removeReturnLine = "/\s+/u";
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, BatchExecutionJournal::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
   * @return array
   *
   * Created at: 07/07/2025 09:36:06 (Europe/Paris)
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
   * [Description for deleteTraitement]
   *
   * @param mixed $traitement_id
   *
   * @return array
   *
   * Created at: 11/11/2025 13:05:12 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteJournal($job_id): array
  {
    $sql = "DELETE
            FROM ma_moulinette.batch_execution_journal
            WHERE job_id = :traitement_id";

    $conn = $this->getEntityManager()->getConnection();

    try {
        $conn->beginTransaction();
        $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $this->bindValue(':journal_id', $job_id);
          $stmt->executeStatement();
        $conn->commit();
    } catch (\Throwable $e) {
        $conn->rollBack();
        return $this->handleDatabaseException($e);
    }

    return ['code' => 200, 'erreur' => ''];
  }

}
