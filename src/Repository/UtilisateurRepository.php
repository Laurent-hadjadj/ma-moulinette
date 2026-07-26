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

use App\Entity\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Utilisateur>
 */
class UtilisateurRepository extends ServiceEntityRepository
{
  private static string $removeReturnLine = "/\s+/u";
  private static string $noDataBase = 'La connexion à la base de données a échoué.';
  private static string $courriel = ':courriel';
  private static string $preference = ':preference';
  private static string $dateModification = ':date_modification';
  private static string $dateFormatted = 'Y-m-d H:i:sO';
  private static string $erreur400 = 'Format des préférences invalide (Erreur 400).';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Utilisateur::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Throwable $e
   *
   * @return array<int|string, mixed>
   *
   * Created at: 06/07/2025 12:50:49 (Europe/Paris)
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
   * [Description for insertUtilisateurFavoriVersion]
   * On ajoute ou supprime la version du projet dans les favori_version
   * Utilisé par le contrôleur SUIVI
   *
   * @param array<int|string, mixed> $preference
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 17/02/2024 23:05:33 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function updateUtilisateurFavoriVersion(array $preference, array $map): array
  {
    /** On génère une date  */
    $now = new \DateTime();

    /** valeur par defaut: si aucune branche ne s'applique, on persiste l'etat courant */
    $listeVersion = $preference['favori_version'];

    /**
     * On récupère la valeur de l'index pour le projet en favori et
     * on regarde si la version est unique.
     *  item = "fr.ma-moulinette:ma-moulinette" => [ 0 => "1.0.0-RELEASE", 1 => "2.0.0-SNAPSHOT"]
     */
    $i = $index = -1;
    $nombreVersion = 0;
    foreach ($preference['favori_version'] as $item) {
      $i++;

      if (array_key_exists($map['maven_key'], $item)) {
        $index = $i;
        // nombre de version pour le projet
        $nombreVersion = count($item[$map['maven_key']]);
      }
    }

    /* si le projet n'est pas un favori on l'ajoute */
    if (!in_array($map['maven_key'], $preference['favori_projet'])) {
      array_push($preference['favori_projet'], $map['maven_key']);
    }

    /** On ajoute la version pour un projet qui est déjà présent */
    if (str_contains(\serialize($preference['favori_version']), $map['maven_key']) && $map['favori'] === 1) {
      /** On ajoute à la liste la version */
      array_push($preference['favori_version'][$index][$map['maven_key']], $map['version']);
      $listeVersion = $preference['favori_version'];
    }

    /** On ajoute la version du projet en favori si le projet n'existe pas. */
    if (!str_contains(\serialize($preference['favori_version']), $map['maven_key']) && $map['favori'] === 1) {
      /** On ajoute à la liste la version */
      array_push($preference['favori_version'], [$map['maven_key'] => [$map['version']]]);
      $listeVersion = $preference['favori_version'];
    }

    /** On supprime le projet en favori car il n'y a qu'une version en favori*/
    if (
      str_contains(\serialize($preference['favori_version']), $map['maven_key']) &&
      $nombreVersion === 1 && $map['favori'] === 0
    ) {
      /** On supprime le projet */
      foreach ($preference['favori_version'] as $index => $subArray) {
        if (isset($subArray[$map['maven_key']])) {
          unset($preference['favori_version'][$index][$map['maven_key']]);
        }
      }
      /** Nettoie le tableau */
      $preference['favori_version'] = array_filter($preference['favori_version'], function ($subArray) {
        return !empty($subArray);
      });
      /** On supprime aussi le projet favori de la liste des projets favoris */
      $key = array_search($map['maven_key'], $preference['favori_projet']); // Recherche la clé
      if ($key !== false) {
        unset($preference['favori_projet'][$key]); // Supprime la valeur
      }
      // Ré-indexer le tableau
      $preference['favori_projet'] = array_values($preference['favori_projet']);
      $listeVersion = $preference['favori_version'];
    }

    /** On supprime la version du projet en favori, le nombre de version est > 1 */
    if (str_contains(\serialize($preference['favori_version']), $map['maven_key']) && $map['favori'] === 0) {
      $listeVersion = [];

      /** On supprime pour le projet la version */
      $nouvelleListeVersion = array_diff($preference['favori_version'][$index][$map['maven_key']], [$map['version']]);
      /** On crée une nouvelle version avec la nouvelleListe */
      $nouvelleVersion = [$map['maven_key'] => $nouvelleListeVersion];

      /** On reconstruit la liste des versions avec ou sans le projet qui a été supprimé. */
      foreach ($preference['favori_version'] as $key => $value) {
        if ($key === $index && $nombreVersion > 1) {
          array_push($listeVersion, $nouvelleVersion);
        }
        if ($key !== $index) {
          array_push($listeVersion, $value);
        }
      }
    }

    // MODIF 2026-06-11 : préserver la clé suivi_version
    // pour ne pas l'effacer lorsque seul favori_version change.
    $jsonArray = stripslashes(
      json_encode([
        'statut' => $preference['statut'],
        'suivi_projet' => $preference['suivi_projet'],
        'favori_projet' => $preference['favori_projet'],
        'favori_version' => $listeVersion,
        'suivi_version' => $preference['suivi_version'] ?? [],
      ])
    );

    $sql = "UPDATE ma_moulinette.utilisateur
            SET preference = :preference,
                date_modification = :date_modification
            WHERE courriel = :courriel";
    try {
      $this->getEntityManager()->getConnection()->beginTransaction();
      $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
      $stmt->bindValue(self::$courriel, $map['courriel']);
      $stmt->bindValue(self::$preference, $jsonArray);
      $stmt->bindValue(self::$dateModification, $now->format(self::$dateFormatted));
      $stmt->executeStatement();
      $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
      $this->getEntityManager()->getConnection()->rollBack();
      return $this->handleDatabaseException($e);
    }
    return  ['code' => 200, 'erreur' => '', 'preference' => $preference, $jsonArray];
  }

  /**
   * [Description for updateUtilisateurFavoriProjet]
   * Met à jour pour le projet le statut de favori - true ou false
   * Utilisé par le contrôleur PROJET
   *
   * @param array<string, mixed> $preference attend `statut`, `suivi_projet`, `favori_projet`,
   *                                          `favori_version` et `suivi_version` (arrays)
   * @param array<string, mixed> $map        attend `maven_key` et `courriel`
   *
   * @return array<int|string, mixed>
   *
   * Created at: 26/03/2024 17:00:51 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function updateUtilisateurFavoriProjet(array $preference, array $map): array
  {
    // Vérifier si la structure des préférences est valide
    if (
      !isset($preference['statut'], $preference['suivi_projet'], $preference['favori_projet'], $preference['favori_version']) ||
      !is_array($preference['statut']) ||
      !is_array($preference['suivi_projet']) ||
      !is_array($preference['favori_projet']) ||
      !is_array($preference['favori_version'])
    ) {
      return ['code' => 400, 'erreur' => self::$erreur400];
    }

    /** On regarde si la projet est dans la liste des favoris (true) */
    $isFavori = in_array($map['maven_key'], $preference['favori_projet']);

    /**
     * On le supprime de la liste des favoris s'il existe dans les préférences
     * Sinon on l'ajoute
     */

    $response = ['code' => 206, 'statut' => -1, 'erreur' => ''];
    $now = new \DateTime();
    if ($isFavori) {
      /** on supprime le projet de la liste */
      $nouvelleListeFavori = array_diff($preference['favori_projet'], [$map['maven_key']]);

      /** Si le nombre de favoris dans la liste est = 0 alors on désactive la gestion des favoris */
      if (count($nouvelleListeFavori) === 0) {
        $preference['statut']['favori_projet'] = false;
      }


      // MODIF 2026-06-11 : préserver la clé suivi_version.
      $jsonArray = json_encode([
        'statut' => $preference['statut'],
        'suivi_projet' => $preference['suivi_projet'],
        'favori_projet' => $nouvelleListeFavori,
        'favori_version' => $preference['favori_version'],
        'suivi_version' => $preference['suivi_version'] ?? [],
      ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

      // Vérification de l'erreur JSON
      if ($jsonArray === false || json_last_error() !== JSON_ERROR_NONE) {
        return ['code' => 400, 'erreur' => 'Invalid JSON: ' . json_last_error_msg()];
      }

      /** On met à jour les préférences. */
      $sql = "UPDATE ma_moulinette.utilisateur
              SET preference = :preference,
                  date_modification = :date_modification
              WHERE courriel=:courriel";

      try {
        $this->getEntityManager()->getConnection()->beginTransaction();
        $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
        $stmt->bindValue(self::$preference, $jsonArray);
        $stmt->bindValue(self::$dateModification, $now->format(self::$dateFormatted));
        $stmt->bindValue(self::$courriel, $map['courriel']);
        $stmt->executeStatement();
        $this->getEntityManager()->getConnection()->commit();
      } catch (\Throwable $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
      }
      $response = ['code' => 200, 'statut' => 0, 'erreur' => ''];
    } else {
      /** On ajoute le projet à la liste des favoris et on active la gestion des favoris */
      array_push($preference['favori_projet'], $map['maven_key']);
      $preference['statut']['favori_projet'] = true;

      // MODIF 2026-06-11 : préserver la clé suivi_version.
      /** On met à jour l'objet. */
      $jsonArray = json_encode([
        'statut' => $preference['statut'],
        'suivi_projet' => $preference['suivi_projet'],
        'favori_projet' => $preference['favori_projet'],
        'favori_version' => $preference['favori_version'],
        'suivi_version' => $preference['suivi_version'] ?? [],
      ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

      // Vérification de l'erreur JSON
      if ($jsonArray === false || json_last_error() !== JSON_ERROR_NONE) {
        return ['code' => 400, 'erreur' => 'Invalid JSON: ' . json_last_error_msg()];
      }

      $sql = "UPDATE ma_moulinette.utilisateur
              SET preference = :preference,
                  date_modification = :date_modification
              WHERE courriel=:courriel";

      /** On met à jour les préférences. */
      try {
        $this->getEntityManager()->getConnection()->beginTransaction();
        $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
        $stmt->bindValue(self::$preference, $jsonArray);
        $stmt->bindValue(self::$courriel, $map['courriel']);
        $stmt->bindValue(self::$dateModification, $now->format(self::$dateFormatted));
        $stmt->executeStatement();
        $this->getEntityManager()->getConnection()->commit();
      } catch (\Throwable $e) {
        $this->getEntityManager()->getConnection()->rollBack();
        return $this->handleDatabaseException($e);
      }
      $response = ['code' => 200, 'statut' => 1, 'erreur' => ''];
    }
    return $response;
  }

  /**
   * [Description for updateUtilisateurResetPassword]
   * Modifie l'indicateur reset du mot de passe.
   *
   * @param array<int|string, mixed> $map
   *
   * @return array<int|string, mixed>
   *
   * Created at: 03/04/2024 19:34:48 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  /**
   * [Description for updateUtilisateurSuiviVersion]
   * Ajoute ou supprime une version du dictionnaire suivi_version pour un projet.
   * Max 15 versions par projet. La référence (initial=true) est toujours incluse par SuiviController.
   * @param array<int|string, mixed> $preference
   * @param array<int|string, mixed> $map  [courriel, maven_key, version, suivi (1=add,0=remove)]
   *
   * @return array<int|string, mixed>
   *
   * Created at: 2026-06-11 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function updateUtilisateurSuiviVersion(array $preference, array $map): array
  {
    if (
      !isset($preference['statut'], $preference['suivi_projet'], $preference['favori_projet'], $preference['favori_version']) ||
      !is_array($preference['statut']) ||
      !is_array($preference['suivi_projet']) ||
      !is_array($preference['favori_projet']) ||
      !is_array($preference['favori_version'])
    ) {
      return ['code' => 400, 'erreur' => self::$erreur400];
    }

    $mavenKey = $map['maven_key'];
    $version  = $map['version'];
    $suivi    = (int) ($map['suivi'] ?? 1);

    // Récupère le dict actuel : {maven_key: [versions]}
    $suiviVersion = $preference['suivi_version'] ?? [];
    if (!is_array($suiviVersion)) {
      $suiviVersion = [];
    }

    $listeVersionsProjet = $suiviVersion[$mavenKey] ?? [];
    if (!is_array($listeVersionsProjet)) {
      $listeVersionsProjet = [];
    }

    if ($suivi === 0) {
      // Suppression de la version
      $listeVersionsProjet = array_values(array_diff($listeVersionsProjet, [$version]));
    } else {
      // Ajout — max 15
      if (count($listeVersionsProjet) >= 15) {
        return ['code' => 400, 'erreur' => 'La limite de 15 versions pour le suivi est atteinte.'];
      }
      if (!in_array($version, $listeVersionsProjet, true)) {
        $listeVersionsProjet[] = $version;
      }
    }

    // Met à jour ou supprime la clé dans le dict
    if (empty($listeVersionsProjet)) {
      unset($suiviVersion[$mavenKey]);
    } else {
      $suiviVersion[$mavenKey] = $listeVersionsProjet;
    }

    // Mise à jour du statut global
    $preference['statut']['suivi_version'] = !empty($suiviVersion);
    $preference['suivi_version'] = $suiviVersion;

    $now = new \DateTime();
    $jsonArray = json_encode([
      'statut'         => $preference['statut'],
      'suivi_projet'   => $preference['suivi_projet'],
      'favori_projet'  => $preference['favori_projet'],
      'favori_version' => $preference['favori_version'],
      'suivi_version'  => $suiviVersion,
    ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

    if ($jsonArray === false || json_last_error() !== JSON_ERROR_NONE) {
      return ['code' => 400, 'erreur' => 'Invalid JSON: ' . json_last_error_msg()];
    }

    $sql = "UPDATE ma_moulinette.utilisateur
            SET preference = :preference,
                date_modification = :date_modification
            WHERE courriel = :courriel";

    try {
      $this->getEntityManager()->getConnection()->beginTransaction();
      $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
      $stmt->bindValue(self::$preference, $jsonArray);
      $stmt->bindValue(self::$courriel, $map['courriel']);
      $stmt->bindValue(self::$dateModification, $now->format(self::$dateFormatted));
      $stmt->executeStatement();
      $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
      $this->getEntityManager()->getConnection()->rollBack();
      return $this->handleDatabaseException($e);
    }

    return ['code' => 200, 'erreur' => '', 'suivi_version' => $suiviVersion];
  }

  /**
   * [Description for countUtilisateurActif]
   * Compte les utilisateurs avec le flag actif = true.
   *
   * @return array<string, mixed>
   *
   * Created at: 09/06/2026 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  // MODIF 2026-06-09 : méthode manquante pour UserAgentReportingService
  public function countUtilisateurActif(): array
  {
    $sql = "SELECT COUNT(*) AS total FROM ma_moulinette.utilisateur WHERE actif = true";
    try {
      $result = $this->getEntityManager()->getConnection()
        ->executeQuery($sql)
        ->fetchAssociative();
    } catch (\Throwable $e) {
      return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'total' => (int) ($result['total'] ?? 0), 'erreur' => ''];
  }

  /**
   * [Description for countUtilisateurDisponible]
   * Compte tous les utilisateurs enregistrés (sans filtre actif).
   *
   * @return array<string, mixed>
   *
   * Created at: 09/06/2026 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  // MODIF 2026-06-09 : méthode manquante pour UserAgentReportingService
  public function countUtilisateurDisponible(): array
  {
    $sql = "SELECT COUNT(*) AS total FROM ma_moulinette.utilisateur";
    try {
      $result = $this->getEntityManager()->getConnection()
        ->executeQuery($sql)
        ->fetchAssociative();
    } catch (\Throwable $e) {
      return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'total' => (int) ($result['total'] ?? 0), 'erreur' => ''];
  }

  /**
   * @param array<string, mixed> $map attend `reset_password`, `courriel`
   *                                  et `date_modification` (\DateTimeInterface)
   *
   * @return array<int|string, mixed>
   */
  public function updateUtilisateurResetPassword(array $map): array
  {
    $sql = "UPDATE ma_moulinette.utilisateur
            SET reset_password = :reset_password,
                reset_password_count = :reset_password_count,
                date_modification = :date_modification
            WHERE courriel=:courriel";

    try {
      $this->getEntityManager()->getConnection()->beginTransaction();
      $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
      $stmt->bindValue(':reset_password', $map['reset_password']);
      $stmt->bindValue(':reset_password_count', 1);
      $stmt->bindValue(self::$courriel, $map['courriel']);
      $stmt->bindValue(':date_modification', $map['date_modification']->format(self::$dateFormatted));
      $stmt->executeStatement();
      $this->getEntityManager()->getConnection()->commit();
    } catch (\Throwable $e) {
      $this->getEntityManager()->getConnection()->rollBack();
      return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'erreur' => ''];
  }
}
