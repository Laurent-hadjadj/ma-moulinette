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
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }

        /** on prépare la réponse */
        return ['mode'=>$mode, 'code'=>200, 'erreur'=>'', 'nombre'=>$request[0]['nombre']];
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
        $conn->bindValue(':initial_true', 0);
        $conn->bindValue(':initial_false', 1);
        $conn->bindValue(':limit', $map['limit']);
        try {
            if ($mode !== 'TEST') {
                $suivi=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        return ['mode'=>$mode, 'code'=>200, 'request'=>$suivi, 'erreur'=>''];
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
        $conn->bindValue(':initial_true', 0);
        $conn->bindValue(':initial_false', 1);
        $conn->bindValue(':limit', $map['limit']);
        try {
            if ($mode !== 'TEST') {
                $severite=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        return ['mode'=>$mode, 'code'=>200, 'request'=>$severite, 'erreur'=>''];
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
        $conn->bindValue(':initial_true', 0);
        $conn->bindValue(':initial_false', 1);
        $conn->bindValue(':limit', $map['limit']);
        try {
            if ($mode !== 'TEST') {
                $details=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        return ['mode'=>$mode, 'code'=>200, 'request'=>$details, 'erreur'=>''];
    }

    /**
     * [Description for selectHistoriqueAnomalieGraphique]
     * On remonte les données pour construire le graphique.
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 25/02/2024 17:31:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
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
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        return ['mode'=>$mode, 'code'=>200, 'request'=>$graph, 'erreur'=>''];
    }

    /**
     * [Description for insertHistoriqueAjoutProjet]
     * On ajoute une version à l'historique à partir des données SonarQube historisées.
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 25/02/2024 17:34:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertHistoriqueAjoutProjet($mode, $map):array {
        /** On prépare la requête */
        $sql = "INSERT OR IGNORE INTO historique
                    (maven_key,version,date_version,
                    nom_projet,version_release,version_snapshot,
                    suppress_warning,no_sonar,nombre_ligne,
                    nombre_ligne_code,couverture,
                    duplication,tests_unitaires,nombre_defaut,dette,
                    nombre_bug,nombre_vulnerability,nombre_code_smell,
                    bug_blocker, bug_critical, bug_major, bug_minor, bug_info,
                    vulnerability_blocker, vulnerability_critical, vulnerability_major,
                    vulnerability_minor, vulnerability_info,
                    code_smell_blocker, code_smell_critical, code_smell_major,
                    code_smell_minor, code_smell_info,
                    frontend,backend,autre,
                    nombre_anomalie_bloquant,nombre_anomalie_critique,
                    nombre_anomalie_majeur,
                    nombre_anomalie_mineur,nombre_anomalie_info,
                    note_reliability,note_security,
                    note_sqale,note_hotspot,hotspot_total,
                    hotspot_high,hotspot_medium,hotspot_low,
                    initial,date_enregistrement)
                VALUES (
                    :map->maven_key, :version, :date_version, :nom_projet,
                    :version_release, :version_snapshot,
                    :suppress_warning, :no_sonar, :nombre_ligne, :nombre_ligne_code,
                    :couverture, :duplication, :tests_unitaires,
                    :nombre_defaut' :map->nombre_defaut,
                    :dette' :map->dette,
                    :nombre_bug' :map->nombre_bug,
                    :nombre_vulnerability' :map->nombre_vulnerability,
                    :nombre_code_smell' :map->nombre_code_smell,
                    :bug_blocker, :bug_critical, :bug_major, :bug_minor, :bug_info,
                    :vulnerability_blocker, :vulnerability_critical, :vulnerability_major, :vulnerability_minor, :vulnerability_info,
                    :code_smell_blocker, :code_smell_critical, :code_smell_major, :code_smell_minor,code_smell_info,
                    :frontend, :backend, :autre,
                    :nombre_anomalie_bloquant, :nombre_anomalie_critique, :nombre_anomalie_majeur,
                    :nombre_anomalie_mineur, :nombre_anomalie_info,
                    :note_reliability, :note_security, :note_sqale, :note_hotspot,
                    :hotspot_total, :hotspot_high, :hotspot_medium, :hotspot_low,
                    :initial, :date_enregistrement)";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':version', $map['version']);
        $conn->bindValue(':date_version', $map['date_version']);
        $conn->bindValue(':nom_projet', $map['nom_projet']);
        $conn->bindValue(':version_release', $map['version_release']);
        $conn->bindValue(':version_snapshot', $map['version_snapshot']);
        $conn->bindValue(':suppress_warning', $map['suppress_warning']);
        $conn->bindValue(':no_sonar', $map['no_sonar']);
        $conn->bindValue(':nombre_ligne', $map['nombre_ligne']);
        $conn->bindValue(':nombre_ligne_code', $map['nombre_ligne_code']);
        $conn->bindValue(':couverture', $map['couverture']);
        $conn->bindValue(':duplication', $map['duplication']);
        $conn->bindValue(':tests_unitaires', $map['tests_unitaires']);
        $conn->bindValue(':nombre_defaut', $map['nombre_defaut']);
        $conn->bindValue(':dette', $map['dette']);
        $conn->bindValue(':nombre_bug', $map['nombre_bug']);
        $conn->bindValue(':nombre_vulnerability', $map['nombre_vulnerability']);
        $conn->bindValue(':nombre_code_smell', $map['nombre_code_smell']);
        $conn->bindValue(':bug_blocker', $map['bug_blocker']);
        $conn->bindValue(':bug_critical', $map['bug_critical']);
        $conn->bindValue(':bug_major', $map['bug_major']);
        $conn->bindValue(':bug_minor', $map['bug_minor']);
        $conn->bindValue(':bug_info', $map['bug_info']);
        $conn->bindValue(':vulnerability_blocker', $map['vulnerability_blocker']);
        $conn->bindValue(':vulnerability_critical', $map['vulnerability_critical']);
        $conn->bindValue(':vulnerability_major', $map['vulnerability_major']);
        $conn->bindValue(':vulnerability_minor', $map['vulnerability_minor']);
        $conn->bindValue(':vulnerability_info', $map['vulnerability_info']);
        $conn->bindValue(':code_smell_blocker', $map['code_smell_blocker']);
        $conn->bindValue(':code_smell_critical', $map['code_smell_critical']);
        $conn->bindValue(':code_smell_major', $map['code_smell_major']);
        $conn->bindValue(':code_smell_minor', $map['code_smell_minor']);
        $conn->bindValue(':code_smell_info', $map['code_smell_info']);
        $conn->bindValue(':frontend' , $map['frontend']);
        $conn->bindValue(':backend' , $map['backend']);
        $conn->bindValue(':autre' , $map['autre']);
        $conn->bindValue(':nombre_anomalie_bloquant' , $map['nombre_anomalie_bloquant']);
        $conn->bindValue(':nombre_anomalie_critique' , $map['nombre_anomalie_critique']);
        $conn->bindValue(':nombre_anomalie_majeur' , $map['nombre_anomalie_majeur']);
        $conn->bindValue(':nombre_anomalie_mineur' , $map['nombre_anomalie_mineu']);
        $conn->bindValue(':nombre_anomalie_info' , $map['nombre_anomalie_info']);
        $conn->bindValue(':note_reliability', $map['note_reliability']);
        $conn->bindValue(':note_security', $map['note_security']);
        $conn->bindValue(':note_sqale', $map['note_sqale']);
        $conn->bindValue(':note_hotspot', $map['note_hotspot']);
        $conn->bindValue(':hotspot_total', $map['hotspot_total']);
        $conn->bindValue(':hotspot_high' , $map['hotspot_high']);
        $conn->bindValue(':hotspot_medium' , $map['hotspot_medium']);
        $conn->bindValue(':hotspot_low' , $map['hotspot_low']);
        $conn->bindValue(':initial', $map['initial']);
        $conn->bindValue(':date_enregistrement', $map['date_enregistrement']);
        try {
            if ($mode !== 'TEST') {
                $conn->executeQuery();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        $response=['mode'=>$mode, 'code'=>200, 'erreur'=>''];
        return $response;
    }

    /**
     * [Description for selectHistoriqueProjetByDate]
     * Retourne ma liste des projet par date décroissant
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/02/2024 19:08:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueProjetByDate($mode, $map):array {

        /** On prépare la requête */
        $sql = "SELECT maven_key, version, date_version as date, initial
                FROM historique
                WHERE maven_key=:maven_key
                ORDER BY date_version DESC";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            if ($mode !== 'TEST') {
                $version=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        return ['mode'=>$mode, 'code'=>200, 'version'=>$version, 'erreur'=>''];
    }

    /**
     * [Description for selectHistoriqueProjetLast]
     * On récupère les informations du projet le plus récent
     * (i.e ayant la date d'analyse la plus récente).
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 29/02/2024 18:15:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueProjetLast($mode, $map):array {

        /** On prépare la requête */
        $sql =  "SELECT version, nom_projet AS name, date_version,
                        note_reliability, note_security, note_hotspot,note_sqale,
                        bug_blocker, bug_critical, bug_major,
                        vulnerability_blocker, vulnerability_critical, vulnerability_major,
                        code_smell_blocker, code_smell_critical, code_smell_major,
                        hotspot_total
                FROM historique
                WHERE maven_key=:maven_key
                ORDER BY date_version DESC LIMIT 1";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            if ($mode !== 'TEST') {
                $infos=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        return ['mode'=>$mode, 'code'=>200, 'infos'=>$infos, 'erreur'=>''];
    }

    /**
     * [Description for selectHistoriqueProjetReference]
     * Remonte les informations du projet de référence.
     *
     * @param string $mode
     * @param string $map
     *
     * @return array
     *
     * Created at: 29/02/2024 18:49:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueProjetReference($mode, $map):array {

        /** On prépare la requête */
        $sql = "SELECT  version, date_version,
                        note_reliability, note_security, note_hotspot, note_sqale,
                        bug_blocker, bug_critical, bug_major,
                        vulnerability_blocker, vulnerability_critical, vulnerability_major,
                        code_smell_blocker, code_smell_critical, code_smell_major, hotspot_total
                FROM historique
                WHERE maven_key=:maven_key AND initial=1";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            if ($mode !== 'TEST') {
                $reference=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        /** on prépare la réponse */
        return ['mode'=>$mode, 'code'=>200, 'reference'=>$reference, 'erreur'=>''];
    }

}
