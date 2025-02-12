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

use App\Entity\Notes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description NotesRepository]
 */
class NotesRepository extends ServiceEntityRepository
{
  private static $removeReturnLine = "/\s+/u";
  private static $mavenKey = ':maven_key';
  private static $type = ':type';
  private static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, Notes::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Doctrine\DBAL\Exception $e
   *
   * @return array
   *
   * Created at: 18/12/2024 15:20:20 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
  {
      $message = $e->getMessage();

      if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
          $message = static::$noDataBase;
      }

      if ($e->getSqlState() == '23502') {
          $message = $e->getMessage();
      }

      if ($e->getSqlState() == '23505'){
          return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
      }

      return ['code' => 500, 'erreur'=> $message];
  }

  /**
   * [Description for deleteNotesMavenKey]
   * Supprime les notes de la version courante (i.e. correspondant à la maven_key)
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 12/03/2024 09:31:27 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteNotesMavenKey(array $map): array
  {
    $sql = "DELETE
            FROM ma_moulinette.notes
            WHERE maven_key=:maven_key and type=:type";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(static::$mavenKey, $map['maven_key']);
              $stmt->bindValue(static::$type, $map['type']);
              $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Doctrine\DBAL\Exception $e) {
        $this->getEntityManager()->rollback();
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for InsertNotes]
   * Ajoute les notes pour le projet
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 12/03/2024 21:47:11 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertNotes(array $map): array
  {
      $sql = "INSERT INTO ma_moulinette.notes
                  (maven_key, type, value, mode_collecte, utilisateur_collecte, date_enregistrement)
              VALUES
                  (:maven_key, :type, :value, :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
      try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                  $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                  $stmt->bindValue(static::$type, $map['type']);
                  $stmt->bindValue(':value', $map['value']);
                  $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
                  $stmt->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);
                  /** on formate la date avant de l'enregistrer */
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
   * [Description for selectNoteMavenType]
   * retourne la note par type (reliability, security, sqale) pour un projet.
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 20/03/2024 16:20:18 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectNotesMavenType(array $map): array
  {
    $sql = "SELECT type, value, date_enregistrement
            FROM ma_moulinette.notes
            WHERE maven_key=:maven_key AND type=:type
            ORDER BY date_enregistrement DESC LIMIT 1";
    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(static::$mavenKey, $map['maven_key']);
            $stmt->bindValue(static::$type, $map['type']);
          $liste = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Doctrine\DBAL\Exception $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

}
