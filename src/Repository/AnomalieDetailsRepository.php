<?php

namespace App\Repository;

use App\Entity\AnomalieDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method AnomalieDetails|null find($id, $lockMode = null, $lockVersion = null)
 * @method AnomalieDetails|null findOneBy(array $criteria, array $orderBy = null)
 * @method AnomalieDetails[]    findAll()
 * @method AnomalieDetails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnomalieDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnomalieDetails::class);
    }

    // /**
    //  * @return AnomalieDetails[] Returns an array of AnomalieDetails objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('a.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?AnomalieDetails
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
