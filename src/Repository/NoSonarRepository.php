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

use App\Entity\NoSonar;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class NoSonarRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, NoSonar::class);
    }

    /**
     * [Description for deleteNoSonarMavenKey]
     * Supprime les données de la version courante (i.e. correspondant à la maven_key)
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 11/03/2024 17:36:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteNoSonarMavenKey($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "DELETE
                        FROM no_sonar
                        WHERE maven_key=:maven_key";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':maven_key', $map['maven_key']);
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectNoSonarRuleGroupByRule]
     * Retourne la liste des règles pour un projet groupé par règle.
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 22/03/2024 11:08:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectNoSonarRuleGroupByRule($map):array
    {
        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $sql = "SELECT rule, count(*) as total
                            FROM no_sonar
                            WHERE maven_key=:maven_key
                            GROUP BY rule";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue(':maven_key', $map['maven_key']);
                        $exec=$stmt->executeQuery();
                        $liste=$exec->fetchAllAssociative();
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for insertNoSonar]
     * Ajout d'un noSonar ou d'un supressWarning
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 07/05/2024 13:19:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertNoSonar($map):array
    {
        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $sql = "INSERT INTO no_sonar
                                (maven_key, rule, component, line, date_enregistrement)
                            VALUES (:maven_key, :rule, :component, :line, :date_enregistrement)";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue(':maven_key', $map['maven_key']);
                        $stmt->bindValue(':rule', $map['rule']);
                        $stmt->bindValue(':component', $map['component']);
                        $stmt->bindValue(':line', $map['line']);
                        $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']);
                        $stmt->executeStatement();
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'erreur'=>''];
    }
}
