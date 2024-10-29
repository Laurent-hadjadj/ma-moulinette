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

use App\Entity\Mesures;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description MesuresRepository]
 */
class MesuresRepository extends ServiceEntityRepository
{
    public static $removeReturnLine = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Mesures::class);
    }

    /**
     * [Description for selectMesuresVersionLast]
     * Retourne les mesures de la dernière version d'un projet
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 17/03/2024 22:51:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function selectMesuresVersionLast($map): array
    {
        try {
                $sql = "SELECT project_name as name, ncloc, language_distribution,
                            lines, coverage, sqale_debt_ratio,
                            duplicated_lines_density, tests, issues
                        FROM ma_moulinette.mesures
                        WHERE maven_key=:maven_key
                        ORDER BY date_enregistrement DESC LIMIT 1";
                $conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $conn->bindValue(':maven_key', $map['maven_key']);
                $mesures=$conn->executeQuery()->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'mesures'=>$mesures, 'erreur'=>''];
    }

    /**
     * [Description for insertMesures]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 21/05/2024 22:57:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function insertMesures($map): array
    {
        try {
            $this->getEntityManager()->getConnection()->beginTransaction();

            $sql = "INSERT INTO ma_moulinette.mesures
                        (maven_key, project_name, lines, ncloc, language_distribution, sqale_debt_ratio, coverage, duplicated_lines_density, tests, issues, mode_collecte, utilisateur_collecte, date_enregistrement)
                    VALUES
                        (:maven_key, :project_name, :lines, :ncloc, :language_distribution::json, :sqale_debt_ratio, :coverage, :duplicated_lines_density, :tests, :issues, :mode_collecte, :utilisateur_collecte, :date_enregistrement)";

            $stmt = $this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
            $stmt->bindValue(':maven_key', $map['maven_key']);
            $stmt->bindValue(':project_name', $map['project_name']);
            $stmt->bindValue(':lines', $map['lines']);
            $stmt->bindValue(':ncloc', $map['ncloc']);
            // Encode le tableau en JSON et l'encapsule dans un tableau PostgreSQL
            $language_distribution_json = json_encode([$map['language_distribution']]);
            $stmt->bindValue(':language_distribution', $language_distribution_json);
            $stmt->bindValue(':sqale_debt_ratio', $map['sqale_debt_ratio']);
            $stmt->bindValue(':coverage', $map['coverage']);
            $stmt->bindValue(':duplicated_lines_density', $map['duplicated_lines_density']);
            $stmt->bindValue(':tests', $map['tests']);
            $stmt->bindValue(':issues', $map['issues']);
            $stmt->bindValue(':mode_collecte', $map['mode_collecte']);
            $stmt->bindValue(':utilisateur_collecte', $map['utilisateur_collecte']);

            // Formate la date avant de l'enregistrer
            $stmt->bindValue(':date_enregistrement', $map['date_enregistrement']->format('Y-m-d H:i:sO'));

            $stmt->executeStatement();
            $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->getConnection()->rollBack();
            return ['code' => 500, 'erreur' => $e->getMessage()];
        }

        return ['code' => 200, 'erreur' => ''];
    }


    /**
     * [Description for deleteMesuresMavenKey]
     * Supprime les mesures de la version courante (i.e. correspondant à la maven_key)
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 26/05/2024 10:51:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteMesuresMavenKey($map): array
    {
        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $sql = "DELETE
                            FROM ma_moulinette.mesures
                            WHERE maven_key=:maven_key";

                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                        $stmt->bindValue(':maven_key', $map['maven_key']);
                        $stmt->executeStatement();
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->rollback();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }
}
