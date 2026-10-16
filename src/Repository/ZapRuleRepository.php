<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Repository;

use App\Entity\ZapRule;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * MODIF 2026-10-13 : référentiel global des règles ZAP (alertes).
 *
 * @extends ServiceEntityRepository<ZapRule>
 */
class ZapRuleRepository extends ServiceEntityRepository
{
    private static string $noDataBase = 'La connexion à la base de données a échoué.';

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ZapRule::class);
    }

    /**
     * [Description for handleDatabaseException]
     *
     * @param \Throwable $e
     *
     * @return array{code: int, erreur: string}
     *
     * Created at: 13/10/2026 21:07:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function handleDatabaseException(\Throwable $e): array
    {
        $message = $e->getMessage();

        if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
            $message = self::$noDataBase;
        }

        if ($e instanceof \Doctrine\DBAL\Exception\NotNullConstraintViolationException) {
            $message = $e->getMessage();
        }

        if ($e instanceof \Doctrine\DBAL\Exception\UniqueConstraintViolationException) {
            return ['code' => 23505, 'erreur' => 'Les informations existent déjà.'];
        }

        return ['code' => 500, 'erreur' => $message];
    }

    /**
     * [Description for findByPluginid]
     *
     * @param string $pluginid
     *
     * @return ZapRule|null
     *
     * Created at: 13/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findByPluginid(string $pluginid): ?ZapRule
    {
        return $this->findOneBy(['pluginid' => $pluginid]);
    }

    /**
     * [Description for findByAlertRef]
     * MODIF 2026-10-15 : alert_ref est la vraie clé d'unicité d'une règle ZAP
     * (pluginid ne fait que regrouper une famille de règles, voir ZapRule).
     *
     * @param string $alertRef
     *
     * @return ZapRule|null
     *
     * Created at: 15/10/2026 00:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findByAlertRef(string $alertRef): ?ZapRule
    {
        return $this->findOneBy(['alertRef' => $alertRef]);
    }
}
