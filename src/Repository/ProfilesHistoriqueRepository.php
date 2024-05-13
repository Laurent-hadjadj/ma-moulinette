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

use App\Entity\ProfilesHistorique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ProfilesHistoriqueRepository]
 */
class ProfilesHistoriqueRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";
    public static $phLanguage = ':language';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfilesHistorique::class);
    }

    /**
     * [Description for insertProfilesHistorique]
     * Mise à jour des changements sur les règles.
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/02/2024 08:41:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertProfilesHistorique($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "INSERT OR IGNORE INTO profiles_historique (
                            date_courte, language, date,
                            action, auteur, regle,
                            description, detail, date_enregistrement)
                        VALUES (
                            :date_courte, :language, :date,
                            :action, :auteur, :regle,
                            :description, :detail, :date_enregistrement)";

                    /** On escape les ' */
                    /* "$reEncode = str_replace("'", "''", $map['description']);" */

                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue(':date_courte', $map['date_courte']);
                        $stmt->bindValue(static::$phLanguage, $map['language']);
                        $stmt->bindValue(':date', $map['date']);
                        $stmt->bindValue(':action', $map['action']);
                        $stmt->bindValue(':auteur', $map['date_courte']);
                        $stmt->bindValue(':regle', $map['regle']);
                        $stmt->bindValue(':description', $map['description']);
                        $stmt->bindValue(':detail', $map['detail']);
                        $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']);
                        $stmt->executeStatement();
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectProfilesHistoriqueAction]
     * Nombre de règles activé/désactivé/mise à jour
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/02/2024 09:43:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesHistoriqueAction($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT COUNT() AS 'nombre'
                        FROM profiles_historique
                        WHERE action=:action AND language=:language";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':action', $map['action']);
                    $stmt->bindValue(static::$phLanguage, $map['language']);
                $request=$stmt->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'request'=>$request, 'erreur'=>''];
    }

    /**
     * [Description for selectProfilesHistoriqueDateTri]
     * Remonte les n premieres dates trié ordre croissant ou décroissant
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/02/2024 10:01:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesHistoriqueDateTri($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT date
                        FROM profiles_historique
                        WHERE language=:language
                        ORDER BY date ".$map['tri']." limit ".$map['limit'];
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phLanguage, $map['language']);
                $request=$stmt->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'request'=>$request, 'erreur'=>''];
    }

    /**
     * [Description for selectProfilesHistoriqueDateCourteGroupeBy]
     * Retourne la liste groupé et trié par date courte
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/02/2024 13:57:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesHistoriqueDateCourteGroupeBy($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT date_courte
                        FROM profiles_historique
                        WHERE language=:language
                        GROUP BY date_courte
                        ORDER BY date_courte DESC";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phLanguage, $map['language']);
                $request=$stmt->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'request'=>$request, 'erreur'=>''];
    }

    /**
     * [Description for selectProfilesHistoriqueLangageDateCourte]
     * Retourne la liste par langage et par date courte
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/02/2024 14:08:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesHistoriqueLangageDateCourte($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT *
                        FROM profiles_historique
                        WHERE language=:language AND date_courte=:date_courte";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phLanguage, $map['language']);
                    $stmt->bindValue(':date_courte', $map['date_courte']);
                $request=$stmt->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'request'=>$request, 'erreur'=>''];
    }
}
