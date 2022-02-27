<?php

namespace App\Repository;

use App\Entity\InformationProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
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

}
