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

use App\Entity\Main\AnomalieDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


/**
 * [Description AnomalieDetailsRepository]
 */
class AnomalieDetailsRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, AnomalieDetails::class);
    }

    /**
     * [Description for deleteAnomalieDetailsMavenKey]
     *  On supprime le détails des anomalies du projet
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 13/03/2024 18:39:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteAnomalieDetailsMavenKey($mode,$map):array
    {
        $sql = "DELETE
                FROM anomalie_details
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
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

    /**
     * [Description for selectAnomalieDetailsMavenKey]
     * Retoune la liste du détails des anomalies pour un projet.
     *
     * @param mixed $mode
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 20/03/2024 16:33:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectAnomalieDetailsMavenKey($mode,$map):array
    {
        $sql = "SELECT *
                FROM anomalie_details
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
                if ($mode !== 'TEST') {
                    $exec=$conn->executeQuery();
                    $liste=$exec->fetchAllAssociative();
                } else {
                    return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
                }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }
}
