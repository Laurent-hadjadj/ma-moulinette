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

namespace App\Repository\Main;

use App\Entity\Main\Todo;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description TodoRepository]
 */
class TodoRepository extends ServiceEntityRepository
{

	public static $removeReturnline = "/\s+/u";

	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, Todo::class);
	}

	/**
	 * [Description for deleteTodoMavenKey]
	 * Supprime les T_do pour la version courrante (i.e. correspondant à la maven_key)
	 *
	 * @param string $mode
	 * @param array $map
	 *
	 * @return array
	 *
	 * Created at: 14/03/2024 11:16:47 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	public function deleteTodoMavenKey($mode,$map):array
	{
		$sql = "DELETE
				FROM todo
				WHERE maven_key=:maven_key";
		$conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
		$conn->bindValue(':maven_key', $map['maven_key']);
		try {
				if ($mode !== 'TEST') {
					$conn->executeQuery();
				} else {
					return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
				}
		} catch (\Doctrine\DBAL\Exception $e) {
			return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
		}
		return ['mode'=>$mode, 'code'=>200, 'erreur'=>''];
	}

	/**
	 * [Description for selectTodoRuleGroupByRule]
	 * Retourne la liste des todo pour un projet groupé par règle.
	 *
	 * @param string $mode
	 * @param array $map
	 *
	 * @return array
	 *
	 * Created at: 22/03/2024 11:33:43 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	public function selectTodoRuleGroupByRule($mode,$map):array
	{
		$sql = "SELECT rule, count(*) as total
				FROM todo
				WHERE maven_key=:maven_key
				GROUP BY rule";
		$conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
		$conn->bindValue(':maven_key', $map['maven_key']);
		try {
				if ($mode !== 'TEST') {
					$exec=$conn->executeQuery();
					$liste=$exec->fetchAllAssociative();
				} else {
					return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
				}
		} catch (\Doctrine\DBAL\Exception $e) {
			return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
		}
		return ['mode'=>$mode, 'code'=>200, 'liste'=>$liste, 'erreur'=>''];
	}

	/**
	 * [Description for selectTodoComponentOrderByRule]
	 * On retourne la liste des règle et du détail pour le projet.
	 *
	 * @param mixed $mode
	 * @param mixed $map
	 *
	 * @return array
	 *
	 * Created at: 22/03/2024 11:38:16 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	public function selectTodoComponentOrderByRule($mode,$map):array
	{
		$sql = "SELECT rule, component, line
				FROM todo
				WHERE maven_key=:maven_key
				ORDER BY rule";
		$conn=$this->getEntityManager()->getConnection()->prepare(preg_replace(static::$removeReturnline, " ", $sql));
		$conn->bindValue(':maven_key', $map['maven_key']);
		try {
				if ($mode !== 'TEST') {
					$exec=$conn->executeQuery();
					$liste=$exec->fetchAllAssociative();
				} else {
					return ['mode'=>$mode, 'code'=> 202, 'erreur'=>'TEST'];
				}
		} catch (\Doctrine\DBAL\Exception $e) {
			return ['mode'=>$mode, 'code'=>500, 'erreur'=> $e->getCode()];
		}
		return ['mode'=>$mode, 'code'=>200, 'liste'=>$liste, 'erreur'=>''];
	}
}
