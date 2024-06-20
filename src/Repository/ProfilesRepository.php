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

use App\Entity\Profiles;
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
     * Compte le nombre total de profiles (par default on compte les profils actifs)
     *
     * @return array
     *
     * Created at: 27/10/2023 13:56:31 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countProfiles($referentielDefault="true", $langage = null): array
    {
        try {
                $sql = " SELECT COUNT(*) AS total
                        FROM profiles
                        WHERE referentiel_default = :referencielDefault";
                if ($langage !== null ){
                    $sql .= " AND language_name LIKE :langage ";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue("referencielDefault", $referentielDefault);
                    $stmt->bindValue("langage", $langage);
                }else{
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue("referencielDefault", $referentielDefault);
                }
                $request=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['request'=>$request, 'code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectProfiles]
     * On récupère la liste des profils (par default on récupère les profils actifs)
     *
     * @return array
     *
     * Created at: 19/02/2024 17:08:03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfiles($referentielDefault="true" ,$langage = null):array
    {
        try {
                $sql = "SELECT name as profil,
                        language_name as langage,
                        active_rule_count as regle,
                        rules_update_at as date,
                        referentiel_default as actif
                        FROM profiles
                        WHERE referentiel_default = :referencielDefault";
                if ($langage !== null ){
                    $sql .= " AND language_name LIKE :langage ";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue("referencielDefault", $referentielDefault);
                    $stmt->bindValue("langage", $langage);
                }else{
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue("referencielDefault", $referentielDefault);
                }
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['liste'=>$liste, 'code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for deleteProfiles]
     * Suppression des enregistrements de la table profiles
     *
     * @return array
     *
     * Created at: 19/02/2024 17:11:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteProfiles():array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "DELETE FROM profiles";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }


    /**
     * [Description for selectProfilesLanguage]
     * @return array
     *
     * Created at: 20/02/2024 11:15:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesLanguage():array
    {
        try {
                $sql = "SELECT language_name AS profile
                        FROM profiles
                        WHERE referentiel_default = true";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $labels=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'labels'=>$labels, 'erreur'=>''];
    }

    /**
     * [Description for selectProfilesRuleCount]
     * Remonte le nombre de règle active pour un profil
     *
     * @return array
     *
     * Created at: 27/02/2024 21:36:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesRuleCount():array
    {
        try {
                $sql = "SELECT active_rule_count AS total
                        FROM profiles
                        WHERE referentiel_default = true";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $dataSets=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'data-set'=>$dataSets, 'erreur'=>''];
    }
}
