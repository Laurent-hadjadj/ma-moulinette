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
     * @param array $map
     *
     * @return array
     *
     * Created at: 13/03/2024 18:01:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteAnomalieMavenKey($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "DELETE
                        FROM anomalie
                        WHERE maven_key=:maven_key";
                $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $conn->bindValue(':maven_key', $map['maven_key']);
                $conn->executeQuery();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectAnomalieByProjectName]
     * Retourne la liste des anomalies par projet, trié  par ordre alphabétique
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 16/03/2024 21:24:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectAnomalieByProjectName():array
    {
        try {
                $sql = "SELECT maven_key as key
                        FROM anomalie
                        GROUP BY maven_key
                        ORDER BY project_name ASC";
                $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $r=$conn->executeQuery();
                        $liste=$r->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectAnomalie]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 20/03/2024 16:17:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectAnomalie($map):array
    {
        try {
                $sql = "SELECT *
                        FROM anomalie
                        WHERE maven_key=:maven_key";
                $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $conn->bindValue(':maven_key', $map['maven_key']);
                $liste=$conn->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for insertAnomalie]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 29/05/2024 18:15:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertAnomalie($map):array
    {
        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $sql = "INSERT INTO anomalie
                                (maven_key, project_name, anomalie_total, dette_minute, dette_reliability_minute, dette_vulnerability_minute, dette_code_smell_minute, dette, dette_reliability, dette_vulnerability, dette_code_smell, frontend, backend, autre, blocker, critical, major, info, minor, bug, vulnerability, code_smell,
                                mode_collecte, utilisateur_collecte,
                                date_enregistrement)
                            VALUES
                                (:maven_key, :project_name, :anomalie_total, :dette_minute, :dette_reliability_minute, :dette_vulnerability_minute, :dette_code_smell_minute, :dette, :dette_reliability, :dette_vulnerability, :dette_code_smell, :frontend, :backend, :autre, :blocker, :critical, :major, :info, :minor, :bug, :vulnerability, :code_smell,
                                :mode_collecte, :utilisateur_collecte,
                                :date_enregistrement)";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue(':maven_key', $map['maven_key']);
                        $stmt->bindValue(':project_name', $map['project_name']);
                        $stmt->bindValue(':anomalie_total', $map['anomalie_total']);
                        $stmt->bindValue(':dette', $map['dette']);
                        $stmt->bindValue(':dette_reliability', $map['dette_reliability']);
                        $stmt->bindValue(':dette_vulnerability', $map['dette_vulnerability']);
                        $stmt->bindValue(':dette_code_smell', $map['dette_code_smell']);
                        $stmt->bindValue(':dette_minute', $map['dette_minute']);
                        $stmt->bindValue(':dette_reliability_minute', $map['dette_reliability_minute']);
                        $stmt->bindValue(':dette_vulnerability_minute', $map['dette_vulnerability_minute']);
                        $stmt->bindValue(':dette_code_smell_minute', $map['dette_code_smell_minute']);
                        $stmt->bindValue(':frontend', $map['frontend']);
                        $stmt->bindValue(':backend', $map['backend']);
                        $stmt->bindValue(':autre', $map['autre']);
                        $stmt->bindValue(':blocker', $map['blocker']);
                        $stmt->bindValue(':critical', $map['critical']);
                        $stmt->bindValue(':major', $map['major']);
                        $stmt->bindValue(':info', $map['info']);
                        $stmt->bindValue(':minor', $map['minor']);
                        $stmt->bindValue(':bug', $map['bug']);
                        $stmt->bindValue(':vulnerability', $map['vulnerability']);
                        $stmt->bindValue(':code_smell', $map['code_smell']);
                        $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
                        $stmt->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);
                        /** on formate la date avant de l'enregistrer */
                        $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));
                        $stmt->executeStatement();
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

}
