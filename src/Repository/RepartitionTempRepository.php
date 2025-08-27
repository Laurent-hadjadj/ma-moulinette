<?php

namespace App\Repository;

use App\Entity\RepartitionTemp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<RepartitionTemp>
 *
 * @method RepartitionTemp|null find($id, $lockMode = null, $lockVersion = null)
 * @method RepartitionTemp|null findOneBy(array $criteria, array $orderBy = null)
 * @method RepartitionTemp[]    findAll()
 * @method RepartitionTemp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RepartitionTempRepository extends ServiceEntityRepository
{
    private static $removeReturnLine = "/\s+/u";
    private static $noDataBase = 'La connexion à la base de données a échoué.';
    private static $mavenKey = ':maven_key';
    private static $setup = ':setup';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepartitionTemp::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array
     *
     * Created at: 14/02/2025 11:31:13 (Europe/Paris)
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
     * [Description for batchInsertIssuesSQL]
     *
     * @param array $issues
     *
     * @return array
     *
     * Created at: 14/02/2025 16:16:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function batchInsertIssuesSQL(array $issues): array
    {

        // Si $issues représente un enregistrement unique,
        // on le convertit en tableau d'enregistrements
        if (isset($issues['maven_key']) && !isset($issues[0])) {
            $issues = [$issues];
        }

        if (empty($issues)) {
            return ['code' => 404, 'erreur' => 'Aucunes données présentes dans la collection.'];
        }

        try {
                $batchSize = 500; // Taille optimale
                $connection = $this->getEntityManager()->getConnection();
                $sqlPrefix = "INSERT INTO ma_moulinette.repartition_temp (maven_key, component, type, severity, setup) VALUES ";
                $chunks = array_chunk($issues, $batchSize);

                foreach ($chunks as $chunk) {
                    $placeholders = [];
                    $params = [];
                    foreach ($chunk as $issue) {
                        if (!isset($issue['maven_key'], $issue['component'], $issue['category'], $issue['severity'], $issue['setup'])) {
                            continue;
                        }

                        $placeholders[] = "(?, ?, ?, ?, ?)";
                        $params[] = $issue['maven_key'];
                        $params[] = $issue['component'];
                        $params[] = $issue['category'];
                        $params[] = $issue['severity'];
                        $params[] = $issue['setup'];
                    }

                    if (empty($placeholders)) {
                        // To.do Mettre un logger
                        continue;
                    }

                    // Ajoute la clause ON CONFLICT pour ignorer les duplicata
                    $fullSql = $sqlPrefix . implode(", ", $placeholders);

                    $connection->executeStatement($fullSql, $params);
                }
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'erreur' => ''];
    }

    /**
     * [Description for deleteOldRecords]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 14/02/2025 18:34:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteOldRecords(array $map): array
    {
        if (!isset($map['maven_key'], $map['setup'])) {
            return ['code' => 400, 'erreur' => 'Paramètres manquants (deleteOldRecords)'];
        }

        $sqlCheck = "SELECT setup FROM ma_moulinette.repartition_temp
                        WHERE maven_key = :maven_key AND setup <> :setup
                        LIMIT 1";

        $sqlDelete = "DELETE
                        FROM ma_moulinette.repartition_temp
                        WHERE maven_key = :maven_key and setup <> :setup";
        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $stmtCheck = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sqlCheck));
                        $stmtCheck->bindValue(static::$mavenKey, $map['maven_key']);
                        $stmtCheck->bindValue(static::$setup, $map['setup']);
                    $result = $stmtCheck->executeQuery();
                    $existingId = $result->fetchOne();

                if ($existingId) {
                    $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sqlDelete));
                        $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                        $stmt->bindValue(static::$setup, $map['setup']);
                        $stmt->executeStatement();
                    }
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'erreur' => ''];
    }


    /**
     * [Description for selectRepartitionByTypeAndSeverity]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 12/02/2025 19:01:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectRepartitionByTypeAndSeverity(array $map): array
    {
        $sql = "SELECT *
                FROM ma_moulinette.repartition_temp
                WHERE maven_key = :maven_key
                AND type = :category
                AND severity = :severity
                AND setup = :setup";
        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                    $stmt->bindValue(':category', $map['category']);
                    $stmt->bindValue(':severity', $map['severity']);
                    $stmt->bindValue(static::$setup, $map['setup']);
                $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for checkExistData]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/08/2025 15:57:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function checkExistData(array $map): array
    {
        $sql = "SELECT count(*) AS total
                FROM ma_moulinette.repartition_temp
                WHERE maven_key = :maven_key
                AND type = :category
                AND severity = :severity
                AND setup = :setup";
        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$mavenKey, $map['maven_key']);
                    $stmt->bindValue(':category', $map['category']);
                    $stmt->bindValue(':severity', $map['severity']);
                    $stmt->bindValue(static::$setup, $map['setup']);
                $result = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'total' => $result['0']['total'], 'erreur' => ''];
    }
}
