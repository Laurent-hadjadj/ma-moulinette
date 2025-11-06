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

use App\Entity\ListeProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ListeProjetRepository]
 */
class ListeProjetRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, ListeProjet::class);
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
   * [Description for countListeProjetVisibility]
   * Execute une requête paramétrique count avec type= PRIVATE || PUBLIC
   *
   * @param string $type
   *
   * @return array
   *
   * Created at: 27/10/2023 12:59:43 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function countListeProjetVisibility($type): array
  {
    $sql = "SELECT count(*) as visibility
            FROM ma_moulinette.liste_projet
            WHERE visibility=:visibility";
    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(":visibility", $type);
          $request = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'request' => $request, 'erreur' => ''];
  }

  /**
   * [Description for countListProjet]
   * Compte le nombre total de projet.
   * @return array
   *
   * Created at: 27/10/2023 13:54:53 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function countListeProjet(): array
  {
    $sql = "SELECT COUNT(*) AS total
            FROM ma_moulinette.liste_projet";
    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $nombre = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'request' => $nombre, 'erreur'=>''];
  }

  /**
   * [Description for countListeProjetTags]
   * On retourne le nombre de projet récupéré du serveur SonarQube et
   * le nombre de tag disponible
   *
   * @return array
   *
   * Created at: 19/05/2024 09:27:26 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function countListeProjetTags(): array
  {
    $sql = "SELECT COUNT(*) AS tag
            FROM ma_moulinette.liste_projet
            WHERE tags IS NOT NULL
            AND jsonb_array_length(tags::jsonb) > 0
            AND NOT EXISTS (
                SELECT 1
                FROM jsonb_array_elements_text(tags::jsonb) AS elem
                WHERE elem = 'aucun')";

    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $nombre = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'nombre' => $nombre, 'erreur' => ''];
  }

  /**
   * [Description for selectListeProjetByGroupe]
   * retourne la liste des projets en fonction de(s) (l')équipes.
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 26/03/2024 17:37:27 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectListeProjetByGroupe($map): array
  {
    $sql = "SELECT DISTINCT
                liste_projet.maven_key AS id,
                LOWER(liste_projet.name) AS text
            FROM
                ma_moulinette.liste_projet,
                jsonb_array_elements_text(liste_projet.tags::jsonb) AS tag
            WHERE ".$map['clause_where'];
    try {
          $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          $liste = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for deleteListeProjet]
   *  Supprime tous les projets de la table
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 03/04/2024 09:47:35 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function deleteListeProjet(): array
  {
    $sql = "DELETE FROM ma_moulinette.liste_projet";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->executeStatement();
          $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }

}
