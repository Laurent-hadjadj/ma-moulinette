<?php

namespace App\Repository\Main;

use App\Entity\Main\Historique;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description HistoriqueRepository]
 */
class HistoriqueRepository extends ServiceEntityRepository
{
    public static $removeReturnline = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Historique::class);
    }

    public function add(Historique $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Historique $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * [Description for getProjetFavori]
     * Récupère les indicateurs du projet favori
     * @param mixed $where
     *
     * @return array
     *
     * Created at: 27/10/2023 15:37:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getProjetFavori($where): array
    {
    $sql = "SELECT DISTINCT
            maven_key as mavenkey, nom_projet as nom,
            version, date_version as date, note_reliability as fiabilite,
            note_security as securite, note_hotspot as hotspot,
            note_sqale as sqale, nombre_bug as bug, nombre_vulnerability as vulnerability,
            nombre_code_smell as code_smell, hotspot_total as hotspots
            FROM historique
            WHERE :where
            ORDER BY date_version DESC limit 4";
    $select=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
    $select->bindValue(":where", $where);
    return $select->fetchAllAssociative();
    }

    /**
     * [Description for deleteHistoriqueProjet]
     * Suppression de la table historique du projet
     *
     * @param string $mode
     * @param mixed $data
     *
     * @return array
     *
     * Created at: 14/02/2024 10:29:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteHistoriqueProjet($mode, $map):array {

        /** on prépare la réponse */
        $response=['mode'=>$mode, 'code'=>200, 'erreur'=>''];

        /** On prépare la requête */
        $sql = "DELETE FROM historique
                WHERE maven_key=:maven_key
                AND version=:version
                AND date_version=:date_version";
        $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
        $conn->bindValue(':maven_key', $map['maven_key']);
        $conn->bindValue(':version', $map['version']);
        $conn->bindValue(':date_version', $map['date_version']);
        try {
            if ($mode !== 'TEST') {
                $conn->executeQuery();
            } else {
                $response=['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
            }
        } catch (\Doctrine\DBAL\Exception $e) {
            $response=['mode'=>$mode, 'code'=> 500, 'erreur'=>$e->getCode()];
        }
        return $response;
}
}
