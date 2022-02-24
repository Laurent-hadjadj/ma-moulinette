<?php

namespace App\Entity;

use App\Entity\Hotspots;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Hotspots|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hotspots|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hotspots[]    findAll()
 * @method Hotspots[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OwaspRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hotspots::class);
    }

    // /**
    //  * @return Hotspots[] Returns an array of Hotspots objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('h.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?Hotspots
    {
        return $this->createQueryBuilder('h')
            ->andWhere('h.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
