<?php

namespace App\Repository;

use App\Entity\ListeProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
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

}
