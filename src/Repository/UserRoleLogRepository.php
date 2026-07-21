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

use App\Entity\UserRoleLog;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserRoleLog>
 *
 * @method UserRoleLog|null find($id, $lockMode = null, $lockVersion = null)
 * @method UserRoleLog|null findOneBy(array $criteria, array $orderBy = null)
 * @method UserRoleLog[]    findAll()
 * @method UserRoleLog[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserRoleLogRepository extends ServiceEntityRepository
{
    private static string $removeReturnLine = "/\s+/u";
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    /* MODIF 2026-07-20 : plafond de sécurité sur la page /admin/journal-roles
     * (pas de pagination serveur pour l'instant, DataTable côté client) —
     * évite de charger une table qui grossit indéfiniment en une seule fois.
     * Le filtre par plage de dates permet de contourner ce plafond. */
    private const MAX_ROWS = 1000;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserRoleLog::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array<int|string, mixed>
     *
     * Created at: 20/07/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function handleDatabaseException(\Throwable $e): array
    {
        $message = $e->getMessage();

        if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
            $message = self::$noDataBase;
        }

        return ['code' => 500, 'erreur' => $message];
    }

    /**
     * Liste filtrée du journal des changements de rôle, la plus récente en tête.
     *
     * @param array<string, mixed> $map clés possibles : courriel, start, end (\DateTimeInterface)
     *
     * @return array<int|string, mixed>
     *
     * Created at: 20/07/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findFiltered(array $map): array
    {
        $courriel = $map['courriel'] ?? null;
        $start = $map['start'] ?? null;
        $end = $map['end'] ?? null;

        $where = [];
        $params = ['limit' => self::MAX_ROWS];
        $types = ['limit' => ParameterType::INTEGER];

        if ($courriel) {
            $where[] = "(user_email ILIKE :courriel OR editor_email ILIKE :courriel)";
            $params['courriel'] = '%' . $courriel . '%';
        }
        if ($start instanceof \DateTimeInterface) {
            $where[] = "created_at >= :start";
            $params['start'] = $start->format('Y-m-d 00:00:00');
        }
        if ($end instanceof \DateTimeInterface) {
            $where[] = "created_at <= :end";
            $params['end'] = $end->format('Y-m-d 23:59:59');
        }

        $sql = "SELECT id, user_email, editor_email, old_roles, new_roles,
                        old_active, new_active, alerts, created_at
                FROM ma_moulinette.user_role_log"
                . ($where !== [] ? ' WHERE ' . implode(' AND ', $where) : '')
                . " ORDER BY created_at DESC LIMIT :limit";

        try {
            $liste = $this->getEntityManager()->getConnection()->executeQuery(
                preg_replace(self::$removeReturnLine, " ", $sql),
                $params,
                $types,
            )->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * Récupère un lot de lignes par identifiant (sélection d'une page pour
     * l'archivage CSV ou l'export PDF).
     *
     * @param int[] $ids
     *
     * @return array<int|string, mixed>
     *
     * Created at: 20/07/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findByIds(array $ids): array
    {
        if ($ids === []) {
            return ['code' => 200, 'liste' => [], 'erreur' => ''];
        }

        $sql = "SELECT id, user_email, editor_email, old_roles, new_roles,
                        old_active, new_active, alerts, created_at
                FROM ma_moulinette.user_role_log
                WHERE id IN (:ids)
                ORDER BY created_at DESC";

        try {
            $liste = $this->getEntityManager()->getConnection()->executeQuery(
                preg_replace(self::$removeReturnLine, " ", $sql),
                ['ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER],
            )->fetchAllAssociative();
        } catch (\Throwable $e) {
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'liste' => $liste, 'erreur' => ''];
    }

    /**
     * Supprime un lot de lignes du journal par identifiant.
     *
     * @param int[] $ids
     *
     * @return array<int|string, mixed>
     *
     * Created at: 20/07/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteByIds(array $ids): array
    {
        if ($ids === []) {
            return ['code' => 200, 'supprime' => 0, 'erreur' => ''];
        }

        $sql = "DELETE FROM ma_moulinette.user_role_log WHERE id IN (:ids)";

        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
            $supprime = $this->getEntityManager()->getConnection()->executeStatement(
                preg_replace(self::$removeReturnLine, " ", $sql),
                ['ids' => $ids],
                ['ids' => ArrayParameterType::INTEGER],
            );
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Throwable $e) {
            $this->getEntityManager()->getConnection()->rollback();
            return $this->handleDatabaseException($e);
        }

        return ['code' => 200, 'supprime' => $supprime, 'erreur' => ''];
    }
}
