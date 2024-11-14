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
    public static $dateModification = ':date_modification';

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
     * [Description for insertUtilisateurPreferenceFavori]
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
    public function insertUtilisateurPreferenceFavori($preference, $map): array
    {
        /** On récupère les préférences */
        $statut = $preference['statut'];
        $listeProjet = $preference['projet'];
        $listeFavori = $preference['favori'];
        $listeVersion = $preference['version'];
        $bookmark = $preference['bookmark'];

        /**
         * On récupère la valeur de l'index pour le projet en favori
         */
        $i=$index=-1;
        foreach($preference['version'] as $projet){
            $i++;
            if (array_key_exists($map['maven_key'],$projet)){
                $index=$i;
            }
        }

        /* si le projet n'est pas un favori on l'ajoute */
        if (!in_array($map['maven_key'], $listeFavori)){
            array_push($preference['favori'], $map['maven_key']);
        }

        /** On ajoute la version du projet en favori s'il n'existe pas */
        if (!str_contains(\serialize($preference['version']), $map['maven_key'])){
            /** On ajoute à la liste la version */
            array_push($preference['version'],[$map['maven_key'] => [$map['version']]]);
        } else {
            /** Il existe déjà un projet avec au moins une version en favori, on ajoute une nouvelle version. */
            $listeVersion = [];
            /**
             * On parse le json, pour trouver la version ($index)
             * et on ajoute une nouvelle version, si la version n'existe pas dans la liste
             * liste = [ "fr.ma-moulinette:monapplication" => [ 0 => "4.1.0-RELEASE", 1 => "4.2.1-RC1"] ]
             */
            foreach ($preference['version'] as $key => $liste) {
                /** On récupère chaque liste de version et on cherche si la version existe ou non pour la clé*/
                if ($key === $index && !in_array($map['version'],$liste[$map['maven_key']])) {
                    array_push($preference['version'][$index][$map['maven_key']],$map['version']);
                }
                if ($key !== $index) {
                    array_push($listeVersion, $preference['version'][$key]);
                }
            }
        }

        /** On met à jour l'objet et on vire les \. */
        $jsonArray = stripslashes(
            json_encode([
                'statut' => $statut,
                'projet' => $listeProjet,
                'favori' => $preference['favori'],
                'version' => $preference['version'],
                'bookmark' => $bookmark
                ])
        );

        $response=['code'=>200, 'erreur'=>''];
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "UPDATE ma_moulinette.utilisateur
                    SET preference=:preference
                    WHERE courriel=:courriel";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$courriel, $map['courriel']);
                $stmt->bindValue(':preference', $jsonArray);
                $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return $response;
    }

    /**
     * [Description for deletePreferenceFavoris]
     * Permet de supprimer un favoris ou une version favorite d'un projet
     *
     * @param array $preference
     * @param array $map
     *
     * @return array
     *
     * Created at: 14/02/2024 09:56:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteUtilisateurPreferenceFavori($preference, $map): array
    {
        /**
         * On regarde d'abord si le projet à une version en favori
         * ensuite on regarde combien de version pour ce projet sont en favori
         * si, il n'y a q'une version, on supprime la version et la clé de la liste de versions
         * et on supprimer aussi le projet de la liste des projets favoris
         * sinon, on supprime seulement la version favorite et on laisse le projet dans la liste de favoris.
        */

        /** On récupère les préférences */
        $statut = $preference['statut'];
        $listeProjet = $preference['projet'];
        $listeFavori = $preference['favori'];
        $listeVersion = $preference['version'];
        $bookmark = $preference['bookmark'];

        /**
         * On récupère la valeur de l'index pour le projet en favori et
         * on regarde si la version est unique.
         *  item = "fr.monapplication:ma-moulinette" => [ 0 => "4.2.0-RELEASE", 1 => "4.2.1-RC1"]
         */
        $i=$index=-1;
        $nombreVersion=0;
        foreach($preference['version'] as $item){
            $i++;

            if (array_key_exists($map['maven_key'],$item)){
                $index=$i;
                // nombre de version pour le projet
                $nombreVersion=count($item[$map['maven_key']]);
            }
        }

        /** On supprime le projet en favori car il n'y a qu'une version en favori*/
        if (in_array($map['maven_key'], $listeFavori) && $nombreVersion===1){
            $listeFavori=array_diff($listeFavori, [$map['maven_key']]);
        }

        /** On supprime la version du projet en favori */
        if (str_contains(\serialize($preference['version']), $map['maven_key'])){
            /** On supprime pour le projet la version */
            $nouvelleListeVersion = array_diff($preference['version'][$index][$map['maven_key']], [$map['version']]);
            /** On crée une nouvelle version avec la nouvelleListe */
            $nouvelleVersion = [$map['maven_key'] => $nouvelleListeVersion];
            /** On reconstruit la liste des versions avec ou sans le projet qui a été supprimé. */
            $listeVersion = [];
            foreach ($preference['version'] as $key => $value) {
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
                'statut' => $statut,
                'projet' => $listeProjet,
                'favori' => $listeFavori,
                'version' => $listeVersion,
                'bookmark' => $bookmark
                ])
            );

        $response=['code'=>200, 'erreur'=>''];
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "UPDATE ma_moulinette.utilisateur
                        SET preference=:preference
                        WHERE courriel=:courriel";

                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$courriel, $map['courriel']);
                $stmt->bindValue(':preference', $jsonArray);

                $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return $response;
    }

    /**
     * [Description for updateUtilisateurPreferenceFavori]
     * Met à jour le favori pour le projet
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
    public function updateUtilisateurPreferenceFavori($preference, $map): array
    {
        /** On regarde si la projet est dans la liste des favoris (true) */
        $isFavori = in_array($map['maven_key'], $preference['favori']);

        /**
         * On le supprime de la liste des favoris s'il existe dans les préférences
         * Sinon on l'ajoute
         */

        /** On récupère les préférences */
        $statut = $preference['statut'];
        $listeProjet = $preference['projet'];
        $listeFavori = $preference['favori'];
        $listeVersion = $preference['version'];
        $bookmark = $preference['bookmark'];

        $response = ['code'=> 206, 'statut'=>-1, 'erreur'=>''];
        $now = new \DateTime();

        if ($isFavori) {

            /** on supprime le projet de la liste */
            $nouvelleListeFavori = array_diff($listeFavori, [$map['maven_key']]);

            /** Si le nombre de favoris dans la liste est = 0 alors on désactive la gestion des favoris */
            if (count($nouvelleListeFavori) === 0){
                $statut['favori'] = false;
            }

            /** On met à jour l'objet. */
            $jsonArray = json_encode([
                'statut' => $statut,
                'projet' => $listeProjet,
                'favori' => $nouvelleListeFavori,
                'version' => $listeVersion,
                'bookmark' => $bookmark
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

             // Vérification de l'erreur JSON
            if (json_last_error() !== JSON_ERROR_NONE) {
                return ['code' => 400, 'erreur' => 'Invalid JSON: ' . json_last_error_msg()];
            }

            /** Modèle JSON */
            // {"statut" :{"projet":false,"favori":false,"version":true,"bookmark":true},
	        // "projet"  :{},
            // "favori":["fr.ma-petite-entreprise:ma-moulinette"],
	        // "version":[{"fr.x:app1":["1.0.0-RELEASE", "2.0.0-RELEASE","3.0.0-RELEASE"]},
            //            {"fr.x:app2":["1.1.0-RELEASE"]}],
            // "bookmark":["fr.ma-petite-entreprise:ma-moulinette"]}'

            /** On met à jour les préférences. */
            try {
                    $this->getEntityManager()->getConnection()->beginTransaction();
                        $sql = "UPDATE ma_moulinette.utilisateur
                                SET preference = :preference, date_modification = :date_modification
                                WHERE courriel=:courriel";
                        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                            $stmt->bindValue(':preference', $jsonArray);
                            $stmt->bindValue(static::$dateModification, $now->format('Y-m-d H:i:sP'));
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
            array_push($preference['favori'], $map['maven_key']);
            $statut['favori'] = true;

            /** On met à jour l'objet. */
            $jsonArray = json_encode([
                'statut' => $statut,
                'projet' => $listeProjet,
                'favori' => $preference['favori'],
                'version' => $listeVersion,
                'bookmark' => $bookmark
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

            /** On met à jour les préférences. */
            try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $sql = "UPDATE ma_moulinette.utilisateur
                        SET preference = :preference, date_modification = :date_modification
                        WHERE courriel=:courriel";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                        $stmt->bindValue(static::$courriel, $map['courriel']);
                        $stmt->bindValue(':preference', $jsonArray);
                        $stmt->bindValue(static::$dateModification, $now->format('Y-m-d H:i:sP'));
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
        SET init = :init, date_modification=:date_modification
        WHERE courriel=:courriel";

        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(':init', $map['init']);
                    $stmt->bindValue(':date_modification', $map['date_modification']);
                    $stmt->bindValue(static::$courriel, $map['courriel']);
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code'=>200, 'erreur'=>''];
    }

}
