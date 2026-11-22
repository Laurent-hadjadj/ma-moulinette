<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Repository;

use App\Entity\NpmProcessingQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description NpmProcessingQueueRepository]
 * Repository de la queue npm audit — même pattern que DcProcessingQueueRepository.
 *
 * @extends ServiceEntityRepository<NpmProcessingQueue>
 */
class NpmProcessingQueueRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NpmProcessingQueue::class);
    }

    /**
     * @return array<int|string, mixed>
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
     * Récupère une ligne par son sha256 (idempotence : si la CI re-poste le
     * même rapport après un timeout, on renvoie le ulid existant sans
     * re-traiter).
     */
    public function findByPayloadSha256(string $sha256): ?NpmProcessingQueue
    {
        return $this->findOneBy(['payloadSha256' => $sha256]);
    }

    /**
     * Récupère une ligne par son ulid public.
     */
    public function findByUlid(string $ulid): ?NpmProcessingQueue
    {
        return $this->findOneBy(['ulid' => $ulid]);
    }

    /**
     * Réclame le prochain lot à traiter pour le worker (FOR UPDATE SKIP
     * LOCKED, le caller doit être dans une transaction).
     *
     * @return NpmProcessingQueue[]
     */
    public function claimNextBatch(int $batchSize = 5): array
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        $sql = "
            SELECT id
            FROM ma_moulinette.npm_processing_queue
            WHERE status = :status
            ORDER BY created_at ASC
            LIMIT :batch_size
            FOR UPDATE SKIP LOCKED";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('status', NpmProcessingQueue::STATUS_QUEUED);
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
            $entity->setStatus(NpmProcessingQueue::STATUS_PROCESSING);
            $entity->setStartedAt($now);
            $entity->setAttempts($entity->getAttempts() + 1);
            $entities[] = $entity;
        }
        $em->flush();

        return $entities;
    }

    /**
     * Réclame les lignes "processing" abandonnées (worker mort).
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
            ->setParameter('queued', NpmProcessingQueue::STATUS_QUEUED)
            ->setParameter('processing', NpmProcessingQueue::STATUS_PROCESSING)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    /**
     * Purge les rows terminées (done|failed) plus vieilles que $threshold.
     * Ne purge jamais queued|processing.
     */
    public function purgeTerminatedBefore(\DateTimeImmutable $threshold, bool $dryRun = false): int
    {
        if ($dryRun) {
            return (int) $this->createQueryBuilder('q')
                ->select('COUNT(q.id)')
                ->where('q.status IN (:terminated)')
                ->andWhere('q.createdAt < :threshold')
                ->setParameter('terminated', [NpmProcessingQueue::STATUS_DONE, NpmProcessingQueue::STATUS_FAILED])
                ->setParameter('threshold', $threshold)
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->createQueryBuilder('q')
            ->delete()
            ->where('q.status IN (:terminated)')
            ->andWhere('q.createdAt < :threshold')
            ->setParameter('terminated', [NpmProcessingQueue::STATUS_DONE, NpmProcessingQueue::STATUS_FAILED])
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    /**
     * Compte les lignes par statut (métrologie).
     *
     * @return array<string, int>
     */
    public function countByStatus(): array
    {
        $rows = $this->createQueryBuilder('q')
            ->select('q.status, COUNT(q.id) AS nb')
            ->groupBy('q.status')
            ->getQuery()
            ->getArrayResult();

        $counts = [
            NpmProcessingQueue::STATUS_QUEUED     => 0,
            NpmProcessingQueue::STATUS_PROCESSING => 0,
            NpmProcessingQueue::STATUS_DONE       => 0,
            NpmProcessingQueue::STATUS_FAILED     => 0,
        ];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['nb'];
        }
        return $counts;
    }
}
