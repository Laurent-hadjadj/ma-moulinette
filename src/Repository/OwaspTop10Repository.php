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

use App\Entity\OwaspTop10;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class OwaspTop10Repository extends ServiceEntityRepository
{
  public static $removeReturnLine = "/\s+/u";
  public static $noDataBase = 'La connexion à la base de données a échoué.';

  public function __construct(ManagerRegistry $registry)
  {
    parent::__construct($registry, OwaspTop10::class);
  }

  /**
   * [Description for handleDatabaseException]
   *
   * @param \Doctrine\DBAL\Exception $e
   *
   * @return array
   *
   * Created at: 21/10/2024 16:55:20 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Lilmod & Lelamed - Creative Common CC-BY-NC-SA 4.0.
   */
  protected function handleDatabaseException(\Doctrine\DBAL\Exception $e): array
  {
    if (strpos($e->getMessage(), 'SQLSTATE[08006]') !== false) {
      return ['code' => 500, 'erreur' => static::$noDataBase];
    } else {
      return ['code' => 500, 'erreur' => $e->getMessage()];
    }
  }

  /**
   * [Description for selectTop10OwaspReferential]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 19/11/2024 20:24:00 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectOwaspTop10Referential($map): array
  {
    $sql = "SELECT *
            FROM ma_moulinette.owasp_top10
            WHERE year = :referential_version ORDER BY id";
    try {
          $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(':referential_version', $map['referential_version']);
            $liste = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Doctrine\DBAL\Exception $e) {
      return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
  }

  /**
   * [Description for selectOwaspTop10Details]
   *
   * @param mixed $map
   *
   * @return array
   *
   * Created at: 21/11/2024 20:11:03 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function selectOwaspTop10Details($map): array
  {
    $sql = "SELECT *
            FROM ma_moulinette.owasp_top10
            WHERE id = :menace";
    try {
          $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(':menace', $map['menace']);
          $details = $stmt->executeQuery()->fetchAllAssociative();
    } catch (\Doctrine\DBAL\Exception $e) {
        return $this->handleDatabaseException($e);
    }
    return ['code' => 200, 'details' => $details, 'erreur' => ''];
  }
}
