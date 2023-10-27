<?php

namespace App\Repository\Main;

use App\Entity\Main\MaMoulinette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description MaMoulinetteRepository]
 */
class MaMoulinetteRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaMoulinette::class);
    }

    public function add(MaMoulinette $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(MaMoulinette $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * [Description for getVersion]
     * Récupère la version de Ma Moulinette
     * @return array
     *
     * Created at: 27/10/2023 15:45:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVersion(): array
    {
      $sql = "SELECT version
      FROM ma_moulinette
      ORDER BY date_version DESC LIMIT 1";
      $select=$this->getEntityManager()->getConnection()->prepare($sql);
      return  $select->executeQuery()->fetchAllAssociative();
  }

}
