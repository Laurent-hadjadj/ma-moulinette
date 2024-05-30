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

use App\Entity\Activite;
use DateTime;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActiviteRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activite::class);
    }

    /**
     * [Description for selectActivite]
     * On recupere la liste de toute les activites
     *
     * @return array
     *
     * Created at: 21/05/2024 13:56:31 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectActivite(): array
    {
        try {
                $sql = " SELECT *
                        FROM activite";
                        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $request=$stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['request'=>$request, 'code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectActivite]
     * On inserer la liste de toute les activites qui sont envoyé
     *
     * @return array
     *
     * Created at: 21/05/2024 13:56:31 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertActivites($data): array
    {
        // Gestion des erreurs
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            // Le début de la requête SQL qui ne change pas
            $sql = "INSERT OR IGNORE INTO activite (maven_key, project_name, analyse_id, status, submitter_login, started_at, executed_at, execution_time) VALUES ";
            // La variable rows sert à sauvegarder les lignes d'insertion
            $rows = array();
            foreach ($data as $value) {
                $maven_key = $value['componentKey'];
                $project_name = $value['componentName'];
                $analyse_id = $value['analysisId'];
                $status = $value['status'];
                $submitter_login = $value['submitterLogin'];
                // Formater les dates
                if (preg_match("/(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})/", $value['startedAt'], $matches)) {
                    $format = $matches[1] . " " . $matches[2];
                    $started_at = new DateTime($format);
                    $started_at_formatted = $started_at->format('Y-m-d H:i:s');
                }
                if (preg_match("/(\d{4}-\d{2}-\d{2})T(\d{2}:\d{2}:\d{2})/", $value['executedAt'], $matches)) {
                    $format = $matches[1] . " " . $matches[2];
                    $executed_at = new DateTime($format);
                    $executed_at_formatted = $executed_at->format('Y-m-d H:i:s');
                }
                $execution_time = (int) round($value['executionTimeMs'] / 1000)+1; // Conversion de l'input en ms en s
                // Construction de la ligne pour la requête SQL
                $row = "('$maven_key', '$project_name', '$analyse_id', '$status', '$submitter_login', '$started_at_formatted', '$executed_at_formatted', $execution_time)";
                // Ajout de la ligne dans le tableau pour la concaténation
                $rows[] = $row;
            }
            // Concaténation des lignes avec des virgules pour former la partie VALUES de la requête
            $sql .= implode(',', $rows) . ';';
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            $stmt->executeQuery();
            $this->getEntityManager()->getConnection()->commit();
            return ['request' => [], 'code' => 200, 'erreur' => ''];
        } catch (\Doctrine\DBAL\Exception $e) {
            // Rollback de la transaction en cas d'erreur
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code' => 500, 'erreur' => $e->getCode()];
        }
    }

    /**
     * [Description for selectActivite]
     * On compte le nombre de jour pour une année donnée
     *
     * @return array
     *
     * Created at: 28/05/2024 13:56:31 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function nombreJourAnneeDonnee($annee): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT COUNT(DISTINCT started_at) AS unique_days
                        FROM activite WHERE EXTRACT(YEAR FROM started_at) = :annee ";
                        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue("annee", $annee);
                $request=$stmt->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['request'=>$request[0], 'code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectActivite]
     * On recherche du temps max d'execution pour une année donnée
     *
     * @return array
     *
     * Created at: 28/05/2024 13:56:31 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function tempsExecutionMax($annee): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT max(execution_time) AS max_time
                        FROM activite WHERE EXTRACT(YEAR FROM started_at) = :annee ";
                        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue("annee", $annee);
                $request=$stmt->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['request'=>$request[0], 'code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectActivite]
     * On compte le nombre de status (SUCCESS,FAILED) envoyer pour une année donnée
     *
     * @return array
     *
     * Created at: 28/05/2024 13:56:31 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function nombreStatus($annee,$status): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT COUNT(*) AS nb_status
                        FROM activite WHERE EXTRACT(YEAR FROM started_at) = :annee and status like :status ";
                        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue("annee", $annee);
                        $stmt->bindValue("status", $status);
                $request=$stmt->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['request'=>$request[0], 'code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for selectActivite]
     * On compte le nombre d'analyse pour une année donnée
     *
     * @return array
     *
     * Created at: 28/05/2024 13:56:31 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function nombreAnalyse($annee): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "SELECT COUNT(*) AS nb_analyse
                        FROM activite WHERE EXTRACT(YEAR FROM started_at) = :annee";
                        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue("annee", $annee);
                $request=$stmt->executeQuery()->fetchAllAssociative();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getCode()];
        }
        return ['request'=>$request[0], 'code'=>200, 'erreur'=>''];
    }

}
