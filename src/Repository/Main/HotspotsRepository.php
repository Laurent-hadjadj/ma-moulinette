<?php

namespace App\Repository\Main;

use App\Entity\Main\Hotspots;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Hotspots>
 *
 * @method Hotspots|null find($id, $lockMode = null, $lockVersion = null)
 * @method Hotspots|null findOneBy(array $criteria, array $orderBy = null)
 * @method Hotspots[]    findAll()
 * @method Hotspots[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HotspotsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hotspots::class);
    }

    public function add(Hotspots $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Hotspots $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
