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

use App\Entity\HotspotOwasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HotspotOwaspRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $mavenKey = ':maven_key';
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, HotspotOwasp::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
   * @return array
   *
   * Created at: 21/12/2024 19:47:50 (Europe/Paris)
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
   * [Description for countHotspotOwaspStatus]
   * On compte le nombre de hotspot REVIEWED
   * @param array $map
   *
   * @return array
   *
   * Created at: 02/03/2024 23:23:25 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function countHotspotOwaspStatus($map): array
  {
      $sql = "SELECT count(*) AS nombre
              FROM ma_moulinette.hotspot_owasp
              WHERE maven_key=:maven_key AND status=:status";
      try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(static::$mavenKey, $map['maven_key']);
            $stmt->bindValue(':status', $map['status']);
          $nombre = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'request' => $nombre, 'erreur' => ''];
  }

  /**
   * [Description for countOwaspStatus]
   * On récupère le nombre de hotspot owasp par niveau de sévérité potentiel.
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 02/03/2024 23:37:22 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function countHotspotOwaspProbability($map): array
  {
    $sql = "SELECT probability, count(*) as total
            FROM ma_moulinette.hotspot_owasp
            WHERE maven_key=:maven_key
            AND status='TO_REVIEW' GROUP BY probability";

    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $stmt->bindValue(static::$mavenKey, $map['maven_key']);
          $nombre = $stmt->executeQuery()->fetchAllAssociative();
      } catch (\Throwable $e) {
          return $this->handleDatabaseException($e);
      }
      return ['code' => 200, 'nombre' => $nombre, 'erreur' => ''];
  }

  /**
   * [Description for countHotspotOwaspMenaces]
   * On récupère le nombre de hotspot au status TO_REVIEW
   * @param array $map
   *
   * @return array
   *
   * Created at: 03/03/2024 12:38:43 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function countHotspotOwaspMenaces($map): array
  {
    $sql = "SELECT menace, count(*) as total
            FROM ma_moulinette.hotspot_owasp
            WHERE maven_key=:maven_key
            AND status='TO_REVIEW' GROUP BY menace";

    try {
        $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(static::$mavenKey, $map['maven_key']);
        $nombre = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'menaces' => $nombre, 'erreur' => ''];
  }

  /**
   * [Description for countHotspotOwaspMenaceByStatus]
   *  On compte le nombre de menace probable par type de status
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 03/03/2024 16:01:48 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function countHotspotOwaspMenaceByStatus($map): array
  {
    $sql = "SELECT count(*) as total
            FROM ma_moulinette.hotspot_owasp
            WHERE maven_key=:maven_key
            AND menace=:menace
            AND status='TO_REVIEW'
            AND probability=:probability";

    try {
        $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $stmt->bindValue(static::$mavenKey, $map['maven_key']);
          $stmt->bindValue(':menace', $map['menace']);
          $stmt->bindValue(':probability', $map['probability']);
        $nombre = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'nombre' => $nombre[0], 'erreur' => ''];
  }

  /**
   * [Description for deleteHotpotOwaspMavenKey]
   * Supprime les hotspots de type owasp pour la version courante (i.e. correspondant à la maven_key)
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 14/03/2024 08:21:10 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteHotspotOwaspMavenKey($map): array
  {
    $sql = "DELETE
            FROM ma_moulinette.hotspot_owasp
            WHERE maven_key=:maven_key";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(static::$mavenKey, $map['maven_key']);
              $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }

  /**
   * [Description for insertHotspotOwasp]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 30/05/2024 15:54:30 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertHotspotOwasp($map): array
  {
    $sql = "INSERT INTO ma_moulinette.hotspot_owasp
              (referential_owasp, maven_key, version, date_version, menace, security_category, rule_key, probability, status, resolution, niveau, mode_collecte, utilisateur_collecte, date_enregistrement)
            VALUES
              (:referential_owasp, :maven_key, :version, :date_version, :menace, :security_category, :rule_key, :probability, :status, :resolution, :niveau, :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
    try {
        $this->getEntityManager()->getConnection()->beginTransaction();
            foreach ($map as $ref) {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(':referential_owasp', $ref['referential_owasp']);
                    $stmt->bindValue(static::$mavenKey, $ref['maven_key']);
                    $stmt->bindValue(':version', $ref['version']);
                    $stmt->bindValue(':date_version', $ref['date_version']->format('Y-m-d H:i:sO'));
                    $stmt->bindValue(':menace', $ref['menace']);
                    $stmt->bindValue(':security_category', $ref['security_category']);
                    $stmt->bindValue(':rule_key', $ref['rule_key']);
                    $stmt->bindValue(':probability', $ref['probability']);
                    $stmt->bindValue(':status', $ref['status']);
                    $stmt->bindValue(':resolution', $ref['resolution']);
                    $stmt->bindValue(':niveau', $ref['niveau']);
                    $stmt->bindValue(':mode_collecte', $ref['mode_collecte']);
                    $stmt->bindValue(':utilisateur_collecte', $ref['utilisateur_collecte']);
                    $stmt->bindValue(':date_enregistrement', $ref['date_enregistrement']->format('Y-m-d H:i:sO'));
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
