<?php

namespace App\Repository\Main;

use App\Entity\Main\MaMoulinette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<MaMoulinette>
 *
 * @method MaMoulinette|null find($id, $lockMode = null, $lockVersion = null)
 * @method MaMoulinette|null findOneBy(array $criteria, array $orderBy = null)
 * @method MaMoulinette[]    findAll()
 * @method MaMoulinette[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class MaMoulinetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaMoulinette::class);
    }

    public function add(MaMoulinette $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MaMoulinette $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

//    /**
//     * @return MaMoulinette[] Returns an array of MaMoulinette objects
//     */
//    public function findByExampleField($value): array
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->orderBy('m.id', 'ASC')
//            ->setMaxResults(10)
//            ->getQuery()
//            ->getResult()
//        ;
//    }

//    public function findOneBySomeField($value): ?MaMoulinette
//    {
//        return $this->createQueryBuilder('m')
//            ->andWhere('m.exampleField = :val')
//            ->setParameter('val', $value)
//            ->getQuery()
//            ->getOneOrNullResult()
//        ;
//    }
}
