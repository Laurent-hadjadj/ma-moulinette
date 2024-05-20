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

use App\Entity\InformationProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description InformationProjetRepository]
 */
class InformationProjetRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";
    public static $phMavenKey = ':maven_key';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InformationProjet::class);
    }

    /**
     * [Description for selectInformationProjetisValide]
     * Vérifie si le projet existe.
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 16/03/2024 21:06:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectInformationProjetisValide($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT *
                        FROM information_projet
                        WHERE maven_key=:maven_key LIMIT 1";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phMavenKey, $map['maven_key']);
                $exec=$stmt->executeQuery();
                $isValide=$exec->fetchAllAssociative();
                /** j'ai pas trouvé de projet */
                if (!$isValide){
                    return ['code'=>404];
                }
            $this->getEntityManager()->getConnection()->commit();
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->getEntityManager()->getConnection()->rollBack();
                return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'is_valide'=>$isValide, 'erreur'=>''];
    }

    /**
     * [Description for countInformationProjetAllType]
     * Compte le nombre de version de tgype RELEASE, SNAPHOT ou AUTRE
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 16/03/2024 21:43:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countInformationProjetAllType($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT COUNT(type) AS total
                        FROM information_projet
                        WHERE maven_key=:maven_key";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phMavenKey, $map['maven_key']);

                $exec=$stmt->executeQuery();
                $nombre=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'nombre'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for countInformationProjetType]
     * On retourne le nombre de version pour un type donné.
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/03/2024 22:18:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countInformationProjetType($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT type, COUNT(*) AS total
                        FROM information_projet
                        WHERE maven_key=:maven_key AND type=:type GROUP BY type";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phMavenKey, $map['maven_key']);
                    $stmt->bindValue(':type', $map['type']);
                $exec=$stmt->executeQuery();
                $nombre=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
                $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'nombre'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for selectInformationProjetType]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/03/2024 22:27:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectInformationProjetTypeIndexed($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT type, COUNT(type) AS total
                        FROM information_projet
                        WHERE maven_key=:maven_key
                        GROUP BY type";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phMavenKey, $map['maven_key']);
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociativeIndexed();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectInformationProjetVersionLast]
     * Retourne la version du dernier projet.
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/03/2024 22:34:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectInformationProjetVersionLast($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT project_version as projet, date
                        FROM information_projet
                        WHERE maven_key=:maven_key
                        ORDER BY date DESC LIMIT 1";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phMavenKey, $map['maven_key']);
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'version'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectInformationProjetversion]
     * Retourne la liste des versions pour un projet.
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/02/2024 21:44:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectInformationProjetVersion($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT maven_key, project_version as version, date
                        FROM information_projet
                        WHERE maven_key=:maven_key";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phMavenKey, $map['maven_key']);
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'versions'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectInformationProjetProjectVersion]
     * Récupère la version du projet de la dernère version du projet
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 11/03/2024 08:32:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectInformationProjetProjectVersion($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT project_version, date
                        FROM information_projet
                        WHERE maven_key=:maven_key
                        ORDER by date DESC LIMIT 1";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phMavenKey, $map['maven_key']);
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'info'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for deleteInformationProjetMavenKey]
     * On supprime les informations sur le projet
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 11/03/2024 19:03:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteInformationProjetMavenKey($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "DELETE
                        FROM information_projet
                        WHERE maven_key=:maven_key";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(static::$phMavenKey, $map['maven_key']);
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for insertInformationProjet]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 20/05/2024 23:09:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertInformationProjet($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "INSERT INTO information_projet (
                                    maven_key, analyse_key, date, project_version, type, date_enregistrement)
                        VALUES (:maven_key, :analyse_key, :date, :project_version, :type, :date_enregistrement)";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':maven_key', $map['maven_key']);
                    $stmt->bindValue(':analyse_key', $map['analyse_key']);
                    $stmt->bindValue(':project_version', $map['project_version']);
                    $stmt->bindValue(':type', $map['type']);
                    $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']);
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

}
