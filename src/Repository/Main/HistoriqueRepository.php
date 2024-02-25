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

use App\Entity\Main\Historique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description HistoriqueRepository]
 */
class HistoriqueRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Historique::class);
    }

    public function add(Historique $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Historique $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * [Description for countHistoriqueprojet]
     * On veut savoir si le projet a été historisé ?
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 16/02/2024 12:08:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHistoriqueprojet($mode, $map):array {

        /** On retire le statut à toute les versions du projet, radical mais efficace */
        $sql = "SELECT count(*) AS nombre
                FROM historique
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            if ($mode !== 'TEST') {
                $request=$conn->executeQuery()->fetchAllAssociative();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }

        /** on prépare la réponse */
        $response=['mode'=>$mode, 'code'=>200, 'erreur'=>'', 'nombre'=>$request[0]['nombre']];
        return $response;
    }

    /**
     * [Description for getProjetFavori]
     * Récupère les indicateurs du projet favori
     * @param mixed $where
     *
     * @return array
     *
     * Created at: 27/10/2023 15:37:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProjetFavori($where): array
    {
    $sql = "SELECT DISTINCT
            maven_key as mavenkey, nom_projet as nom,
            version, date_version as date, note_reliability as fiabilite,
            note_security as securite, note_hotspot as hotspot,
            note_sqale as sqale, nombre_bug as bug, nombre_vulnerability as vulnerability,
            nombre_code_smell as code_smell, hotspot_total as hotspots
            FROM historique
            WHERE :where
            ORDER BY date_version DESC limit 4";
    $select=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
    $select->bindValue(":where", $where);
    return $select->fetchAllAssociative();
    }

    /**
     * [Description for updateHistoriqueReference]
     *  Met à jour la version de référence pour un projet
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 16/02/2024 10:57:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateHistoriqueReference($mode, $map):array {

        /** on prépare la réponse */
        $response=['mode'=>$mode, 'code'=>200, 'erreur'=>''];

        /** On retire le statut à toute les versions du projet, radical mais efficace */
        $sql = "UPDATE historique
                SET initial=0
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            if ($mode !== 'TEST') {
                $conn->executeQuery();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }

        /** On met ajour la version de réference pour le projet */
        $sql = "UPDATE historique
                SET initial=:initial
                WHERE maven_key=:maven_key
                AND version=:version
                AND date_version=:date_version";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':initial', $map['initial']);
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':version', $map['version']);
        $conn->bindValue(':date_version', $map['date_version']);
        try {
            if ($mode !== 'TEST') {
                $conn->executeQuery();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        return $response;
    }

    /**
     * [Description for deleteHistoriqueProjet]
     * Suppression de la table historique du projet
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 14/02/2024 10:29:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteHistoriqueProjet($mode, $map):array {

        /** on prépare la réponse */
        $response=['mode'=>$mode, 'code'=>200, 'erreur'=>''];

        /** On prépare la requête */
        $sql = "DELETE FROM historique
                WHERE maven_key=:maven_key
                AND version=:version
                AND date_version=:date_version";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':version', $map['version']);
        $conn->bindValue(':date_version', $map['date_version']);
        try {
            if ($mode !== 'TEST') {
                $conn->executeQuery();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        return $response;
    }

    /**
     * [Description for selectUnionHistoriqueProjet]
     * Remonte les projets en historique
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 25/02/2024 10:13:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueProjet($mode, $map):array {
        /** On prépare la requête */
        $sql = "SELECT * FROM
                (SELECT nom_projet AS nom, date_version AS date, version,
                    suppress_warning, no_sonar, nombre_bug AS bug,
                    nombre_vulnerability AS faille,
                    nombre_code_smell AS mauvaise_pratique,
                    hotspot_total AS nombre_hotspot,
                    frontend AS presentation, backend as metier, autre,
                    note_reliability AS fiabilite,
                    note_security AS securite, note_hotspot,
                    note_sqale AS maintenabilite, initial
                FROM historique
                WHERE maven_key=:maven_key AND initial=:initial_true)
                UNION SELECT * FROM
                (SELECT nom_projet as nom, date_version as date,
                    version, suppress_warning, no_sonar, nombre_bug as bug,
                    nombre_vulnerability as faille,
                    nombre_code_smell as mauvaise_pratique,
                    hotspot_total as nombre_hotspot,
                    frontend as presentation, backend as metier,
                    autre, note_reliability as fiabilite,
                    note_security as securite, note_hotspot,
                    note_sqale as maintenabilite, initial
                FROM historique
                WHERE maven_key=:maven_key AND initial=:initial_false
                ORDER BY date_version DESC LIMIT :limit)";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':initial_true', TRUE);
        $conn->bindValue(':initial_false', FALSE);
        $conn->bindValue(':limit', $map['limit']);
        try {
            if ($mode !== 'TEST') {
                $suivi=$conn->executeQuery()->fetchAllAssociative();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        $response=['mode'=>$mode, 'code'=>200, 'request'=>$suivi, 'erreur'=>''];
        return $response;
    }

    /**
     * [Description for selectUnionHistoriqueAnomalie]
     * On remonte les anomalies des projets favoris
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 25/02/2024 12:38:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueAnomalie($mode, $map):array {
        /** On prépare la requête */
        $sql = "SELECT * FROM
                (SELECT date_version AS date,
                        nombre_anomalie_bloquant AS bloquant,
                        nombre_anomalie_critique AS critique,
                        nombre_anomalie_majeur AS majeur,
                        nombre_anomalie_mineur AS mineur
                FROM historique
                WHERE maven_key=:maven_key AND initial=:initial_true)
                UNION SELECT * FROM
                (SELECT date_version AS date,
                        nombre_anomalie_bloquant AS bloquant,
                        nombre_anomalie_critique as critique,
                        nombre_anomalie_majeur as majeur,
                        nombre_anomalie_mineur as mineur
                FROM historique
                WHERE maven_key=:maven_key AND initial=:initial_false
                ORDER BY date_version DESC LIMIT :limit)";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':initial_true', TRUE);
        $conn->bindValue(':initial_false', FALSE);
        $conn->bindValue(':limit', $map['limit']);
        try {
            if ($mode !== 'TEST') {
                $severite=$conn->executeQuery()->fetchAllAssociative();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        $response=['mode'=>$mode, 'code'=>200, 'request'=>$severite, 'erreur'=>''];
        return $response;
    }

    /**
     * [Description for selectUnionHistoriqueDetails]
     * remonte les anomalies par type des favoris
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 25/02/2024 12:48:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueDetails($mode, $map):array {
        /** On prépare la requête */
        $sql = "SELECT * FROM
                (SELECT date_version AS date, version,
                        bug_blocker, bug_critical, bug_major,
                        bug_minor, bug_info,
                        vulnerability_blocker, vulnerability_critical,
                        vulnerability_major, vulnerability_minor,
                        vulnerability_info,
                        code_smell_blocker, code_smell_critical,
                        code_smell_major, code_smell_minor,
                        code_smell_info, initial
                FROM historique
                WHERE maven_key=:maven_key AND initial=:initial_true)
                UNION SELECT * FROM
                (SELECT date_version AS date, version,
                        bug_blocker, bug_critical, bug_major,
                        bug_minor, bug_info,
                        vulnerability_blocker, vulnerability_critical,
                        vulnerability_major, vulnerability_minor,
                        vulnerability_info,
                        code_smell_blocker, code_smell_critical,
                        code_smell_major, code_smell_minor,
                        code_smell_info, initial
                FROM historique
                WHERE maven_key=:maven_key AND initial=:initial_false
                ORDER BY date_version DESC LIMIT :limit)";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':initial_true', TRUE);
        $conn->bindValue(':initial_false', FALSE);
        $conn->bindValue(':limit', $map['limit']);
        try {
            if ($mode !== 'TEST') {
                $details=$conn->executeQuery()->fetchAllAssociative();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        $response=['mode'=>$mode, 'code'=>200, 'request'=>$details, 'erreur'=>''];
        return $response;
    }

    public function selectHistoriqueAnomalieGraphique($mode, $map):array {
        /** On prépare la requête */
        $sql = "SELECT  nombre_bug AS bug, nombre_vulnerability AS secu,
                        nombre_code_smell AS code_smell, date_version AS date
                FROM historique
                WHERE maven_key=:maven_key
                GROUP BY date_version
                ORDER BY date_version ASC";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            if ($mode !== 'TEST') {
                $graph=$conn->executeQuery()->fetchAllAssociative();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        $response=['mode'=>$mode, 'code'=>200, 'request'=>$graph, 'erreur'=>''];
        return $response;
    }

}
