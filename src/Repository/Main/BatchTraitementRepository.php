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

use App\Entity\Main\BatchTraitement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BatchTraitement>
 */
class BatchTraitementRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatchTraitement::class);
    }

    /**
     * [Description for selectBatchTraitementDateEnregistrementAutomatiqueLast]
     * On récupère la dernière date de programmation du batch automatique
     *
     * @param string $mode
     *
     * @return array
     *
     * Created at: 10/04/2024 09:10:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectBatchTraitementDateEnregistrementAutomatiqueLast($mode):array
    {
        $sql = "SELECT date_enregistrement as date
                FROM batch_traitement
                WHERE demarrage='Automatique'
                ORDER BY date_enregistrement DESC limit 1";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
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

    /**
     * [Description for selectBatchTraitementDateEnregistrementLast]
     * On récupère la dernière date du batch executé
     *
     * @param string $mode
     *
     * @return array
     *
     * Created at: 10/04/2024 09:35:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectBatchTraitementDateEnregistrementLast($mode):array
    {
        $sql = "SELECT date_enregistrement as date
                FROM batch_traitement
                ORDER BY date_enregistrement DESC limit 1";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
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

    /**
     * [Description for selectBatchTraitementLast]
     * On récupere la liste des derniers traitements,
     * groupé par titre et ordonné par responsable
     *
     * @param string $mode
     * @param string $date
     *
     * @return array
     *
     * Created at: 10/04/2024 09:38:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectBatchTraitementLast($mode, $dateLike):array
    {
        $sql = "SELECT  demarrage, resultat, titre, portefeuille,
                        nombre_projet as projet, responsable,
                        debut_traitement as debut, fin_traitement as fin
                        FROM batch_traitement
                        WHERE date_enregistrement like :dateLike
                        GROUP BY titre
                        ORDER BY responsable ASC, demarrage ASC";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':dateLike', $dateLike);
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
