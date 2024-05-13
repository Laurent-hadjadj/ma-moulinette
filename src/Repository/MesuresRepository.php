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
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'mesures'=>$mesures, 'erreur'=>''];
    }

}
