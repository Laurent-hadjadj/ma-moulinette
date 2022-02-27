<?php

namespace App\Entity;

use App\Entity\Owasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @method Owasp|null find($id, $lockMode = null, $lockVersion = null)
 * @method Owasp|null findOneBy(array $criteria, array $orderBy = null)
 * @method Owasp[]    findAll()
 * @method Owasp[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class OwaspRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Owasp::class);
    }


}
