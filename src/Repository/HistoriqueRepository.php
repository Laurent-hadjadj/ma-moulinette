<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Repository;

use App\Entity\Historique;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;

/**
 * @extends ServiceEntityRepository<Historique>
 */
class HistoriqueRepository extends ServiceEntityRepository
{
    private static string $removeReturnLine = "/\s+/u";
    private static string $mavenKey = ':maven_key';
    private static string $version = ':version';
    private static string $dateVersion = ':date_version';
    private static string $initialTrue = ':initial_true';
    private static string $initialFalse = ':initial_false';
    private static string $limit = ':limit';
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Historique::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array<int|string, mixed>
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
     * [Description for countHistoriqueProjet]
     * On veut savoir si le projet a été historisé ?
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 16/02/2024 12:08:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHistoriqueProjet(array $map): array
    {
        $sql = "SELECT count(*) AS nombre
                FROM ma_moulinette.historique
                WHERE maven_key=:maven_key";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'nombre' => $request[0]['nombre'], 'erreur' => ''];
    }

    /**
     * [Description for getProjetFavori]
     * Récupère les indicateurs du projet favori pour un maven_key et une liste de versions.
     *
     * @param string $mavenKey
     * @param array<int, string> $versions
     *
     * @return array<int|string, mixed>
     *
     * Created at: 27/10/2023 15:37:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProjetFavori(string $mavenKey, array $versions): array
    {
        if ($versions === []) {
            return ['code' => 200, 'version' => [], 'erreur' => ''];
        }

        /* MODIF 2026-05-07 : alignement noms de colonnes apres rename
         * du 2026-03-30 (project_name, *_rating, bugs/vulnerabilities/code_smells au pluriel,
         * note_hotspot -> security_review_rating). Alias conservés pour compat appelants.
         */
        $sql = "SELECT DISTINCT
                    maven_key as mavenkey, project_name as nom,
                    version, date_version as date, reliability_rating as reliability,
                    security_rating as security, security_review_rating as hotspot,
                    sqale_rating as sqale, bugs as bug,
                    vulnerabilities as vulnerability,
                    code_smells as code_smell, menace_potentielle_totale as hotspots
                FROM ma_moulinette.historique
                WHERE maven_key = :maven_key AND version IN (:versions)
                ORDER BY date DESC LIMIT 4";

        try {
            $request = $this->getEntityManager()->getConnection()->executeQuery(
                preg_replace(self::$removeReturnLine, " ", $sql),
                ['maven_key' => $mavenKey, 'versions' => $versions],
                ['maven_key' => ParameterType::STRING, 'versions' => ArrayParameterType::STRING],
            )->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'version' => $request, 'erreur' => ''];
    }

    /**
     * [Description for updateHistoriqueReference]
     *  Met à jour la version de référence pour un projet
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 16/02/2024 10:57:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateHistoriqueReference(array $map): array
    {
        $sql = "UPDATE ma_moulinette.historique
                SET initial = false
                WHERE maven_key = :maven_key";

        /** on prépare la réponse */
        $response = ['code' => 200, 'erreur' => ''];
        try {
            /** On désactive toutes les versions */
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(self::$mavenKey, $map['maven_key']);
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
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql2));
                $stmt->bindValue(':initial', $map['initial']);
                $stmt->bindValue(self::$mavenKey, $map['maven_key']);
                $stmt->bindValue(self::$version, $map['version']);
                $stmt->bindValue(self::$dateVersion, $map['date_version']);
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
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 14/02/2024 10:29:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteHistoriqueProjet(array $map): array
    {
        /** On prépare la requête */
        $sql = "DELETE FROM ma_moulinette.historique
                WHERE maven_key=:maven_key
                AND version=:version
                AND date_version=:date_version";

        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(self::$mavenKey, $map['maven_key']);
                $stmt->bindValue(self::$version, $map['version']);
                $stmt->bindValue(self::$dateVersion, $map['date_version']);
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
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/02/2024 10:13:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueProjet(array $map): array
    {
        /** On prépare la requête */
        // -- Sélection de la version initiale (la plus ancienne)
        // -- Sélection des 10 dernières versions (triées par date décroissante)
        // -- Tri final : version initiale en premier, puis les autres par date croissante
        /* MODIF 2026-05-06 : ajout de 7 colonnes pour
         * alimenter le tableau de synthese des notes A-E (T7 - page de garde rapport PDF) :
         * alert_status (Quality Gate), software_quality_*_rating x3 (Sonar 10),
         * comment_lines_rating, complexity_rating, cognitive_complexity_rating. */
        $sql = "SELECT *
            FROM (
                (
                    SELECT
                        project_name AS nom,
                        date_version AS date,
                        version,
                        suppress_warning,
                        (java_no_sonar + python_no_sonar + php_no_sonar) AS no_sonar,
                        bugs AS bug,
                        vulnerabilities AS faille,
                        code_smells AS mauvaise_pratique,
                        menace_potentielle_totale,
                        repartition_frontend AS presentation,
                        repartition_backend AS metier,
                        repartition_autre AS autre,
                        repartition_inconnu AS inconnu,
                        reliability_rating AS reliability,
                        security_rating AS security,
                        security_review_rating AS note_hotspot,
                        sqale_rating AS maintainability,
                        alert_status,
                        software_quality_reliability_rating AS s10_reliability,
                        software_quality_security_rating AS s10_security,
                        software_quality_maintainability_rating AS s10_maintainability,
                        comment_lines_rating AS note_comment,
                        complexity_rating AS note_complexity,
                        cognitive_complexity_rating AS note_cognitive,
                        coverage, branch_coverage, line_coverage,
                        lines_to_cover, conditions_to_cover, uncovered_conditions,
                        tests, test_execution_time, test_errors, test_failures,
                        skipped_tests, test_success_density,
                        complexity, cognitive_complexity,
                        complexity_ratio, cognitive_complexity_ratio,
                        functions,
                        ncloc, ncloc_language_distribution,
                        mode_collecte,
                        initial
                    FROM ma_moulinette.historique
                    WHERE maven_key = :maven_key AND initial = :initial_true
                ) UNION ALL (
                    SELECT
                        project_name AS nom,
                        date_version AS date,
                        version,
                        suppress_warning,
                        (java_no_sonar + python_no_sonar + php_no_sonar) AS no_sonar,
                        bugs AS bug,
                        vulnerabilities AS faille,
                        code_smells AS mauvaise_pratique,
                        menace_potentielle_totale,
                        repartition_frontend AS presentation,
                        repartition_backend AS metier,
                        repartition_autre AS autre,
                        repartition_inconnu AS inconnu,
                        reliability_rating AS reliability,
                        security_rating AS security,
                        security_review_rating AS note_hotspot,
                        sqale_rating AS maintainability,
                        alert_status,
                        software_quality_reliability_rating AS s10_reliability,
                        software_quality_security_rating AS s10_security,
                        software_quality_maintainability_rating AS s10_maintainability,
                        comment_lines_rating AS note_comment,
                        complexity_rating AS note_complexity,
                        cognitive_complexity_rating AS note_cognitive,
                        coverage, branch_coverage, line_coverage,
                        lines_to_cover, conditions_to_cover, uncovered_conditions,
                        tests, test_execution_time, test_errors, test_failures,
                        skipped_tests, test_success_density,
                        complexity, cognitive_complexity,
                        complexity_ratio, cognitive_complexity_ratio,
                        functions,
                        ncloc, ncloc_language_distribution,
                        mode_collecte,
                        initial
                    FROM ma_moulinette.historique
                    WHERE maven_key = :maven_key AND initial = :initial_false
                    ORDER BY date_version DESC
                    LIMIT :limit)) AS versions
                ORDER BY date ASC";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(self::$mavenKey, $map['maven_key']);
                    $stmt->bindValue(self::$initialTrue, 1);
                    $stmt->bindValue(self::$initialFalse, 0);
                    $stmt->bindValue(self::$limit, $map['limit']);
                $suivi = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'request' => $suivi, 'erreur' => ''];
    }

    /* MODIF 2026-05-06 :
     * Variante de selectUnionHistoriqueProjet pour le rapport PDF Suivi.
     * Limite aux N dernieres versions (LIMIT :limit, defaut 10 cote appelant)
     * et trie ASC sur date pour l'affichage. Pas d'UNION avec la version `initial=1` :
     * elle n'a pas sa place dans un rapport "N dernieres".
     *
     * NB : on a essaye un filtre `version_release > 0` pour ne garder que les
     * releases — abandonne car version_release est un compteur cumule, pas un flag
     * par-version. Le rapport prend donc TOUTES les versions (release + snapshot)
     * et c'est l'utilisateur qui choisira ses versions a l'usage. */
    /**
     * [Description for selectHistoriqueProjetForRapport]
     * Remonte les N dernieres versions du projet pour le rapport PDF.
     * @param array<int|string, mixed> $map  attend `maven_key` et `limit`
     *
     * @return array<int|string, mixed>
     *
     * Created at: 06/05/2026
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueProjetForRapport(array $map): array
    {
        $sql = "SELECT * FROM (
                    SELECT
                        project_name AS nom,
                        date_version AS date,
                        version,
                        suppress_warning,
                        (java_no_sonar + python_no_sonar + php_no_sonar) AS no_sonar,
                        bugs AS bug,
                        vulnerabilities AS faille,
                        code_smells AS mauvaise_pratique,
                        menace_potentielle_totale,
                        repartition_frontend AS presentation,
                        repartition_backend AS metier,
                        repartition_autre AS autre,
                        repartition_inconnu AS inconnu,
                        reliability_rating AS reliability,
                        security_rating AS security,
                        security_review_rating AS note_hotspot,
                        sqale_rating AS maintainability,
                        alert_status,
                        software_quality_reliability_rating AS s10_reliability,
                        software_quality_security_rating AS s10_security,
                        software_quality_maintainability_rating AS s10_maintainability,
                        comment_lines_rating AS note_comment,
                        complexity_rating AS note_complexity,
                        cognitive_complexity_rating AS note_cognitive,
                        coverage, branch_coverage, line_coverage,
                        lines_to_cover, conditions_to_cover, uncovered_conditions,
                        tests, test_execution_time, test_errors, test_failures,
                        skipped_tests, test_success_density,
                        complexity, cognitive_complexity,
                        complexity_ratio, cognitive_complexity_ratio,
                        functions,
                        ncloc, ncloc_language_distribution,
                        mode_collecte,
                        initial
                    FROM ma_moulinette.historique
                    WHERE maven_key = :maven_key
                    ORDER BY date_version DESC
                    LIMIT :limit
                ) AS versions
                ORDER BY date ASC";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $stmt->bindValue(self::$limit, $map['limit'] ?? 10, ParameterType::INTEGER);
            $suivi = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'request' => $suivi, 'erreur' => ''];
    }

    /**
     * [Description for selectUnionHistoriqueMesure]
     * On remonte les mesures pour les projets du suivi
     *
     * @param array $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 20/01/2025 11:28:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueMesure(array $map): array
    {
        /** On prépare la requête */
        $sql = "SELECT *
                FROM (
                        (SELECT date_version AS date,
                                lines AS nombre_ligne,
                                ncloc AS nombre_ligne_code,
                                files AS nombre_files,
                                classes AS nombre_classes,
                                functions AS nombre_functions
                        FROM ma_moulinette.historique
                        WHERE maven_key = :maven_key AND initial = :initial_true)
                        UNION ALL
                        (SELECT date_version AS date,
                                lines AS nombre_ligne,
                                ncloc AS nombre_ligne_code,
                                files AS nombre_files,
                                classes AS nombre_classes,
                                functions AS nombre_functions
                        FROM ma_moulinette.historique
                        WHERE maven_key = :maven_key AND initial = :initial_false
                        ORDER BY date_version DESC
                        LIMIT :limit)) AS versions
                ORDER BY date ASC";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(self::$mavenKey, $map['maven_key']);
                $stmt->bindValue(self::$initialTrue, 1);
                $stmt->bindValue(self::$initialFalse, 0);
                $stmt->bindValue(self::$limit, $map['limit']);
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
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/02/2024 12:38:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueAnomalie(array $map): array
    {
        /** On prépare la requête */
        $sql = "SELECT *
                FROM (
                    (SELECT date_version AS date,
                            blocker_violations AS bloquant,
                            critical_violations AS critique,
                            major_violations AS majeur,
                            minor_violations AS mineur
                    FROM ma_moulinette.historique
                    WHERE maven_key = :maven_key AND initial = :initial_true)
                    UNION ALL
                    (SELECT date_version AS date,
                        blocker_violations AS bloquant,
                        critical_violations AS critique,
                        major_violations AS majeur,
                        minor_violations AS mineur
                    FROM ma_moulinette.historique
                    WHERE maven_key = :maven_key AND initial = :initial_false
                    ORDER BY date_version DESC
                    LIMIT :limit)) AS versions
                ORDER BY date ASC";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(self::$mavenKey, $map['maven_key']);
                $stmt->bindValue(self::$initialTrue, 1);
                $stmt->bindValue(self::$initialFalse, 0);
                $stmt->bindValue(self::$limit, $map['limit']);
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
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/02/2024 12:48:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUnionHistoriqueDetails(array $map): array
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
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(self::$mavenKey, $map['maven_key']);
                    $stmt->bindValue(self::$initialTrue, 1);
                    $stmt->bindValue(self::$initialFalse, 0);
                    $stmt->bindValue(self::$limit, $map['limit']);
                $details = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'request' => $details, 'erreur' => ''];
    }

    // MODIF 2026-06-11 variantes "par liste de versions"
    // pour bypasser le LIMIT quand l'utilisateur a choisi ses versions dans ses préférences.
    // Pattern : WHERE maven_key = :maven_key AND (initial = true OR version IN (:v0, :v1, ...))
    // La version de référence (initial=true) est toujours incluse.

    /**
     * [Description for selectHistoriqueProjetParVersions]
     * @param array<int|string, mixed> $map  [maven_key, versions: string[]]
     * @return array<int|string, mixed>
     */
    public function selectHistoriqueProjetParVersions(array $map): array
    {
        $versions = $map['versions'] ?? [];
        $placeholders = array_map(static fn(int $i) => ':v' . $i, array_keys($versions));
        $inClause = implode(', ', $placeholders);

        $sql = "SELECT
                    project_name AS nom, date_version AS date, version,
                    suppress_warning,
                    (java_no_sonar + python_no_sonar + php_no_sonar) AS no_sonar,
                    bugs AS bug, vulnerabilities AS faille, code_smells AS mauvaise_pratique,
                    menace_potentielle_totale,
                    repartition_frontend AS presentation, repartition_backend AS metier,
                    repartition_autre AS autre, repartition_inconnu AS inconnu,
                    reliability_rating AS reliability, security_rating AS security,
                    security_review_rating AS note_hotspot, sqale_rating AS maintainability,
                    alert_status,
                    software_quality_reliability_rating AS s10_reliability,
                    software_quality_security_rating AS s10_security,
                    software_quality_maintainability_rating AS s10_maintainability,
                    comment_lines_rating AS note_comment, complexity_rating AS note_complexity,
                    cognitive_complexity_rating AS note_cognitive,
                    coverage, branch_coverage, line_coverage,
                    lines_to_cover, conditions_to_cover, uncovered_conditions,
                    tests, test_execution_time, test_errors, test_failures,
                    skipped_tests, test_success_density,
                    complexity, cognitive_complexity, complexity_ratio, cognitive_complexity_ratio,
                    functions, ncloc, ncloc_language_distribution, mode_collecte, initial
                FROM ma_moulinette.historique
                WHERE maven_key = :maven_key AND (initial = true" .
                ($inClause !== '' ? " OR version IN ($inClause)" : '') . ")
                ORDER BY date_version ASC";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            foreach ($versions as $i => $v) {
                $stmt->bindValue(':v' . $i, $v);
            }
            $suivi = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'request' => $suivi, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriquesMesureParVersions]
     * @param array<int|string, mixed> $map  [maven_key, versions: string[]]
     * @return array<int|string, mixed>
     */
    public function selectHistoriquesMesureParVersions(array $map): array
    {
        $versions = $map['versions'] ?? [];
        $placeholders = array_map(static fn(int $i) => ':v' . $i, array_keys($versions));
        $inClause = implode(', ', $placeholders);

        $sql = "SELECT date_version AS date,
                    lines AS nombre_ligne, ncloc AS nombre_ligne_code,
                    files AS nombre_files, classes AS nombre_classes,
                    functions AS nombre_functions
                FROM ma_moulinette.historique
                WHERE maven_key = :maven_key AND (initial = true" .
                ($inClause !== '' ? " OR version IN ($inClause)" : '') . ")
                ORDER BY date_version ASC";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            foreach ($versions as $i => $v) {
                $stmt->bindValue(':v' . $i, $v);
            }
            $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'request' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueAnomalieParVersions]
     * @param array<int|string, mixed> $map  [maven_key, versions: string[]]
     * @return array<int|string, mixed>
     */
    public function selectHistoriqueAnomalieParVersions(array $map): array
    {
        $versions = $map['versions'] ?? [];
        $placeholders = array_map(static fn(int $i) => ':v' . $i, array_keys($versions));
        $inClause = implode(', ', $placeholders);

        $sql = "SELECT date_version AS date,
                    blocker_violations AS bloquant, critical_violations AS critique,
                    major_violations AS majeur, minor_violations AS mineur
                FROM ma_moulinette.historique
                WHERE maven_key = :maven_key AND (initial = true" .
                ($inClause !== '' ? " OR version IN ($inClause)" : '') . ")
                ORDER BY date_version ASC";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            foreach ($versions as $i => $v) {
                $stmt->bindValue(':v' . $i, $v);
            }
            $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'request' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueDetailsParVersions]
     * @param array<int|string, mixed> $map  [maven_key, versions: string[]]
     * @return array<int|string, mixed>
     */
    public function selectHistoriqueDetailsParVersions(array $map): array
    {
        $versions = $map['versions'] ?? [];
        $placeholders = array_map(static fn(int $i) => ':v' . $i, array_keys($versions));
        $inClause = implode(', ', $placeholders);

        $sql = "SELECT date_version AS date, version,
                    bug_blocker, bug_critical, bug_major, bug_minor, bug_info,
                    vulnerability_blocker, vulnerability_critical, vulnerability_major,
                    vulnerability_minor, vulnerability_info,
                    code_smell_blocker, code_smell_critical, code_smell_major,
                    code_smell_minor, code_smell_info, initial
                FROM ma_moulinette.historique
                WHERE maven_key = :maven_key AND (initial = true" .
                ($inClause !== '' ? " OR version IN ($inClause)" : '') . ")
                ORDER BY date_version ASC";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            foreach ($versions as $i => $v) {
                $stmt->bindValue(':v' . $i, $v);
            }
            $details = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'request' => $details, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueAnomalieGraphique]
     * On remonte les données pour construire le graphique.
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/02/2024 17:31:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueAnomalieGraphique(array $map): array
    {
        /** On prépare la requête */
        $sql = "SELECT bugs AS bug,
                        vulnerabilities AS sec,
                        code_smells AS code_smell,
                        date_version AS date
                FROM ma_moulinette.historique
                WHERE maven_key = :maven_key
                GROUP BY bugs, vulnerabilities, code_smells, date_version
                ORDER BY date ASC";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(self::$mavenKey, $map['maven_key']);
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
     * [Description for selectHistoriqueAnomalieGraphiqueParVersions]
     * MODIF 2026-07-16 : variante de selectHistoriqueAnomalieGraphique() qui respecte
     * la liste de suivi personnalisée (mêmes versions que les autres tableaux de la
     * page Suivi quand $modeVersionsChoisies est actif), au lieu de toujours remonter
     * tout l'historique sans filtre.
     *
     * @param array<int|string, mixed> $map  [maven_key, versions: string[]]
     *
     * @return array<int|string, mixed>
     *
     * Created at: 16/07/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueAnomalieGraphiqueParVersions(array $map): array
    {
        $versions = $map['versions'] ?? [];
        $placeholders = array_map(static fn(int $i) => ':v' . $i, array_keys($versions));
        $inClause = implode(', ', $placeholders);

        $sql = "SELECT bugs AS bug,
                        vulnerabilities AS sec,
                        code_smells AS code_smell,
                        date_version AS date
                FROM ma_moulinette.historique
                WHERE maven_key = :maven_key AND (initial = true" .
                ($inClause !== '' ? " OR version IN ($inClause)" : '') . ")
                GROUP BY bugs, vulnerabilities, code_smells, date_version
                ORDER BY date ASC";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(self::$mavenKey, $map['maven_key']);
                foreach ($versions as $i => $v) {
                    $stmt->bindValue(':v' . $i, $v);
                }
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
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/02/2024 17:34:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    /**
     * Whitelist exhaustive des colonnes de la table historique alignées sur les
     * clés SonarQube (= sortie BuildMapHistoryService::metricsRebuild). Toute
     * nouvelle métrique se câble en 1 ligne ici. `actuator_info` est exclu car
     * il est inséré comme littéral JSON encodé dans la SQL (pas un bind param).
     */
    private static array $historiqueColumns = [
        // Identité projet / version
        'maven_key', 'analyse_key', 'version', 'date_version', 'project_name',
        // Version typée par parser ma-moulinette
        'version_release', 'version_snapshot', 'version_autre',
        // Répartition langages (parser ma-moulinette)
        'repartition_frontend', 'repartition_backend', 'repartition_autre', 'repartition_inconnu',
        // No-sonar / suppress / pmd / checkstyle (parser ma-moulinette)
        'java_no_sonar', 'python_no_sonar', 'php_no_sonar',
        'suppress_warning', 'no_pmd', 'check_style',
        // MODIF 2026-05-06 : ajout web_to.do
        'java_todo', 'python_todo', 'php_todo', 'xml_todo', 'web_todo',
        'javascript_todo', 'typescript_todo', 'ruby_todo',
        // Quality gate
        'alert_status',
        // Size
        'lines', 'ncloc', 'files', 'classes', 'functions', 'statements',
        // MODIF 2026-05-06 :ncloc_language_distribution était pose dans $map cote ApiEnregistrement
        // (cf. serializeLanguageDistribution) mais absent du whitelist => INSERT
        // l'ignorait silencieusement => colonne NULL en base.
        'ncloc_language_distribution',
        // Comments
        'comment_lines', 'comment_lines_density', 'comment_lines_rating',
        // Coverage
        // MODIF 2026-05-15 : ajout `coverage_rating` (présent
        // dans mesures + peintureProjetMesures + table historique, mais oublié
        // dans la whitelist → colonne toujours NULL après INSERT).
        'coverage', 'branch_coverage', 'line_coverage', 'coverage_rating',
        'lines_to_cover', 'conditions_to_cover', 'uncovered_conditions',
        // Tests
        'tests', 'test_execution_time', 'test_errors', 'test_failures',
        'skipped_tests', 'test_success_density',
        // Duplication
        // MODIF 2026-05-15 : ajout `duplicated_lines_rating`.
        'duplicated_files', 'duplicated_blocks', 'duplicated_lines', 'duplicated_lines_density',
        'duplicated_lines_rating',
        // Complexity (cyclomatique + cognitive + ratios + ratings dérivés)
        'complexity', 'complexity_rating', 'cognitive_complexity', 'cognitive_complexity_rating',
        'complexity_ratio', 'cognitive_complexity_ratio',
        // Issues status (Core + LTA10)
        'open_issues', 'reopened_issues', 'confirmed_issues', 'false_positive_issues',
        'accepted_issues', 'high_impact_accepted_issues',
        // Violations (Core severity breakdown)
        'violations', 'blocker_violations', 'critical_violations',
        'major_violations', 'minor_violations', 'info_violations',
        // Software quality issues (LTA24/26 severity breakdown)
        'software_quality_blocker_issues', 'software_quality_high_issues',
        'software_quality_medium_issues', 'software_quality_low_issues',
        'software_quality_info_issues',
        // Code smells (Core + ma-moulinette severity legacy)
        'code_smells', 'code_smell_blocker', 'code_smell_critical',
        'code_smell_major', 'code_smell_minor', 'code_smell_info',
        // Maintainability (Core + LTA10 + LTA24/26)
        'maintainability_issues', 'sqale_index', 'sqale_debt_ratio', 'sqale_rating',
        'effort_to_reach_maintainability_rating_a',
        'software_quality_maintainability_issues', 'software_quality_maintainability_rating',
        'software_quality_maintainability_debt_ratio',
        'software_quality_maintainability_remediation_effort',
        'effort_to_reach_software_quality_maintainability_rating_a',
        // Reliability (Core + LTA10 + LTA24/26)
        'bugs', 'bug_blocker', 'bug_critical', 'bug_major', 'bug_minor', 'bug_info',
        'reliability_issues', 'reliability_rating', 'reliability_remediation_effort',
        'software_quality_reliability_issues', 'software_quality_reliability_rating',
        'software_quality_reliability_remediation_effort',
        // Security (Core + LTA10 + LTA24/26)
        'vulnerabilities', 'vulnerability_blocker', 'vulnerability_critical',
        'vulnerability_major', 'vulnerability_minor', 'vulnerability_info',
        'security_issues', 'security_rating', 'security_remediation_effort',
        'software_quality_security_issues', 'software_quality_security_rating',
        'software_quality_security_remediation_effort',
        // Security review
        'security_hotspots', 'security_review_rating', 'security_hotspots_reviewed',
        // Menace potentielle (custom ma-moulinette)
        'menace_potentielle_to_review_high', 'menace_potentielle_to_review_medium',
        'menace_potentielle_to_review_low',
        'menace_potentielle_reviewed_high', 'menace_potentielle_reviewed_medium',
        'menace_potentielle_reviewed_low',
        'menace_potentielle_totale',
        // Logger (parser ma-moulinette)
        'logger_info', 'logger_warn', 'logger_error', 'logger_debug',
        // MODIF 2026-05-17 : indicateurs SonarQube 10+
        'cc_consistent', 'cc_intentional', 'cc_adaptable', 'cc_responsible',
        'quality_maintainability', 'quality_reliability', 'quality_security',
        'impact_blocker', 'impact_high', 'impact_medium', 'impact_low', 'impact_info',
        'owasp_top10', 'sans_top25', 'cwe',
        // Méta enregistrement
        'initial', 'mode_collecte', 'utilisateur_collecte', 'date_enregistrement',
    ];

    /* MODIF 2026-05-06 : liste des colonnes ratings
     * SonarQube. Convention SonarQube : 1.0=A, 2.0=B, 3.0=C, 4.0=D, 5.0=E. La collecte
     * recoit parfois la valeur numerique brute ("0"/"1.0"/...), parfois la lettre.
     * On normalise au moment de l'INSERT pour avoir toujours A-E ou null en base. */
    private static array $historiqueRatingColumns = [
        'comment_lines_rating', 'coverage_rating', 'duplicated_lines_rating',
        'complexity_rating', 'cognitive_complexity_rating',
        'sqale_rating', 'software_quality_maintainability_rating',
        'reliability_rating', 'software_quality_reliability_rating',
        'security_rating', 'software_quality_security_rating',
        'security_review_rating',
    ];

    /**
     * Normalise un rating SonarQube : numérique 1-5 -> A-E, lettre A-E inchangée,
     * autre valeur (0, null, '', 'null') -> null.
     */
    private static function normalizeRating(mixed $value): ?string
    {
        if ($value === null || $value === '' || $value === 'null') {
            return null;
        }
        if (is_string($value) && preg_match('/^[A-E]$/i', $value)) {
            return strtoupper($value);
        }
        return match ((int) $value) {
            1 => 'A',
            2 => 'B',
            3 => 'C',
            4 => 'D',
            5 => 'E',
            default => null,
        };
    }

    /**
     * MODIF 2026-05-15 : 3e paramètre `$loggerBreakdown`
     * (array nullable) sérialisé en JSONB littéral pour la colonne
     * `historique.logger_breakdown`. Pattern symétrique à `actuator_info`
     * (déjà JSONB). Null si plugin track-logger-method désactivé ou non
     * collecté.
     *
     * @param array<int|string, mixed>      $map
     * @param array<int|string, mixed>      $json
     * @param array<int|string, mixed>|null $loggerBreakdown
     */
    public function insertHistoriqueAjoutProjet(array $map, array $json, ?array $loggerBreakdown = null): array
    {
        /** SQL générée à partir du whitelist + champ actuator_info en littéral JSON. */
        $cols = self::$historiqueColumns;
        $colList = implode(', ', $cols) . ', actuator_info, logger_breakdown';
        $loggerBreakdownLit = $loggerBreakdown === null ? 'NULL' : "'" . json_encode($loggerBreakdown) . "'";
        $placeholders = implode(', ', array_map(static fn (string $c): string => ':' . $c, $cols))
            . ", '" . json_encode($json) . "'"
            . ", " . $loggerBreakdownLit;
        $sql = "INSERT INTO ma_moulinette.historique ($colList) VALUES ($placeholders)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $ratingColsSet = array_flip(self::$historiqueRatingColumns);
            foreach ($cols as $col) {
                /** Cas spéciaux :
                 *   - 'initial' : Doctrine ne peut pas binder un bool, on force la chaîne 'false'
                 *     (les nouvelles versions ne sont jamais "version de référence" à la création
                 *     — le toggle se fait via updateHistoriqueReference).
                 *   - 'date_enregistrement' : DateTimeInterface → string ISO avec offset.
                 *   - ratings SonarQube : normalisation 1-5 -> A-E (cf. self::normalizeRating).
                 *   - Tout le reste : valeur du map ou null si absent (colonnes nullable). */
                $value = $map[$col] ?? null;
                if ($col === 'initial') {
                    $value = 'false';
                } elseif ($col === 'date_enregistrement' && $value instanceof \DateTimeInterface) {
                    $value = $value->format('Y-m-d H:i:sO');
                } elseif (isset($ratingColsSet[$col])) {
                    $value = self::normalizeRating($value);
                // MODIF 2026-05-26 : else terminal requis par S126 (colonne standard, valeur inchangée).
                } else {
                    // colonne standard : $value déjà assigné depuis $map[$col] ?? null
                }
                $stmt->bindValue(':' . $col, $value);
            }
            $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueProjetByDate]
     * Retourne ma liste des projet par date décroissant
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 27/02/2024 19:08:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueProjetByDate(array $map): array
    {
        /** On prépare la requête */
        $sql = "SELECT maven_key, version, date_version as date, initial
                FROM ma_moulinette.historique
                WHERE maven_key=:maven_key
                ORDER BY date_version DESC";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $version = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        /** on prépare la réponse */
        return ['code' => 200, 'version' => $version, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueProjetLast]
     * On récupère les informations du projet le plus récent
     * (i.e ayant la date d'analyse la plus récente).
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 29/02/2024 18:15:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    /**
     * Dernier JSON actuator_info connu pour un projet (colonne JSONB), pour la
     * pastille + modale Actuator de la page Projet — voir ApiPeintureController::peintureProjetActuator.
     * `null` si aucune ligne historique n'existe encore pour ce maven_key.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 23/07/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueActuatorInfo(array $map): array
    {
        $sql = "SELECT actuator_info
                FROM ma_moulinette.historique
                WHERE maven_key = :maven_key
                ORDER BY date_version DESC LIMIT 1";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $ligne = $stmt->executeQuery()->fetchAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        if ($ligne === false) {
            return ['code' => 200, 'actuator_info' => null, 'erreur' => ''];
        }

        $actuatorInfo = $ligne['actuator_info'] !== null
            ? json_decode((string) $ligne['actuator_info'], true)
            : null;

        return ['code' => 200, 'actuator_info' => $actuatorInfo, 'erreur' => ''];
    }

    public function selectHistoriqueProjetLast(array $map): array
    {
        /** On prépare la requête */
        $sql =  "SELECT version, project_name AS name, date_version,
                        reliability_rating AS note_reliability, security_rating AS note_security,
                        security_review_rating AS note_hotspot, sqale_rating AS note_sqale,
                        bug_blocker, bug_critical, bug_major,
                        vulnerability_blocker, vulnerability_critical, vulnerability_major,
                        code_smell_blocker, code_smell_critical, code_smell_major,
                        menace_potentielle_totale, coverage, sqale_debt_ratio
                FROM ma_moulinette.historique
                WHERE maven_key = :maven_key
                ORDER BY date_version DESC LIMIT 1";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(self::$mavenKey, $map['maven_key']);
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
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 29/02/2024 18:49:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueProjetReference(array $map): array
    {
        /** On prépare la requête */
        $sql = "SELECT version, date_version,
                        reliability_rating AS note_reliability, security_rating AS note_security,
                        security_review_rating AS note_hotspot, sqale_rating AS note_sqale,
                        bug_blocker, bug_critical, bug_major,
                        vulnerability_blocker, vulnerability_critical, vulnerability_major,
                        code_smell_blocker, code_smell_critical, code_smell_major,
                        menace_potentielle_totale, coverage, sqale_debt_ratio
                FROM ma_moulinette.historique
                WHERE maven_key=:maven_key AND initial=true";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(self::$mavenKey, $map['maven_key']);
                $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'reference' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueProjetFavori]
     * Retourne la liste des données pour la dernière version des projets favoris.
     *
     * @param array<int|string, mixed> $map  ['liste_projet' => string[], 'nombre_projet_favori' => int]
     *
     * @return array<int|string, mixed>
     *
     * Created at: 27/03/2024 19:07:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueProjetFavori(array $map): array
    {
        $listeProjet = (array) ($map['liste_projet'] ?? []);
        $nombreFavori = (int) ($map['nombre_projet_favori'] ?? 0);

        if ($listeProjet === [] || $nombreFavori <= 0) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }

        /** On prépare la requête */
        /* MODIF 2026-05-15 : remplacement de la concaténation
         * IN (".$map['liste_projet'].") par le placeholder :liste_projet bindé
         * via ArrayParameterType::STRING (cf executeQuery ci-dessous).
         * Bug runtime : "Warning: Array to string conversion" — la
         * concaténation d'un array dans une string SQL produisait
         * "WHERE maven_key IN (Array)", erreur SQL silencieusement attrapée
         * par handleDatabaseException qui renvoyait systématiquement 500.
         * Symétrique du fix appliqué à selectHistoriqueIndicateurs en session
         * 2026-05-04 — cette méthode-ci avait été oubliée. */
        $sql = "WITH LastVersions AS (
                SELECT  maven_key AS mavenkey, project_name AS nom,
                        version, date_version AS date,
                        reliability_rating AS reliability,
                        security_rating AS security, security_review_rating AS hotspot,
                        sqale_rating AS sqale, bugs AS bug,
                        vulnerabilities AS vulnerability,
                        code_smells AS code_smell,
                        menace_potentielle_totale AS hotspots,
                ROW_NUMBER() OVER (PARTITION BY maven_key ORDER BY date_version DESC) AS rn
                FROM ma_moulinette.historique
                WHERE maven_key IN (:liste_projet))
                SELECT  mavenkey, nom, version, date, reliability, security, hotspot, sqale, bug,
                        vulnerability, code_smell, hotspots
                FROM LastVersions
                WHERE rn = 1
                LIMIT :nombre_favori";
        try {
            $liste = $this->getEntityManager()->getConnection()->executeQuery(
                preg_replace(self::$removeReturnLine, " ", $sql),
                ['liste_projet' => $listeProjet, 'nombre_favori' => $nombreFavori],
                ['liste_projet' => ArrayParameterType::STRING, 'nombre_favori' => ParameterType::INTEGER],
            )->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueIsValide]
     *
     *
     * @return array<int|string, mixed>
     *
     * Created at: 18/06/2024 21:12:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueIsValide(array $map): array
    {
        /** On prépare la requête */
        $sql = "SELECT version, project_name AS name,
                        date_version, analyse_key
                FROM ma_moulinette.historique
                WHERE maven_key=:maven_key
                ORDER BY date_version DESC LIMIT 1";
        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(self::$mavenKey, $map['maven_key']);
                $isValide = $stmt->executeQuery()->fetchAllAssociative();
                    /** j'ai pas trouvé de projet */
            if (!$isValide) {
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
     * @param array<int, string> $mavenKeys
     *
     * @return array<int|string, mixed>
     *
     * Created at: 24/09/2024 16:47:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHistoriqueIndicateurs(array $mavenKeys): array
    {
        if ($mavenKeys === []) {
            return ['code' => 200, 'indicateur' => [], 'erreur' => ''];
        }

        /** On prépare la requête */
        /* MODIF 2026-05-04 : remplacement de la concaténation IN (".$map.") (variable indéfinie)
         * par le placeholder :maven_keys déjà bindé via ArrayParameterType::STRING ci-dessous.
         * La méthode était cassée : la concaténation produisait WHERE maven_key IN () et levait une exception
         * SQL silencieusement attrapée par handleDatabaseException, renvoyant systématiquement code 500. */
        $sql = "SELECT DISTINCT ON (maven_key)
                    project_name, version, suppress_warning,
                    (java_no_sonar + python_no_sonar + php_no_sonar) AS no_sonar,
                    /* MODIF 2026-05-07 : ancien commentaire SQL ligne (deux tirets)
                    supprime - le collapse \s+/u tronquait la requête jusqu a la fin.
                       Le total inclut web_todo (MODIF 2026-05-06). */
                    (COALESCE(java_todo,0) + COALESCE(python_todo,0) + COALESCE(php_todo,0) + COALESCE(xml_todo,0) + COALESCE(web_todo,0) + COALESCE(javascript_todo,0) + COALESCE(typescript_todo,0) + COALESCE(ruby_todo,0)) AS todo,
                    lines AS nombre_ligne, ncloc AS nombre_ligne_code, tests, violations,
                    bugs AS nombre_bug, vulnerabilities AS nombre_vulnerability, code_smells AS nombre_code_smell,
                    repartition_frontend AS frontend, repartition_backend AS backend,
                    repartition_autre AS autre, repartition_inconnu AS inconnu,
                    reliability_rating AS note_reliability, security_rating AS note_security,
                    sqale_rating AS note_sqale, security_review_rating AS note_hotspot,
                    logger_info, logger_warn, logger_error, logger_debug
                FROM ma_moulinette.historique
                WHERE maven_key IN (:maven_keys) ORDER BY maven_key ASC, version DESC, date_version DESC";

        try {
            $indicateur = $this->getEntityManager()->getConnection()->executeQuery(
                preg_replace(self::$removeReturnLine, " ", $sql),
                ['maven_keys' => $mavenKeys],
                ['maven_keys' => ArrayParameterType::STRING],
            )->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        /** on prépare la réponse */
        return ['code' => 200, 'indicateur' => $indicateur, 'erreur' => ''];
    }

    /**
     * [Description for selectHistoriqueCleanCodeSynthese]
     * Retourne la dernière collecte clean code par projet pour la vue synthèse multi-projets.
     *
     * @param array $mavenKeys
     *
     * @return array
     *
     * Created at: 19/05/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    /* MODIF 2026-05-19 : synthèse multi-projets, dernière collecte par projet */
    public function selectHistoriqueCleanCodeSynthese(array $mavenKeys): array
    {
        if ($mavenKeys === []) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }

        $sql = "SELECT DISTINCT ON (maven_key)
                    maven_key, project_name, version, date_enregistrement,
                    alert_status,
                    COALESCE(software_quality_maintainability_rating, sqale_rating) AS note_mnt,
                    COALESCE(software_quality_reliability_rating, reliability_rating) AS note_fia,
                    COALESCE(software_quality_security_rating, security_rating) AS note_sec,
                    security_review_rating AS note_hsp,
                    complexity_rating AS note_cpx,
                    cognitive_complexity_rating AS note_cpx_cog,
                    COALESCE(violations, 0) AS violations,
                    COALESCE(bugs, 0) AS bugs,
                    COALESCE(vulnerabilities, 0) AS vulnerabilities,
                    coverage,
                    duplicated_lines_density,
                    COALESCE(cc_consistent, 0) AS cc_consistent,
                    COALESCE(cc_intentional, 0) AS cc_intentional,
                    COALESCE(cc_adaptable, 0) AS cc_adaptable,
                    COALESCE(cc_responsible, 0) AS cc_responsible,
                    COALESCE(impact_blocker, 0) AS impact_blocker,
                    COALESCE(impact_high, 0) AS impact_high,
                    COALESCE(impact_medium, 0) AS impact_medium,
                    COALESCE(impact_low, 0) AS impact_low,
                    COALESCE(impact_info, 0) AS impact_info,
                    COALESCE(owasp_top10, 0) AS owasp_top10
                FROM ma_moulinette.historique
                WHERE maven_key IN (:maven_keys) AND cc_responsible IS NOT NULL
                ORDER BY maven_key ASC, date_enregistrement DESC";

        try {
            $liste = $this->getEntityManager()->getConnection()->executeQuery(
                preg_replace(self::$removeReturnLine, " ", $sql),
                ['maven_keys' => $mavenKeys],
                ['maven_keys' => ArrayParameterType::STRING],
            )->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    // MODIF 2026-06-09
    /**
     * Retourne la dernière synthèse (DISTINCT ON maven_key) de chaque projet,
     * triée par date_enregistrement DESC.
     *
     * @return array<int|string, mixed>
     */
    /**
     * MODIF 2026-07-22 : ajout du filtrage par $mavenKeys (périmètre groupe
     * fonctionnel de l'utilisateur) — auparavant, tous les projets de la base
     * étaient remontés à n'importe quel utilisateur authentifié, sans
     * restriction de périmètre (contrairement à mes_projets/clean_code_synthese).
     *
     * @param string[] $mavenKeys
     * @return array<int|string, mixed>
     */
    public function selectAllProjetsDerniereSynthese(array $mavenKeys): array
    {
        if ($mavenKeys === []) {
            return ['code' => 200, 'projets' => [], 'erreur' => ''];
        }

        $sql = "SELECT DISTINCT ON (maven_key)
                    maven_key, project_name, version, date_version,
                    alert_status,
                    lines, ncloc, files, classes, functions, statements,
                    comment_lines, comment_lines_rating,
                    coverage, coverage_rating, tests,
                    reliability_rating, security_rating, sqale_rating,
                    duplicated_lines, duplicated_lines_density, duplicated_lines_rating,
                    security_review_rating, complexity, complexity_rating,
                    cognitive_complexity, cognitive_complexity_rating,
                    bugs, vulnerabilities, code_smells, security_hotspots,
                    blocker_violations, critical_violations, major_violations,
                    minor_violations, info_violations,
                    date_enregistrement
                FROM ma_moulinette.historique
                WHERE maven_key IN (:maven_keys)
                ORDER BY maven_key, date_enregistrement DESC";
        try {
            $rows = $this->getEntityManager()->getConnection()->executeQuery(
                preg_replace(self::$removeReturnLine, ' ', $sql),
                ['maven_keys' => $mavenKeys],
                ['maven_keys' => ArrayParameterType::STRING],
            )->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'projets' => $rows, 'erreur' => ''];
    }

}
