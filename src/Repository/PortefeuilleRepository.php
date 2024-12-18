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

use App\Entity\Portefeuille;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description PortefeuilleRepository]
 */
class PortefeuilleRepository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, Portefeuille::class);
  }


  /**
   * [Description for handleDatabaseException]
   *
   * @param \Doctrine\DBAL\Exception $e
   *
   * @return array
   *
   * Created at: 18/12/2024 15:12:12 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
  {
    if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
        return ['code'=>500, 'erreur' => static::$noDataBase];
    } else {
        return ['code' => 500, 'erreur' => $e->getMessage()];
    }
  }

  /**
   * [Description for selectPortefeuille]
   * Retourne la liste des projets d'un portefeuille
   *
   * @param array $map
   *
   * @return array
   *
   * Created at: 10/04/2024 11:23:35 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectPortefeuille($map): array
  {
    $sql = "SELECT liste
            FROM ma_moulinette.portefeuille
            WHERE titre=:portefeuille";
    try {
          /** On escape les ' : normalement on en a pas besoin */
          //"$reEncode = str_replace("'", "''", $map['portefeuille']);

          $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(':portefeuille', $map['portefeuille']);
          $liste=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
    }
    return ['code'=>200, 'liste'=>$liste, 'erreur'=>''];
  }
}
