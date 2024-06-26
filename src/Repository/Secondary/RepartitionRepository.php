<?php

namespace App\Repository\Secondary;

use App\Entity\Secondary\Repartition;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Repartition>
 *
 * @method Repartition|null find($id, $lockMode = null, $lockVersion = null)
 * @method Repartition|null findOneBy(array $criteria, array $orderBy = null)
 * @method Repartition[]    findAll()
 * @method Repartition[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class RepartitionRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Repartition::class);
    }

    public function add(Repartition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Repartition $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
