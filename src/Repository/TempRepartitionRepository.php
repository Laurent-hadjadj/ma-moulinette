<?php

namespace App\Repository;

use App\Entity\TempRepartition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TempRepartition>
 *
 * @method TempRepartition|null find($id, $lockMode = null, $lockVersion = null)
 * @method TempRepartition|null findOneBy(array $criteria, array $orderBy = null)
 * @method TempRepartition[]    findAll()
 * @method TempRepartition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class TempRepartitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TempRepartition::class);
    }

    public function add(TempRepartition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(TempRepartition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
