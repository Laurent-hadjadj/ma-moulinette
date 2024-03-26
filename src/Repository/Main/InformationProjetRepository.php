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

use App\Entity\Main\InformationProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description InformationProjetRepository]
 */
class InformationProjetRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, InformationProjet::class);
    }

    /**
     * [Description for selectInformationProjetisValide]
     * Vérifie si le projet existe.
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 16/03/2024 21:06:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectInformationProjetisValide($mode, $map):array
    {
        $sql = "SELECT *
                FROM information_projet
                WHERE maven_key=:maven_key LIMIT 1";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            if ($mode !== 'TEST') {
                $exec=$conn->executeQuery();
                $isValide=$exec->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'is_valide'=>$isValide, 'erreur'=>''];
    }

    /**
     * [Description for countInformationProjetAllType]
     * Compte le nombre de version de tgype RELEASE, SNAPHOT ou AUTRE
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 16/03/2024 21:43:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countInformationProjetAllType($mode, $map):array
    {
        $sql = "SELECT COUNT(type) AS 'total'
                FROM information_projet
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            if ($mode !== 'TEST') {
                $exec=$conn->executeQuery();
                $nombre=$exec->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'nombre'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for countInformationProjetType]
     * On retourne le nombre de version pour un type donné.
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/03/2024 22:18:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countInformationProjetType($mode, $map):array
    {
        $sql = "SELECT type, COUNT(type) AS 'total'
                FROM information_projet
                WHERE maven_key=:maven_key AND type=:type";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':type', $map['type']);
        try {
            if ($mode !== 'TEST') {
                $exec=$conn->executeQuery();
                $nombre=$exec->fetchAllAssociative();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'nombre'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for selectInformationProjetType]
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/03/2024 22:27:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectInformationProjetTypeIndexed($mode, $map):array
    {
        $sql = "SELECT type, COUNT(type) AS 'total'
                FROM information_projet
                WHERE maven_key=:maven_key
                GROUP BY type";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            if ($mode !== 'TEST') {
                $exec=$conn->executeQuery();
                $liste=$exec->fetchAllAssociativeIndexed();
            } else {
                return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['mode'=>$mode, 'code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectInformationProjetVersionLast]
     * Retourne la version du dernier projet.
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/03/2024 22:34:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectInformationProjetVersionLast($mode, $map):array
    {
        $sql = "SELECT project_version as projet, date
                FROM information_projet
                WHERE maven_key=:maven_key
                ORDER BY date DESC LIMIT 1";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
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
        return ['mode'=>$mode, 'code'=>200, 'version'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectInformationProjetversion]
     * Retourne la liste des versions pour un projet.
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 27/02/2024 21:44:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectInformationProjetVersion($mode, $map):array
    {
        $sql = "SELECT maven_key, project_version as version, date
                FROM information_projet
                WHERE maven_key=:maven_key";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
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
        return ['mode'=>$mode, 'code'=>200, 'versions'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectInformationProjetProjectVersion]
     * Récupère la version du projet de la dernère version du projet
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 11/03/2024 08:32:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectInformationProjetProjectVersion($mode, $map):array
    {
        $sql = "SELECT project_version, date
                FROM information_projet
                WHERE maven_key=:maven_key
                ORDER by date DESC LIMIT 1";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
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
        return ['mode'=>$mode, 'code'=>200, 'info'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for deleteInformationProjetMavenKey]
     * On supprime les informations sur le projet
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 11/03/2024 19:03:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteInformationProjetMavenKey($mode,$map):array
    {
        $sql = "DELETE
                FROM information_projet
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
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

}
