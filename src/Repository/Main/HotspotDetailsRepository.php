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

namespace App\Repository\Main;

use App\Entity\Main\HotspotDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class HotspotDetailsRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotspotDetails::class);
    }

    /**
     * [Description for selectHotspotDetailsByNiveau]
     * On récupère la liste des hotspots status de la table détails.
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 03/03/2024 12:44:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHotspotDetailsByStatus($mode,$map):array
    {
        $sql = "SELECT *
                FROM hotspot_details
                WHERE maven_key=:maven_key
                ORDER BY status ASC";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            if ($mode !== 'TEST') {
                $nombre=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'menaces'=>$nombre, 'erreur'=>''];
    }
}
