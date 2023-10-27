<?php

namespace App\Repository\Main;

use App\Entity\Main\ListeProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ListeProjetRepository]
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

    /**
     * [Description for countVisibility]
     * Execute une requête paramétrique count avec type= PRIVATE || PUBLIC
     * @param mixed $type
     *
     * @return array
     *
     * Created at: 27/10/2023 12:59:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countVisibility($type): array
    {
      $sql = "SELECT count(*) as visibility FROM liste_projet WHERE visibility=:visibility";
      $r=$this->getEntityManager()->getConnection()->prepare($sql);
      $r->bindValue(":visibility", $type);
      return  $r->executeQuery()->fetchAllAssociative();
    }

    /**
     * [Description for countProjet]
     * Compte le nombre total de projet.
     * @return array
     *
     * Created at: 27/10/2023 13:54:53 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countProjet(): array
    {
      $sql = $sql = "SELECT COUNT(*) as total from liste_projet";
      return  $this->getEntityManager()->getConnection()->prepare($sql)->executeQuery()->fetchAllAssociative();
    }

}
