<?php

namespace App\Repository\Main;

use App\Entity\Main\Properties;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description PropertiesRepository]
 */
class PropertiesRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Properties::class);
    }

    public function add(Properties $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Properties $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * [Description for getProperties]
     * Récupère la liste des properties
     * @param mixed $type
     *
     * @return array
     *
     * Created at: 27/10/2023 14:06:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProperties($type): array
    {
      $sql = "SELECT * FROM properties WHERE (:type)";
      $r=$this->getEntityManager()->getConnection()->prepare($sql);
      $r->bindValue(":type", $type);
      return  $r->executeQuery()->fetchAllAssociative();
    }

    /**
     * [Description for insertProperties]
     * Ajoute les properties
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 27/10/2023 15:14:39 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertProperties($map): array
    {

      $sql = "INSERT INTO properties
                (type, projet_bd, projet_sonar, profil_bd, profil_sonar,
                date_modification_projet, date_modification_profil, date_creation)
              VALUES
                ('properties', :projetBD, :projetSonar, :profilBD, :profilSonar,
                :projetModificationDate, :profilModificationDate, :dateCreationFormat)";
      $r=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
      $r->bindValue(":projetBD", $map["projetBD"]);
      $r->bindValue(":projetSonar", $map["projetSonar"]);
      $r->bindValue(":profilBD", $map["profilBD"]);
      $r->bindValue(":profilSonar", $map["profilSonar"]);
      $r->bindValue(":projetModificationDate", $map["projetModificationDate"]);
      $r->bindValue(":profilModificationDate", $map["profilModificationDate"]);
      $r->bindValue(":dateCreationFormat", $map["dateCreationFormat"]);
      return  $r->executeQuery()->fetchAllAssociative();
    }

    /**
     * [Description for updateProjetProperties]
     * Mise à jour des properties pour les projets
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 27/10/2023 15:23:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateProjetProperties($map): array
    {
      $sql = "UPDATE properties
              SET projet_bd = :bd, projet_sonar = :sonar, date_modification_projet = :dateModificationProjet
              WHERE type = :properties";
      $r=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
      $r->bindValue(":bd", $map["bd"]);
      $r->bindValue(":sonar", $map["sonar"]);
      $r->bindValue(":dateModificationProjet", $map["dateModificationProjet"]);
      $r->bindValue(":properties", "properties");
      return  $r->executeQuery()->fetchAllAssociative();
    }

    /**
     * [Description for updateProfilProperties]
     * Mise à jour des properties pour les profils
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 27/10/2023 15:23:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateProfilProperties($map): array
    {
      $sql = "UPDATE properties
              SET profil_bd = :bd, profil_sonar = :sonar, date_modification_profil = :dateModificationProfil
              WHERE type = :properties";
      $r=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
      $r->bindValue(":bd", $map["bd"]);
      $r->bindValue(":sonar", $map["sonar"]);
      $r->bindValue(":dateModificationProfil", $map["dateModificationProfil"]);
      $r->bindValue(":properties", "properties");
      return  $r->executeQuery()->fetchAllAssociative();
    }
}
