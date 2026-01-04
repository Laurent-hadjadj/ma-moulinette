<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2025..
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Repository;

use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * Repository dédié aux statistiques User-Agent.
 *
 * ⚠️ Lecture seule
 */
class UserAgentStatsRepository extends ServiceEntityRepository
{
    public static string $noDataBase = 'La connexion à la base de données a échoué.';
    public const DATE_COMPLETE = 'Y-m-d H:i:sO';
    public const START = ':start';
    public const END = ':end';

    public function __construct(ManagerRegistry $registry)
    {
        // Entité arbitraire, non utilisée ici
        parent::__construct($registry, \App\Entity\UserAgentAnalysis::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array
     *
     * Created at: 04/01/2026 16:17:38 (Europe/Paris)
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
     * [Description for selectDeviceTypeStats]
     * Répartition des types d'appareils
     *
     * @return array
     *
     * Created at: 14/12/2025 18:25:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectDeviceTypeStatsByPeriod(
        \DateTimeInterface $start,
        \DateTimeInterface $end): array {

        $sql = "SELECT device_type as name, COUNT(*) AS total
                FROM assistant_ia.user_agent_analysis
                WHERE created_at BETWEEN :start AND :end
                GROUP BY device_type
                ORDER BY total DESC";
        $conn = $this->getEntityManager()->getConnection();

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(self::START, $start->format(self::DATE_COMPLETE));
            $stmt->bindValue(self::END, $end->format(self::DATE_COMPLETE));
            $result = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $result];
    }

    /**
     * [Description for selectOsStatsByPeriod]
     * Répartition des systèmes d'exploitation
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     *
     * @return array
     *
     * Created at: 17/12/2025 14:11:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectOsStatsByPeriod(
        \DateTimeInterface $start, \DateTimeInterface $end): array {

        $sql = "SELECT os_name as name, os_version as version,
                COUNT(*) AS total
                FROM assistant_ia.user_agent_analysis
                WHERE created_at BETWEEN :start AND :end
                GROUP BY os_name, os_version
                ORDER BY total DESC";

        $conn = $this->getEntityManager()->getConnection();

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(self::START, $start->format(self::DATE_COMPLETE));
            $stmt->bindValue(self::END, $end->format(self::DATE_COMPLETE));
            $result = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $result];
    }

    /**
     * [Description for selectBrowserStats]
     * Répartition globale des navigateurs
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     *
     * @return array
     *
     * Created at: 17/12/2025 14:36:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectBrowserStatsByPeriod(
        \DateTimeInterface $start, \DateTimeInterface $end): array {

        $sql = "SELECT browser_name as name,
                        browser_version as version, COUNT(*) AS total
                FROM assistant_ia.user_agent_analysis
                WHERE created_at BETWEEN :start AND :end
                GROUP BY browser_name, browser_version
                ORDER BY total DESC";

            $conn = $this->getEntityManager()->getConnection();
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(self::START, $start->format(self::DATE_COMPLETE));
            $stmt->bindValue(self::END, $end->format(self::DATE_COMPLETE));
            $result = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $result];
    }

    /**
     * [Description for selectAuthenticatedBrowserStats]
     * Retour le liste des pages visitées
     *
     * @return array
     * Navigateurs utilisés par les utilisateurs authentifiés
     *
     * Created at: 14/12/2025 18:26:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectStateByPeriod(
        \DateTimeInterface $start, \DateTimeInterface $end): array {

        $sql = "SELECT browser_name as name,
                    browser_version as version, COUNT(*) AS total
                FROM assistant_ia.user_agent_analysis
                JOIN assistant_ia.user_agent_event
                    ON id = user_agent_event_id
                WHERE created_at BETWEEN :start AND :end
                GROUP BY browser_name, browser_version
                ORDER BY total DESC";

        $conn = $this->getEntityManager()->getConnection();

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(self::START, $start->format(self::DATE_COMPLETE));
            $stmt->bindValue(self::END, $end->format(self::DATE_COMPLETE));
            $result = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $result, 'error' => ''];
    }

    /**
     * [Description for selectSessionPagesStats]
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     *
     * @return array
     *
     * Created at: 21/12/2025 12:42:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectSessionPagesStats(
        \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $sqlKpi =  "SELECT
                        COUNT(DISTINCT session_id) AS unique_users,
                        COUNT(*) AS page_views
                    FROM assistant_ia.user_agent_analysis
                    WHERE session_id IS NOT NULL
                    AND created_at BETWEEN :start AND :end";

        $sqlPages = "SELECT event_type AS label, url, COUNT(*) AS total
                    FROM assistant_ia.user_agent_analysis
                    WHERE session_id IS NOT NULL
                        AND event_type != 'LOGGED'
                        AND event_type != 'LOGIN_PAGE_VIEW'
                        AND created_at BETWEEN :start AND :end
                    GROUP BY event_type, url
                    ORDER BY total DESC";

        $conn = $this->getEntityManager()->getConnection();

        try {
                $stmt = $conn->prepare($sqlKpi);
                    $stmt->bindValue(self::START, $start->format(self::DATE_COMPLETE));
                    $stmt->bindValue(self::END, $end->format(self::DATE_COMPLETE));
                $kpi = $stmt->executeQuery()->fetchAssociative();

                $stmt = $conn->prepare($sqlPages);
                    $stmt->bindValue(self::START, $start->format(self::DATE_COMPLETE));
                    $stmt->bindValue(self::END, $end->format(self::DATE_COMPLETE));
                $items = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return [ 'code' => 200, 'kpi' => $kpi, 'items' => $items, 'error' => '' ];
    }

    /**
     * [Description for selectAvgSessionDurationStats]
     *
     * @return array
     *
     * Created at: 21/12/2025 18:36:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectAvgSessionDurationStats(): array
    {
        $sql = "WITH session_bounds AS (
                SELECT
                    session_id,
                    created_at AS session_date,
                    MIN(created_at) FILTER (WHERE event_type IN ('PROMPT', 'PROMPT_SIMPLE')) AS session_start,
                    MAX(created_at) FILTER (WHERE event_type = 'LOGOUT') AS session_logout,
                    MAX(created_at) AS session_last_event
                FROM assistant_ia.user_agent_analysis
                WHERE event_type IS NOT NULL
                AND event_type NOT IN ('LOGGED', 'LOGIN_PAGE_VIEW')
                GROUP BY session_id, created_at
            ),
            session_durations AS (
                SELECT
                    session_date,
                    EXTRACT(EPOCH FROM (COALESCE(session_logout, session_last_event) - session_start))/60.0 AS duration_minutes
                FROM session_bounds
            )
            SELECT
                session_date,
                ROUND(AVG(duration_minutes), 2) AS avg_duration_minutes
            FROM session_durations
            GROUP BY session_date
            ORDER BY session_date";

        $conn = $this->getEntityManager()->getConnection();

        try {
                $stmt = $conn->prepare($sql);
                $rows = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'rows' => $rows, 'error' => ''];
    }

    /**
     * [Description for selectUniqueSessionStats]
     *
     * @return array
     *
     * Created at: 22/12/2025 08:04:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectUniqueSessionStats(): array
    {
        $sql = "WITH session_bounds AS (
                SELECT
                    session_id,
                    created_at AS session_date
                FROM assistant_ia.user_agent_analysis
                WHERE event_type IN ('PROMPT','PROMPT_SIMPLE')
                AND created_at IS NOT NULL
                GROUP BY session_id, created_at
                )
                SELECT
                    session_date,
                    COUNT(session_id) AS nb_sessions
                FROM session_bounds
                GROUP BY session_date
                ORDER BY session_date";

        $conn = $this->getEntityManager()->getConnection();

        try {
                $stmt = $conn->prepare($sql);
                $rows = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'rows' => $rows, 'error' => ''];
    }

    /**
     * [Description for selectSessionDurationByCategoryStats]
     *
     * @return array
     *
     * Created at: 22/12/2025 10:20:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectSessionDurationByCategoryStats(): array
    {
        $sql = "WITH session_bounds AS (
                SELECT
                    session_id,
                    MIN(created_at) FILTER (WHERE event_type IN ('PROMPT', 'PROMPT_SIMPLE')) AS session_start,
                    MAX(created_at) FILTER (WHERE event_type = 'LOGOUT') AS session_logout,
                    MAX(created_at) AS session_last_event
                FROM assistant_ia.user_agent_analysis
                WHERE created_at::date = CURRENT_DATE
                    AND event_type IS NOT NULL
                    AND event_type NOT IN ('LOGGED', 'LOGIN_PAGE_VIEW')
                GROUP BY session_id
            ),
            session_durations AS (
                SELECT
                    session_id,
                    session_start,
                    COALESCE(session_logout, session_last_event) AS session_end,
                    EXTRACT(EPOCH FROM (COALESCE(session_logout, session_last_event) - session_start)) AS duration_seconds
                FROM session_bounds
            )
            SELECT
                session_id,
                session_start,
                session_end,
                ROUND(duration_seconds/60.0, 2) AS duration_minutes,
                ROUND(duration_seconds/3600.0, 2) AS duration_hours,
                CASE
                    WHEN duration_seconds < 600 THEN 'court'                -- <10 min
                    WHEN duration_seconds BETWEEN 600 AND 3600 THEN 'moyen' -- 10-120 min
                    ELSE 'long'                                             -- >120 min
                END AS session_length_category
            FROM session_durations
            ORDER BY duration_seconds DESC";

        $conn = $this->getEntityManager()->getConnection();

        try {
                $stmt = $conn->prepare($sql);
                $rows = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'rows' => $rows, 'error' => ''];
    }

    /**
     * [Description for selectCategoryByUniqueSessionStats]
     *
     * @return array
     *
     * Created at: 22/12/2025 11:22:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectCategoryByUniqueSessionStats(): array
    {
        $sql = "WITH session_bounds AS (
                    SELECT
                        session_id,
                        MIN(created_at) FILTER (WHERE event_type IN ('PROMPT', 'PROMPT_SIMPLE')) AS session_start,
                        MAX(created_at) FILTER (WHERE event_type = 'LOGOUT') AS session_logout,
                        MAX(created_at) AS session_last_event
                    FROM assistant_ia.user_agent_analysis
                    WHERE created_at::date = CURRENT_DATE
                    AND event_type IS NOT NULL
                    AND event_type NOT IN ('LOGGED', 'LOGIN_PAGE_VIEW')
                    GROUP BY session_id
                ),
                session_durations AS (
                    SELECT
                        session_id,
                        COALESCE(session_logout, session_last_event) AS session_end,
                        EXTRACT(EPOCH FROM (COALESCE(session_logout, session_last_event) - session_start)) AS duration_seconds
                    FROM session_bounds
                ),
                session_with_category AS (
                    SELECT
                        session_id,
                        duration_seconds,
                        CASE
                            WHEN duration_seconds < 600 THEN 'Court'
                            WHEN duration_seconds BETWEEN 600 AND 3600 THEN 'Moyen'
                            ELSE 'Long'
                        END AS session_length_category
                    FROM session_durations
                )
                SELECT
                    session_length_category AS category,
                    COUNT(*) AS session_count,
                    ROUND(100.0 * COUNT(*) / NULLIF(SUM(COUNT(*)) OVER (), 0), 0) AS percentage,
                    ROUND(AVG(duration_seconds)/60.0, 2) AS avg_duration_min,
                    ROUND(AVG(duration_seconds)/3600.0, 2) AS avg_duration_hr
                FROM session_with_category
                GROUP BY session_length_category
                ORDER BY
                    CASE session_length_category
                        WHEN 'Court' THEN 1
                        WHEN 'Moyen' THEN 2
                        WHEN 'Long' THEN 3
                    END";

        $conn = $this->getEntityManager()->getConnection();

        try {
                $stmt = $conn->prepare($sql);
                $rows = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'rows' => $rows, 'error' => ''];
    }

    /**
     * [Description for selectSessionDurationByPeriodStats]
     *
     * @param \DateTimeInterface $start
     * @param \DateTimeInterface $end
     *
     * @return array
     *
     * Created at: 22/12/2025 12:11:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectSessionDurationByPeriodStats(
        \DateTimeInterface $start, \DateTimeInterface $end): array
    {
        $sql = "WITH session_bounds AS (
                    SELECT
                        session_id,
                        MIN(created_at) FILTER (WHERE event_type IN ('PROMPT', 'PROMPT_SIMPLE')) AS session_start,
                        MAX(created_at) FILTER (WHERE event_type = 'LOGOUT') AS session_logout,
                        MAX(created_at) AS session_last_event
                    FROM assistant_ia.user_agent_analysis
                    WHERE event_type IS NOT NULL
                    AND event_type NOT IN ('LOGGED', 'LOGIN_PAGE_VIEW')
                    AND created_at BETWEEN :start AND :end
                    GROUP BY session_id
                ),
                session_durations AS (
                    SELECT
                        session_id,
                        session_start,
                        COALESCE(EXTRACT(EPOCH FROM (COALESCE(session_logout, session_last_event) - session_start))/60.0, 0) AS duration_minutes,
                        COALESCE(EXTRACT(EPOCH FROM (COALESCE(session_logout, session_last_event) - session_start))/3600.0, 0) AS duration_hours
                    FROM session_bounds
                    WHERE session_start IS NOT NULL
                ),
                daily_avg AS (
                    SELECT
                        DATE(session_start) AS session_date,
                        AVG(duration_minutes) AS avg_duration_minutes,
                        AVG(duration_hours) AS avg_duration_hours
                    FROM session_durations
                    GROUP BY DATE(session_start)
                )
                SELECT
                    session_date,
                    ROUND(avg_duration_minutes, 2) AS avg_duration_minutes,
                    ROUND(avg_duration_hours, 2)   AS avg_duration_hours,
                    ROUND(
                        100.0 * avg_duration_minutes / NULLIF(SUM(avg_duration_minutes) OVER (), 0),
                        2
                    ) AS percent_of_total_avg
                FROM daily_avg
                ORDER BY session_date";

        $conn = $this->getEntityManager()->getConnection();

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(self::START, $start->format(self::DATE_COMPLETE));
            $stmt->bindValue(self::END, $end->format(self::DATE_COMPLETE));
            $result = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $result, 'error' => ''];
    }

}
