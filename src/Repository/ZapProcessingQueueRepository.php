<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Repository;

use App\Entity\ZapProcessingQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ZapProcessingQueueRepository]
 * MODIF 2026-10-13 : repository de la queue ZAP (DAST), sur le gabarit
 * DcProcessingQueueRepository.
 *
 * @extends ServiceEntityRepository<ZapProcessingQueue>
 */
class ZapProcessingQueueRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZapProcessingQueue::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array<int|string, mixed>
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
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
     * [Description for findByPayloadSha256]
     * Récupère une ligne par son sha256 (idempotence).
     *
     * @param string $sha256
     *
     * @return ZapProcessingQueue|null
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findByPayloadSha256(string $sha256): ?ZapProcessingQueue
    {
        return $this->findOneBy(['payloadSha256' => $sha256]);
    }

    /**
     * [Description for findByUlid]
     * Récupère une ligne par son ulid public.
     *
     * @param string $ulid
     *
     * @return ZapProcessingQueue|null
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findByUlid(string $ulid): ?ZapProcessingQueue
    {
        return $this->findOneBy(['ulid' => $ulid]);
    }

    /**
     * [Description for claimNextBatch]
     *
     * Réclame le prochain lot a traiter pour le worker.
     * Utilise `FOR UPDATE SKIP LOCKED` (PostgreSQL >= 9.5) pour permettre
     * a plusieurs workers concurrents de prendre des sous-ensembles disjoints
     * de la queue, sans contention. Le caller doit être dans une transaction.
     *
     * @param int $batchSize
     *
     * @return ZapProcessingQueue[]
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function claimNextBatch(int $batchSize = 5): array
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $sql = "
            SELECT id
            FROM ma_moulinette.zap_processing_queue
            WHERE status = :status
            ORDER BY created_at ASC
            LIMIT :batch_size
            FOR UPDATE SKIP LOCKED";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('status', ZapProcessingQueue::STATUS_QUEUED);
        $stmt->bindValue('batch_size', $batchSize, ParameterType::INTEGER);
        $rows = $stmt->executeQuery()->fetchFirstColumn();

        if ($rows === []) {
            return [];
        }

        $now = new \DateTimeImmutable();
        $entities = [];
        foreach ($rows as $id) {
            $entity = $this->find((int) $id);
            if ($entity === null) {
                continue;
            }
            $entity->setStatus(ZapProcessingQueue::STATUS_PROCESSING);
            $entity->setStartedAt($now);
            $entity->setAttempts($entity->getAttempts() + 1);
            $entities[] = $entity;
        }
        $em->flush();

        return $entities;
    }

    /**
     * [Description for reclaimStaleProcessing]
     * Réclame les lignes "processing" abandonnées (worker mort).
     *
     * @param int $timeoutSeconds
     *
     * @return int
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function reclaimStaleProcessing(int $timeoutSeconds = 300): int
    {
        $threshold = (new \DateTimeImmutable())->modify("-{$timeoutSeconds} seconds");

        return $this->createQueryBuilder('q')
            ->update()
            ->set('q.status', ':queued')
            ->set('q.startedAt', 'NULL')
            ->where('q.status = :processing')
            ->andWhere('q.startedAt < :threshold')
            ->setParameter('queued', ZapProcessingQueue::STATUS_QUEUED)
            ->setParameter('processing', ZapProcessingQueue::STATUS_PROCESSING)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    /**
     * [Description for purgeTerminatedBefore]
     * Purge les rows terminées (done | failed) plus vieilles que $threshold.
     *
     * @param \DateTimeImmutable $threshold
     * @param bool $dryRun
     *
     * @return int
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function purgeTerminatedBefore(\DateTimeImmutable $threshold, bool $dryRun = false): int
    {
        if ($dryRun) {
            return (int) $this->createQueryBuilder('q')
                ->select('COUNT(q.id)')
                ->where('q.status IN (:terminated)')
                ->andWhere('q.createdAt < :threshold')
                ->setParameter('terminated', [ZapProcessingQueue::STATUS_DONE, ZapProcessingQueue::STATUS_FAILED])
                ->setParameter('threshold', $threshold)
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->createQueryBuilder('q')
            ->delete()
            ->where('q.status IN (:terminated)')
            ->andWhere('q.createdAt < :threshold')
            ->setParameter('terminated', [ZapProcessingQueue::STATUS_DONE, ZapProcessingQueue::STATUS_FAILED])
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    /**
     * [Description for countByStatus]
     * Compte les lignes par statut.
     *
     * @return array<string, int>
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('q')
            ->select('q.status, COUNT(q.id) AS nb')
            ->groupBy('q.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [
            ZapProcessingQueue::STATUS_QUEUED     => 0,
            ZapProcessingQueue::STATUS_PROCESSING => 0,
            ZapProcessingQueue::STATUS_DONE       => 0,
            ZapProcessingQueue::STATUS_FAILED     => 0,
        ];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['nb'];
        }
        return $counts;
    }
}
