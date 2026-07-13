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

use App\Entity\DcProcessingQueue;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description DcProcessingQueueRepository]
 * MODIF 2026-05-08 : repository de la queue OWASP DependencyCheck.
 *
 * @extends ServiceEntityRepository<DcProcessingQueue>
 */
class DcProcessingQueueRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, DcProcessingQueue::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array<int|string, mixed>
     *
     * Created at: 08/05/2026 18:33:29 (Europe/Paris)
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
     * Récupère une ligne par son sha256 (idempotence : si la CI re-poste le
     * même rapport après un timeout, on renvoie le ulid existant sans
     * re-traiter).
     *
     * @param string $sha256
     *
     * @return DcProcessingQueue|null
     *
     * Created at: 08/05/2026 18:34:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findByPayloadSha256(string $sha256): ?DcProcessingQueue
    {
        return $this->findOneBy(['payloadSha256' => $sha256]);
    }

    /**
     * [Description for findByUlid]
     * Récupère une ligne par son ulid public.
     *
     * @param string $ulid
     *
     * @return DcProcessingQueue|null
     *
     * Created at: 08/05/2026 18:34:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findByUlid(string $ulid): ?DcProcessingQueue
    {
        return $this->findOneBy(['ulid' => $ulid]);
    }

    /**
     * [Description for claimNextBatch]
     *
     * Réclame le prochain lot a traiter pour le worker.
     *
     * Utilise `FOR UPDATE SKIP LOCKED` (PostgreSQL >= 9.5) pour permettre
     * a plusieurs workers concurrents de prendre des sous-ensembles disjoints
     * de la queue, sans contention.
     *
     * Le caller doit être dans une transaction. La méthode :
     *   1. lock les rows réclamées
     *   2. UPDATE status='processing', started_at=NOW(), attempts=attempts+1
     *   3. retourne les entities prêtes pour traitement
     *
     * @param int $batchSize
     *
     * @return DcProcessingQueue[]
     *
     * Created at: 08/05/2026 18:35:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function claimNextBatch(int $batchSize = 5): array
    {
        $em = $this->getEntityManager();
        $conn = $em->getConnection();

        // Selection avec lock - rows ignorées si deja lockées par un autre worker
        $sql = "
            SELECT id
            FROM ma_moulinette.dc_processing_queue
            WHERE status = :status
            ORDER BY created_at ASC
            LIMIT :batch_size
            FOR UPDATE SKIP LOCKED";

        $stmt = $conn->prepare($sql);
        $stmt->bindValue('status', DcProcessingQueue::STATUS_QUEUED);
        $stmt->bindValue('batch_size', $batchSize, ParameterType::INTEGER);
        $rows = $stmt->executeQuery()->fetchFirstColumn();

        if ($rows === []) {
            return [];
        }

        // Marque les rows en processing dans la meme transaction
        $now = new \DateTimeImmutable();
        $entities = [];
        foreach ($rows as $id) {
            $entity = $this->find((int) $id);
            if ($entity === null) {
                continue;
            }
            $entity->setStatus(DcProcessingQueue::STATUS_PROCESSING);
            $entity->setStartedAt($now);
            $entity->setAttempts($entity->getAttempts() + 1);
            $entities[] = $entity;
        }
        $em->flush();

        return $entities;
    }

    /**
     * [Description for reclaimStaleProcessing]
     *
     * Réclame les lignes "processing" abandonnées (worker mort).
     * A appeler en debut de chaque cron pour éviter les rows bloquées.
     *
     * @param int $timeoutSeconds
     *
     * @return int
     *
     * Created at: 08/05/2026 18:36:22 (Europe/Paris)
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
            ->setParameter('queued', DcProcessingQueue::STATUS_QUEUED)
            ->setParameter('processing', DcProcessingQueue::STATUS_PROCESSING)
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    /**
     * [Description for purgeTerminatedBefore]
     *
     * Purge les rows terminées (done | failed) plus vieilles que $threshold.
     *
     * On ne purge JAMAIS les status queued | processing :
     *  - queued : pourrait être une anomalie, mieux vaut investiguer
     *  - processing : un worker peut être en train de la traiter
     *
     * @param \DateTimeImmutable $threshold rows avec created_at < threshold sont purgées
     * @param bool $dryRun si true, retourne juste le compte sans supprimer
     *
     * @return int nombre de rows supprimées (ou candidates en mode dry-run)
     *
     * Created at: 08/05/2026 18:37:00 (Europe/Paris)
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
                ->setParameter('terminated', [DcProcessingQueue::STATUS_DONE, DcProcessingQueue::STATUS_FAILED])
                ->setParameter('threshold', $threshold)
                ->getQuery()
                ->getSingleScalarResult();
        }

        return $this->createQueryBuilder('q')
            ->delete()
            ->where('q.status IN (:terminated)')
            ->andWhere('q.createdAt < :threshold')
            ->setParameter('terminated', [DcProcessingQueue::STATUS_DONE, DcProcessingQueue::STATUS_FAILED])
            ->setParameter('threshold', $threshold)
            ->getQuery()
            ->execute();
    }

    /**
     * [Description for countByStatus]
     * Compte les lignes par statut (pour métrologie / point d'accès health).
     *
     * @return array<string, int>
     *
     * Created at: 08/05/2026 18:38:36 (Europe/Paris)
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
            DcProcessingQueue::STATUS_QUEUED     => 0,
            DcProcessingQueue::STATUS_PROCESSING => 0,
            DcProcessingQueue::STATUS_DONE       => 0,
            DcProcessingQueue::STATUS_FAILED     => 0,
        ];
        foreach ($rows as $row) {
            $counts[$row['status']] = (int) $row['nb'];
        }
        return $counts;
    }
}
