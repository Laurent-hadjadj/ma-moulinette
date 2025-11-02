<?php

namespace App\Repository;

use App\Entity\BatchProfiling;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class BatchProfilingRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatchProfiling::class);
    }

    public function save(BatchProfiling $profiling): void
    {
        $em = $this->getEntityManager();
        $em->persist($profiling);
        $em->flush();
    }
}
