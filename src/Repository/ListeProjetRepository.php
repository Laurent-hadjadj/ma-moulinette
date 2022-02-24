<?php

namespace App\Repository;

use App\Entity\ListeProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method ListeProjet|null find($id, $lockMode = null, $lockVersion = null)
 * @method ListeProjet|null findOneBy(array $criteria, array $orderBy = null)
 * @method ListeProjet[]    findAll()
 * @method ListeProjet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ListeProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeProjet::class);
    }

    // /**
    //  * @return ListeProjet[] Returns an array of ListeProjet objects
    //  */
    /*
    public function findByExampleField($value)
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('l.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }
    */

    /*
    public function findOneBySomeField($value): ?ListeProjet
    {
        return $this->createQueryBuilder('l')
            ->andWhere('l.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
