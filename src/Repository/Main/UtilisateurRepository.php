<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Repository\Main;

use App\Entity\Main\Utilisateur;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description UtilisateurRepository]
 */
class UtilisateurRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Utilisateur::class);
    }

    /**
     * [Description for add]
     *
     * @param Utilisateur $entity
     * @param bool $flush
     *
     * @return void
     *
     * Created at: 13/02/2024 19:29:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function add(Utilisateur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * [Description for remove]
     *
     * @param Utilisateur $entity
     * @param bool $flush
     *
     * @return void
     *
     * Created at: 13/02/2024 19:29:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function remove(Utilisateur $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * [Description for insertUtilisateurPreferenceFavori]
     *
     * @param string $mode
     * @param array $preference
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/02/2024 23:05:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertUtilisateurPreferenceFavori($mode, $preference, $map):array
    {
        /** On récupére les préférences */
        $statut = $preference['statut'];
        $listeProjet = $preference['projet'];
        $listeFavori = $preference['favori'];
        $listeVersion = $preference['version'];
        $bookmark = $preference['bookmark'];

        /**
         * On recupère la valeur de l'index pour le projet en favori
         */
        $i=$index=-1;
        foreach($preference['version'] as $projet){
            $i++;
            if (array_key_exists($map['maven_key'],$projet)){
                $index=$i;
            };
        }


        /* si le projet n'est pas un favori on l'ajoute */
        if (!in_array($map['maven_key'], $listeFavori)){
            array_push($preference['favori'], $map['maven_key']);
        }

        /** On ajoute la version du projet en favori si il n'existe pas */
        if (!str_contains(\serialize($preference['version']), $map['maven_key'])){
            /** On ajoute à la liste la version */
            array_push($preference['version'],[$map['maven_key'] => [$map['version']]]);
        } else {
            /** Il existe déjà un projet avec au moins une version en favori, on ajoute une nouvelle version. */
            $listeVersion = [];
            /**
             * On parse le json, pour trouver la version ($index)
             * et on ajoute une nouvelle version, si la version n'existe pas dans la liste
             * liste = [ "fr.ma-moulinette:monapplication" => [ 0 => "4.1.0-RELEASE"] ]
             */
            foreach ($preference['version'] as $key => $liste) {
                /** On récupère chaque liste de version et on cherche si la version existe ou non pour la clé*/
                if ($key === $index) {
                    if (!in_array($map['version'],$liste[$map['maven_key']])) {
                        array_push($preference['version'][$index][$map['maven_key']],$map['version']);
                    }
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

        $response=['mode'=>$mode, 'code'=>200, 'erreur'=>''];
        $sql = "UPDATE utilisateur
                SET preference=:preference
                WHERE courriel=:courriel";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':courriel', $map['courriel']);
        $conn->bindValue(':preference', $jsonArray);
        try {
            if ($mode !== 'TEST') {
                $conn->executeQuery();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return $response;
    }

    /**
     * [Description for deletePreferenceFavoris]
     * Permet de supprimer un favoris ou une version favorite d'un projet
     *
     * @param string $mode
     * @param array $preference
     * @param array $map
     *
     * @return array
     *
     * Created at: 14/02/2024 09:56:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteUtilisateurPreferenceFavori($mode, $preference, $map):array {
        /**
         * On regarde d'abord si le projet à une version en favori
         * ensuite on regarde conblen de version pour ce projet sont en favori
         * si, il n'y a q'une version, on supprime la version et la clé de la liste de versions
         * et on supprimer aussi le projet de la liste des projets favoris
         * sinon, on supprime seulement la version favorite et on laisse le projet dans la liste de favoris.
        */

        /** On récupére les préférences */
        $statut = $preference['statut'];
        $listeProjet = $preference['projet'];
        $listeFavori = $preference['favori'];
        $listeVersion = $preference['version'];
        $bookmark = $preference['bookmark'];

        /**
         * On recupère la valeur de l'index pour le projet en favori et
         * on regarde si la version est unique.
         */
        $i=$index=-1;
        foreach($preference['version'] as $item){
            $i++;
            if (array_key_exists($map['maven_key'],$item)){
                $index=$i;
                $nombreVersion=count($item);
            };
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

        $response=['mode'=>$mode, 'code'=>200, 'erreur'=>''];
        $sql = "UPDATE utilisateur
                SET preference=:preference
                WHERE courriel=:courriel";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':courriel', $map['courriel']);
        $conn->bindValue(':preference', $jsonArray);
        try {
            if ($mode !== 'TEST') {
                $conn->executeQuery();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return $response;
    }

    /**
     * [Description for updateUtilisateurPreferenceFavori]
     * Met à jour le favori pour le projet
     *
     * @param string $mode
     * @param mixed $preference
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 26/03/2024 17:00:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateUtilisateurPreferenceFavori($mode, $preference, $map):array {
        /** On regarde si la projet est dans les favoris */
        $isFavori = in_array($map->maven_key, $preference['favori']);

        /**
         * On le supprime de la liste des favoris s'il exsite dans les préferences
         * Sinon on l'ajoute
         */

        /** On récupére les préférences */
        $statut = $preference['statut'];
        $listeProjet = $preference['projet'];
        $listeFavori = $preference['favori'];
        $listeVersion = $preference['version'];
        $bookmark = $preference['bookmark'];

        $response = ['mode'=>$mode, 'code'=> 206, 'statut'=>-1, 'erreur'=>''];
        if ($isFavori) {
            /** on supprime le projet de la liste */
            $nouvelleListeFavori = array_diff($listeFavori, [$map->maven_key]);
            $statut['favori'] = false;

            /** On met à jour l'objet. */
            $jarray = json_encode([
                'statut' => $statut,
                'projet' => $listeProjet,
                'favori' => $nouvelleListeFavori,
                'version' =>$listeVersion,
                'bookmark' => $bookmark
            ]);

            /** On met à jour les préférences. */
            $sql = "UPDATE utilisateur
                    SET preference = '$jarray'
                    WHERE courriel=:courriel";
            $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            $conn->bindValue(':courriel', $map['courriel']);
            try {
                    if ($mode !== 'TEST') {
                        $conn->executeQuery();
                    } else {
                        $response = ['mode'=>$mode, 'code'=> 202, 'statut'=>-1, 'erreur'=>'TEST'];
                    }
            } catch (\Doctrine\DBAL\Exception $e) {
                $response = ['mode'=>$mode, 'code'=>500, 'statut'=>-1, 'erreur'=> $e->getCode()];
            }
            $response = ['mode'=>$mode, 'code'=> 200, 'statut'=>0, 'erreur'=>''];
        } else {
            /** On ajoute le projet à la liste */
            array_push($preference['favori'], $map->maven_key);
            $statut['favori'] = true;

            /** On met à jour l'objet. */
            $jarray = json_encode([
                'statut' => $statut,
                'projet' => $listeProjet,
                'favori' => $listeFavori,
                'version' => $listeVersion,
                'bookmark' => $bookmark
            ]);

            /** On met à jour les préférences. */
            $sql = "UPDATE utilisateur
                    SET preference = '$jarray'
                    WHERE courriel=:courriel";
            $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            $conn->bindValue(':courriel', $map['courriel']);
            try {
                    if ($mode !== 'TEST') {
                        $conn->executeQuery();
                    } else {
                        $response = ['mode'=>$mode, 'code'=> 202, 'statut'=>-1, 'erreur'=>'TEST'];
                    }
            } catch (\Doctrine\DBAL\Exception $e) {
                $response = ['mode'=>$mode, 'code'=>500, 'statut'=>-1, 'erreur'=> $e->getCode()];
            }
            $response = ['mode'=>$mode, 'code'=> 200, 'statut'=>1, 'erreur'=>''];
        }

        return $response;
    }

    /**
     * [Description for updateUtilisateurResetPassword]
     * Modifie l'indicateur reset du mot de passe.
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 03/04/2024 19:34:48 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateUtilisateurResetPassword($mode, $map):array {

        $sql = "UPDATE utilisateur
                SET init = :init, date_modification=:date_modification
                WHERE courriel=:courriel";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':init', $map['init']);
        $conn->bindValue(':date_modification', $map['date_modification']);
        $conn->bindValue(':courriel', $map['courriel']);
        try {
                if ($mode !== 'TEST') {
                    $conn->executeQuery();
                } else {
                    return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
                }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'statut'=>-1, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'erreur'=>''];
    }

}
