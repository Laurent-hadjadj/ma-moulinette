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

use App\Entity\Main\NoSonar;
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
     * Supprime les données de la version courrante (i.e. correspondant à la maven_key)
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 11/03/2024 17:36:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteNoSonarMavenKey($mode,$map):array
    {
        $sql = "DELETE
                FROM no_sonar
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
     * [Description for selectNoSonarRuleGroupByRule]
     * Retourne la liste des règles pour un projet groupé par règle.
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 22/03/2024 11:08:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectNoSonarRuleGroupByRule($mode,$map):array
    {
        $sql = "SELECT rule, count(*) as total
                FROM no_sonar
                WHERE maven_key=:maven_key
                GROUP BY rule";
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
