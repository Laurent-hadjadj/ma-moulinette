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

use App\Entity\HotspotDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;


class HotspotDetailsRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, HotspotDetails::class);
    }

    /**
     * [Description for selectHotspotDetailsByNiveau]
     * On récupère la liste des hotspots status de la table détails.
     * @param array $map
     *
     * @return array
     *
     * Created at: 03/03/2024 12:44:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectHotspotDetailsByStatus($map):array
    {
        /** Utiliser dans la page OWASP */
        $sql = "SELECT *
                FROM hotspot_details
                WHERE maven_key=:maven_key
                ORDER BY status ASC";
        try {
            $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                $conn->bindValue(':maven_key', $map['maven_key']);
                $nombre=$conn->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'menaces'=>$nombre, 'erreur'=>''];
    }

    /**
     * [Description for deleteHotspotDetailsMavenKey]
     * On supprime le détails des hotspots  pour la version courante
     * @param array $map
     *
     * @return array
     *
     * Created at: 14/03/2024 09:42:33 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteHotspotDetailsMavenKey($map):array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                $sql = "DELETE
                        FROM hotspot_details
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
     * [Description for insertHotspotDetails]
     *
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 31/05/2024 14:43:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertHotspotDetails($map):array
    {
        $sql = "INSERT INTO hotspot_details
                    (maven_key, version, date_version, security_category, rule_key, rule_name, severity, status, resolution, niveau, frontend, backend, autre, file_name, file_path, line, message, hotspot_key,
                    mode_collecte, utilisateur_collecte,
                    date_enregistrement)
                VALUES
                    (:maven_key, :version, :date_version, :security_category, :rule_key, :rule_name, :severity, :status, :resolution, :niveau, :frontend, :backend, :autre, :file_name, :file_path, :line, :message, :hotspot_key, :mode_collecte, :utilisateur_collecte,
                    :date_enregistrement)";
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();
                foreach($map as $item){
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
                        $stmt->bindValue(':maven_key', $item['maven_key']);
                        $stmt->bindValue(':version', $item['version']);
                        $stmt->bindValue(':date_version', $item['date_version']->format('Y-m-d H:i:sO'));
                        $stmt->bindValue(':security_category', $item['security_category']);
                        $stmt->bindValue(':rule_key', $item['rule_key']);
                        $stmt->bindValue(':rule_name', $item['rule_name']);
                        $stmt->bindValue(':severity', $item['severity']);
                        $stmt->bindValue(':status', $item['status']);
                        $stmt->bindValue(':resolution', $item['resolution']);
                        $stmt->bindValue(':niveau', $item['niveau']);
                        $stmt->bindValue(':frontend', $item['frontend']);
                        $stmt->bindValue(':backend', $item['backend']);
                        $stmt->bindValue(':autre', $item['autre']);
                        $stmt->bindValue(':file_name', $item['file_name']);
                        $stmt->bindValue(':file_path', $item['file_path']);
                        $stmt->bindValue(':line', $item['line']);
                        $stmt->bindValue(':message', $item['message']);
                        $stmt->bindValue(':hotspot_key', $item['hotspot_key']);
                        $stmt->bindValue(':mode_collecte', $item['mode_collecte']);
                        $stmt->bindValue(':utilisateur_collecte', $item['utilisateur_collecte']);
                        $stmt->bindValue(':date_enregistrement', $item['date_enregistrement']->format('Y-m-d H:i:sO'));
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
