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

use App\Entity\Accessibility;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description accessibilityRepository]
 * MODIF 26/10/2026 : repository pour l'indicateur Accessibilité (tag SonarQube
 * `accessibility`, css/web/javascript/typescript). Snapshot courant, sur le
 * modèle de EcoConceptionRepository/RciRepository.
 *
 * @extends ServiceEntityRepository<accessibility>
 */
class AccessibilityRepository extends ServiceEntityRepository
{
    private static string $removeReturnLine = "/\s+/u";
    private static string $mavenKey = ':maven_key';
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, accessibility::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array<int|string, mixed>
     *
     * Created at: 26/10/2026 (Europe/Paris)
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
     * [Description for deleteAccessibilityMavenKey]
     * Supprime l'Accessibilité du projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 26/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteAccessibilityMavenKey(array $map): array
    {
        $sql = "DELETE FROM ma_moulinette.accessibility WHERE maven_key = :maven_key";
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
     * [Description for selectAccessibility]
     * Retourne l'Accessibilité d'un projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 26/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectAccessibility(array $map): array
    {
        $sql = "SELECT *
                FROM ma_moulinette.accessibility
                WHERE maven_key = :maven_key
                ORDER BY date_enregistrement DESC
                LIMIT 1";
        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(
                preg_replace(self::$removeReturnLine, ' ', $sql)
            );
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $liste = $stmt->executeQuery()->fetchAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for insertAccessibility]
     * Insère l'Accessibilité d'un projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 26/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertAccessibility(array $map): array
    {
        $sql = "INSERT INTO ma_moulinette.accessibility
                    (maven_key, accessibility_anomalies, accessibility_dette_minute, accessibility_rating,
                    accessibility_css_anomalies, accessibility_css_dette_minute, accessibility_css_rating,
                    accessibility_web_anomalies, accessibility_web_dette_minute, accessibility_web_rating,
                    accessibility_javascript_anomalies, accessibility_javascript_dette_minute, accessibility_javascript_rating,
                    accessibility_typescript_anomalies, accessibility_typescript_dette_minute, accessibility_typescript_rating,
                    mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES
                    (:maven_key, :accessibility_anomalies, :accessibility_dette_minute, :accessibility_rating,
                    :accessibility_css_anomalies, :accessibility_css_dette_minute, :accessibility_css_rating,
                    :accessibility_web_anomalies, :accessibility_web_dette_minute, :accessibility_web_rating,
                    :accessibility_javascript_anomalies, :accessibility_javascript_dette_minute, :accessibility_javascript_rating,
                    :accessibility_typescript_anomalies, :accessibility_typescript_dette_minute, :accessibility_typescript_rating,
                    :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(
                preg_replace(self::$removeReturnLine, ' ', $sql)
            );
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $stmt->bindValue(':accessibility_anomalies', $map['accessibility_anomalies']);
            $stmt->bindValue(':accessibility_dette_minute', $map['accessibility_dette_minute']);
            $stmt->bindValue(':accessibility_rating', $map['accessibility_rating']);
            $stmt->bindValue(':accessibility_css_anomalies', $map['accessibility_css_anomalies']);
            $stmt->bindValue(':accessibility_css_dette_minute', $map['accessibility_css_dette_minute']);
            $stmt->bindValue(':accessibility_css_rating', $map['accessibility_css_rating']);
            $stmt->bindValue(':accessibility_web_anomalies', $map['accessibility_web_anomalies']);
            $stmt->bindValue(':accessibility_web_dette_minute', $map['accessibility_web_dette_minute']);
            $stmt->bindValue(':accessibility_web_rating', $map['accessibility_web_rating']);
            $stmt->bindValue(':accessibility_javascript_anomalies', $map['accessibility_javascript_anomalies']);
            $stmt->bindValue(':accessibility_javascript_dette_minute', $map['accessibility_javascript_dette_minute']);
            $stmt->bindValue(':accessibility_javascript_rating', $map['accessibility_javascript_rating']);
            $stmt->bindValue(':accessibility_typescript_anomalies', $map['accessibility_typescript_anomalies']);
            $stmt->bindValue(':accessibility_typescript_dette_minute', $map['accessibility_typescript_dette_minute']);
            $stmt->bindValue(':accessibility_typescript_rating', $map['accessibility_typescript_rating']);
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
