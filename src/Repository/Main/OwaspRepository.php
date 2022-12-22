<?php

namespace App\Repository\Main;

use App\Entity\Main\Owasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Owasp>
 *
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

    public function add(Owasp $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Owasp $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

}
