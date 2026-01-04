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

use App\Entity\UserAgentAnalysis;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAgentAnalysis>
 */
class UserAgentAnalysisRepository extends ServiceEntityRepository
{
    public static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAgentAnalysis::class);
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
     * [Description for insertUserAgentAnalysis]
     * Insertion du résultat d'analyse Matomo
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 14/12/2025 18:12:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertUserAgentAnalysis(array $map): array
    {
        $sql = "INSERT INTO assistant_ia.user_agent_analysis
                    (user_agent_event_id, event_type, url, session_id,
                    device_type, os_name, os_version,
                    browser_name, browser_version, is_bot,
                    detector_version, created_at)
                VALUES
                    (:event_id, :event_type, :url, :session_id,
                    :device_type, :os_name, :os_version,
                    :browser_name, :browser_version, :is_bot,
                    :detector_version, :created_at)";

        $conn = $this->getEntityManager()->getConnection();

        try {
            $conn->beginTransaction();

                $stmt = $conn->prepare($sql);
                $stmt->bindValue(':event_id', $map['user_agent_event_id']);
                $stmt->bindValue(':event_type', $map['event_type']);
                $stmt->bindValue(':url', $map['url']);
                $stmt->bindValue(':session_id', $map['session_id']);
                $stmt->bindValue(':device_type', $map['device_type']);
                $stmt->bindValue(':os_name', $map['os_name']);
                $stmt->bindValue(':os_version', $map['os_version']);
                $stmt->bindValue(':browser_name', $map['browser_name']);
                $stmt->bindValue(':browser_version', $map['browser_version']);
                $stmt->bindValue(':is_bot', $map['is_bot'] ? 'true' : 'false', );
                $stmt->bindValue(':detector_version', $map['detector_version']);
                $stmt->bindValue(':created_at', $map['created_at']->format('Y-m-d H:i:sO'));

            $stmt->executeStatement();
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'error' => ''];
    }

}
