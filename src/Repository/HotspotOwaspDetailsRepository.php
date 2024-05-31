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

use App\Entity\HotspotOwaspDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class HotspotOwaspDetailsRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotspotOwaspDetails::class);
    }

    /**
     * [Description for selectHotspotOwaspDetailsByNiveau]
     * On récupère la liste des hotspots status de la table détails.
     * @param array $map
     *
     * @return array
     *
     * Created at: 03/03/2024 12:44:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHotspotOwaspDetailsByStatus($map):array
    {
        $sql = "SELECT *
                FROM hotspot_owasp_details
                WHERE maven_key=:maven_key
                ORDER BY status ASC";
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
     * [Description for deleteHotspotOwaspDetailsMavenKey]
     * On supprime le détails des hotspots  pour la version courante
     * @param array $map
     *
     * @return array
     *
     * Created at: 14/03/2024 09:42:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteHotspotOwaspDetailsMavenKey($map):array
    {
        $sql = "DELETE
                FROM hotspot_owasp_details
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
                #f ($mode !== 'TEST') {
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