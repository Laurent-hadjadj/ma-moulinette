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

use App\Entity\Mesures;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description MesuresRepository]
 */
class MesuresRepository extends ServiceEntityRepository
{
    public static $removeReturnLine = "/\s+/u";
    public static $mavenKey = ':maven_key';
    public static $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mesures::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array
     *
     * Created at: 18/12/2024 15:29:21 (Europe/Paris)
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
     * [Description for selectMesuresVersionLast]
     * Retourne les mesures de la dernière version d'un projet
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/03/2024 22:51:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectMesuresVersionLast($map): array
    {
        $sql = "SELECT *
                FROM ma_moulinette.mesures
                WHERE maven_key = :maven_key
                ORDER BY date_enregistrement DESC LIMIT 1";

        $con = $this->getEntityManager()->getConnection();

        try {
            $stmt = $con->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$mavenKey, $map['maven_key']);
            //on returne un tableau associatif (une ligne) ou false si aucune ligne trouvée
            $mesures = $stmt->executeQuery()->fetchAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'mesures' => $mesures, 'erreur' => ''];
    }

    /**
     * [Description for insertMesures]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/05/2024 22:57:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertMesures($map): array
    {
        $columns = [
            'maven_key',
            'project_name',

            'alert_status',

            'lines',
            'ncloc',
            'ncloc_language_distribution',
            'files',
            'classes',
            'functions',
            'statements',

            'comment_lines',
            'comment_lines_density',
            'comment_lines_rating',

            'coverage',
            'branch_coverage',
            'line_coverage',
            'lines_to_cover',
            'conditions_to_cover',
            'uncovered_conditions',
            'coverage_rating',

            'tests',
            'test_execution_time',
            'test_errors',
            'test_failures',
            'skipped_tests',
            'test_success_density',

            'duplicated_files',
            'duplicated_blocks',
            'duplicated_lines',
            'duplicated_lines_density',
            'duplicated_lines_rating',

            'complexity',
            'complexity_rating',
            'cognitive_complexity',
            'cognitive_complexity_rating',
            'complexity_ratio',
            'cognitive_complexity_ratio',

            'open_issues',
            'reopened_issues',
            'confirmed_issues',
            'false_positive_issues',
            'accepted_issues',
            'high_impact_accepted_issues',

            'violations',
            'blocker_violations',
            'critical_violations',
            'major_violations',
            'minor_violations',
            'info_violations',

            'software_quality_blocker_issues',
            'software_quality_high_issues',
            'software_quality_medium_issues',
            'software_quality_low_issues',
            'software_quality_info_issues',

            'code_smells',
            'maintainability_issues',
            'sqale_index',
            'sqale_debt_ratio',
            'sqale_rating',
            'effort_to_reach_maintainability_rating_a',

            'software_quality_maintainability_issues',
            'software_quality_maintainability_rating',
            'software_quality_maintainability_debt_ratio',
            'software_quality_maintainability_remediation_effort',
            'effort_to_reach_software_quality_maintainability_rating_a',

            'bugs',
            'reliability_issues',
            'reliability_rating',
            'reliability_remediation_effort',

            'software_quality_reliability_issues',
            'software_quality_reliability_rating',
            'software_quality_reliability_remediation_effort',

            'vulnerabilities',
            'security_issues',
            'security_rating',
            'security_remediation_effort',

            'software_quality_security_issues',
            'software_quality_security_rating',
            'software_quality_security_remediation_effort',

            'security_hotspots',
            'security_review_rating',
            'security_hotspots_reviewed',

            'mode_collecte',
            'utilisateur_collecte',
            'date_enregistrement'
        ];

        // Construction SQL automatique
        $sql = sprintf(
            "INSERT INTO ma_moulinette.mesures (%s) VALUES (%s)",
            implode(', ', $columns),
            ':' . implode(', :', $columns)
        );

        $conn = $this->getEntityManager()->getConnection();

        try {
            $conn->beginTransaction();
            $stmt = $conn->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                // Bind automatique sécurisé
                foreach ($columns as $column) {
                    if ($column === 'date_enregistrement') {
                        $stmt->bindValue(
                            ':' . $column,
                            $map[$column]->format('Y-m-d H:i:s')
                        );
                    } else {
                        $stmt->bindValue(':' . $column, $map[$column] ?? null);
                    }
                }

                $stmt->executeStatement();
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'erreur' => ''];
    }

    /**
     * [Description for deleteMesuresMavenKey]
     * Supprime les mesures de la version courante (i.e. correspondant à la maven_key)
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 26/05/2024 10:51:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteMesuresMavenKey($map): array
    {
        $sql = "DELETE
                FROM ma_moulinette.mesures
                WHERE maven_key = :maven_key";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollback();
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'erreur' => ''];
    }
}
