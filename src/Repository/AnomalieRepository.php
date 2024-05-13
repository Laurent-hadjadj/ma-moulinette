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

use App\Entity\Anomalie;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description AnomalieRepository]
 */
class AnomalieRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Anomalie::class);
    }

    /**
     * [Description for deleteAnomalieMavenKey]
     *  On supprime les anomalies sur le projet
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 13/03/2024 18:01:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteAnomalieMavenKey($mode,$map):array
    {
        $sql = "DELETE
                FROM anomalie
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
     * [Description for selectAnomalieByProjectName]
     * Retourne la liste des anomalies par projet, trié  par ordre alphabétique
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 16/03/2024 21:24:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectAnomalieByProjectName($mode):array
    {
        $sql = "SELECT maven_key as key
                FROM anomalie
                GROUP BY maven_key
                ORDER BY project_name ASC";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        try {
                if ($mode !== 'TEST') {
                    $r=$conn->executeQuery();
                    $liste=$r->fetchAllAssociative();
                } else {
                    return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
                }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectAnomalie]
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 20/03/2024 16:17:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectAnomalie($mode, $map):array
    {
        $sql = "SELECT *
                FROM anomalie
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
                if ($mode !== 'TEST') {
                    $liste=$conn->executeQuery()->fetchAllAssociative();
                } else {
                    return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
                }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

}
