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

use App\Entity\Properties;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description PropertiesRepository]
 */
class PropertiesRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $type = ':type';
  public static $horodatage = 'Y-m-d H:i:sO';
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Properties::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
   * @return array
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
   * [Description for getProperties]
   * Récupère la liste des properties
   *
   * @param string $type
   *
   * @return array
   *
   * Created at: 27/10/2023 14:06:11 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function getProperties($type): array
  {
    $sql = "SELECT *
            FROM ma_moulinette.properties
            WHERE type=:type";

    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(static::$type, $type);
          $request = $stmt->executeQuery()->fetchAllAssociative();
        return ['code' => 200, 'request' => $request, 'erreur' => ''];
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
  }

  /**
   * [Description for insertProperties]
   * Ajoute les properties
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 27/10/2023 15:14:39 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertProperties($map): array
  {
    $sql = "INSERT INTO ma_moulinette.properties (
                        type,
                        projet_bd, projet_sonar,
                        profil_bd, profil_sonar,
                        date_modification_projet,
                        date_modification_profil,
                        date_creation)
            VALUES (
                        :type,
                        :projet_bd, :projet_sonar,
                        :profil_bd, :profil_sonar,
                        :date_modification_projet,
                        :date_modification_profil,
                        :date_creation)";
    try {
        $this->getEntityManager()->getConnection()->beginTransaction();
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(":type", 'properties');
            $stmt->bindValue(":projet_bd", $map["projet_bd"]);
            $stmt->bindValue(":projet_sonar", $map["projet_sonar"]);
            $stmt->bindValue(":profil_bd", $map["profil_bd"]);
            $stmt->bindValue(":profil_sonar", $map["profil_sonar"]);
            /** Les dates sont déjà en string */
            $stmt->bindValue(":date_modification_projet", $map["date_modification_projet"]->format(static::$horodatage));
            $stmt->bindValue(":date_modification_profil", $map["date_modification_profil"]->format(static::$horodatage));
            $stmt->bindValue(":date_creation", $map["date_creation"]->format(static::$horodatage));
            $stmt->executeStatement();
        $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for updatePropertiesProjet]
   * Mise à jour des properties pour les projets
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 27/10/2023 15:23:33 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function updatePropertiesProjet($map): array
  {
    $sql = "UPDATE ma_moulinette.properties
            SET projet_bd = :projet_bd,
                projet_sonar = :projet_sonar,
                date_modification_projet = :date_modification_projet
            WHERE type = :type";

    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(':projet_bd', $map['projet_bd']);
              $stmt->bindValue(':projet_sonar', $map['projet_sonar']);
              $stmt->bindValue(':date_modification_projet', $map['date_modification_projet']->format(static::$horodatage));
              $stmt->bindValue(static::$type, 'properties');
              $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for updatePropertiesProfiles]
   * Mise à jour des properties pour les profils
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 27/10/2023 15:23:09 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function updatePropertiesProfiles($map): array
  {
    $sql = "UPDATE ma_moulinette.properties
            SET profil_bd=:profil_bd,
                profil_sonar=:profil_sonar,
                date_modification_profil=:date_modification_profil
            WHERE type=:type";

    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(':profil_bd', $map['profil_bd']);
              $stmt->bindValue(':profil_sonar', $map['profil_sonar']);
              $stmt->bindValue(':date_modification_profil', $map['date_modification_profil']->format(static::$horodatage));
              $stmt->bindValue(static::$type, 'properties');
              $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }
}
