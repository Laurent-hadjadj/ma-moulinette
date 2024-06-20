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

use App\Entity\BatchTraitement;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<BatchTraitement>
 */
class BatchTraitementRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, BatchTraitement::class);
    }

    /**
     * [Description for selectBatchTraitementDateEnregistrementAutomatiqueLast]
     * On récupère la dernière date de programmation du batch automatique
     *
     *
     * @return array
     *
     * Created at: 10/04/2024 09:10:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectBatchTraitementDateEnregistrementAutomatiqueLast():array
    {
        try {
                $sql = "SELECT date_enregistrement as date
                        FROM batch_traitement
                        WHERE demarrage='Automatique'
                        ORDER BY date_enregistrement DESC limit 1";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectBatchTraitementDateEnregistrementLast]
     * On récupère la dernière date du batch exécuté
     *
     * @return array
     *
     * Created at: 10/04/2024 09:35:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectBatchTraitementDateEnregistrementLast():array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT date_enregistrement as date
                        FROM batch_traitement
                        ORDER BY date_enregistrement DESC limit 1";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectBatchTraitementLast]
     * On récupère la liste des derniers traitements,
     * groupé par titre et ordonné par responsable
     *
     * @param string $date
     *
     * @return array
     *
     * Created at: 10/04/2024 09:38:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectBatchTraitementLast($dateShort):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT  demarrage, resultat, titre, portefeuille,
                                nombre_projet as projet, responsable,
                                debut_traitement as debut, fin_traitement as fin
                        FROM batch_traitement
                        WHERE date(date_enregistrement)= :date_short
                        GROUP BY demarrage, resultat, titre, portefeuille, nombre_projet, responsable, debut_traitement, fin_traitement
                        ORDER BY responsable ASC, demarrage ASC";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':date_short', $dateShort);
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for selectBatchTraitement]
     *
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 09/06/2024 21:55:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectBatchTraitement($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT id, demarrage, titre, portefeuille, nombre_projet as projet
                        FROM batch_traitement
                        WHERE titre = :titre";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':titre', $map['portefeuille']);
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

    /**
     * [Description for updateBatchTraitement]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 22/05/2024 17:56:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateBatchTraitement($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "UPDATE batch_traitement
                        SET debut_traitement = :debut, fin_traitement = :fin, resultat = true
                        WHERE id = :id";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':debut_traitement', $map['debut_traitement']);
                    $stmt->bindValue(':fin_traitement', $map['fin_traitement']);
                    $stmt->bindValue(':id', $map['id']);
                    $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for insertBatchTraitement]
     *
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 07/06/2024 11:54:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertBatchTraitement($map):array
    {
        $connection = $this->getEntityManager()->getConnection();
        try {
            $connection->beginTransaction();

            $sql = "INSERT INTO batch_traitement
                    (demarrage, resultat, titre, portefeuille, nombre_projet, responsable, date_enregistrement)
                    VALUES (:demarrage, :resultat, :titre, :portefeuille, :nombre_projet, :responsable, :date_enregistrement)";

            $stmt = $connection->prepare(preg_replace(static::$removeReturnline, " ", $sql));

            $stmt->bindValue(':demarrage', $map['demarrage']);
            $stmt->bindValue(':resultat', $map['resultat']);
            $stmt->bindValue(':titre', $map['titre']);
            $stmt->bindValue(':portefeuille', $map['portefeuille']);
            $stmt->bindValue(':nombre_projet', $map['nombre_projet']);
            $stmt->bindValue(':responsable', $map['responsable']);
            $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));

            $stmt->executeStatement();

            $connection->commit();
            return ['code' => 200, 'erreur' => ''];
        } catch (\Doctrine\DBAL\Exception $e) {
            $connection->rollBack();
            return ['code' => 500, 'erreur' => $e->getMessage()];
        }
    }
}
