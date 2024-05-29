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

use App\Entity\Owasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OwaspRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Owasp::class);
    }

    /**
     * [Description for selectOwaspOrderByDateEnregistrement]
     * On récupère les infos de la dernière analyse.
     * @param array $map
     *
     * @return array
     *
     * Created at: 02/03/2024 23:20:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectOwaspOrderByDateEnregistrement($map):array
    {
        $sql = "SELECT *
                FROM owasp
                WHERE maven_key=:maven_key
                ORDER BY date_enregistrement DESC LIMIT 1";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            #if ($mode !== 'TEST') {
                $liste=$conn->executeQuery()->fetchAllAssociative();
            #} else {
                return ['code'=> 202, 'erreur'=>'TEST'];
            #}
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }


    /**
     * [Description for deleteOwaspMavenKey]
     * Supprime les données de la version courante (i.e. correspondant à la maven_key)
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 11/03/2024 08:37:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteOwaspMavenKey($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "DELETE
                        FROM owasp
                        WHERE maven_key=:maven_key";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $stmt->bindValue(':maven_key', $map['maven_key']);
                $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for InsertOwasp]
     * Ajoute les signalements et les issues OWASP pour le projet
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 26/05/2024 13:03:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertOwasp($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $sql = "INSERT INTO owasp
                        (maven_key, version, date_version, effort_total, date_enregistrement,
                        a1, a2, a3, a4, a5, a6, a7, a8, a9, a10,
                        a1_blocker, a1_critical, a1_major, a1_info, a1_minor,
                        a2_blocker, a2_critical, a2_major, a2_info, a2_minor,
                        a3_blocker, a3_critical, a3_major, a3_info, a3_minor,
                        a4_blocker, a4_critical, a4_major, a4_info, a4_minor,
                        a5_blocker, a5_critical, a5_major, a5_info, a5_minor,
                        a6_blocker, a6_critical, a6_major, a6_info, a6_minor,
                        a7_blocker, a7_critical, a7_major, a7_info, a7_minor,
                        a8_blocker, a8_critical, a8_major, a8_info, a8_minor,
                        a9_blocker, a9_critical, a9_major, a9_info, a9_minor,
                        a10_blocker, a10_critical, a10_major, a10_info, a10_minor)
                    VALUES
                        (:maven_key, :version, :date_version, :effort_total, :date_enregistrement,
                        :a1, :a2, :a3, :a4, :a5, :a6, :a7, :a8, :a9, :a10,
                        :a1_blocker, :a1_critical, :a1_major, :a1_info, :a1_minor,
                        :a2_blocker, :a2_critical, :a2_major, :a2_info, :a2_minor,
                        :a3_blocker, :a3_critical, :a3_major, :a3_info, :a3_minor,
                        :a4_blocker, :a4_critical, :a4_major, :a4_info, :a4_minor,
                        :a5_blocker, :a5_critical, :a5_major, :a5_info, :a5_minor,
                        :a6_blocker, :a6_critical, :a6_major, :a6_info, :a6_minor,
                        :a7_blocker, :a7_critical, :a7_major, :a7_info, :a7_minor,
                        :a8_blocker, :a8_critical, :a8_major, :a8_info, :a8_minor,
                        :a9_blocker, :a9_critical, :a9_major, :a9_info, :a9_minor,
                        :a10_blocker, :a10_critical, :a10_major, :a10_info, :a10_minor)";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));

                    foreach ($map as $key => $value) {
                        if ($value instanceof \DateTime) {
                            $stmt->bindValue(":$key", $value->format('Y-m-d H:i:sO'));
                        } else {
                            $stmt->bindValue(":$key", $value);
                        }
                    }
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

}
