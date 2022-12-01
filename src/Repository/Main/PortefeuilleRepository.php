<?php

namespace App\Repository\Main;

use App\Entity\Main\Portefeuille;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Portefeuille>
 *
 * @method Portefeuille|null find($id, $lockMode = null, $lockVersion = null)
 * @method Portefeuille|null findOneBy(array $criteria, array $orderBy = null)
 * @method Portefeuille[]    findAll()
 * @method Portefeuille[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class PortefeuilleRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portefeuille::class);
    }

    public function save(Portefeuille $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Portefeuille $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
