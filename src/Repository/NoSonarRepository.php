<?php

namespace App\Repository;

use App\Entity\NoSonar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method NoSonar|null find($id, $lockMode = null, $lockVersion = null)
 * @method NoSonar|null findOneBy(array $criteria, array $orderBy = null)
 * @method NoSonar[]    findAll()
 * @method NoSonar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoSonarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoSonar::class);
    }


}
