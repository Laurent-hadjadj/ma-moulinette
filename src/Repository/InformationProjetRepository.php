<?php

namespace App\Repository;

use App\Entity\InformationProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method InformationProjet|null find($id, $lockMode = null, $lockVersion = null)
 * @method InformationProjet|null findOneBy(array $criteria, array $orderBy = null)
 * @method InformationProjet[]    findAll()
 * @method InformationProjet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InformationProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InformationProjet::class);
    }

    // /**
    //  * @return InformationProjet[] Returns an array of InformationProjet objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('i.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?InformationProjet
    {
        return $this->createQueryBuilder('i')
            ->andWhere('i.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
