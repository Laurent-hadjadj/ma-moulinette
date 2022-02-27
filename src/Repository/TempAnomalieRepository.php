<?php

namespace App\Repository;

use App\Entity\TempAnomalie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method TempAnomalie|null find($id, $lockMode = null, $lockVersion = null)
 * @method TempAnomalie|null findOneBy(array $criteria, array $orderBy = null)
 * @method TempAnomalie[]    findAll()
 * @method TempAnomalie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TempAnomalieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TempAnomalie::class);
    }

}
