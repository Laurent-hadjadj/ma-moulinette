<?php

namespace App\Repository\Main;

use App\Entity\Main\AnomalieDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<AnomalieDetails>
 *
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

    public function add(AnomalieDetails $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(AnomalieDetails $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
