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

use App\Entity\LoggerDetail;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MODIF 2026-05-15 : repository pour LoggerDetail.
 *
 * Méthodes exposées :
 *   - handleDatabaseException        : pattern partagé
 *   - deleteLoggerDetailMavenKey     : drop des rows d'une maven_key
 *   - insertLoggerDetailBatch        : INSERT batch (1 SQL pour N rows)
 *   - selectByMavenKey               : lecture pour UI (drill-down par projet)
 *   - countByMavenKeyGroupedByLevel  : agrégation niveau (info/warn/error/debug)
 *   - countByMavenKeyGroupedByFramework : agrégation techno (SLF4J/Commons…)
 *
 * @extends ServiceEntityRepository<LoggerDetail>
 */
class LoggerDetailRepository extends ServiceEntityRepository
{
    private static string $removeReturnLine = "/\s+/u";
    private static string $mavenKey         = ':maven_key';
    private static string $noDataBase       = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, LoggerDetail::class);
    }

    /**
     * @return array{code: int, erreur: string}
     */
    public function handleDatabaseException(\Throwable $e): array
    {
        $message = $e->getMessage();

        if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
            $message = self::$noDataBase;
        }
        if ($e instanceof \Doctrine\DBAL\Exception\NotNullConstraintViolationException) {
            $message = $e->getMessage();
        }
        if ($e instanceof \Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
        }

        return ['code' => 500, 'erreur' => $message];
    }

    /**
     * [Description for deleteLoggerDetailMavenKey]
     * Drop des rows logger_details pour une maven_key. Appelé en début de
     * chaque collecte pour rejouer un état frais (cf BatchCollecteLoggerController).
     *
     * @param array<int|string, mixed> $map
     * @return array{code: int, erreur: string}
     *
     * Created at: 15/05/2026 09:12:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteLoggerDetailMavenKey(array $map): array
    {
        $sql = "DELETE FROM ma_moulinette.logger_details WHERE maven_key = :maven_key";
        try {
            $conn = $this->getEntityManager()->getConnection();
            $conn->beginTransaction();
            $stmt = $conn->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $stmt->executeStatement();
            $conn->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'erreur' => ''];
    }

    /**
     * [Description for insertLoggerDetailBatch]
     * INSERT batch : 1 requête SQL pour N rows (perf >> N requêtes individuelles).
     * Si $rows est vide, retourne sans rien faire (code 200).
     *
     * @param array<int, array{
     *   maven_key: string,
     *   project_version?: ?string,
     *   level: string,
     *   framework?: ?string,
     *   file_path: string,
     *   file_name: string,
     *   class_name?: ?string,
     *   line_number?: ?int,
     *   sonar_issue_key?: ?string,
     *   mode_collecte?: ?string,
     *   utilisateur_collecte?: ?string,
     *   date_enregistrement: \DateTimeImmutable
     * }> $rows
     * @return array{code: int, erreur: string, nombre?: int}
     *
     * Created at: 15/05/2026 09:12:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertLoggerDetailBatch(array $rows): array
    {
        if ($rows === []) {
            return ['code' => 200, 'erreur' => '', 'nombre' => 0];
        }

        // Construction des placeholders (?, ?, ?, …) × N rows
        $placeholders = [];
        $params       = [];
        foreach ($rows as $i => $r) {
            $placeholders[] = "(:mk{$i}, :pv{$i}, :lv{$i}, :fw{$i}, :fp{$i}, :fn{$i}, :cn{$i}, :ln{$i}, :sk{$i}, :mc{$i}, :uc{$i}, :de{$i})";
            $params["mk{$i}"] = $r['maven_key'];
            $params["pv{$i}"] = $r['project_version']      ?? null;
            $params["lv{$i}"] = $r['level'];
            $params["fw{$i}"] = $r['framework']            ?? null;
            $params["fp{$i}"] = $r['file_path'];
            $params["fn{$i}"] = $r['file_name'];
            $params["cn{$i}"] = $r['class_name']           ?? null;
            $params["ln{$i}"] = $r['line_number']          ?? null;
            $params["sk{$i}"] = $r['sonar_issue_key']      ?? null;
            $params["mc{$i}"] = $r['mode_collecte']        ?? null;
            $params["uc{$i}"] = $r['utilisateur_collecte'] ?? null;
            $params["de{$i}"] = $r['date_enregistrement']->format('Y-m-d H:i:sO');
        }

        $sql = "INSERT INTO ma_moulinette.logger_details
                    (maven_key, project_version, level, framework, file_path, file_name, class_name, line_number, sonar_issue_key, mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES " . implode(', ', $placeholders);

        try {
            $conn = $this->getEntityManager()->getConnection();
            $conn->beginTransaction();
            $stmt = $conn->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }
            $stmt->executeStatement();
            $conn->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'erreur' => '', 'nombre' => count($rows)];
    }

    /**
     * [Description for selectByMavenKey]
     * Lecture pour drill-down UI : tous les details d'une maven_key, triés
     * par file_path ASC puis line_number ASC.
     *
     * @param string $mavenKey
     *
     * @return array{code: int, liste?: array<int, array<string, mixed>>, erreur?: string}
     *
     * Created at: 15/05/2026 09:13:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectByMavenKey(string $mavenKey): array
    {
        $sql = "SELECT id, maven_key, project_version, level, framework, file_path,
                    file_name, class_name, line_number, sonar_issue_key,
                    mode_collecte, utilisateur_collecte, date_enregistrement
                FROM ma_moulinette.logger_details
                WHERE maven_key = :maven_key
                ORDER BY file_path ASC, line_number ASC NULLS LAST";
        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery(preg_replace(self::$removeReturnLine, " ", $sql), ['maven_key' => $mavenKey])
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $rows, 'erreur' => ''];
    }

    /**
     * [Description for countByMavenKeyGroupedByLevel]
     * Agrégation : nb de loggers par level pour une maven_key.
     *
     * @param string $mavenKey
     *
     * @return array{code: int, liste?: array<int, array{level: string, nb: int}>, erreur?: string}
     *
     * Created at: 15/05/2026 09:13:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countByMavenKeyGroupedByLevel(string $mavenKey): array
    {
        $sql = "SELECT level, COUNT(*)::int AS nb
                FROM ma_moulinette.logger_details
                WHERE maven_key = :maven_key
                GROUP BY level
                ORDER BY nb DESC, level ASC";
        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery(preg_replace(self::$removeReturnLine, " ", $sql), ['maven_key' => $mavenKey])
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $rows, 'erreur' => ''];
    }

    /**
     * [Description for countByMavenKeyGroupedByLevelAndFramework]
     * MODIF 2026-05-15 : agrégation par level × framework.
     * Pour chaque (level, framework) on retourne le nombre d'occurrences.
     * Format plat (pas de structure nichée) : facile à GROUP côté PHP/Twig
     * et à sérialiser en JSON pour le front (charts empilés).
     *
     * @param string $mavenKey
     *
     * @return array{code: int, liste?: array<int, array{level: string, framework: ?string, nb: int}>, erreur?: string}
     *
     * Created at: 15/05/2026 09:14:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countByMavenKeyGroupedByLevelAndFramework(string $mavenKey): array
    {
        $sql = "SELECT level, framework, COUNT(*)::int AS nb
                FROM ma_moulinette.logger_details
                WHERE maven_key = :maven_key
                GROUP BY level, framework
                ORDER BY level ASC, nb DESC, framework ASC NULLS LAST";
        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery(preg_replace(self::$removeReturnLine, " ", $sql), ['maven_key' => $mavenKey])
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $rows, 'erreur' => ''];
    }

    /**
     * [Description for countByMavenKeyGroupedByFramework]
     * Agrégation : nb de loggers par framework pour une maven_key.
     * Utilisé pour la "répartition techno" sur la page projet.
     *
     * @param string $mavenKey
     *
     * @return array{code: int, liste?: array<int, array{framework: ?string, nb: int}>, erreur?: string}
     *
     * Created at: 15/05/2026 09:15:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countByMavenKeyGroupedByFramework(string $mavenKey): array
    {
        $sql = "SELECT framework, COUNT(*)::int AS nb
                FROM ma_moulinette.logger_details
                WHERE maven_key = :maven_key
                GROUP BY framework
                ORDER BY nb DESC, framework ASC NULLS LAST";
        try {
            $rows = $this->getEntityManager()->getConnection()
                ->executeQuery(preg_replace(self::$removeReturnLine, " ", $sql), ['maven_key' => $mavenKey])
                ->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $rows, 'erreur' => ''];
    }
}
