<?php

namespace App\Repository;

use App\Entity\HotspotOwasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HotspotOwasp|null find($id, $lockMode = null, $lockVersion = null)
 * @method HotspotOwasp|null findOneBy(array $criteria, array $orderBy = null)
 * @method HotspotOwasp[]    findAll()
 * @method HotspotOwasp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HotspotOwaspRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotspotOwasp::class);
    }


}
