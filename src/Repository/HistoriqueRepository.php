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

use App\Entity\Historique;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * [Description HistoriqueRepository]
 */
class HistoriqueRepository extends ServiceEntityRepository
{
    public static $removeReturnLine = "/\s+/u";
    public static $mavenKey = ':maven_key';
    public static $version = ':version';
    public static $dateVersion = ':date_version';
    public static $initialTrue = ':initial_true';
    public static $initialFalse = ':initial_false';
    public static $limit = ':limit';
    public static $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Historique::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array
     *
     * Created at: 21/12/2024 19:59:31 (Europe/Paris)
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
     * [Description for countHistoriqueProjet]
     * On veut savoir si le projet a été historisé ?
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 16/02/2024 12:08:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHistoriqueProjet($map): array
    {
        $sql = "SELECT count(*) AS nombre
                FROM ma_moulinette.historique
                WHERE maven_key=:maven_key";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$mavenKey, $map['maven_key']);
            $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'nombre' => $request[0]['nombre'], 'erreur' => ''];
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
                    version, date_version as date, note_reliability as reliability,
                    note_security as security, note_hotspot as hotspot,
                    note_sqale as sqale, nombre_bug as bug,
                    nombre_vulnerability as vulnerability,
                    nombre_code_smell as code_smell, nombre_hotspot as hotspots
                FROM ma_moulinette.historique
                WHERE ". $where ." ORDER BY date DESC limit 4";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'version' => $request, 'erreur' => ''];
    }

    /**
     * [Description for updateHistoriqueReference]
     *  Met à jour la version de référence pour un projet
     * @param array $map
     *
     * @return array
     *
     * Created at: 16/02/2024 10:57:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateHistoriqueReference($map): array
    {
        $sql = "UPDATE ma_moulinette.historique
                SET initial=false
                WHERE maven_key=:maven_key";

        /** on prépare la réponse */
        $response=['code' => 200, 'erreur' => ''];
        try {
            /** On désactive toutes les versions */
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }

        $sql2 = "UPDATE ma_moulinette.historique
                SET initial=:initial
                WHERE maven_key=:maven_key
                AND version=:version
                AND date_version=:date_version";

        /** On met à jour la version de reference pour le projet */
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql2));
                $stmt->bindValue(':initial', $map['initial']);
                $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                $stmt->bindValue(static::$version, $map['version']);
                $stmt->bindValue(static::$dateVersion, $map['date_version']);
                $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return $response;
    }

    /**
     * [Description for deleteHistoriqueProjet]
     * Suppression de la table historique du projet
     * @param array $map
     *
     * @return array
     *
     * Created at: 14/02/2024 10:29:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteHistoriqueProjet($map): array
    {
        /** On prépare la requête */
        $sql = "DELETE FROM ma_moulinette.historique
                WHERE maven_key=:maven_key
                AND version=:version
                AND date_version=:date_version";

        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                $stmt->bindValue(static::$version, $map['version']);
                $stmt->bindValue(static::$dateVersion, $map['date_version']);
                $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'erreur' => ''];
    }

    /**
     * [Description for selectUnionHistoriqueProjet]
     * Remonte les projets en historique
     * @param array $map
     *
     * @return array
     *
     * Created at: 25/02/2024 10:13:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueProjet($map): array
    {
        /** On prépare la requête */
        // -- Sélection de la version initiale (la plus ancienne)
        // -- Sélection des 10 dernières versions (triées par date décroissante)
        // -- Tri final : version initiale en premier, puis les autres par date croissante
        $sql="SELECT *
            FROM (
                (
                    SELECT
                        nom_projet AS nom,
                        date_version AS date,
                        version,
                        suppress_warning,
                        no_sonar,
                        nombre_bug AS bug,
                        nombre_vulnerability AS faille,
                        nombre_code_smell AS mauvaise_pratique,
                        nombre_hotspot,
                        frontend AS presentation,
                        backend AS metier,
                        autre,
                        inconnue,
                        note_reliability AS reliability,
                        note_security AS security,
                        note_hotspot,
                        note_sqale AS maintainability,
                        initial
                    FROM ma_moulinette.historique
                    WHERE maven_key = :maven_key AND initial = :initial_true
                ) UNION ALL (
                    SELECT
                        nom_projet AS nom,
                        date_version AS date,
                        version,
                        suppress_warning,
                        no_sonar,
                        nombre_bug AS bug,
                        nombre_vulnerability AS faille,
                        nombre_code_smell AS mauvaise_pratique,
                        nombre_hotspot,
                        frontend AS presentation,
                        backend AS metier,
                        autre,
                        inconnue,
                        note_reliability AS reliability,
                        note_security AS security,
                        note_hotspot,
                        note_sqale AS maintainability,
                        initial
                    FROM ma_moulinette.historique
                    WHERE maven_key = :maven_key AND initial = :initial_false
                    ORDER BY date_version DESC
                    LIMIT :limit)) AS versions
                ORDER BY date ASC";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                    $stmt->bindValue(static::$initialTrue, 1);
                    $stmt->bindValue(static::$initialFalse, 0);
                    $stmt->bindValue(static::$limit, $map['limit']);
                $suivi = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'request' => $suivi, 'erreur' => ''];
    }

    /**
     * [Description for selectUnionHistoriqueMesure]
     * On remonte les mesures pour les projets du suivi
     *
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 20/01/2025 11:28:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueMesure($map): array
    {
        /** On prépare la requête */
        $sql = "SELECT *
                FROM (
                        (SELECT date_version AS date,
                                nombre_ligne,
                                nombre_ligne_code,
                                files,
                                classes,
                                functions
                        FROM ma_moulinette.historique
                        WHERE maven_key = :maven_key AND initial = :initial_true)
                        UNION ALL
                        (SELECT date_version AS date,
                                nombre_ligne,
                                nombre_ligne_code,
                                files,
                                classes,
                                functions
                        FROM ma_moulinette.historique
                        WHERE maven_key = :maven_key AND initial = :initial_false
                        ORDER BY date_version DESC
                        LIMIT :limit)) AS versions
                ORDER BY date ASC";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                $stmt->bindValue(static::$initialTrue, 1);
                $stmt->bindValue(static::$initialFalse, 0);
                $stmt->bindValue(static::$limit, $map['limit']);
            $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'request' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for selectUnionHistoriqueAnomalie]
     * On remonte les anomalies des projets favoris
     * @param array $map
     *
     * @return array
     *
     * Created at: 25/02/2024 12:38:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueAnomalie($map): array
    {
        /** On prépare la requête */
        $sql = "SELECT *
                FROM (
                    (SELECT date_version AS date,
                            nombre_anomalie_bloquant AS bloquant,
                            nombre_anomalie_critique AS critique,
                            nombre_anomalie_majeur AS majeur,
                            nombre_anomalie_mineur AS mineur
                    FROM ma_moulinette.historique
                    WHERE maven_key = :maven_key AND initial = :initial_true)
                    UNION ALL
                    (SELECT date_version AS date,
                        nombre_anomalie_bloquant AS bloquant,
                        nombre_anomalie_critique AS critique,
                        nombre_anomalie_majeur AS majeur,
                        nombre_anomalie_mineur AS mineur
                    FROM ma_moulinette.historique
                    WHERE maven_key = :maven_key AND initial = :initial_false
                    ORDER BY date_version DESC
                    LIMIT :limit)) AS versions
                ORDER BY date ASC";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                $stmt->bindValue(static::$initialTrue, 1);
                $stmt->bindValue(static::$initialFalse, 0);
                $stmt->bindValue(static::$limit, $map['limit']);
            $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'request' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for selectUnionHistoriqueDetails]
     * remonte les anomalies par type des favoris
     * @param array $map
     *
     * @return array
     *
     * Created at: 25/02/2024 12:48:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueDetails($map): array
    {
        /** On prépare la requête */
        $sql = "SELECT *
                FROM (
                    (SELECT date_version AS date, version,
                            bug_blocker, bug_critical, bug_major,
                            bug_minor, bug_info,
                            vulnerability_blocker, vulnerability_critical,
                            vulnerability_major, vulnerability_minor,
                            vulnerability_info,
                            code_smell_blocker, code_smell_critical,
                            code_smell_major, code_smell_minor,
                            code_smell_info, initial
                    FROM ma_moulinette.historique
                    WHERE maven_key = :maven_key AND initial = :initial_true)
                    UNION ALL
                        (SELECT date_version AS date, version,
                            bug_blocker, bug_critical, bug_major,
                            bug_minor, bug_info,
                            vulnerability_blocker, vulnerability_critical,
                            vulnerability_major, vulnerability_minor,
                            vulnerability_info,
                            code_smell_blocker, code_smell_critical,
                            code_smell_major, code_smell_minor,
                            code_smell_info, initial
                        FROM ma_moulinette.historique
                        WHERE maven_key = :maven_key AND initial = :initial_false
                        ORDER BY date_version DESC
                        LIMIT :limit)) AS versions
                    ORDER BY date ASC";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                    $stmt->bindValue(static::$initialTrue, 1);
                    $stmt->bindValue(static::$initialFalse, 0);
                    $stmt->bindValue(static::$limit, $map['limit']);
                $details = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'request' => $details, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueAnomalieGraphique]
     * On remonte les données pour construire le graphique.
     * @param array $map
     *
     * @return array
     *
     * Created at: 25/02/2024 17:31:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueAnomalieGraphique($map): array
    {
        /** On prépare la requête */
        $sql = "SELECT nombre_bug AS bug,
                        nombre_vulnerability AS sec,
                        nombre_code_smell AS code_smell,
                        date_version AS date
                FROM ma_moulinette.historique
                WHERE maven_key = :maven_key
                GROUP BY nombre_bug, nombre_vulnerability, nombre_code_smell, date_version
                ORDER BY date ASC";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                $graph = $stmt->executeQuery()->fetchAllAssociative();

            // Conversion des dates en format ISO 8601 (DateTime::ATOM)
            array_walk($graph, function (&$row) {
                $row['date'] = (new \DateTime($row['date']))->format(\DateTime::ATOM);
            });
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'request' => $graph, 'erreur' => ''];
    }

    /**
     * [Description for insertHistoriqueAjoutProjet]
     * On ajoute une version à l'historique à partir des données SonarQube historisées.
     * @param array $map
     *
     * @return array
     *
     * Created at: 25/02/2024 17:34:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertHistoriqueAjoutProjet($map,$json): array
    {
                    /** On prépare la requête */
                    $sql = "INSERT INTO ma_moulinette.historique
                    (maven_key, analyse_key, version, date_version,
                    nom_projet, version_release, version_snapshot, version_autre,
                    suppress_warning, no_sonar, todo,
                    logger_info, logger_warn, logger_error, logger_debug,
                    nombre_ligne, nombre_ligne_code,
                    files, classes, functions,
                    coverage, duplicated_lines_density, sqale_debt_ratio, tests, violations, dette,
                    nombre_bug, nombre_vulnerability, nombre_code_smell,
                    bug_blocker, bug_critical, bug_major, bug_minor, bug_info,
                    vulnerability_blocker, vulnerability_critical, vulnerability_major,
                    vulnerability_minor, vulnerability_info,
                    code_smell_blocker, code_smell_critical, code_smell_major,
                    code_smell_minor, code_smell_info,
                    frontend, backend, autre, inconnue,
                    nombre_anomalie_bloquant, nombre_anomalie_critique, nombre_anomalie_majeur,
                    nombre_anomalie_mineur, nombre_anomalie_info,
                    note_reliability, note_security, note_sqale, note_hotspot, nombre_hotspot,
                    hotspot_high, hotspot_medium, hotspot_low,
                    initial,
                    mode_collecte, utilisateur_collecte,
                    actuator_info,
                    date_enregistrement)
                VALUES (
                    :maven_key, :analyse_key, :version, :date_version, :nom_projet,
                    :version_release, :version_snapshot, :version_autre,
                    :suppress_warning, :no_sonar, :todo,
                    :logger_info, :logger_warn, :logger_error, :logger_debug,
                    :nombre_ligne, :nombre_ligne_code,
                    :files, :classes, :functions,
                    :coverage, :duplicated_lines_density, :sqale_debt_ratio, :tests,
                    :violations, :dette, :nombre_bug, :nombre_vulnerability,
                    :nombre_code_smell, :bug_blocker, :bug_critical, :bug_major,
                    :bug_minor, :bug_info, :vulnerability_blocker, :vulnerability_critical,
                    :vulnerability_major, :vulnerability_minor, :vulnerability_info,
                    :code_smell_blocker, :code_smell_critical, :code_smell_major,
                    :code_smell_minor, :code_smell_info, :frontend, :backend, :autre, :inconnue,
                    :nombre_anomalie_bloquant, :nombre_anomalie_critique,
                    :nombre_anomalie_majeur, :nombre_anomalie_mineur,
                    :nombre_anomalie_info, :note_reliability, :note_security,
                    :note_sqale, :note_hotspot, :nombre_hotspot,
                    :hotspot_high, :hotspot_medium, :hotspot_low,
                    :initial,
                    :mode_collecte, :utilisateur_collecte, '".json_encode($json)."',
                    :date_enregistrement)";

        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                        $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                        $stmt->bindValue(':analyse_key', $map['analyse_key']);
                        $stmt->bindValue(static::$version, $map['version']);
                        $stmt->bindValue(static::$dateVersion, $map['date_version']);
                        $stmt->bindValue(':nom_projet', $map['nom_projet']);
                        $stmt->bindValue(':version_release', $map['version_release']);
                        $stmt->bindValue(':version_snapshot', $map['version_snapshot']);
                        $stmt->bindValue(':version_autre', $map['version_autre']);
                        $stmt->bindValue(':suppress_warning', $map['suppress_warning']);
                        $stmt->bindValue(':no_sonar', $map['no_sonar']);
                        $stmt->bindValue(':todo', $map['todo']);
                        $stmt->bindValue(':logger_info', $map['logger_info']);
                        $stmt->bindValue(':logger_warn', $map['logger_warn']);
                        $stmt->bindValue(':logger_error', $map['logger_error']);
                        $stmt->bindValue(':logger_debug', $map['logger_debug']);
                        $stmt->bindValue(':nombre_ligne', $map['nombre_ligne']);
                        $stmt->bindValue(':nombre_ligne_code', $map['nombre_ligne_code']);
                        $stmt->bindValue(':files', $map['files']);
                        $stmt->bindValue(':classes', $map['classes']);
                        $stmt->bindValue(':functions', $map['functions']);
                        $stmt->bindValue(':coverage', $map['coverage']);
                        $stmt->bindValue(':duplicated_lines_density', $map['duplicated_lines_density']);
                        $stmt->bindValue(':sqale_debt_ratio', $map['sqale_debt_ratio']);
                        $stmt->bindValue(':tests', $map['tests']);
                        $stmt->bindValue(':violations', $map['violations']);
                        $stmt->bindValue(':dette', $map['dette']);
                        $stmt->bindValue(':nombre_bug', $map['nombre_bug']);
                        $stmt->bindValue(':nombre_vulnerability', $map['nombre_vulnerability']);
                        $stmt->bindValue(':nombre_code_smell', $map['nombre_code_smell']);
                        $stmt->bindValue(':bug_blocker', $map['bug_blocker']);
                        $stmt->bindValue(':bug_critical', $map['bug_critical']);
                        $stmt->bindValue(':bug_major', $map['bug_major']);
                        $stmt->bindValue(':bug_minor', $map['bug_minor']);
                        $stmt->bindValue(':bug_info', $map['bug_info']);
                        $stmt->bindValue(':vulnerability_blocker', $map['vulnerability_blocker']);
                        $stmt->bindValue(':vulnerability_critical', $map['vulnerability_critical']);
                        $stmt->bindValue(':vulnerability_major', $map['vulnerability_major']);
                        $stmt->bindValue(':vulnerability_minor', $map['vulnerability_minor']);
                        $stmt->bindValue(':vulnerability_info', $map['vulnerability_info']);
                        $stmt->bindValue(':code_smell_blocker', $map['code_smell_blocker']);
                        $stmt->bindValue(':code_smell_critical', $map['code_smell_critical']);
                        $stmt->bindValue(':code_smell_major', $map['code_smell_major']);
                        $stmt->bindValue(':code_smell_minor', $map['code_smell_minor']);
                        $stmt->bindValue(':code_smell_info', $map['code_smell_info']);
                        $stmt->bindValue(':frontend', $map['frontend']);
                        $stmt->bindValue(':backend', $map['backend']);
                        $stmt->bindValue(':autre', $map['autre']);
                        $stmt->bindValue(':inconnue', $map['inconnue']);
                        $stmt->bindValue(':nombre_anomalie_bloquant', $map['nombre_anomalie_bloquant']);
                        $stmt->bindValue(':nombre_anomalie_critique', $map['nombre_anomalie_critique']);
                        $stmt->bindValue(':nombre_anomalie_majeur', $map['nombre_anomalie_majeur']);
                        $stmt->bindValue(':nombre_anomalie_mineur', $map['nombre_anomalie_mineur']);
                        $stmt->bindValue(':nombre_anomalie_info', $map['nombre_anomalie_info']);
                        $stmt->bindValue(':note_reliability', $map['note_reliability']);
                        $stmt->bindValue(':note_security', $map['note_security']);
                        $stmt->bindValue(':note_sqale', $map['note_sqale']);
                        $stmt->bindValue(':note_hotspot', $map['note_hotspot']);
                        $stmt->bindValue(':nombre_hotspot', $map['nombre_hotspot']);
                        $stmt->bindValue(':hotspot_high', $map['hotspot_high']);
                        $stmt->bindValue(':hotspot_medium', $map['hotspot_medium']);
                        $stmt->bindValue(':hotspot_low', $map['hotspot_low']);
                        /** On ne peut pas binder la valeur d'un boolean en true/false
                         * car le type est toujours string/number ou dateTime */
                        $stmt->bindValue(':initial', 'false');
                        $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
                        $stmt->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);
                        $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));
                        $stmt->executeStatement();
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'erreur' => ''];
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
    public function selectHistoriqueProjetByDate($map): array
    {
        /** On prépare la requête */
        $sql = "SELECT maven_key, version, date_version as date, initial
                FROM ma_moulinette.historique
                WHERE maven_key=:maven_key
                ORDER BY date_version DESC";

        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                    $version = $stmt->executeQuery()->fetchAllAssociative();
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }

        /** on prépare la réponse */
        return ['code' => 200, 'version' => $version, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueProjetLast]
     * On récupère les informations du projet le plus récent
     * (i.e ayant la date d'analyse la plus récente).
     * @param array $map
     *
     * @return array
     *
     * Created at: 29/02/2024 18:15:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueProjetLast($map): array
    {
        /** On prépare la requête */
        $sql =  "SELECT version, nom_projet AS name, date_version,
                        note_reliability, note_security, note_hotspot,note_sqale,
                        bug_blocker, bug_critical, bug_major,
                        vulnerability_blocker, vulnerability_critical, vulnerability_major,
                        code_smell_blocker, code_smell_critical, code_smell_major,
                        nombre_hotspot, coverage, sqale_debt_ratio
                FROM ma_moulinette.historique
                WHERE maven_key=:maven_key
                ORDER BY date_version DESC LIMIT 1";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                $infos = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
            /** on prépare la réponse */
            return ['code' => 200, 'infos' => $infos, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueProjetReference]
     * Remonte les informations du projet de référence.
     * @param string $map
     *
     * @return array
     *
     * Created at: 29/02/2024 18:49:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueProjetReference($map): array
    {
        /** On prépare la requête */
        $sql = "SELECT version, date_version,
                        note_reliability, note_security, note_hotspot, note_sqale,
                        bug_blocker, bug_critical, bug_major,
                        vulnerability_blocker, vulnerability_critical, vulnerability_major,
                        code_smell_blocker, code_smell_critical, code_smell_major,
                        nombre_hotspot, coverage, sqale_debt_ratio
                FROM ma_moulinette.historique
                WHERE maven_key=:maven_key AND initial=true";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'reference' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueProjetFavori]
     * retourne la liste des données pour la dernière version des projets favoris .
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/03/2024 19:07:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueProjetFavori($map): array
    {
        /** On prépare la requête */
        $sql = "WITH LastVersions AS (
                SELECT  maven_key AS mavenkey, nom_projet AS nom,
                        version, date_version AS date,
                        note_reliability AS reliability,
                        note_security AS security, note_hotspot AS hotspot,
                        note_sqale AS sqale, nombre_bug AS bug,
                        nombre_vulnerability AS vulnerability,
                        nombre_code_smell AS code_smell,
                        nombre_hotspot AS hotspots,
                ROW_NUMBER() OVER (PARTITION BY maven_key ORDER BY date_version DESC) AS rn
                FROM ma_moulinette.historique
                WHERE maven_key IN (".$map['liste_projet']."))
                SELECT  mavenkey, nom, version, date, reliability, security, hotspot, sqale, bug,
                        vulnerability, code_smell, hotspots
                FROM LastVersions
                WHERE rn = 1
                LIMIT ".$map['nombre_projet_favori'];
        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueIsValide]
     *
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 18/06/2024 21:12:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueIsValide($map): array
    {
        /** On prépare la requête */
        $sql = "SELECT version, nom_projet AS name,
                        date_version, analyse_key
                FROM ma_moulinette.historique
                WHERE maven_key=:maven_key
                ORDER BY date_version DESC LIMIT 1";
        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                $isValide = $stmt->executeQuery()->fetchAllAssociative();
                    /** j'ai pas trouvé de projet */
                if (!$isValide){
                    return ['code' => 404, 'erreur' => "Je n'ai pas trouvé le projet dans la base de données."];
                }
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'is_valide' => $isValide[0], 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueIndicateurs]
     *
     * @param string $map
     *
     * @return array
     *
     * Created at: 24/09/2024 16:47:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueIndicateurs(string $map): array
    {
        /** On prépare la requête */
        $sql = "SELECT DISTINCT ON (maven_key)
                    nom_projet, version, suppress_warning, no_sonar, todo, nombre_ligne, nombre_ligne_code, tests, violations, nombre_bug, nombre_vulnerability, nombre_code_smell, frontend, backend, autre, inconnue, note_reliability, note_security, note_sqale, note_hotspot, logger_info, logger_warn, logger_error, logger_debug
                FROM ma_moulinette.historique
                WHERE maven_key IN (".$map.") ORDER BY maven_key ASC, version DESC, date_version DESC";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $indicateur = $stmt->executeQuery()->fetchAllAssociative();
                /** j'ai pas trouvé de projet */
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'indicateur' => $indicateur, 'erreur' => ''];
    }

}
