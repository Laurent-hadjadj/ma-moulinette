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

use App\Entity\Main\ProfilesHistorique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ProfilesHistoriqueRepository]
 */
class ProfilesHistoriqueRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProfilesHistorique::class);
    }

    /**
     * [Description for insertProfilesHistorique]
     * Mise à jour des changements sur les règles.
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/02/2024 08:41:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertProfilesHistorique($mode, $map):array
    {
        $sql = "INSERT OR IGNORE INTO profiles_historique (
                    date_courte, langage, date,
                    action, auteur, regle,
                    description, detail, date_enregistrement)
                VALUES (
                    :date_courte, :langage, :date,
                    :action, :auteur, :regle,
                    :description, :detail, :date_enregistrement)";
        /** On escape les ' */
        $reEncode = str_replace("'", "''", $map['description']);

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':date_courte', $map['date_courte']);
        $conn->bindValue(':langage', $map['langage']);
        $conn->bindValue(':date', $map['date']);
        $conn->bindValue(':action', $map['action']);
        $conn->bindValue(':auteur', $map['date_courte']);
        $conn->bindValue(':regle', $map['regle']);
        $conn->bindValue(':description', $map['description']);
        $conn->bindValue(':detail', $map['detail']);
        $conn->bindValue(':date_enregistrement', $map['date_enregistrement']);

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
     * [Description for selectProfilesHistoriqueAction]
     * Nombre de règles activé/désactivé/mise à jour
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/02/2024 09:43:45 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesHistoriqueAction($mode, $map):array
    {
        $sql = "SELECT COUNT() AS 'nombre'
                FROM profiles_historique
                WHERE action=:action AND langage=:langage";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':action', $map['action']);
        $conn->bindValue(':langage', $map['langage']);
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
     * [Description for selectProfilesHistoriqueDateTr]
     * Remonte les n premieres dates trié ordre croissant ou décroissant
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/02/2024 10:01:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesHistoriqueDateTri($mode, $map):array
    {
        $sql = "SELECT date
                FROM profiles_historique
                WHERE langage=:langage
                ORDER BY date ".$map['tri']." limit ".$map['limit'];

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':langage', $map['langage']);
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
     * [Description for selectProfilesHistoriqueDateCourteGroupeBy]
     * Retourne la liste groupé et trié par date courte
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/02/2024 13:57:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesHistoriqueDateCourteGroupeBy($mode, $map):array
    {
        $sql = "SELECT date_courte
                FROM profiles_historique
                WHERE langage=:langage
                GROUP BY date_courte
                ORDER BY date_courte DESC";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':langage', $map['langage']);
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
     * [Description for selectProfilesHistoriqueLangageDateCourte]
     * Retourne la liste par langage et par date courte
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/02/2024 14:08:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectProfilesHistoriqueLangageDateCourte($mode,$map):array
    {
        $sql = "SELECT *
                FROM profiles_historique
                WHERE langage=:langage AND date_courte=:date_courte";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':langage', $map['langage']);
        $conn->bindValue(':date_courte', $map['date_courte']);
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
