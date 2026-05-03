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

use App\Entity\ProfilesHistorique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProfilesHistorique>
 */
class ProfilesHistoriqueRepository extends ServiceEntityRepository
{
  private static string $removeReturnLine = "/\s+/u";
  private static string $language = ':language';
  private static string $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, ProfilesHistorique::class);
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
   * [Description for insertProfilesHistorique]
   * Mise à jour des changements sur les règles.
   *
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 21/02/2024 08:41:24 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertProfilesHistorique(array $map): array
  {
    $sql = "INSERT INTO ma_moulinette.profiles_historique (
                        date_courte, language, date,
                        action, auteur, rule,
                        description, detail, date_enregistrement)
            VALUES (
                        :date_courte, :language, :date,
                        :action, :auteur, :rule,
                        :description, :detail, :date_enregistrement)";
      try {
            $this->getEntityManager()->getConnection()->beginTransaction();
              /** On escape les ' */
              /* "$reEncode = str_replace("'", "''", $map['description']);" */
      $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(':date_courte', $map['date_courte']);
                $stmt->bindValue(self::$language, $map['language']);
                $stmt->bindValue(':date', $map['date']);
                $stmt->bindValue(':action', $map['action']);
                $stmt->bindValue(':auteur', $map['auteur']);
                $stmt->bindValue(':rule', $map['rule']);
                $stmt->bindValue(':description', $map['description']);
                $stmt->bindValue(':detail', $map['detail']);
                $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));
                $stmt->executeStatement();
              $this->getEntityManager()->getConnection()->commit();
      } catch (\Throwable $e) {
          $this->getEntityManager()->getConnection()->rollBack();
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for selectProfilesHistoriqueAction]
   * Nombre de règles activé/désactivé/mise à jour
   *
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 21/02/2024 09:43:45 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectProfilesHistoriqueAction(array $map): array
  {
    $sql = "SELECT COUNT(*) AS nombre
                    FROM ma_moulinette.profiles_historique
                    WHERE action=:action AND language=:language";
    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(':action', $map['action']);
            $stmt->bindValue(self::$language, $map['language']);
          $nombre = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'nombre' => $nombre, 'erreur' => ''];
  }

  /**
   * [Description for selectProfilesHistoriqueDateTri]
   * Remonte les n premieres dates triees en ordre croissant ou decroissant.
   *
   * @param array<int|string, mixed> $map  ['language' => string, 'tri' => 'ASC'|'DESC', 'limit' => int]
   *
   * @return array<int|string, mixed>
   *
   * Created at: 21/02/2024 10:01:56 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectProfilesHistoriqueDateTri(array $map): array
  {
    /** Whitelist du sens de tri (ne peut pas etre parametre PostgreSQL) */
    $tri = strtoupper((string) ($map['tri'] ?? 'DESC'));
    if ($tri !== 'ASC' && $tri !== 'DESC') {
      $tri = 'DESC';
    }

    $sql = "SELECT date
            FROM ma_moulinette.profiles_historique
            WHERE language = :language
            ORDER BY date {$tri} LIMIT :limit";
      try {
      $liste = $this->getEntityManager()->getConnection()->executeQuery(
        preg_replace(self::$removeReturnLine, " ", $sql),
        ['language' => $map['language'], 'limit' => (int) ($map['limit'] ?? 1)],
        ['language' => ParameterType::STRING, 'limit' => ParameterType::INTEGER],
      )->fetchAllAssociative();
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for selectProfilesHistoriqueDateCourteGroupeBy]
   * Retourne la liste groupé et trié par date courte
   *
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 21/02/2024 13:57:22 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectProfilesHistoriqueDateCourteGroupeBy(array $map): array
  {
    $sql = "SELECT date_courte
            FROM ma_moulinette.profiles_historique
            WHERE language=:language
            GROUP BY date_courte
            ORDER BY date_courte DESC";
    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$language, $map['language']);
          $liste = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
      return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for selectProfilesHistoriqueLangageDateCourte]
   * Retourne la liste par langage et par date courte
   *
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 21/02/2024 14:08:47 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectProfilesHistoriqueLangageDateCourte(array $map): array
  {
    $sql = "SELECT *
            FROM ma_moulinette.profiles_historique
            WHERE language=:language AND date_courte=:date_courte";
    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$language, $map['language']);
            $stmt->bindValue(':date_courte', $map['date_courte']);
          $liste = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }
}
