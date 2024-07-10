<?php

namespace App\Repository;

use App\Entity\Actuator;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

use function PHPUnit\Framework\isEmpty;

/**
 * @extends ServiceEntityRepository<Actuator>
 *
 * @method Actuator|null find($id, $lockMode = null, $lockVersion = null)
 * @method Actuator|null findOneBy(array $criteria, array $orderBy = null)
 * @method Actuator[]    findAll()
 * @method Actuator[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActuatorRepository extends ServiceEntityRepository
{
    public static $removeReturnLine = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Actuator::class);
    }

    /**
     * [Description for deleteActuatorUrl]
     *
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 23/06/2024 14:56:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function deleteActuatorUrl($map):array
    {
        try {
                $this->getEntityManager()->getConnection()->beginTransaction();
                    $sql = "DELETE
                            FROM actuator
                            WHERE url=:url";
                    $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(':url', $map['url']);
                    $stmt->executeStatement();
                $this->getEntityManager()->getConnection()->commit();
        } catch (\Doctrine\DBAL\Exception $e) {
            $this->getEntityManager()->rollback();
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'erreur'=>''];
    }

    /**
     * [Description for findActuatorOrderBy]
     *
     * @param mixed $sortColumn
     * @param mixed $sortDirection
     *
     * @return array
     *
     * Created at: 23/06/2024 14:56:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findActuatorOrderBy($sortColumn, $sortDirection):array
    {
        try {
                $sql = "SELECT *
                        FROM ma_moulinette.actuator
                        ORDER BY ".$sortColumn." ".$sortDirection;
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $exec=$stmt->executeQuery();
                    $paginator=$exec->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'paginator_query'=>$paginator,'erreur'=>''];
    }

    /**
     * [Description for findActuatorOrderByDate]
     *
     * @param mixed $sortDirection
     *
     * @return array
     *
     * Created at: 23/06/2024 14:56:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findActuatorOrderByDate($sortDirection):array
    {
        try {
                $sql = "SELECT * FROM ma_moulinette.actuator
                        ORDER BY
                            CASE
                                WHEN date_modification IS NOT NULL THEN date_modification
                                ELSE date_enregistrement
                            END ".$sortDirection;
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $exec=$stmt->executeQuery();
                    $paginator=$exec->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'paginator_query'=>$paginator,'erreur'=>''];
    }

    /**
     * [Description for findActuatorMavenKey]
     *
     * @param mixed $map
     *
     * @return array
     *
     * Created at: 26/06/2024 18:57:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findActuatorMavenKey($map):array
    {
        try {
                $sql = "SELECT id, url, actuator_user, actuator_password
                        FROM ma_moulinette.actuator
                        WHERE maven_key= :maven_key";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(':maven_key', $map['maven_key']);
                    $exec=$stmt->executeQuery();
                    $liste=$exec->fetchAllAssociative();
                    if (empty($liste)) {
                        return ['code' => 404, 'message' => "Le projet n'a pas de point d'accès défini"];
                    }
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }

        $id=$liste[0]['id'];
        $url=$liste[0]['url'];
        $user=$liste[0]['actuator_user'] ?? null;
        $password=$liste[0]['actuator_password'] ?? null;

        return ['code'=>200, 'erreur'=>''] + compact('url', 'user', 'password', 'id');
    }
}
