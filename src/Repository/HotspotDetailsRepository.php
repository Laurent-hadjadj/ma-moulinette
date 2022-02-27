<?php

namespace App\Repository;

use App\Entity\HotspotDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HotspotDetails|null find($id, $lockMode = null, $lockVersion = null)
 * @method HotspotDetails|null findOneBy(array $criteria, array $orderBy = null)
 * @method HotspotDetails[]    findAll()
 * @method HotspotDetails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HotspotDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotspotDetails::class);
    }


}
