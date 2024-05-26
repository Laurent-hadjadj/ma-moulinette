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

use App\Entity\HotspotOwasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HotspotOwaspRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotspotOwasp::class);
    }

    /**
     * [Description for countHotspotOwaspStatus]
     * On compte le nombre de hotspot REVIEWED
     * @param array $map
     *
     * @return array
     *
     * Created at: 02/03/2024 23:23:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHotspotOwaspStatus($map):array
    {
        $sql = "SELECT count(*) AS nombre
                FROM hotspot_owasp
                WHERE maven_key=:maven_key AND status=:status";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':status', $map['status']);
        try {
            #if ($mode !== 'TEST') {
                $nombre=$conn->executeQuery()->fetchAllAssociative();
            #} else {
                return ['code'=> 202, 'erreur'=>'TEST'];
            #}
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'request'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for countOwaspStatus]
     * On récupère le nombre de hotspot owasp par niveau de sévérité potentiel.
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 02/03/2024 23:37:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHotspotOwaspProbability($map):array
    {
        $sql = "SELECT probability, count(*) as total
                FROM hotspot_owasp
                WHERE maven_key=:maven_key AND status='TO_REVIEW' GROUP BY probability";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            #if ($mode !== 'TEST') {
                $nombre=$conn->executeQuery()->fetchAllAssociative();
            #} else {
                return ['code'=> 202, 'erreur'=>'TEST'];
            #}
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'nombre'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for countHotspotOwaspMenaces]
     * On récupère le nombre de hotspost au status TO_REVIEW
     * @param array $map
     *
     * @return array
     *
     * Created at: 03/03/2024 12:38:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHotspotOwaspMenaces($map):array
    {
        $sql = "SELECT menace, count(*) as total
                FROM hotspot_owasp
                WHERE maven_key=:maven_key
                AND status='TO_REVIEW' GROUP BY menace";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            #if ($mode !== 'TEST') {
                $nombre=$conn->executeQuery()->fetchAllAssociative();
            #} else {
                return ['code'=> 202, 'erreur'=>'TEST'];
            #}
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'menaces'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for countHotspotOwaspMenaceByStatus]
     *  On compte le nombre de menace par type de Status
     * @param array $map
     *
     * @return array
     *
     * Created at: 03/03/2024 16:01:48 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHotspotOwaspMenaceByStatus($map):array
    {
        $sql = "SELECT count(*) as total
                FROM hotspot_owasp
                WHERE maven_key=:maven_key
                AND menace=:menace
                AND status='TO_REVIEW'
                AND probability=:probability";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':menace', $map['menace']);
        $conn->bindValue(':probability', $map['probability']);
        try {
            #if ($mode !== 'TEST') {
                $nombre=$conn->executeQuery()->fetchAllAssociative();
            #} else {
                return ['code'=> 202, 'erreur'=>'TEST'];
            #}
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'nombre'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for deleteHotspotOwaspMavenKey]
     * Supprime les hotspots de type owasp pour la version courrante (i.e. correspondant à la maven_key)
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 14/03/2024 08:21:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteHotspotOwaspMavenKey($map):array
    {
        $sql = "DELETE
                FROM hotspot_owasp
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
}
