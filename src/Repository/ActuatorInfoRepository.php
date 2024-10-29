<?php

namespace App\Repository;

use App\Entity\ActuatorInfo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActuatorInfo>
 *
 * @method ActuatorInfo|null find($id, $lockMode = null, $lockVersion = null)
 * @method ActuatorInfo|null findOneBy(array $criteria, array $orderBy = null)
 * @method ActuatorInfo[]    findAll()
 * @method ActuatorInfo[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class ActuatorInfoRepository extends ServiceEntityRepository
{
    public static $removeReturnLine = "/\s+/u";

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActuatorInfo::class);
    }

    /**
     * [Description for findActuatorInfoById]
     *
     * @param array $map
     *
     * @return array
     *
     * Created at: 26/06/2024 21:06:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function findActuatorInfoById(array $map): array
    {
        try {
                $sql = "SELECT actuator_info_description AS nom, actuator_info_value as valeur
                        FROM ma_moulinette.actuator_info
                        WHERE actuator_id= :actuator_id";
                $stmt=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnLine, " ", $sql));
                    $stmt->bindValue(':actuator_id', $map['actuator_id']);
                    $exec=$stmt->executeQuery();
                    $liste=$exec->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return ['code'=>500, 'erreur'=> $e->getMessage()];
        }
        return ['code'=>200, 'liste'=>$liste,'erreur'=>''];
    }

}
