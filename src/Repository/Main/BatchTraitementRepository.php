<?php

namespace App\Repository\Main;

use App\Entity\Main\BatchTraitement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BatchTraitement>
 *
 * @method BatchTraitement|null find($id, $lockMode = null, $lockVersion = null)
 * @method BatchTraitement|null findOneBy(array $criteria, array $orderBy = null)
 * @method BatchTraitement[]    findAll()
 * @method BatchTraitement[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class BatchTraitementRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatchTraitement::class);
    }

    public function save(BatchTraitement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(BatchTraitement $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
