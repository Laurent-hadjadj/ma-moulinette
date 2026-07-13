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

use App\Entity\CleanCode;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/* MODIF 2026-05-17 : repository pour les indicateurs
 * SonarQube 10+ (clean code taxonomy + compliance OWASP/SANS/CWE). */

/**
 * @extends ServiceEntityRepository<CleanCode>
 */
class CleanCodeRepository extends ServiceEntityRepository
{
    private static string $removeReturnLine = "/\s+/u";
    private static string $mavenKey = ':maven_key';
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CleanCode::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array<int|string, mixed>
     *
     * Created at: 17/05/2026 12:45:04 (Europe/Paris)
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
     * [Description for deleteCleanCodeMavenKey]
     * Supprime les indicateurs clean code du projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 17/05/2026 12:46:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteCleanCodeMavenKey(array $map): array
    {
        $sql = "DELETE FROM ma_moulinette.clean_code WHERE maven_key = :maven_key";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(
                preg_replace(self::$removeReturnLine, ' ', $sql)
            );
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'erreur' => ''];
    }

    /**
     * [Description for selectCleanCode]
     * Retourne les indicateurs clean code d'un projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 17/05/2026 12:47:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectCleanCode(array $map): array
    {
        $sql = "SELECT *
                FROM ma_moulinette.clean_code
                WHERE maven_key = :maven_key
                ORDER BY date_enregistrement DESC
                LIMIT 1";
        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(
                preg_replace(self::$removeReturnLine, ' ', $sql)
            );
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for insertCleanCode]
     * Insère les indicateurs clean code d'un projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 17/05/2026 12:48:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertCleanCode(array $map): array
    {
        $sql = "INSERT INTO ma_moulinette.clean_code
                    (maven_key, project_name, issue_total,
                    cc_consistent, cc_intentional, cc_adaptable, cc_responsible,
                    quality_maintainability, quality_reliability, quality_security,
                    impact_blocker, impact_high, impact_medium, impact_low, impact_info,
                    owasp_top10, sans_top25, cwe,
                    mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES
                    (:maven_key, :project_name, :issue_total,
                    :cc_consistent, :cc_intentional, :cc_adaptable, :cc_responsible,
                    :quality_maintainability, :quality_reliability, :quality_security,
                    :impact_blocker, :impact_high, :impact_medium, :impact_low, :impact_info,
                    :owasp_top10, :sans_top25, :cwe,
                    :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(
                preg_replace(self::$removeReturnLine, ' ', $sql)
            );
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $stmt->bindValue(':project_name', $map['project_name']);
            $stmt->bindValue(':issue_total', $map['issue_total']);
            $stmt->bindValue(':cc_consistent', $map['cc_consistent']);
            $stmt->bindValue(':cc_intentional', $map['cc_intentional']);
            $stmt->bindValue(':cc_adaptable', $map['cc_adaptable']);
            $stmt->bindValue(':cc_responsible', $map['cc_responsible']);
            $stmt->bindValue(':quality_maintainability', $map['quality_maintainability']);
            $stmt->bindValue(':quality_reliability', $map['quality_reliability']);
            $stmt->bindValue(':quality_security', $map['quality_security']);
            $stmt->bindValue(':impact_blocker', $map['impact_blocker']);
            $stmt->bindValue(':impact_high', $map['impact_high']);
            $stmt->bindValue(':impact_medium', $map['impact_medium']);
            $stmt->bindValue(':impact_low', $map['impact_low']);
            $stmt->bindValue(':impact_info', $map['impact_info']);
            $stmt->bindValue(':owasp_top10', $map['owasp_top10']);
            $stmt->bindValue(':sans_top25', $map['sans_top25']);
            $stmt->bindValue(':cwe', $map['cwe']);
            $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
            $stmt->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);
            $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));
            $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'erreur' => ''];
    }
}
