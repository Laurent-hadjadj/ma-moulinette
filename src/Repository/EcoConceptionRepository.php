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

use App\Entity\EcoConception;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 */
/**
 * [Description EcoConceptionRepository]
 * MODIF 2026-10-08 : repository pour l'Empreinte Écologique (plugins SonarQube
 * Creedengo creedengo-java/javascript/php/python). Snapshot courant, sur le
 * modèle de RciRepository/CleanCodeRepository.
 *
 * @extends ServiceEntityRepository<EcoConception>
 */
class EcoConceptionRepository extends ServiceEntityRepository
{
    private static string $removeReturnLine = "/\s+/u";
    private static string $mavenKey = ':maven_key';
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, EcoConception::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array<int|string, mixed>
     *
     * Created at: 08/10/2026 (Europe/Paris)
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
     * [Description for deleteEcoConceptionMavenKey]
     * Supprime l'Empreinte Écologique du projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 08/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteEcoConceptionMavenKey(array $map): array
    {
        $sql = "DELETE FROM ma_moulinette.eco_conception WHERE maven_key = :maven_key";
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
     * [Description for selectEcoConception]
     * Retourne l'Empreinte Écologique d'un projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 08/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectEcoConception(array $map): array
    {
        $sql = "SELECT *
                FROM ma_moulinette.eco_conception
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
     * [Description for insertEcoConception]
     * Insère l'Empreinte Écologique d'un projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 08/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertEcoConception(array $map): array
    {
        $sql = "INSERT INTO ma_moulinette.eco_conception
                    (maven_key, eco_anomalies, eco_dette_minute, eco_rating,
                    eco_java_anomalies, eco_java_dette_minute, eco_java_rating,
                    eco_javascript_anomalies, eco_javascript_dette_minute, eco_javascript_rating,
                    eco_php_anomalies, eco_php_dette_minute, eco_php_rating,
                    eco_python_anomalies, eco_python_dette_minute, eco_python_rating,
                    mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES
                    (:maven_key, :eco_anomalies, :eco_dette_minute, :eco_rating,
                    :eco_java_anomalies, :eco_java_dette_minute, :eco_java_rating,
                    :eco_javascript_anomalies, :eco_javascript_dette_minute, :eco_javascript_rating,
                    :eco_php_anomalies, :eco_php_dette_minute, :eco_php_rating,
                    :eco_python_anomalies, :eco_python_dette_minute, :eco_python_rating,
                    :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(
                preg_replace(self::$removeReturnLine, ' ', $sql)
            );
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $stmt->bindValue(':eco_anomalies', $map['eco_anomalies']);
            $stmt->bindValue(':eco_dette_minute', $map['eco_dette_minute']);
            $stmt->bindValue(':eco_rating', $map['eco_rating']);
            $stmt->bindValue(':eco_java_anomalies', $map['eco_java_anomalies']);
            $stmt->bindValue(':eco_java_dette_minute', $map['eco_java_dette_minute']);
            $stmt->bindValue(':eco_java_rating', $map['eco_java_rating']);
            $stmt->bindValue(':eco_javascript_anomalies', $map['eco_javascript_anomalies']);
            $stmt->bindValue(':eco_javascript_dette_minute', $map['eco_javascript_dette_minute']);
            $stmt->bindValue(':eco_javascript_rating', $map['eco_javascript_rating']);
            $stmt->bindValue(':eco_php_anomalies', $map['eco_php_anomalies']);
            $stmt->bindValue(':eco_php_dette_minute', $map['eco_php_dette_minute']);
            $stmt->bindValue(':eco_php_rating', $map['eco_php_rating']);
            $stmt->bindValue(':eco_python_anomalies', $map['eco_python_anomalies']);
            $stmt->bindValue(':eco_python_dette_minute', $map['eco_python_dette_minute']);
            $stmt->bindValue(':eco_python_rating', $map['eco_python_rating']);
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
