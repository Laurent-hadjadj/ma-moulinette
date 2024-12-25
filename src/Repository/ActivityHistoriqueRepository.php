<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Repository;

use App\Entity\ActivityHistorique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActivityHistoriqueRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $formatDate = 'Y-m-d H:i:sO';
  public static $year = ':year';

  public function __construct(ManagerRegistry $registry)
  {
      parent::__construct($registry, ActivityHistorique::class);
  }

  /**
   * [Description for insertHistoriqueActivity]
   * On insère la liste de toutes les activités qui sont envoyé
   *
   * @return array
   *
   * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
   * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function insertHistoriqueActivity($data): array
  {
    $sql = "INSERT INTO ma_moulinette.activity_historique
                    (year, day, analyse, analyse_average,
                    success, fail, success_rate, max_time,
                    date_enregistrement)
            VALUES (:year, :nb_hour, :analyse, :analyse_average,
                    :success, :fail, :success_rate, :max_time, :date_enregistrement)";
    try {
          $this->getEntityManager()->getConnection()->beginTransaction();
            foreach ($data as $year => $valeur) {
              $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$year, $year);
                $stmt->bindValue(':nb_hour', $valeur['day']);
                $stmt->bindValue(':analyse', $valeur['analyse']);
                $stmt->bindValue(':analyse_average', $valeur['analyse_average']);
                $stmt->bindValue(':success', $valeur['success']);
                $stmt->bindValue(':fail', $valeur['fail']);
                $stmt->bindValue(':success_rate', $valeur['success_rate']);
                $stmt->bindValue(':max_time', $valeur['max_time']);
                $stmt->bindValue(':date_enregistrement', $valeur['date_enregistrement']->format(static::$formatDate));
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
   * [Description for updateHistoriqueActivity]
   * On insérer la liste de toute les activities qui sont envoyé
   *
   * @return array
   *
   * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
   * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function updateHistoriqueActivity($data): array
  {
    $sql = "UPDATE ma_moulinette.activity_historique
            SET day = :day,
                analyse = :analyse,
                analyse_average = :analyse_average,
                success = :success,
                fail = :fail,
                success_rate = :success_rate,
                max_time = :max_time,
                date_enregistrement = :date_enregistrement
            WHERE year = :year";
      try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            foreach ($data as $year => $valeur) {
              $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$year, $year);
                $stmt->bindValue(':day', $valeur['day']);
                $stmt->bindValue(':analyse', $valeur['analyse']);
                $stmt->bindValue(':analyse_average', $valeur['analyse_average']);
                $stmt->bindValue(':success', $valeur['success']);
                $stmt->bindValue(':fail', $valeur['fail']);
                $stmt->bindValue(':success_rate', $valeur['success_rate']);
                $stmt->bindValue(':max_time', $valeur['max_time']);
                $stmt->bindValue(':date_enregistrement', $valeur['date_enregistrement']->format(static::$formatDate));
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
   * [Description for selectActivity]
   * On récupère la liste de toute les activities
   *
   * @return array
   *
   * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
   * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectActivity($year = null): array
  {
    $sql = "SELECT * FROM ma_moulinette.activity_historique";
    $params = [];

    if ($year !== null) {
        $sql .= " WHERE year = :year";
        $params[':year'] = $year;
    }

    try {
        // Préparer et nettoyer la requête
        $sql = preg_replace(static::$removeReturnLine, " ", $sql);
        $stmt = $this->getEntityManager()->getConnection()->prepare($sql);

        // Associer les paramètres, si présents
        foreach ($params as $key => $value) {
            $stmt->bindValue($key, $value);
        }

        $liste = $stmt->executeQuery()->fetchAllAssociative();

    } catch (\Doctrine\DBAL\Exception $e) {
        return [
            'code' => 500,
            'erreur' => $e->getMessage()
        ];
    }

    return [
        'liste' => $liste,
        'code' => 200,
        'erreur' => ''
    ];




    $sql = "SELECT *
            FROM ma_moulinette.activity_historique";
    try {
          if ($year !== null){
              $sql .= " WHERE year = :year";
              $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
              $stmt->bindValue(static::$year, $year);
          }else{
              $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
          }
          $request=$stmt->executeQuery()->fetchAllAssociative();
    } catch (\Doctrine\DBAL\Exception $e) {
        return ['code'=>500, 'erreur'=> $e->getCode()];
    }
    return ['request'=>$request, 'code'=>200, 'erreur'=>''];
  }

}
