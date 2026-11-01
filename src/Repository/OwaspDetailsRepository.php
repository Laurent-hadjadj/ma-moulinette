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

use App\Entity\OwaspDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description OwaspDetailsRepository]
 * @extends ServiceEntityRepository<OwaspDetails>
 */
class OwaspDetailsRepository extends ServiceEntityRepository
{
    private static string $removeReturnLine = "/\s+/u";
    private static string $mavenKey = ':maven_key';
    private static string $referentialOwasp = ':referential_owasp';
    private static string $noDataBase = 'La connexion à la base de données a échoué.';
    /** Tri par criticité (BLOCKER d'abord), pas alphabétique — réutilisé par les 2 méthodes de lecture. */
    private static string $orderBySeverity = "CASE severity
            WHEN 'BLOCKER' THEN 1
            WHEN 'CRITICAL' THEN 2
            WHEN 'MAJOR' THEN 3
            WHEN 'MINOR' THEN 4
            ELSE 5
        END ASC, line ASC";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, OwaspDetails::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array<int|string, mixed>
     *
     * Created at: 28/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * [Description for selectOwaspDetailsTop10BySeverity]
     * Les 10 vulnérabilités les plus critiques d'un (maven_key, referential_owasp), triées par
     * sévérité (Bloquant > Critique > Majeur > Mineur > Info) — pour le tableau de détail de la page
     * OWASP et de l'export PDF. Le total réel (pas limité à 10) est fourni séparément par
     * countOwaspDetails().
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 28/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectOwaspDetailsTop10BySeverity(array $map): array
    {
        $sql = "SELECT *
                FROM ma_moulinette.owasp_details
                WHERE maven_key=:maven_key AND referential_owasp=:referential_owasp
                ORDER BY " . self::$orderBySeverity . "
                LIMIT 10";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $stmt->bindValue(self::$referentialOwasp, $map['referential_owasp']);
            $details = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'details' => $details, 'erreur' => ''];
    }

    /**
     * [Description for countOwaspDetails]
     * Nombre total de vulnérabilités classées pour un (maven_key, referential_owasp) — pas limité à 10,
     * contrairement à selectOwaspDetailsTop10BySeverity().
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 28/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countOwaspDetails(array $map): array
    {
        $sql = "SELECT COUNT(*) AS total
                FROM ma_moulinette.owasp_details
                WHERE maven_key=:maven_key AND referential_owasp=:referential_owasp";

        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $stmt->bindValue(self::$referentialOwasp, $map['referential_owasp']);
            $total = $stmt->executeQuery()->fetchOne();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'total' => (int) $total, 'erreur' => ''];
    }

    /**
     * [Description for deleteOwaspDetailsMavenKey]
     * Supprime le détail existant pour un projet (les 2 référentiels sont toujours reconstruits
     * ensemble à chaque collecte, même logique que OwaspRepository::deleteOwaspMavenKey()).
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 28/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteOwaspDetailsMavenKey(array $map): array
    {
        $sql = "DELETE
                FROM ma_moulinette.owasp_details
                WHERE maven_key=:maven_key";

        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
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
     * [Description for insertOwaspDetails]
     *
     * @param array<int, array<int|string, mixed>> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 28/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertOwaspDetails(array $map): array
    {
        $sql = "INSERT INTO ma_moulinette.owasp_details
                    (maven_key, referential_owasp, version, date_version, rule_key, categories, severity, source, module, file_path, line, message,
                    mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES
                    (:maven_key, :referential_owasp, :version, :date_version, :rule_key, :categories, :severity, :source, :module, :file_path, :line, :message,
                    :mode_collecte, :utilisateur_collecte, :date_enregistrement)";

        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            foreach ($map as $item) {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                $stmt->bindValue(self::$mavenKey, $item['maven_key']);
                $stmt->bindValue(self::$referentialOwasp, $item['referential_owasp']);
                $stmt->bindValue(':version', $item['version']);
                $stmt->bindValue(':date_version', $item['date_version']->format('Y-m-d H:i:sO'));
                $stmt->bindValue(':rule_key', $item['rule_key']);
                $stmt->bindValue(':categories', $item['categories']);
                $stmt->bindValue(':severity', $item['severity']);
                $stmt->bindValue(':source', $item['source']);
                $stmt->bindValue(':module', $item['module']);
                $stmt->bindValue(':file_path', $item['file_path']);
                $stmt->bindValue(':line', $item['line']);
                $stmt->bindValue(':message', $item['message']);
                $stmt->bindValue(':mode_collecte', $item['mode_collecte']);
                $stmt->bindValue(':utilisateur_collecte', $item['utilisateur_collecte']);
                $stmt->bindValue(':date_enregistrement', $item['date_enregistrement']->format('Y-m-d H:i:sO'));
                $stmt->executeStatement();
            }
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'erreur' => ''];
    }
}
