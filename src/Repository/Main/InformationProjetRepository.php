<?php

namespace App\Repository\Main;

use App\Entity\Main\InformationProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<InformationProjet>
 *
 * @method InformationProjet|null find($id, $lockMode = null, $lockVersion = null)
 * @method InformationProjet|null findOneBy(array $criteria, array $orderBy = null)
 * @method InformationProjet[]    findAll()
 * @method InformationProjet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class InformationProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InformationProjet::class);
    }

    public function add(InformationProjet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(InformationProjet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }


}
