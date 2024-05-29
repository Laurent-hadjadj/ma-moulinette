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

use App\Entity\Mesures;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description MesuresRepository]
 */
class MesuresRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mesures::class);
    }

    /**
     * [Description for selectMesuresVersionLast]
     * Retourne les mesures de la dernière version d'un projet
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/03/2024 22:51:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectMesuresVersionLast($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT project_name as name, ncloc, lines, coverage, sqale_debt_ratio,
                duplication_density as duplication, tests, issues
                        FROM mesures
                        WHERE maven_key=:maven_key
                        ORDER BY date_enregistrement DESC LIMIT 1";
                $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $conn->bindValue(':maven_key', $map['maven_key']);
                $mesures=$conn->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'mesures'=>$mesures, 'erreur'=>''];
    }

    /**
     * [Description for insertMesures]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/05/2024 22:57:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertMesures($map):array
    {
        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $sql = "INSERT INTO mesures
                                (maven_key, project_name, lines, ncloc, sqale_debt_ratio, coverage, duplication_density, tests, issues, date_enregistrement)
                            VALUES
                                (:maven_key, :project_name, :lines, :ncloc, :sqale_debt_ratio, :coverage, :duplication_density, :tests, :issues, :date_enregistrement)";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue(':maven_key', $map['maven_key']);
                        $stmt->bindValue(':project_name', $map['project_name']);
                        $stmt->bindValue(':lines', $map['lines']);
                        $stmt->bindValue(':ncloc', $map['ncloc']);
                        $stmt->bindValue(':sqale_debt_ratio', $map['sqale_debt_ratio']);
                        $stmt->bindValue(':coverage', $map['coverage']);
                        $stmt->bindValue(':duplication_density', $map['duplication_density']);
                        $stmt->bindValue(':tests', $map['tests']);
                        $stmt->bindValue(':issues', $map['issues']);
                        /** on formate la date avant de l'enregistrer */
                        $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO')->format('Y-m-d H:i:sO'));
                        $stmt->executeStatement();
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for deleteMesuresMavenKey]
     * Supprime les mesures de la version courante (i.e. correspondant à la maven_key)
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 26/05/2024 10:51:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteMesuresMavenKey($map):array
    {
        $sql = "DELETE
                FROM mesures
                WHERE maven_key=:maven_key";
        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $stmt->bindValue(':maven_key', $map['maven_key']);
        try {
                $stmt->executeQuery();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->rollback();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }
}
