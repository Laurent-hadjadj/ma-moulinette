<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
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
    public static $removeReturnLine = "/\s+/u";
    public static $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Profiles::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Doctrine\DBAL\Exception $e
     *
     * @return array
     *
     * Created at: 21/10/2024 16:55:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Lilmod & Lelamed - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
    {
        if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
            return ['code'=>500, 'erreur' => static::$noDataBase];
        } else {
            return ['code' => 500, 'erreur' => $e->getMessage()];
        }
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
    public function countProfiles($referential_default = 'true', $langage = null): array
    {
        $sql = "SELECT COUNT(*) AS total
                FROM ma_moulinette.profiles
                WHERE referential_default = :referential_default";
        try {
            if ($langage !== null ){
                $sql .= ' AND language_name LIKE :langage ';
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, ' ', $sql));
                    $stmt->bindValue('referential_default', $referential_default);
                    $stmt->bindValue('langage', $langage);
            } else {
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, ' ', $sql));
                    $stmt->bindValue('referential_default', $referential_default);
            }
            $request=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
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
    public function selectProfiles($referential_default = 'true' ,$langage = null): array
    {
        $sql = "SELECT name as profil,
                    language_name as langage,
                    active_rule_count as rule,
                    rules_updated_at as date,
                    referential_default as actif
                FROM ma_moulinette.profiles
                WHERE referential_default = :referential_default";
        try {
            if ($langage !== null ){
                $sql .= ' AND language_name LIKE :langage ';
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue('referential_default', $referential_default);
                $stmt->bindValue('langage', $langage);
            } else {
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, ' ', $sql));
                $stmt->bindValue('referential_default', $referential_default);
            }
            $exec=$stmt->executeQuery();
            $liste=$exec->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
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
    public function deleteProfiles(): array
    {
        $sql = "DELETE FROM ma_moulinette.profiles";

        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectProfilesLanguage]
     *
     * @return array
     *
     * Created at: 20/02/2024 11:15:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesLanguage(): array
    {
        $sql = "SELECT language_name AS profile
                FROM ma_moulinette.profiles
                WHERE referential_default = true";
        try {
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $labels=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
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
    public function selectProfilesRuleCount(): array
    {
        $sql = "SELECT active_rule_count AS total
                FROM ma_moulinette.profiles
                WHERE referential_default = true";
        try {
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $dataSets=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code'=>200, 'data-set'=>$dataSets, 'erreur'=>''];
    }

    /**
     * [Description for insertProfiles]
     * Ajout les référentiels de règles
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 07/11/2024 14:01:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertProfiles($map): array
    {
        $sql = "INSERT INTO ma_moulinette.profiles
                    (key, name, language_name, referential_default, active_rule_count, rules_updated_at, date_enregistrement)
                VALUES
                    (:key, :name, :language_name, :referential_default, :active_rule_count, :rules_updated_at, :date_enregistrement)";
        try {
                $nombre=0;
                $this->getEntityManager()->getConnection()->beginTransaction();
                foreach($map['profiles'] as $profil){
                        $nombre = $nombre + 1;
                        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                            $stmt->bindValue(':key', $profil['key']);
                            $stmt->bindValue(':name', $profil['name']);
                            $stmt->bindValue(':language_name', $profil['languageName']);
                            $stmt->bindValue(':referential_default', $profil['isDefault'] === true ? 'true' : 'false');
                            $stmt->bindValue(':active_rule_count', $profil['activeRuleCount']);
                            $rulesUpdatedAt = new \DateTimeImmutable($profil['rulesUpdatedAt']);
                            $formattedRulesUpdatedAt = $rulesUpdatedAt->format('Y-m-d H:i:sP');

                            $stmt->bindValue(':rules_updated_at', $formattedRulesUpdatedAt);
                            $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sP'));
                            $stmt->executeStatement();
                    }
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
                $this->getEntityManager()->getConnection()->rollBack();
                return $this->handleDatabaseException($e);
        }
        return ['code'=>200, 'nombre'=>$nombre, 'erreur'=>''];
    }
}
