<?php

namespace App\Repository\Main;

use App\Entity\Main\Anomalie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Anomalie>
 *
 * @method Anomalie|null find($id, $lockMode = null, $lockVersion = null)
 * @method Anomalie|null findOneBy(array $criteria, array $orderBy = null)
 * @method Anomalie[]    findAll()
 * @method Anomalie[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class AnomalieRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anomalie::class);
    }

    public function add(Anomalie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Anomalie $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
