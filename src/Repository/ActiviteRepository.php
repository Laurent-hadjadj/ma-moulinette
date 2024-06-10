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
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActiviteRepository extends ServiceEntityRepository
{

    public static $removeReturnline = "/\s+/u";

    public static $formatDate = 'Y-m-d H:i:sO';

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
    public function selectActivite($annee): array
    {
        try {
                $sql = " SELECT *
                        FROM activite WHERE EXTRACT(YEAR FROM started_at) = :annee ";
                        $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue("annee", $annee);
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
        $sql = "INSERT INTO activite (maven_key, project_name, analyse_id, status, submitter_login, submitted_at, started_at, executed_at, execution_time) VALUES (:maven_key, :project_name, :analyse_id, :status, :submitter_login,:submitted_at, :started_at, :executed_at, :execution_time)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                foreach ($data as $ref) {
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue(':maven_key', $ref['maven_key']);
                        $stmt->bindValue(':project_name', $ref['project_name']);
                        $stmt->bindValue(':analyse_id', $ref['analyse_id']);
                        $stmt->bindValue(':status', $ref['status']);
                        $stmt->bindValue(':submitter_login', $ref['submitter_login']);
                        $stmt->bindValue(':submitted_at', $ref['submitted_at']->format('Y-m-d H:i:sO'));
                        $stmt->bindValue(':started_at', $ref['started_at']->format('Y-m-d H:i:sO'));
                        $stmt->bindValue(':executed_at', $ref['executed_at']->format('Y-m-d H:i:sO'));
                        $stmt->bindValue(':execution_time', $ref['execution_time']);
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

    /**
     * [Description for selectActivite]
     * On recupere la date la plus recente dans la table pour une année donnée
     *
     * @return array
     *
     * Created at: 28/05/2024 13:56:31 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function dernierDate($annee = null): array
    {
        try {
            $sql = "SELECT executed_at as date FROM activite";
            if ($annee !== null) {
                $sql .= " WHERE EXTRACT(YEAR FROM started_at) = :annee ORDER BY executed_at DESC LIMIT 1";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $stmt->bindValue("annee", $annee);
            } else {
                $sql .= " ORDER BY executed_at DESC LIMIT 1";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            }
            $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code' => 500, 'erreur' => $e->getCode()];
        }
        return ['request' => $request, 'code' => 200, 'erreur' => ''];
    }

    /**
     * [Description for selectActivite]
     * On recupere la date la plus recente dans la table pour une année donnée
     *
     * @return array
     *
     * Created at: 28/05/2024 13:56:31 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function premiereDate($annee = null): array
    {
        try {
            $sql = "SELECT executed_at as date FROM activite";
            if ($annee !== null) {
                $sql .= " WHERE EXTRACT(YEAR FROM started_at) = :annee ORDER BY executed_at ASC LIMIT 1";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $stmt->bindValue("annee", $annee);
            } else {
                $sql .= " ORDER BY executed_at DESC LIMIT 1";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            }
            $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code' => 500, 'erreur' => $e->getCode()];
        }
        return ['request' => $request, 'code' => 200, 'erreur' => ''];
    }
}
