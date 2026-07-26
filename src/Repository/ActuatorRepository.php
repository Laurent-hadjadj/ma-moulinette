<?php

namespace App\Repository;

use App\Entity\Actuator;
use App\Service\ActuatorCredentialCipher;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Actuator>
 */
class ActuatorRepository extends ServiceEntityRepository
{
    private static string $removeReturnLine = "/\s+/u";
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(
      ManagerRegistry $registry,
      private ActuatorCredentialCipher $cipher
  ) {
      parent::__construct($registry, Actuator::class);
  }


  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
     * @return array<int|string, mixed>
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
   * [Description for deleteActuatorUrl]
   *
   * @param array<string, mixed> $map
   *
     * @return array<int|string, mixed>
   *
   * Created at: 23/06/2024 14:56:24 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
    public function deleteActuatorUrl(array $map): array
  {
    $sql = "DELETE
            FROM actuator
            WHERE url = :url";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
              $stmt->bindValue(':url', $map['url']);
              $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
        $this->getEntityManager()->getConnection()->rollback();
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }

    /** Colonnes triables autorisees (defense en profondeur cote repo) */
    private const ALLOWED_SORT_COLUMNS = ['nom_application', 'url', 'personne', 'date_modification', 'date_enregistrement'];

    /**
     * Sanitize la direction de tri (ASC ou DESC, fallback DESC).
     */
    private static function sanitizeSortDirection(string $direction): string
    {
        $direction = strtoupper($direction);
        return ($direction === 'ASC' || $direction === 'DESC') ? $direction : 'DESC';
    }

  /**
   * [Description for findActuatorOrderBy]
   *
     * @param string $sortColumn
     * @param string $sortDirection
   *
     * @return array<int|string, mixed>
   *
   * Created at: 23/06/2024 14:56:20 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
    public function findActuatorOrderBy(string $sortColumn, string $sortDirection): array
  {
        $sortColumn = in_array($sortColumn, self::ALLOWED_SORT_COLUMNS, true) ? $sortColumn : 'nom_application';
        $sortDirection = self::sanitizeSortDirection((string) $sortDirection);

    $sql = "SELECT *
            FROM ma_moulinette.actuator
                ORDER BY {$sortColumn} {$sortDirection}";

    try {
         $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
         $paginator = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'paginator_query' => $paginator, 'erreur' => ''];
  }

  /**
   * [Description for findActuatorOrderByDate]
   *
     * @param string $sortDirection
   *
     * @return array<int|string, mixed>
   *
   * Created at: 23/06/2024 14:56:13 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
    public function findActuatorOrderByDate(string $sortDirection): array
  {
        $sortDirection = self::sanitizeSortDirection((string) $sortDirection);

    $sql = "SELECT * FROM ma_moulinette.actuator
            ORDER BY
                CASE
                    WHEN date_modification IS NOT NULL THEN date_modification
                    ELSE date_enregistrement
                    END {$sortDirection}";
    try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $paginator = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'paginator_query' => $paginator, 'erreur' => ''];
  }

  /**
   * [Description for findActuatorMavenKey]
   *
   * @param array<string, mixed> $map
   *
     * @return array<int|string, mixed>
   *
   * Created at: 26/06/2024 18:57:59 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
    public function findActuatorMavenKey(array $map): array
  {
    $sql = "SELECT id, url, actuator_user, actuator_password
            FROM ma_moulinette.actuator
            WHERE LOWER(maven_key) = LOWER(:maven_key)";
    try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(':maven_key', $map['maven_key']);
            $liste = $stmt->executeQuery()->fetchAllAssociative();
            if (empty($liste)) {
                return ['code' => 404, 'message' => "Le projet n'a pas de point d'accès défini"];
            }
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }

        $id = $liste[0]['id'];
        $url = $liste[0]['url'];
        $user = $liste[0]['actuator_user'] ?? null;
        $password = $this->cipher->decrypt($liste[0]['actuator_password'] ?? null);

      return ['code' => 200, 'erreur' => ''] + compact('url', 'user', 'password', 'id');
  }
}
