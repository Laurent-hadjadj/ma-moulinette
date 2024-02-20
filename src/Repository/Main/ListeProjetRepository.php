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

use App\Entity\Main\ListeProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ListeProjetRepository]
 */
class ListeProjetRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeProjet::class);
    }

    /**
     * [Description for countListeProjetVisibility]
     * Execute une requête paramétrique count avec type= PRIVATE || PUBLIC
     *
     * @param string $mode
     * @param string $type
     *
     * @return array
     *
     * Created at: 27/10/2023 12:59:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countListeProjetVisibility($mode, $type): array
    {
        $sql = "SELECT count(*) as visibility
                FROM liste_projet
                WHERE visibility=:visibility";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(":visibility", $type);
        try {
            if ($mode !== 'TEST') {
                $request=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'request'=>$request, 'erreur'=>''];
    }


    /**
     * [Description for countListProjet]
     * Compte le nombre total de projet.
     *
     * @param string $mode
     *
     * @return array
     *
     * Created at: 27/10/2023 13:54:53 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countListeProjet($mode): array
    {
        $sql = "SELECT COUNT(*) AS total
        FROM liste_projet";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        try {
            if ($mode !== 'TEST') {
                $request=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'request'=>$request, 'erreur'=>''];
    }

}
