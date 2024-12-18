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
 * [Description UtilisateurRepository]
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public static $removeReturnLine = "/\s+/u";
    public static $noDataBase = 'La connexion à la base de données a échoué.';
    public static $courriel = ':courriel';
    public static $preference = ':preference';
    public static $dateModification = ':date_modification';
    public static $dateFormatted = 'Y-m-d H:i:sO';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
   * [Description for handleDatabaseException]
   *
   * @param \Doctrine\DBAL\Exception $e
   *
   * @return array
   *
   * Created at: 21/10/2024 16:55:20 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Lilmod & Lelamed - Creative Common CC-BY-NC-SA 4.0.
   */
    protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
    {
        if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
            return ['code'=>500, 'erreur' => static::$noDataBase];
        } else {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
    }

    /**
     * [Description for insertUtilisateurFavoriVersion]
     * On ajoute ou supprime la version du projet dans les favori_version
     * Utilisé par le contrôleur SUIVI
     *
     * @param array $preference
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/02/2024 23:05:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateUtilisateurFavoriVersion($preference, $map): array
    {
        /** On génère une date  */
        $now = new \DateTime();

        /**
         * On récupère la valeur de l'index pour le projet en favori et
         * on regarde si la version est unique.
         *  item = "fr.ma-petite-entreprise:ma-moulinette" => [ 0 => "1.0.0-RELEASE", 1 => "2.0.0-SNAPSHOT"]
         */
        $i=$index=-1;
        $nombreVersion=0;
        foreach($preference['favori_version'] as $item){
            $i++;

            if (array_key_exists($map['maven_key'],$item)){
                $index=$i;
                // nombre de version pour le projet
                $nombreVersion=count($item[$map['maven_key']]);
            }
        }

        /* si le projet n'est pas un favori on l'ajoute */
        if (!in_array($map['maven_key'], $preference['favori_projet'])){
            array_push($preference['favori_projet'], $map['maven_key']);
        }

        /** On ajoute la version pour un projet qui est déjà présent */
        if (str_contains(\serialize($preference['favori_version']), $map['maven_key']) && $map['favori'] === 1){
            /** On ajoute à la liste la version */
            array_push($preference['favori_version'][$index][$map['maven_key']], $map['version']);
            $listeVersion=$preference['favori_version'];
        }

        /** On ajoute la version du projet en favori si le projet n'existe pas. */
        if (!str_contains(\serialize($preference['favori_version']), $map['maven_key']) && $map['favori'] === 1){
            /** On ajoute à la liste la version */
            array_push($preference['favori_version'],[$map['maven_key'] => [$map['version']]]);
            $listeVersion=$preference['favori_version'];
        }

        /** On supprime le projet en favori car il n'y a qu'une version en favori*/
        if (str_contains(\serialize($preference['favori_version']), $map['maven_key']) &&
                $nombreVersion===1 && $map['favori'] === 0){
            /** On supprime le projet */
            foreach ($preference['favori_version'] as $index => $subArray) {
                if (isset($subArray[$map['maven_key']])) {
                    unset($preference['favori_version'][$index][$map['maven_key']]);
                }
            }
            /** Nettoie le tableau */
            $preference['favori_version'] = array_filter($preference['favori_version'], function($subArray) {
                return !empty($subArray);
            });
            /** On supprime aussi le projet favori de la liste des projets favoris */
            $key = array_search($map['maven_key'], $preference['favori_projet']); // Recherche la clé
            if ($key !== false) {
                unset($preference['favori_projet'][$key]); // Supprime la valeur
            }
            // Ré-indexer le tableau
            $preference['favori_projet'] = array_values($preference['favori_projet']);
            $listeVersion=$preference['favori_version'];
        }

        /** On supprime la version du projet en favori, le nombre de version est > 1 */
        if (str_contains(\serialize($preference['favori_version']), $map['maven_key']) && $map['favori'] === 0){
            /** On supprime pour le projet la version */
            $nouvelleListeVersion = array_diff($preference['favori_version'][$index][$map['maven_key']], [$map['version']]);
            /** On crée une nouvelle version avec la nouvelleListe */
            $nouvelleVersion = [$map['maven_key'] => $nouvelleListeVersion];

            /** On reconstruit la liste des versions avec ou sans le projet qui a été supprimé. */
            $listeVersion = [];
            foreach ($preference['favori_version'] as $key => $value) {
                if ($key === $index && $nombreVersion>1) {
                    array_push($listeVersion, $nouvelleVersion);
                }
                if ($key !== $index) {
                    array_push($listeVersion, $value);
                }
            }
        }

        /** On met à jour l'objet et on vire les \. */
        $jsonArray = stripslashes(
            json_encode([
                'statut' => $preference['statut'],
                'suivi_projet' => $preference['suivi_projet'],
                'favori_projet' => $preference['favori_projet'],
                'favori_version' => $listeVersion,
                'bookmark' => $preference['bookmark']
                ])
        );

        $response=['code'=>200, 'erreur'=>''];

        $sql = "UPDATE ma_moulinette.utilisateur
                SET preference = :preference,
                    date_modification = :date_modification
                WHERE courriel=:courriel";

        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$courriel, $map['courriel']);
                    $stmt->bindValue(static::$preference, $jsonArray);
                    $stmt->bindValue(static::$dateModification, $now->format(static::$dateFormatted));
                $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return $response;
    }

    /**
     * [Description for updateUtilisateurFavoriProjet]
     * Met à jour pour le projet le statut de favori - true ou false
     * Utilisé par le contrôleur PROJET
     *
     * @param mixed $preference
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 26/03/2024 17:00:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateUtilisateurFavoriProjet($preference, $map): array
    {
        /** On regarde si la projet est dans la liste des favoris (true) */
        $isFavori = in_array($map['maven_key'], $preference['favori_projet']);

        /**
         * On le supprime de la liste des favoris s'il existe dans les préférences
         * Sinon on l'ajoute
         */

        $response = ['code'=> 206, 'statut'=>-1, 'erreur'=>''];
        $now = new \DateTime();
        if ($isFavori) {
            /** on supprime le projet de la liste */
            $nouvelleListeFavori = array_diff($preference['favori_projet'], [$map['maven_key']]);

            /** Si le nombre de favoris dans la liste est = 0 alors on désactive la gestion des favoris */
            if (count($nouvelleListeFavori) === 0){
                $preference['statut']['favori_projet'] = false;
            }

            /** On met à jour l'objet. */
            $jsonArray = json_encode([
                'statut' => $preference['statut'],
                'suivi_projet' => $preference['suivi_projet'],
                'favori_projet' => $nouvelleListeFavori,
                'favori_version' => $preference['favori_version'],
                'bookmark' => $preference['bookmark']
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

             // Vérification de l'erreur JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['code' => 400, 'erreur' => 'Invalid JSON: ' . json_last_error_msg()];
            }
            /** On met à jour les préférences. */
            $sql = "UPDATE ma_moulinette.utilisateur
                    SET preference = :preference,
                        date_modification = :date_modification
                    WHERE courriel=:courriel";

            try {
                    $this->getEntityManager()->getConnection()->beginTransaction();
                        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                            $stmt->bindValue(static::$preference, $jsonArray);
                            $stmt->bindValue(static::$dateModification, $now->format(static::$dateFormatted));
                            $stmt->bindValue(static::$courriel, $map['courriel']);
                            $stmt->executeStatement();
                    $this->getEntityManager()->getConnection()->commit();
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->getEntityManager()->getConnection()->rollBack();
                return $this->handleDatabaseException($e);
            }
            $response = ['code' => 200, 'statut' => 0, 'erreur' => ''];
        } else {
            /** On ajoute le projet à la liste des favoris et on active la gestion des favoris */
            array_push($preference['favori_projet'], $map['maven_key']);
            $preference['statut']['favori_projet'] = true;

            /** On met à jour l'objet. */
            $jsonArray = json_encode([
                'statut' => $preference['statut'],
                'suivi_projet' => $preference['suivi_projet'],
                'favori_projet' => $preference['favori_projet'],
                'favori_version' => $preference['favori_version'],
                'bookmark' => $preference['bookmark']
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            dd('supprime', $jsonArray);

            $sql = "UPDATE ma_moulinette.utilisateur
                    SET preference = :preference,
                        date_modification = :date_modification
                    WHERE courriel=:courriel";

            /** On met à jour les préférences. */
            try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                        $stmt->bindValue(static::$preference, $jsonArray);
                        $stmt->bindValue(static::$courriel, $map['courriel']);
                        $stmt->bindValue(static::$dateModification, $now->format(static::$dateFormatted));
                        $stmt->executeStatement();
                $this->getEntityManager()->getConnection()->commit();
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->getEntityManager()->getConnection()->rollBack();
                return $this->handleDatabaseException($e);
            }
            $response = ['code' => 200, 'statut' => 1, 'erreur'=> ''];
        }
        return $response;
    }

    /**
     * [Description for updateUtilisateurResetPassword]
     * Modifie l'indicateur reset du mot de passe.
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 03/04/2024 19:34:48 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateUtilisateurResetPassword($map): array
    {
        $sql = "UPDATE ma_moulinette.utilisateur
                SET init = :init,
                    date_modification = :date_modification
                WHERE courriel=:courriel";

        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(':init', $map['init']);
                    $stmt->bindValue(static::$courriel, $map['courriel']);
                    $stmt->bindValue(':date_modification', $map['date_modification']->format(static::$dateFormatted));
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code'=>200, 'erreur'=>''];
    }

}
