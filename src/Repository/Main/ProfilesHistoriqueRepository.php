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

}
