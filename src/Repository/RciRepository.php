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

use App\Entity\Rci;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/* MODIF 2026-09-25 : repository pour le détail par module (frontend/backend/
 * autre/inconnu) du RCI (Risk Criticality Index, plugin sonar-rci porté en
 * PHP). Snapshot courant, sur le modèle de CleanCodeRepository. */

/**
 * @extends ServiceEntityRepository<Rci>
 */
class RciRepository extends ServiceEntityRepository
{
    private static string $removeReturnLine = "/\s+/u";
    private static string $mavenKey = ':maven_key';
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Rci::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/09/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
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
     * [Description for deleteRciMavenKey]
     * Supprime le détail RCI par module du projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/09/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteRciMavenKey(array $map): array
    {
        $sql = "DELETE FROM ma_moulinette.rci WHERE maven_key = :maven_key";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(
                preg_replace(self::$removeReturnLine, ' ', $sql)
            );
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'erreur' => ''];
    }

    /**
     * [Description for selectMesuresRci]
     * Retourne le détail RCI par module d'un projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/09/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectMesuresRci(array $map): array
    {
        $sql = "SELECT *
                FROM ma_moulinette.rci
                WHERE maven_key = :maven_key
                ORDER BY date_enregistrement DESC
                LIMIT 1";
        try {
            $stmt = $this->getEntityManager()->getConnection()->prepare(
                preg_replace(self::$removeReturnLine, ' ', $sql)
            );
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $liste = $stmt->executeQuery()->fetchAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * [Description for insertRci]
     * Insère le détail RCI par module d'un projet.
     *
     * @param array<int|string, mixed> $map
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/09/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertRci(array $map): array
    {
        $sql = "INSERT INTO ma_moulinette.rci
                    (maven_key,
                    ncloc, weight, indicator, rating,
                    frontend_ncloc, frontend_weight, frontend_indicator, frontend_rating,
                    backend_ncloc, backend_weight, backend_indicator, backend_rating,
                    autre_ncloc, autre_weight, autre_indicator, autre_rating,
                    inconnu_ncloc, inconnu_weight, inconnu_indicator, inconnu_rating,
                    mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES
                    (:maven_key,
                    :ncloc, :weight, :indicator, :rating,
                    :frontend_ncloc, :frontend_weight, :frontend_indicator, :frontend_rating,
                    :backend_ncloc, :backend_weight, :backend_indicator, :backend_rating,
                    :autre_ncloc, :autre_weight, :autre_indicator, :autre_rating,
                    :inconnu_ncloc, :inconnu_weight, :inconnu_indicator, :inconnu_rating,
                    :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $stmt = $this->getEntityManager()->getConnection()->prepare(
                preg_replace(self::$removeReturnLine, ' ', $sql)
            );
            $stmt->bindValue(self::$mavenKey, $map['maven_key']);
            $stmt->bindValue(':ncloc', $map['ncloc']);
            $stmt->bindValue(':weight', $map['weight']);
            $stmt->bindValue(':indicator', $map['indicator']);
            $stmt->bindValue(':rating', $map['rating']);
            $stmt->bindValue(':frontend_ncloc', $map['frontend_ncloc']);
            $stmt->bindValue(':frontend_weight', $map['frontend_weight']);
            $stmt->bindValue(':frontend_indicator', $map['frontend_indicator']);
            $stmt->bindValue(':frontend_rating', $map['frontend_rating']);
            $stmt->bindValue(':backend_ncloc', $map['backend_ncloc']);
            $stmt->bindValue(':backend_weight', $map['backend_weight']);
            $stmt->bindValue(':backend_indicator', $map['backend_indicator']);
            $stmt->bindValue(':backend_rating', $map['backend_rating']);
            $stmt->bindValue(':autre_ncloc', $map['autre_ncloc']);
            $stmt->bindValue(':autre_weight', $map['autre_weight']);
            $stmt->bindValue(':autre_indicator', $map['autre_indicator']);
            $stmt->bindValue(':autre_rating', $map['autre_rating']);
            $stmt->bindValue(':inconnu_ncloc', $map['inconnu_ncloc']);
            $stmt->bindValue(':inconnu_weight', $map['inconnu_weight']);
            $stmt->bindValue(':inconnu_indicator', $map['inconnu_indicator']);
            $stmt->bindValue(':inconnu_rating', $map['inconnu_rating']);
            $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
            $stmt->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);
            $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));
            $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return $this->handleDatabaseException($e);
        }
        return ['code' => 200, 'erreur' => ''];
    }
}
