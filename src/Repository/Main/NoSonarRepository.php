<?php

namespace App\Repository\Main;

use App\Entity\Main\NoSonar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<NoSonar>
 *
 * @method NoSonar|null find($id, $lockMode = null, $lockVersion = null)
 * @method NoSonar|null findOneBy(array $criteria, array $orderBy = null)
 * @method NoSonar[]    findAll()
 * @method NoSonar[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class NoSonarRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoSonar::class);
    }

    public function add(NoSonar $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(NoSonar $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
