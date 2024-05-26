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

use App\Entity\Owasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OwaspRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Owasp::class);
    }

    /**
     * [Description for selectOwaspOrderByDateEnregistrement]
     * On récupère les infos de la dernière analyse.
     * @param array $map
     *
     * @return array
     *
     * Created at: 02/03/2024 23:20:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectOwaspOrderByDateEnregistrement($map):array
    {
        $sql = "SELECT *
                FROM owasp
                WHERE maven_key=:maven_key
                ORDER BY date_enregistrement DESC LIMIT 1";

        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
            #if ($mode !== 'TEST') {
                $liste=$conn->executeQuery()->fetchAllAssociative();
            #} else {
                return ['code'=> 202, 'erreur'=>'TEST'];
            #}
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }


    /**
     * [Description for deleteOwaspMavenKey]
     * Supprime les données de la version courrante (i.e. correspondant à la maven_key)
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 11/03/2024 08:37:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteOwaspMavenKey($map):array
    {
        $sql = "DELETE
                FROM owasp
                WHERE maven_key=:maven_key";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        try {
                #if ($mode !== 'TEST') {
                    $conn->executeQuery();
                #} else {
                    return ['code'=> 202, 'erreur'=>'TEST'];
                #}
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

}
