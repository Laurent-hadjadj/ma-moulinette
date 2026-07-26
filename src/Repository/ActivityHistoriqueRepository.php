<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
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

/**
 * @extends ServiceEntityRepository<ActivityHistorique>
 */
class ActivityHistoriqueRepository extends ServiceEntityRepository
{
    private static string $removeReturnLine = "/\s+/u";
    private static string $formatDate = 'Y-m-d H:i:sO';
    private static string $year = ':year';
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActivityHistorique::class);
    }

    /**
     * @return array{code: int, erreur: string}
     */
    public function handleDatabaseException(\Throwable $e): array
    {
        $message = $e->getMessage();

        // message = 'SQLSTATE[08006]'
        if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
            $message = self::$noDataBase;
        }

        // state = '23502'
        if ($e instanceof \Doctrine\DBAL\Exception\NotNullConstraintViolationException) {
            $message = $e->getMessage();
        }

        // state = '23505'
        if ($e instanceof \Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
        }

        return ['code' => 500, 'erreur' => $message];
    }

    /**
     * [Description for insertHistoriqueActivity]
     * On insère la liste de toutes les activités qui sont envoyé
     *
     * @param array<int, array{day: int, analyse: int, analyse_average: int|float, success: int,
     *              failed: int, success_rate: float, max_time: int,
     *              date_enregistrement: \DateTimeInterface}> $data
     *
     * @return array<int|string, mixed>
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertHistoriqueActivity(array $data): array
    {
        $sql = "INSERT INTO ma_moulinette.activity_historique
                        (year, day, \"analyse\", analyse_average,
                        success, failed, success_rate, max_time,
                        date_enregistrement)
                VALUES (:year, :day, :analyse, :analyse_average,
                        :success, :failed, :success_rate, :max_time, :date_enregistrement)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                foreach ($data as $year => $valeur) {
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(self::$year, $year);
                    $stmt->bindValue(':day', $valeur['day']);
                    $stmt->bindValue(':analyse', $valeur['analyse']);
                    $stmt->bindValue(':analyse_average', $valeur['analyse_average']);
                    $stmt->bindValue(':success', $valeur['success']);
                    $stmt->bindValue(':failed', $valeur['failed']);
                    $stmt->bindValue(':success_rate', $valeur['success_rate']);
                    $stmt->bindValue(':max_time', $valeur['max_time']);
                    $stmt->bindValue(':date_enregistrement', $valeur['date_enregistrement']->format(self::$formatDate));
                    $stmt->executeStatement();
                }
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return [ 'code' => 200, 'erreur' => ''];
    }

    /**
     * [Description for updateHistoriqueActivity]
     * On insérer la liste de toute les activities qui sont envoyé
     *
     * @param array<int, array{day: int, analyse: int, analyse_average: int|float, success: int,
     *              failed: int, success_rate: float, max_time: int,
     *              date_enregistrement: \DateTimeInterface}> $data
     *
     * @return array<int|string, mixed>
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function updateHistoriqueActivity(array $data): array
    {
        $sql = "UPDATE ma_moulinette.activity_historique
                SET day = :day,
                    \"analyse\" = :analyse,
                    analyse_average = :analyse_average,
                    success = :success,
                    failed = :failed,
                    success_rate = :success_rate,
                    max_time = :max_time,
                    date_enregistrement = :date_enregistrement
                WHERE year = :year";
        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                foreach ($data as $year => $valeur) {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(self::$year, $year);
                    $stmt->bindValue(':day', $valeur['day']);
                    $stmt->bindValue(':analyse', $valeur['analyse']);
                    $stmt->bindValue(':analyse_average', $valeur['analyse_average']);
                    $stmt->bindValue(':success', $valeur['success']);
                    $stmt->bindValue(':failed', $valeur['failed']);
                    $stmt->bindValue(':success_rate', $valeur['success_rate']);
                    $stmt->bindValue(':max_time', $valeur['max_time']);
                    $stmt->bindValue(':date_enregistrement', $valeur['date_enregistrement']->format(self::$formatDate));
                    $stmt->executeStatement();
                }
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'erreur' => ''];
    }

    /**
     * [Description for selectActivity]
     * On récupère la liste de toute les activities
     *
     * @return array<int|string, mixed>
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectActivity(int|string|null $year = null): array
    {
        $sql = "SELECT *
                FROM ma_moulinette.activity_historique";
        $params = [];

        if ($year !== null) {
            $sql .= " WHERE year = :year";
            $params[':year'] = $year;
        }

        try {
            // Préparer et nettoyer la requête
            $sql = preg_replace(self::$removeReturnLine, " ", $sql);
            $stmt = $this->getEntityManager()->getConnection()->prepare($sql);

            // Associer les paramètres, si présents
            foreach ($params as $key => $value) {
                $stmt->bindValue($key, $value);
            }

            $liste = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
        return $this->handleDatabaseException($e);
        }
        return [ 'code' => 200, 'liste' => $liste, 'erreur' => '' ];
    }
}
