<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Repository;

use App\Entity\Properties;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description PropertiesRepository]
 */
class PropertiesRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $stmtegistry)
    {
        parent::__construct($stmtegistry, Properties::class);
    }

    /**
     * [Description for getProperties]
     * Récupère la liste des properties
     *
     * @param string $type
     *
     * @return array
     *
     * Created at: 27/10/2023 14:06:11 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProperties($type): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT *
                        FROM properties
                        WHERE type=:type";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':type', $type);
                    $stmtequest = $stmt->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
            return ['code' => 200, 'request' => $stmtequest, 'erreur' => ''];
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code' => 500, 'erreur' => $e->getMessage()];
        }
    }

    /**
     * [Description for insertProperties]
     * Ajoute les properties
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/10/2023 15:14:39 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertProperties($map): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "INSERT INTO properties (
                    type,
                    projet_bd, projet_sonar,
                    profil_bd, profil_sonar,
                    date_modification_projet,
                    date_modification_profil,
                    date_creation)
                VALUES (
                    :type,
                    :projet_bd, :projet_sonar,
                    :profil_bd, :profil_sonar,
                    :date_modification_projet,
                    :date_modification_profil,
                    :date_creation)";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(":type", 'properties');
                    $stmt->bindValue(":projet_bd", $map["projet_bd"]);
                    $stmt->bindValue(":projet_sonar", $map["projet_sonar"]);
                    $stmt->bindValue(":profil_bd", $map["profil_bd"]);
                    $stmt->bindValue(":profil_sonar", $map["profil_sonar"]);
                    $stmt->bindValue(":date_modification_projet", $map["date_modification_projet"]->format('Y-m-d H:i:sO'));
                    $stmt->bindValue(":date_modification_profil", $map["date_modification_profil"]->format('Y-m-d H:i:sO'));
                    $stmt->bindValue(":date_creation", $map["date_creation"]);
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for updatePropertiesProjet]
     * Mise à jour des properties pour les projets
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/10/2023 15:23:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updatePropertiesProjet($map): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "UPDATE properties
                        SET projet_bd = :projet_bd,
                            projet_sonar = :projet_sonar,
                            date_modification_projet = :date_modification_projet
                        WHERE type = :type";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':projet_bd', $map['projet_bd']);
                    $stmt->bindValue(':projet_sonar', $map['projet_sonar']);
                    $stmt->bindValue(':date_modification_projet', $map['date_modification_projet']->format('Y-m-d H:i:sO'));
                    $stmt->bindValue(':type', 'properties');
                    $stmt->executeQuery();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for updatePropertiesProfiles]
     * Mise à jour des properties pour les profils
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/10/2023 15:23:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updatePropertiesProfiles($map): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "UPDATE properties
                        SET profil_bd=:profil_bd,
                            profil_sonar=:profil_sonar,
                            date_modification_profil=:date_modification_profil
                        WHERE type=:type";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':profil_bd', $map['profil_bd']);
                    $stmt->bindValue(':profil_sonar', $map['profil_sonar']);
                    $stmt->bindValue(':date_modification_profil', $map['date_modification_profil']->format('Y-m-d H:i:sO'));
                    $stmt->bindValue(':type', 'properties');
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }
}
