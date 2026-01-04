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

use App\Entity\UserAgentEvent;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserAgentEvent>
 */
class UserAgentEventRepository extends ServiceEntityRepository
{
    public static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserAgentEvent::class);
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
     * [Description for insertUserAgentEvent]
     * Insertion d'un événement User-Agent (collecte brute)
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 14/12/2025 18:05:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertUserAgentEvent(array $map): array
    {
        $sql = "INSERT INTO assistant_ia.user_agent_event
                  (event_type, url, user_agent, session_id, user_id, auth_state,
                  processing_status, ip_hash, created_at)
                VALUES
                  (:event_type, :url, :user_agent, :session_id, :user_id,
                  :auth_state, :processing_status, :ip_hash, :created_at)";

        $conn = $this->getEntityManager()->getConnection();

        try {
            $conn->beginTransaction();

            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':event_type', $map['event_type']);
            $stmt->bindValue(':url', $map['url']);
            $stmt->bindValue(':user_agent', $map['user_agent']);
            $stmt->bindValue(':session_id', $map['session_id']);
            $stmt->bindValue(':user_id', $map['user_id']);
            $stmt->bindValue(':auth_state', $map['auth_state']);
            $stmt->bindValue(':processing_status', $map['processing_status']);
            $stmt->bindValue(':ip_hash', $map['ip_hash']);
            $stmt->bindValue(':created_at', $map['created_at']->format('Y-m-d H:i:sO'));

            $stmt->executeStatement();
            $conn->commit();
        } catch (\Throwable $e) {
            $conn->rollBack();
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'error' => ''];
    }

    /**
     * [Description for selectPendingEvents]
     * Sélection d'événements à analyser (batch)
     *
     * @param int $limit
     *
     * @return array
     *
     * Created at: 14/12/2025 18:05:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectPendingEvents(int $limit = 20): array
    {
        $sql = "SELECT *
                FROM assistant_ia.user_agent_event
                WHERE processing_status = 'PENDING'
                ORDER BY created_at ASC
                LIMIT :limit";

        $conn = $this->getEntityManager()->getConnection();

        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':limit', $limit,);
            $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $liste, 'error' => ''];
    }

    /**
     * [Description for updateProcessingStatus]
     * Mise à jour du statut de traitement d'un événement
     *
     * @param int $id
     * @param string $status
     * @param \DateTimeImmutable|null $processedAt
     *
     * @return array
     *
     * Created at: 14/12/2025 18:05:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateProcessingStatus(
        int $id,
        string $status,
        ?\DateTimeImmutable $processedAt = null
    ): array {

        $sql = "UPDATE assistant_ia.user_agent_event
                SET processing_status = :status,
                    processed_at = :processed_at
                WHERE id = :id";

        $conn = $this->getEntityManager()->getConnection();
        try {
            $stmt = $conn->prepare($sql);
            $stmt->bindValue(':status', $status);
            $stmt->bindValue(':processed_at', $processedAt?->format('Y-m-d H:i:sO'));
            $stmt->bindValue(':id', $id);
            $stmt->executeStatement();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'error' => ''];
    }
}
