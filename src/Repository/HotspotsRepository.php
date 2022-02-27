<?php

namespace App\Repository;

use App\Entity\Hotspots;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Hotspots|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hotspots|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hotspots[]    findAll()
 * @method Hotspots[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HotspotsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hotspots::class);
    }

}
