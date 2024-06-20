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

use App\Entity\HotspotOwasp;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

class HotspotOwaspRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotspotOwasp::class);
    }

    /**
     * [Description for countHotspotOwaspStatus]
     * On compte le nombre de hotspot REVIEWED
     * @param array $map
     *
     * @return array
     *
     * Created at: 02/03/2024 23:23:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHotspotOwaspStatus($map):array
    {
        try {
            $sql = "SELECT count(*) AS nombre
                    FROM hotspot_owasp
                    WHERE maven_key=:maven_key AND status=:status";
            $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            $conn->bindValue(':maven_key', $map['maven_key']);
            $conn->bindValue(':status', $map['status']);
            $nombre=$conn->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'request'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for countOwaspStatus]
     * On récupère le nombre de hotspot owasp par niveau de sévérité potentiel.
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 02/03/2024 23:37:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHotspotOwaspProbability($map):array
    {
        try {
            $sql = "SELECT probability, count(*) as total
                    FROM hotspot_owasp
                    WHERE maven_key=:maven_key AND status='TO_REVIEW' GROUP BY probability";
            $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            $conn->bindValue(':maven_key', $map['maven_key']);
            $nombre=$conn->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'nombre'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for countHotspotOwaspMenaces]
     * On récupère le nombre de hotspot au status TO_REVIEW
     * @param array $map
     *
     * @return array
     *
     * Created at: 03/03/2024 12:38:43 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHotspotOwaspMenaces($map):array
    {

        try {
            $sql = "SELECT menace, count(*) as total
                    FROM hotspot_owasp
                    WHERE maven_key=:maven_key
                    AND status='TO_REVIEW' GROUP BY menace";
            $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            $conn->bindValue(':maven_key', $map['maven_key']);
            $nombre=$conn->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'menaces'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for countHotspotOwaspMenaceByStatus]
     *  On compte le nombre de menace par type de Status
     * @param array $map
     *
     * @return array
     *
     * Created at: 03/03/2024 16:01:48 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function countHotspotOwaspMenaceByStatus($map):array
    {
        try {
            $sql = "SELECT count(*) as total
                    FROM hotspot_owasp
                    WHERE maven_key=:maven_key
                    AND menace=:menace
                    AND status='TO_REVIEW'
                    AND probability=:probability";
            $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
            $conn->bindValue(':maven_key', $map['maven_key']);
            $conn->bindValue(':menace', $map['menace']);
            $conn->bindValue(':probability', $map['probability']);
            $nombre=$conn->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'nombre'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for deleteHotpotOwaspMavenKey]
     * Supprime les hotspots de type owasp pour la version courante (i.e. correspondant à la maven_key)
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 14/03/2024 08:21:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteHotspotOwaspMavenKey($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "DELETE
                        FROM hotspot_owasp
                        WHERE maven_key=:maven_key";
                $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                    $conn->bindValue(':maven_key', $map['maven_key']);
                    $conn->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for insertHotspotOwasp]
     *
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 30/05/2024 15:54:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertHotspotOwasp($map):array
    {
        $sql = "INSERT INTO hotspot_owasp
                (referentiel_owasp, maven_key, version, date_version, menace, security_category, rule_key, probability, status, resolution, niveau,
                mode_collecte, utilisateur_collecte, date_enregistrement)
                VALUES
                (:referentiel_owasp, :maven_key, :version, :date_version, :menace, :security_category, :rule_key, :probability, :status, :resolution, :niveau, :mode_collecte, :utilisateur_collecte, :date_enregistrement)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                foreach ($map as $ref) {
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue(':referentiel_owasp', $ref['referentiel_owasp']);
                        $stmt->bindValue(':maven_key', $ref['maven_key']);
                        $stmt->bindValue(':version', $ref['version']);
                        $stmt->bindValue(':date_version', $ref['date_version']->format('Y-m-d H:i:sO'));
                        $stmt->bindValue(':menace', $ref['menace']);
                        $stmt->bindValue(':security_category', $ref['security_category']);
                        $stmt->bindValue(':rule_key', $ref['rule_key']);
                        $stmt->bindValue(':probability', $ref['probability']);
                        $stmt->bindValue(':status', $ref['status']);
                        $stmt->bindValue(':resolution', $ref['resolution']);
                        $stmt->bindValue(':niveau', $ref['niveau']);
                        $stmt->bindValue(':mode_collecte', $ref['mode_collecte']);
                        $stmt->bindValue(':utilisateur_collecte', $ref['utilisateur_collecte']);
                        $stmt->bindValue(':date_enregistrement', $ref['date_enregistrement']->format('Y-m-d H:i:sO'));
                        $stmt->executeStatement();
                }
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }
}
