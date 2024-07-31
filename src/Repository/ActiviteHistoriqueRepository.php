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

use App\Entity\ActiviteHistorique;
use DateTime;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActiviteHistoriqueRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public static $formatDate = 'Y-m-d H:i:sO';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActiviteHistorique::class);
    }

    /**
     * [Description for selectActivite]
     * On inserer la liste de toute les activites qui sont envoyé
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    //to.do
    public function insertHistoriqueActivites($data): array
    {
        $sql = "INSERT INTO historique_activite (annee, nb_jour, nb_analyse, moyenne_analyse, nb_reussi, nb_echec, taux_reussite, max_temps, date_enregistrement) VALUES (:annee, :nb_hour, :nb_analyse, :moyenne_analyse, :nb_reussi,:nb_echec, :taux_reussite, :max_temps, :date_enregistrement)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                foreach ($data as $annee => $valeur) {
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $stmt->bindValue(':annee', $annee);
                    $stmt->bindValue(':nb_hour', $valeur['nb_jour']);
                    $stmt->bindValue(':nb_analyse', $valeur['nb_analyse']);
                    $stmt->bindValue(':moyenne_analyse', $valeur['moyenne_analyse']);
                    $stmt->bindValue(':nb_reussi', $valeur['nb_reussi']);
                    $stmt->bindValue(':nb_echec', $valeur['nb_echec']);
                    $stmt->bindValue(':taux_reussite', $valeur['taux_reussite']);
                    $stmt->bindValue(':max_temps', $valeur['max_temps']);
                    $stmt->bindValue(':date_enregistrement', $valeur['date_enregistrement']->format('Y-m-d H:i:sO'));
                    $stmt->executeStatement();
                }
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectActivite]
     * On inserer la liste de toute les activites qui sont envoyé
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateHistoriqueActivites($data): array
    {
        $sql = "UPDATE historique_activite
                SET nb_jour = :nb_jour,
                    nb_analyse = :nb_analyse,
                    moyenne_analyse = :moyenne_analyse,
                    nb_reussi = :nb_reussi,
                    nb_echec = :nb_echec,
                    taux_reussite = :taux_reussite,
                    max_temps = :max_temps,
                    date_enregistrement = :date_enregistrement
                WHERE annee = :annee";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            foreach ($data as $annee => $valeur) {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $stmt->bindValue(':annee', $annee);
                $stmt->bindValue(':nb_jour', $valeur['nb_jour']);
                $stmt->bindValue(':nb_analyse', $valeur['nb_analyse']);
                $stmt->bindValue(':moyenne_analyse', $valeur['moyenne_analyse']);
                $stmt->bindValue(':nb_reussi', $valeur['nb_reussi']);
                $stmt->bindValue(':nb_echec', $valeur['nb_echec']);
                $stmt->bindValue(':taux_reussite', $valeur['taux_reussite']);
                $stmt->bindValue(':max_temps', $valeur['max_temps']);
                $stmt->bindValue(':date_enregistrement', $valeur['date_enregistrement']->format('Y-m-d H:i:sO'));
                $stmt->executeStatement();
            }
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code' => 500, 'erreur' => $e->getMessage()];
        }

        return ['code' => 200, 'erreur' => ''];
    }


    /**
     * [Description for selectActivite]
     * On recupere la liste de toute les activites
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectActivite($annee = null): array
    {
        try {
                $sql = " SELECT *
                        FROM historique_activite";
                        if ($annee !== null){
                            $sql .= " WHERE annee = :annee";
                            $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                            $stmt->bindValue(':annee', $annee);
                        }else{
                            $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        }
                        $request=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['request'=>$request, 'code'=>200, 'erreur'=>''];
    }

}
