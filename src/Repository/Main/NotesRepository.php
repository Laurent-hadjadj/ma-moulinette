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

use App\Entity\Main\Notes;
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
     * @param mixed $mode
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 12/03/2024 09:31:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteNotesMavenKey($mode,$map):array
    {
        $sql = "DELETE
                FROM notes
                WHERE maven_key=:maven_key and type=:type";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':type', $map['type']);
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
    public function InsertNotes($mode,$map):array
    {
        $sql = "INSERT INTO notes (maven_key, type, value, date_enregistrement)
                VALUES (:maven_key, :type, :value, :date_enregistrement)";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':type', $map['type']);
        $conn->bindValue(':value', $map['value']);
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
}
