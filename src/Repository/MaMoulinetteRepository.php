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

use App\Entity\MaMoulinette;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description MaMoulinetteRepository]
 */
class MaMoulinetteRepository extends ServiceEntityRepository
{
    public static $removeReturnLine = "/\s+/u";
    public static $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, MaMoulinette::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Doctrine\DBAL\Exception $e
     *
     * @return array
     *
     * Created at: 18/12/2024 15:40:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * [Description for  getMaMoulinetteVersion]
     * Récupère la version de Ma Moulinette
     * @return array
     *
     * Created at: 27/10/2023 15:45:02 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getMaMoulinetteVersion(): array
    {
        $sql = "SELECT version
                FROM ma_moulinette.ma_moulinette
                ORDER BY date_version DESC LIMIT 1";
        try {
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $request=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code'=>200, 'request'=>$request, 'erreur'=>''];
    }

}
