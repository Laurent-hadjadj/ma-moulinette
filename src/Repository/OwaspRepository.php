<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Repository;

use App\Entity\Owasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Owasp>
 */
class OwaspRepository extends ServiceEntityRepository
{
  private static string $removeReturnLine = "/\s+/u";
  private static string $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, Owasp::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
   * @return array<int|string, mixed>
   *
   * Created at: 21/10/2024 16:55:20 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Lilmod & Lelamed - Creative Common CC-BY-NC-SA 4.0.
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
   * [Description for selectOwaspOrderByDateEnregistrement]
   * On récupère les infos de la dernière analyse.
   *
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 02/03/2024 23:20:29 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectOwaspOrderByDateEnregistrement(array $map): array
  {
    $sql = "SELECT *
            FROM ma_moulinette.owasp
            WHERE maven_key=:maven_key AND referential_owasp=:referential_owasp
            ORDER BY date_enregistrement DESC LIMIT 1";

    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(':maven_key', $map['maven_key']);
            $stmt->bindValue(':referential_owasp', $map['referential_owasp']);
          $liste = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }


  /**
   * [Description for selectOwaspVersion]
   * Récupère l'application (maven_key) et la version de la dernière
   * analyse OWASP pour alimenter le breadcrumb de la page OWASP.
   *
   * @param string $maven_key
   *
   * @return array<int|string, mixed>
   *
   * Created at: 10/05/2026 00:00:00 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectOwaspVersion(string $maven_key): array
  {
    $sql = "SELECT maven_key, version
            FROM ma_moulinette.owasp
            WHERE maven_key=:maven_key
            ORDER BY date_enregistrement DESC LIMIT 1";

    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(':maven_key', $maven_key);
          $row = $stmt->executeQuery()->fetchAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }

    if ($row === false) {
        return ['code' => 200, 'application' => null, 'version' => null, 'erreur' => ''];
    }

    return ['code' => 200, 'application' => $row['maven_key'], 'version' => $row['version'], 'erreur' => ''];
  }

  /**
   * [Description for deleteOwaspMavenKey]
   * Supprime les données de la version courante (i.e. correspondant à la maven_key)
   *
   * @param array<string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 11/03/2024 08:37:44 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteOwaspMavenKey(array $map): array
  {
    $sql = "DELETE
            FROM ma_moulinette.owasp
            WHERE maven_key=:maven_key";

      try {
            $this->getEntityManager()->getConnection()->beginTransaction();
      $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(':maven_key', $map['maven_key']);
                $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
      } catch (\Throwable $e) {
          $this->getEntityManager()->getConnection()->rollBack();
          return $this->handleDatabaseException($e);
      }
    return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for InsertOwasp]
   * Ajoute les signalements et les issues OWASP pour le projet
   *
   * @param array<int|string, mixed> $owaspDataList
   *
   * @return array<int|string, mixed>
   *
   * Created at: 26/05/2024 13:03:43 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertOwasp(array $owaspDataList): array
  {
    $sql = "INSERT INTO ma_moulinette.owasp
                (referential_owasp, maven_key, version, date_version, effort_total, source,
                mode_collecte, utilisateur_collecte, date_enregistrement,
                a1, a2, a3, a4, a5, a6, a7, a8, a9, a10,
                a1_blocker, a1_critical, a1_major, a1_info, a1_minor,
                a2_blocker, a2_critical, a2_major, a2_info, a2_minor,
                a3_blocker, a3_critical, a3_major, a3_info, a3_minor,
                a4_blocker, a4_critical, a4_major, a4_info, a4_minor,
                a5_blocker, a5_critical, a5_major, a5_info, a5_minor,
                a6_blocker, a6_critical, a6_major, a6_info, a6_minor,
                a7_blocker, a7_critical, a7_major, a7_info, a7_minor,
                a8_blocker, a8_critical, a8_major, a8_info, a8_minor,
                a9_blocker, a9_critical, a9_major, a9_info, a9_minor,
                a10_blocker, a10_critical, a10_major, a10_info, a10_minor)
            VALUES
                (:referential_owasp, :maven_key, :version, :date_version, :effort_total, :source, :mode_collecte, :utilisateur_collecte, :date_enregistrement,
                :a1, :a2, :a3, :a4, :a5, :a6, :a7, :a8, :a9, :a10,
                :a1_blocker, :a1_critical, :a1_major, :a1_info, :a1_minor,
                :a2_blocker, :a2_critical, :a2_major, :a2_info, :a2_minor,
                :a3_blocker, :a3_critical, :a3_major, :a3_info, :a3_minor,
                :a4_blocker, :a4_critical, :a4_major, :a4_info, :a4_minor,
                :a5_blocker, :a5_critical, :a5_major, :a5_info, :a5_minor,
                :a6_blocker, :a6_critical, :a6_major, :a6_info, :a6_minor,
                :a7_blocker, :a7_critical, :a7_major, :a7_info, :a7_minor,
                :a8_blocker, :a8_critical, :a8_major, :a8_info, :a8_minor,
                :a9_blocker, :a9_critical, :a9_major, :a9_info, :a9_minor,
                :a10_blocker, :a10_critical, :a10_major, :a10_info, :a10_minor)";

    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
      $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
      /* MODIF 2026-07-18 : executeStatement() était hors de la boucle — une
       * seule exécution après avoir lié TOUTES les lignes sur la même
       * requête préparée n'insérait que la dernière ligne (les liaisons se
       * réécrivent, elles ne s'accumulent pas). Avec 2 référentiels (2017 +
       * 2021), seul le dernier de la liste était réellement persisté. */
      foreach ($owaspDataList as $map) {
                foreach ($map as $key => $value) {
                  if ($value instanceof \DateTimeImmutable) {
                      $stmt->bindValue(":$key", $value->format('Y-m-d H:i:sO'));
                  } else {
            if ($key !== 'total') {
                          $stmt->bindValue(":$key", $value);
                      }
                  }
                }
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
