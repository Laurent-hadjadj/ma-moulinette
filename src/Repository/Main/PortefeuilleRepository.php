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

use App\Entity\Main\Portefeuille;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description PortefeuilleRepository]
 */
class PortefeuilleRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Portefeuille::class);
    }

    /**
     * [Description for selectPortefeuille]
     * Retourne la liste des projets d'un portefeuille
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 10/04/2024 11:23:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectPortefeuille($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT liste
                        FROM portefeuille
                        WHERE titre=:portefeuille";
                /** On escape les ' : normalement on en a pas bedoin */
                //"$reEncode = str_replace("'", "''", $map['portefeuille']);

                $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $conn->bindValue(':portefeuille', $map['portefeuille']);
                $exec=$conn->executeQuery();
                $liste=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
            } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }
}
