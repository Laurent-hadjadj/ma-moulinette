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

use App\Entity\Main\Profiles;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ProfilesRepository]
 */
class ProfilesRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profiles::class);
    }

    /**
     * [Description for countProfiles]
     * Compte le nombre total de profiles
     *
     * @param string $mode
     *
     * @return array
     *
     * Created at: 27/10/2023 13:56:31 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countProfiles($mode): array
    {
        $sql = "SELECT COUNT(*) AS total
                FROM profiles";
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
        return ['mode'=>$mode, 'request'=>$request, 'code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectProfiles]
     * On récupre la liste des profils
     *
     * @param string $mode
     *
     * @return array
     *
     * Created at: 19/02/2024 17:08:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfiles($mode):array
    {
        $sql = "SELECT name as profil,
                        language_name as langage,
                        active_rule_count as regle,
                        rules_update_at as date,
                        is_default as actif
                        FROM profiles";
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
        return ['mode'=>$mode, 'liste'=>$request, 'code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for deleteProfiles]
     * Suppression des enregistrements de la table profiles
     *
     * @param string $mode
     *
     * @return array
     *
     * Created at: 19/02/2024 17:11:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteProfiles($mode):array
    {
        $sql = "DELETE FROM profiles";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
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
     * [Description for selectProfilesLanguage]
     *
     * @param string $mode
     *
     * @return array
     *
     * Created at: 20/02/2024 11:15:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesLanguage($mode):array
    {
        $sql = "SELECT language_name AS profile
                FROM profiles";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        try {
            if ($mode !== 'TEST') {
                $labels=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'labels'=>$labels, 'erreur'=>''];
    }

    /**
     * [Description for selectProfilesRuleCount]
     * Remonte le nombre de règle avtive pour un profil
     *
     * @param string $mode
     *
     * @return array
     *
     * Created at: 27/02/2024 21:36:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesRuleCount($mode):array
    {
        $sql = "SELECT active_rule_count AS total
                FROM profiles";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        try {
            if ($mode !== 'TEST') {
                $dataSets=$conn->executeQuery()->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'data-set'=>$dataSets, 'erreur'=>''];
    }
}
