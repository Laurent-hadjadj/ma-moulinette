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

use App\Entity\AnomalieDetails;
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
     * @param array $map
     *
     * @return array
     *
     * Created at: 13/03/2024 18:39:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteAnomalieDetailsMavenKey($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "DELETE
                        FROM anomalie_details
                        WHERE maven_key=:maven_key";
                $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $conn->bindValue(':maven_key', $map['maven_key']);
                    $conn->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectAnomalieDetailsMavenKey]
     * Retoune la liste du détails des anomalies pour un projet.
     *
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 20/03/2024 16:33:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectAnomalieDetailsMavenKey($map):array
    {
        try {
                $sql = "SELECT *
                        FROM anomalie_details
                        WHERE maven_key=:maven_key";
                $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $conn->bindValue(':maven_key', $map['maven_key']);
                $exec=$conn->executeQuery();
                $liste=$exec->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for insertAnomalieDetail]
     *
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 03/06/2024 17:18:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertAnomalieDetail($map):array
    {
        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $sql = "INSERT INTO anomalie_details
                    (maven_key, name, bug_blocker, bug_critical, bug_major, bug_minor, bug_info, vulnerability_blocker, vulnerability_critical, vulnerability_major, vulnerability_minor, vulnerability_info, code_smell_blocker, code_smell_critical, code_smell_major, code_smell_minor, code_smell_info, mode_collecte, utilisateur_collecte, date_enregistrement)
                            VALUES
                    (:maven_key, :name, :bug_blocker, :bug_critical, :bug_major, :bug_minor, :bug_info, :vulnerability_blocker, :vulnerability_critical, :vulnerability_major, :vulnerability_minor, :vulnerability_info, :code_smell_blocker, :code_smell_critical, :code_smell_major, :code_smell_minor, :code_smell_info, :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue(':maven_key', $map['maven_key']);
                        $stmt->bindValue(':name', $map['name']);

                        $stmt->bindValue(':bug_blocker', $map['bug_blocker']);
                        $stmt->bindValue(':bug_critical', $map['bug_critical']);
                        $stmt->bindValue(':bug_major', $map['bug_major']);
                        $stmt->bindValue(':bug_minor', $map['bug_minor']);
                        $stmt->bindValue(':bug_info', $map['bug_info']);

                        $stmt->bindValue(':vulnerability_blocker', $map['vulnerability_blocker']);
                        $stmt->bindValue(':vulnerability_critical', $map['vulnerability_critical']);
                        $stmt->bindValue(':vulnerability_major', $map['vulnerability_major']);
                        $stmt->bindValue(':vulnerability_minor', $map['vulnerability_minor']);
                        $stmt->bindValue(':vulnerability_info', $map['vulnerability_info']);

                        $stmt->bindValue(':code_smell_blocker', $map['code_smell_blocker']);
                        $stmt->bindValue(':code_smell_critical', $map['code_smell_critical']);
                        $stmt->bindValue(':code_smell_major', $map['code_smell_major']);
                        $stmt->bindValue(':code_smell_minor', $map['code_smell_minor']);
                        $stmt->bindValue(':code_smell_info', $map['code_smell_info']);

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
