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

use App\Entity\Hotspots;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description HotspotsRepository]
 */
class HotspotsRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Hotspots::class);
    }

    /**
     * [Description for deleteHotspotsMavenKey]
     * Supprime les hotspots pour la version courrante (i.e. correspondant à la maven_key)
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 12/03/2024 21:49:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteHotspotsMavenKey($map):array
    {
        $sql = "DELETE
                FROM hotspots
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
                #if ($mode !== 'TEST') {
                    $conn->executeQuery();
                #} else {
                    return ['code'=> 202, 'erreur'=>'TEST'];
                #}
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectHotspotsToReview]
     * Retourne la liste des hotspots pour le status TO_REVIEW
     * @param array $map
     *
     * @return array
     *
     * Created at: 14/03/2024 08:50:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHotspotsToReview($map):array
    {
        $sql = "SELECT *
                FROM hotspots
                WHERE maven_key=:maven_key AND status='TO_REVIEW'
                ORDER BY niveau";
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
        return ['code'=>200, 'erreur'=>'', 'liste'=>$liste];
    }

    /**
     * [Description for countHotspotsStatus]
     * Compte le nombre de hotspots au status TO_REVIEW, REVIEWED
     * @param array $map
     *
     * @return array
     *
     * Created at: 20/03/2024 16:45:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHotspotsStatus($map):array
    {
        $sql = "SELECT COUNT(*) as nombre
                FROM hotspots
                WHERE maven_key=:maven_key AND status=:status";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':status', $map['status']);
        try {
                #if ($mode !== 'TEST') {
                    $exec=$conn->executeQuery();
                    $nombre=$exec->fetchAllAssociative();
                #} else {
                    return ['code'=> 202, 'erreur'=>'TEST'];
                #}
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>'', 'nombre'=>$nombre];
    }

    /**
     * [Description for selectHotspotsByNiveau]
     * Retourne la liste du niveau et du nombre de hotspots pour une version au status TO_REVIEWED
     * @param array $map
     *
     * @return array
     *
     * Created at: 20/03/2024 19:50:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHotspotsByNiveau($map):array
    {
        $sql = "SELECT niveau, count(*) as hotspot
                FROM hotspots
                WHERE maven_key=:maven_key AND status=:status
                GROUP BY niveau";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':status', $map['status']);
        try {
                #if ($mode !== 'TEST') {
                    $exec=$conn->executeQuery();
                    $liste=$exec->fetchAllAssociative();
                #} else {
                    return ['code'=> 202, 'erreur'=>'TEST'];
                #}
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>'', 'liste'=>$liste];
    }

}
