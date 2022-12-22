<?php

namespace App\Repository\Main;

use App\Entity\Main\HotspotDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HotspotDetails>
 *
 * @method HotspotDetails|null find($id, $lockMode = null, $lockVersion = null)
 * @method HotspotDetails|null findOneBy(array $criteria, array $orderBy = null)
 * @method HotspotDetails[]    findAll()
 * @method HotspotDetails[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HotspotDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotspotDetails::class);
    }

    public function add(HotspotDetails $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HotspotDetails $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
