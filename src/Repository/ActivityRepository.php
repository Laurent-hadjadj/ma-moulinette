<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Repository;

use App\Entity\Activity;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class ActivityRepository extends ServiceEntityRepository
{
    public static $removeReturnLine = "/\s+/u";
    public static $formatDate = 'Y-m-d H:i:sO';
    public static $noDataBase = 'La connexion à la base de données a échoué.';
    public static $year = ':year';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Activity::class);
    }

    public function handleDatabaseException(\Throwable $e): array
    {
        $message = $e->getMessage();

        // message = 'SQLSTATE[08006]'
        if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
            $message = static::$noDataBase;
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
     * [Description for selectActivity]
     * On récupère la liste de toute les activités
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectActivity($year): array
    {
        $sql = "SELECT *
                FROM ma_moulinette.activity
                WHERE EXTRACT(YEAR FROM started_at) = :year ";
            try {
                    $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                        $stmt->bindValue(static::$year, $year);
                    $liste = $stmt->executeQuery()->fetchAllAssociative();
            } catch (\Throwable $e) {
                return $this->handleDatabaseException($e);
        }
        return [ 'code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for insertActivity]
     * On insère la liste de toute les activités qui sont envoyé
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertActivity($data): array
    {
        $sql = "INSERT INTO ma_moulinette.activity (
                    maven_key, project_name, analyse_id,
                    status, submitter_login, submitted_at,
                    started_at, executed_at, execution_time)
                VALUES (
                    :maven_key, :project_name, :analyse_id,
                    :status, :submitter_login,:submitted_at,
                    :started_at, :executed_at, :execution_time)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                foreach ($data as $ref) {
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                        $stmt->bindValue(':maven_key', $ref['maven_key']);
                        $stmt->bindValue(':project_name', $ref['project_name']);
                        $stmt->bindValue(':analyse_id', $ref['analyse_id']);
                        $stmt->bindValue(':status', $ref['status']);
                        $stmt->bindValue(':submitter_login', $ref['submitter_login']);
                        $stmt->bindValue(':submitted_at', $ref['submitted_at']->format(static::$formatDate));
                        $stmt->bindValue(':started_at', $ref['started_at']->format(static::$formatDate));
                        $stmt->bindValue(':executed_at', $ref['executed_at']->format(static::$formatDate));
                        $stmt->bindValue(':execution_time', $ref['execution_time']);
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
     * [Description for nombreJourAnneeDonnee]
     * On compte le nombre de jour pour une année donnée
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function nombreJourAnneeDonnee($year): array
    {
        $sql = "SELECT COUNT(DISTINCT started_at) AS unique_days
                FROM ma_moulinette.activity
                WHERE EXTRACT(YEAR FROM started_at) = :year ";
        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$year, $year);
                $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return [ 'code' => 200, 'request' => $request[0], 'erreur' => ''];
    }

    /**
     * [Description for tempsExecutionMax]
     * On recherche du temps max d'execution pour une année donnée
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function tempsExecutionMax($year): array
    {
        $sql = "SELECT max(execution_time) AS max_time
                FROM ma_moulinette.activity
                WHERE EXTRACT(YEAR FROM started_at) = :year ";
        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue("year", $year);
                $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return [ 'code' => 200, 'request' => $request[0], 'erreur' => ''];
    }

    /**
     * [Description for nombreStatus]
     * On compte le nombre de status (SUCCESS,FAILED) envoyer pour une année donnée
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function nombreStatus($year, $status): array
    {
        $sql = "SELECT COUNT(*) AS nb_status
                FROM ma_moulinette.activity
                WHERE EXTRACT(YEAR FROM started_at) = :year
                AND status LIKE :status";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$year, $year);
                    $stmt->bindValue(':status', $status);
                $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return [ 'code' => 200, 'request' => $request[0], 'erreur' => ''];
    }

    /**
     * [Description for nombreAnalyse]
     * On compte le nombre d'analyse pour une année donnée
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function nombreAnalyse($year): array
    {
        $sql = "SELECT COUNT(*) AS nb_analyse
                FROM ma_moulinette.activity
                WHERE EXTRACT(YEAR FROM started_at) = :year";

        try {
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(static::$year, $year);
                $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return [ 'code' => 200, 'request' => $request[0], 'erreur' => ''];
    }

    /**
     * [Description for dernierDate]
     * On récupère la date la plus récente dans la table pour une année donnée ou non
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function dernierDate(?int $year = null): array
    {
        // Base SQL
        $sql = "SELECT executed_at AS date FROM ma_moulinette.activity";

        try {
            // Ajout de la condition si une année est fournie
            if ($year !== null) {
                $sql .= " WHERE EXTRACT(YEAR FROM started_at) = :year";
            }
            // Ajout de l'ordre et de la limite
            $sql .= " ORDER BY executed_at DESC LIMIT 1";

            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));

            // Ajout des paramètres si nécessaire
            if ($year !== null) {
                $stmt->bindValue(':year', $year);
            }
            // Exécution de la requête
            $liste = $stmt->executeQuery()->fetchAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for premiereDate]
     * On récupère la date la plus récente dans la table pour une année donnée
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function premiereDate(?int $year = null): array
    {
        // Construction de la base de la requête SQL
        $sql = "SELECT executed_at AS date FROM ma_moulinette.activity";

        try {
            // Ajout conditionnel pour l'année
            if ($year !== null) {
                $sql .= " WHERE EXTRACT(YEAR FROM started_at) = :year";
            }

            // Ajout de l'ordre et de la limite
            $sql .= " ORDER BY executed_at ASC LIMIT 1";

            // Préparation de la requête
            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));

            // Ajout des paramètres si une année est spécifiée
            if ($year !== null) {
                $stmt->bindValue(':year', $year);
            }

            // Exécution de la requête et récupération du résultat
            $liste = $stmt->executeQuery()->fetchAssociative();

            // Retourne une réponse formatée avec le code de succès
            return [
                // Retourne un tableau vide si aucun résultat n'est trouvé
                'liste' => $liste ?: [],
                'code' => 200, 'erreur' => '',
            ];
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
    }


    /**
     * [Description for listeProjectAnalyse]
     * On récupère la date la plus récente dans la table pour une année donnée
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listeProjectAnalyse($year = null): array
    {
        $sql = "SELECT DATE(executed_at) AS day,
                    COUNT(DISTINCT project_name) AS count
                FROM ma_moulinette.activity ";
        try {
            if ($year !== null) {
                $sql .= "WHERE EXTRACT(YEAR FROM executed_at) = :year
                        GROUP BY DATE(executed_at) ORDER BY day ASC";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$year, $year);
            } else {
                $sql .= "GROUP BY DATE(executed_at) ORDER BY day DESC ";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            }
            $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return [ 'code' => 200, 'request' => $request, 'erreur' => ''];
    }

    /**
     * [Description for  listeAnalyseJour]
     * On récupère la date la plus récente dans la table pour une année donnée
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listeAnalyseJour($year = null): array
    {
        try {
            $sql = "SELECT DATE(executed_at) AS day,
                            COUNT(*) AS count FROM ma_moulinette.activity ";
            if ($year !== null) {
                $sql .= "WHERE EXTRACT(YEAR FROM executed_at) = :year
                GROUP BY DATE(executed_at) ORDER BY day ASC";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$year, $year);
            } else {
                $sql .= "GROUP BY DATE(executed_at) ORDER BY day DESC";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            }
            $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return [ 'code' => 200, 'request' => $request, 'erreur' => ''];
    }

    /**
     * [Description for listeAnalyseProjet]
     * On récupère la date la plus récente dans la table pour une année donnée
     *
     * @return array
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Quentin BOUETEL <pro.qbouetel1@gmail.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function listeAnalyseProjet($year = null): array
    {
        try {
            $sql = "SELECT DATE(executed_at) AS day,
                        COUNT(*) AS analyse,
                        COUNT(DISTINCT project_name) AS projet
                    FROM ma_moulinette.activity ";
            if ($year !== null) {
                $sql .= "WHERE EXTRACT(YEAR FROM executed_at) = :year
                        GROUP BY DATE(executed_at) ORDER BY day ASC";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                $stmt->bindValue(static::$year, $year);
            } else {
                $sql .= "GROUP BY DATE(executed_at) ORDER BY day DESC";
                $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            }
            $request = $stmt->executeQuery()->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return [ 'code' => 200, 'request' => $request, 'erreur' => ''];
    }
}
