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

use App\Entity\Notes;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description NotesRepository]
 */
class NotesRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Notes::class);
    }

    /**
     * [Description for deleteNotesMavenKey]
     * Supprime les notes de la version courrante (i.e. correspondant à la maven_key)
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 12/03/2024 09:31:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteNotesMavenKey($map):array
    {
        $sql = "DELETE
                FROM notes
                WHERE maven_key=:maven_key and type=:type";
        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $stmt->bindValue(':maven_key', $map['maven_key']);
        $stmt->bindValue(':type', $map['type']);
        try {
                $stmt->executeQuery();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->rollback();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for InsertNotes]
     * Ajoute les notes pour le projet
     *
     * @param string $mode
     * @param array $map
     *
     * @return array
     *
     * Created at: 12/03/2024 21:47:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function InsertNotes($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "INSERT INTO notes (maven_key, type, value, date_enregistrement)
                VALUES (:maven_key, :type, :value, :date_enregistrement)";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':maven_key', $map['maven_key']);
                    $stmt->bindValue(':type', $map['type']);
                    $stmt->bindValue(':value', $map['value']);
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
     * [Description for selectNoteMavenType]
     * retourne la note par type (reliability, security, sqale) pour un projet.
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 20/03/2024 16:20:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectNotesMavenType($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT type, value, date_enregistrement
                        FROM notes
                        WHERE maven_key=:maven_key AND type=:type
                        ORDER BY date_enregistrement DESC LIMIT 1";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $stmt->bindValue(':maven_key', $map['maven_key']);
                $stmt->bindValue(':type', $map['type']);
                $exec=$stmt->executeQuery();
                $liste=$exec->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
    }

}
