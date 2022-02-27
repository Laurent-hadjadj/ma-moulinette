<?php

namespace App\Repository;

use App\Entity\Profiles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Profiles|null find($id, $lockMode = null, $lockVersion = null)
 * @method Profiles|null findOneBy(array $criteria, array $orderBy = null)
 * @method Profiles[]    findAll()
 * @method Profiles[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ProfilesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profiles::class);
    }


}
