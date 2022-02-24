<?php

namespace App\Entity;

use App\Entity\HotspotOwasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method HotspotOwasp|null find($id, $lockMode = null, $lockVersion = null)
 * @method HotspotOwasp|null findOneBy(array $criteria, array $orderBy = null)
 * @method HotspotOwasp[]    findAll()
 * @method HotspotOwasp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HotpsotOwaspRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotspotOwasp::class);
    }

    // /**
    //  * @return HotspotOwasp[] Returns an array of HotspotOwasp objects
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
    public function findOneBySomeField($value): ?HotspotOwasp
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
