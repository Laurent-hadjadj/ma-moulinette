<?php

namespace App\Repository\Main;

use App\Entity\Main\HotspotOwasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<HotspotOwasp>
 *
 * @method HotspotOwasp|null find($id, $lockMode = null, $lockVersion = null)
 * @method HotspotOwasp|null findOneBy(array $criteria, array $orderBy = null)
 * @method HotspotOwasp[]    findAll()
 * @method HotspotOwasp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class HotspotOwaspRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotspotOwasp::class);
    }

    public function add(HotspotOwasp $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(HotspotOwasp $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
