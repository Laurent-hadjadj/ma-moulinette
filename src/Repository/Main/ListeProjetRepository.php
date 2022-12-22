<?php

namespace App\Repository\Main;

use App\Entity\Main\ListeProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ListeProjet>
 *
 * @method ListeProjet|null find($id, $lockMode = null, $lockVersion = null)
 * @method ListeProjet|null findOneBy(array $criteria, array $orderBy = null)
 * @method ListeProjet[]    findAll()
 * @method ListeProjet[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ListeProjetRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeProjet::class);
    }

    public function add(ListeProjet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(ListeProjet $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
