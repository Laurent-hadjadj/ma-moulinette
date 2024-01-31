<?php

namespace App\Repository\Main;

use App\Entity\Main\Profiles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ProfilesRepository]
 */
class ProfilesRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profiles::class);
    }

    public function add(Profiles $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Profiles $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * [Description for countProfiles]
     * Compte le nombre total de profiles
     * @return array
     *
     * Created at: 27/10/2023 13:56:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countProfiles(): array
    {
        $sql = $sql = "SELECT COUNT(*) as total from profiles";
        return  $this->getEntityManager()->getConnection()->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

}
