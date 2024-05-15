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

use App\Entity\ListeProjet;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description ListeProjetRepository]
 */
class ListeProjetRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ListeProjet::class);
    }

    /**
     * [Description for countListeProjetVisibility]
     * Execute une requête paramétrique count avec type= PRIVATE || PUBLIC
     *
     * @param string $type
     *
     * @return array
     *
     * Created at: 27/10/2023 12:59:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countListeProjetVisibility($type): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT count(*) as visibility
                        FROM liste_projet
                        WHERE visibility=:visibility";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(":visibility", $type);
                $request=$stmt->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'request'=>$request, 'erreur'=>''];
    }


    /**
     * [Description for countListProjet]
     * Compte le nombre total de projet.
     * @return array
     *
     * Created at: 27/10/2023 13:54:53 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countListeProjet(): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT COUNT(*) AS total
                        FROM liste_projet";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $exec=$stmt->executeQuery();
                $nombre=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
            } catch (\Doctrine\DBAL\Exception $e) {
                $this->getEntityManager()->getConnection()->rollBack();
                return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'request'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for selectListeProjetByEquipe]
     * retourne la liste des projets en fonction de(s) (l')équipes.
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 26/03/2024 17:37:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectListeProjetByEquipe($map): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT DISTINCT liste_projet.maven_key AS id, liste_projet.name AS text
                        FROM ma_moulinette.liste_projet, jsonb_array_elements_text(liste_projet.tags::jsonb) AS tag
                        WHERE ".$map['clause_where'];
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for deleteListeProjet]
     *  Supprime tous les projets de la table
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 03/04/2024 09:47:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteListeProjet():array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "DELETE
                        FROM liste_projet";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

}
