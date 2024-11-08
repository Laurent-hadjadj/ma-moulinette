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

use App\Entity\ProfilesHistorique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ProfilesHistoriqueRepository]
 */
class ProfilesHistoriqueRepository extends ServiceEntityRepository
{
    public static $removeReturnLine = "/\s+/u";
    public static $language = ':language';
    public static $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfilesHistorique::class);
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
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
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
    public function insertProfilesHistorique($map): array
    {
        $sql = "INSERT INTO ma_moulinette.profiles_historique (
                            date_courte, language, date,
                            action, auteur, rule,
                            description, detail, date_enregistrement)
                VALUES (
                            :date_courte, :language, :date,
                            :action, :auteur, :rule,
                            :description, :detail, :date_enregistrement)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                    /** On escape les ' */
                    /* "$reEncode = str_replace("'", "''", $map['description']);" */
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                        $stmt->bindValue(':date_courte', $map['date_courte']);
                        $stmt->bindValue(static::$language, $map['language']);
                        $stmt->bindValue(':date', $map['date']);
                        $stmt->bindValue(':action', $map['action']);
                        $stmt->bindValue(':auteur', $map['auteur']);
                        $stmt->bindValue(':rule', $map['rule']);
                        $stmt->bindValue(':description', $map['description']);
                        $stmt->bindValue(':detail', $map['detail']);
                        $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sP'));
                        $stmt->executeStatement();
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
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
    public function selectProfilesHistoriqueAction($map): array
    {
        $sql = "SELECT COUNT(*) AS nombre
                        FROM ma_moulinette.profiles_historique
                        WHERE action=:action AND language=:language";
        try {
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(':action', $map['action']);
                    $stmt->bindValue(static::$language, $map['language']);
                $nombre=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code'=>200, 'nombre'=>$nombre, 'erreur'=>''];
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
    public function selectProfilesHistoriqueDateTri($map): array
    {
        $sql = "SELECT date
                FROM ma_moulinette.profiles_historique
                WHERE language=:language
                ORDER BY date ".$map['tri']." limit ".$map['limit'];
        try {
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$language, $map['language']);
                $liste=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
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
    public function selectProfilesHistoriqueDateCourteGroupeBy($map): array
    {
        $sql = "SELECT date_courte
                FROM ma_moulinette.profiles_historique
                WHERE language=:language
                GROUP BY date_courte
                ORDER BY date_courte DESC";
        try {
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$language, $map['language']);
                $liste=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
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
    public function selectProfilesHistoriqueLangageDateCourte($map): array
    {
        $sql = "SELECT *
                FROM ma_moulinette.profiles_historique
                WHERE language=:language AND date_courte=:date_courte";
        try {
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$language, $map['language']);
                    $stmt->bindValue(':date_courte', $map['date_courte']);
                $liste=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }
}
