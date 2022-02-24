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

    // /**
    //  * @return TempAnomalie[] Returns an array of TempAnomalie objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('t.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?TempAnomalie
    {
        return $this->createQueryBuilder('t')
            ->andWhere('t.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
