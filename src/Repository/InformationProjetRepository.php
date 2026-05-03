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

use App\Entity\InformationProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InformationProjet>
 */
class InformationProjetRepository extends ServiceEntityRepository
{
  private static string $removeReturnLine = "/\s+/u";
  private static string $mavenKey = ':maven_key';
  private static string $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, InformationProjet::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
   * @return array<int|string, mixed>
   *
   * Created at: 18/12/2024 15:51:18 (Europe/Paris)
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
   * [Description for selectInformationProjetIsValide]
   * Vérifie si le projet existe.
   *
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 16/03/2024 21:06:43 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectInformationProjetIsValide(array $map): array
  {
    $sql = "SELECT *
            FROM ma_moulinette.information_projet
            WHERE maven_key=:maven_key LIMIT 1";
      try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
              $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $isValide = $stmt->executeQuery()->fetchAllAssociative();
            /** j'ai pas trouvé de projet */
      if (!$isValide) {
        return ['code' => 404, 'erreur' => "Je n'ai pas trouvé le projet dans la base de données."];
            }
          } catch (\Throwable $e) {
              return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'is_valide' => $isValide[0], 'erreur' => ''];
  }

  /**
   * [Description for selectInformationProjetVersion]
   * Retourne la liste des versions pour un projet.
   *
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 27/02/2024 21:44:39 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectInformationProjetVersion(array $map): array
  {
    $sql = "SELECT maven_key, analyse_key, project_version as version, date_analyse AS date,
                    type_analyse AS type, version_sonar, version_release_sonar, version_snapshot_sonar, version_autre_sonar
            FROM ma_moulinette.information_projet
            WHERE maven_key=:maven_key";
      try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
              $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $liste = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
      $this->getEntityManager()->getConnection()->rollBack();
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'info' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for TestDeleteInformationProjetMavenKey]
   * On supprime les informations sur le projet
   *
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 11/03/2024 19:03:15 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteInformationProjetMavenKey(array $map): array
  {
    $sql = "DELETE
            FROM ma_moulinette.information_projet
            WHERE maven_key=:maven_key";
      try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
              $stmt->bindValue(self::$mavenKey, $map['maven_key']);
              $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
      } catch (\Throwable $e) {
          $this->getEntityManager()->getConnection()->rollBack();
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for insertInformationProjet]
   *
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 20/05/2024 23:09:33 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertInformationProjet(array $map): array
  {
    $sql = "INSERT INTO ma_moulinette.information_projet
              (maven_key, analyse_key, date_analyse, project_version, type_analyse, version_sonar, version_release_sonar, version_snapshot_sonar, version_autre_sonar, mode_collecte, utilisateur_collecte, date_enregistrement)
            VALUES
              (:maven_key, :analyse_key, :date, :project_version, :type, :version_sonar, :version_release_sonar, :version_snapshot_sonar, :version_autre_sonar,:mode_collecte, :utilisateur_collecte, :date_enregistrement)";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
              $stmt->bindValue(self::$mavenKey, $map['maven_key']);
              $stmt->bindValue(':analyse_key', $map['analyse_key']);
              $stmt->bindValue(':date', $map['date']);
              $stmt->bindValue(':project_version', $map['project_version']);
              $stmt->bindValue(':type', $map['type']);
              $stmt->bindValue(':version_sonar', $map['version_sonar']);
              $stmt->bindValue(':version_release_sonar', $map['version_release_sonar']);
              $stmt->bindValue(':version_snapshot_sonar', $map['version_snapshot_sonar']);
              $stmt->bindValue(':version_autre_sonar', $map['version_autre_sonar']);
              $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
              $stmt->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);
              /** on formate la date avant de l'enregistrer */
              $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));
              $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
    }

    return ['code' => 200, 'erreur' =>  ''];
  }

}
