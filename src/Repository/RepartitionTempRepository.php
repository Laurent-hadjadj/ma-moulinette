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

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, RepartitionTemp::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Doctrine\DBAL\Exception $e
     *
     * @return array
     *
     * Created at: 14/02/2025 11:31:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
    {
        $message = $e->getMessage();

        if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
            $message = static::$noDataBase;
        }

        // Récupération de l'exception précédente qui contient le SQLState
        $previousException = $e->getPrevious();

        if ($previousException instanceof \Doctrine\DBAL\Driver\Exception) {
            $sqlState = $previousException->getSQLState();

            // Violation de contrainte NOT NULL
            if ($sqlState === '23502') {
                return ['code' =>'23502', 'erreur' => $e->getMessage()];
            }

            // Violation de contrainte UNIQUE
            if ($sqlState === '23505') {
                return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
            }
        }

        return ['code' => 500, 'erreur'=> $message];
    }

    /**
     * [Description for handleErrorException]
     *
     * @param \ErrorException $e
     *
     * @return array
     *
     * Created at: 15/02/2025 22:05:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function handleErrorException(\ErrorException $e): array
    {
        $message = $e->getMessage();
        return ['code' => 500, 'erreur'=> $message];
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
                        if (!isset($issue['maven_key'], $issue['component'], $issue['type'], $issue['severity'], $issue['setup'])) {
                            continue;
                        }
                        $placeholders[] = "(?, ?, ?, ?, ?)";
                        $params[] = $issue['maven_key'];
                        $params[] = $issue['component'];
                        $params[] = $issue['type'];
                        $params[] = $issue['severity'];
                        $params[] = $issue['setup'];
                    }
                    // Ajoute la clause ON CONFLICT pour ignorer les duplicata
                    $fullSql = $sqlPrefix . implode(", ", $placeholders);

                    $connection->executeStatement($fullSql, $params);
                }
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
        } catch(\ErrorException $e){
            return $this->handleErrorException($e);
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
                // Vérification d'existence
                $stmtCheck = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sqlCheck));
                    $stmtCheck->bindValue(':maven_key', $map['maven_key']);
                    $stmtCheck->bindValue(':setup', $map['setup']);
                $result = $stmtCheck->executeQuery();
                $existingId = $result->fetchOne();

                if ($existingId) {
                    $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sqlDelete));
                        $stmt->bindValue(':maven_key', $map['maven_key']);
                        $stmt->bindValue(':setup', $map['setup']);
                        $stmt->executeStatement();
                    }
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
        } catch(\ErrorException $e){
            return $this->handleErrorException($e);
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
                AND type = :type
                AND severity = :severity
                AND setup = :setup";
        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(':maven_key', $map['maven_key']);
                    $stmt->bindValue(':type', $map['type']);
                    $stmt->bindValue(':severity', $map['severity']);
                    $stmt->bindValue(':setup', $map['setup']);
                $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
        } catch(\ErrorException $e){
            return $this->handleErrorException($e);
        }
        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

}
