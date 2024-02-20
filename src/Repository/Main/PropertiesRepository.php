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
     *
     * @param string $mode
     * @param string $type
     *
     * @return array
     *
     * Created at: 27/10/2023 14:06:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProperties($mode, $type): array
    {
      $sql = "SELECT *
              FROM properties
              WHERE type=:type";
      $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
      $conn->bindValue(':type', $type);
      try {
        if ($mode !== 'TEST') {
            $request=$conn->executeQuery()->fetchAllAssociative();
        } else {
            return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
        }
      } catch (\Doctrine\DBAL\Exception $e) {
          return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
      }
      return ['mode'=>$mode, 'code'=>200, 'request'=>$request, 'erreur'=>''];
    }

    /**
     * [Description for insertProperties]
     * Ajoute les properties
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/10/2023 15:14:39 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertProperties($mode, $map): array
    {
      $sql = "INSERT INTO properties
                (type,
                  projet_bd,
                  projet_sonar,
                  profil_bd,
                  profil_sonar,
                  date_modification_projet,
                  date_modification_profil,
                  date_creation)
              VALUES
                ('properties',
                  :projet_bd,
                  :projet_sonar,
                  :profil_bd,
                  :profil_sonar,
                  :date_modification_projet,
                  :date_modification_profil,
                  :date_creation)";
      $r=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
      $r->bindValue(":projet_bd", $map["projet_bd"]);
      $r->bindValue(":projet_sonar", $map["projet_sonar"]);
      $r->bindValue(":profil_bd", $map["profil_bd"]);
      $r->bindValue(":profil_sonar", $map["profil_sonar"]);
      $r->bindValue(":date_modification_projet", $map["date_modification_projet"]);
      $r->bindValue(":date_modification_profil", $map["date_modification_profil"]);
      $r->bindValue(":date_creation", $map["date_creation"]);
      try {
        if ($mode !== 'TEST') {
            $r->executeQuery();
        } else {
            return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
        }
      } catch (\Doctrine\DBAL\Exception $e) {
          return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
      }
      return ['mode'=>$mode, 'code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for updatePropertiesProjet]
     * Mise à jour des properties pour les projets
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/10/2023 15:23:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updatePropertiesProjet($mode, $map): array
    {
      $sql = "UPDATE properties
              SET projet_bd = :projet_bd,
                  projet_sonar = :projet_sonar,
                  date_modification_projet = :date_modification_projet
              WHERE type = :properties";
      $r=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
      $r->bindValue(":projet_bd", $map["projet_bd"]);
      $r->bindValue(":projet_sonar", $map["projet_sonar"]);
      $r->bindValue(":date_modification_projet", $map["date_modification_projet"]);
      $r->bindValue(":properties", "properties");
      try {
        if ($mode !== 'TEST') {
            $r->executeQuery();
          } else {
              return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
          }
      } catch (\Doctrine\DBAL\Exception $e) {
        return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
      }
      return ['mode'=>$mode, 'code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for updatePropertiesProfiles]
     * Mise à jour des properties pour les profils
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/10/2023 15:23:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updatePropertiesProfiles($mode, $map): array
    {
      $sql = "UPDATE properties
              SET profil_bd=:profil_bd,
                  profil_sonar=:profil_sonar,
                  date_modification_profil=:date_modification_profil
              WHERE type=:properties";
      $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
      $conn->bindValue(':profil_bd', $map['profil_bd']);
      $conn->bindValue(':profil_sonar', $map['profil_sonar']);
      $conn->bindValue(':date_modification_profil', $map['date_modification_profil']);
      $conn->bindValue(':properties', 'properties');
      try {
        if ($mode !== 'TEST') {
            $conn->executeQuery();
        } else {
            return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
        }
    } catch (\Doctrine\DBAL\Exception $e) {
        return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
    }
    return ['mode'=>$mode, 'code'=>200, 'erreur'=>''];
    }
}
