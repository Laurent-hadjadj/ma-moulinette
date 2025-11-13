<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2025.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Repository;

use App\Entity\BatchProfiling;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * [Description BatchProfilingRepository]
 */
class BatchProfilingRepository extends ServiceEntityRepository
{
	public static $removeReturnLine = "/\s+/u";

	public function __construct(ManagerRegistry $registry)
	{
		parent::__construct($registry, BatchProfiling::class);
	}

	/**
	 * [Description for handleDatabaseException]
	 *
	 * @param \Throwable $e
	 *
	 * @return array
	 *
	 * Created at: 26/10/2025 12:19:01 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	public function handleDatabaseException(\Throwable $e): array
	{
		$message = $e->getMessage();

		// message = 'SQLSTATE[08006]'
		if ($e instanceof \Doctrine\DBAL\Exception\ConnectionException) {
			$message = static::$noDataBase;
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
	 * [Description for getLastExecutions]
	 * Retourne les 10 derniers traitements (toutes équipes)
	 *
	 * @return array
	 *
	 * Created at: 13/11/2025 20:42:12 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	public function getLastExecutions(): array
	{
		$sql = "
			SELECT portefeuille, utilisateur, temps_total_moyen_s, memoire_pea{_moyenne_mo, derniere_execution
			FROM ma_moulinette.vw_batch_profiling_stats
			ORDER BY derniere_execution DESC
			LIMIT 10";

		$conn = $this->getEntityManager()->getConnection();

		try {
				$stmt = $conn->prepare(preg_replace(self::$removeReturnLine, ' ', $sql));
				$result = $stmt->executeQuery()->fetchAllAssociative();
		} catch (\Throwable $e) {
			return $this->handleDatabaseException($e);
		}
		return ['code' => 200, 'data' => $result, 'erreur' => ''];
	}

	/**
	 * [Description for getWeeklyTrend]
	 * Moyenne des performances par semaine (pour graphique)
	 * @return array
	 *
	 * Created at: 13/11/2025 20:42:40 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	public function getWeeklyTrend(): array
	{
		$sql = "
			SELECT semaine,
					ROUND(AVG(temps_total_moyen_s), 2) AS temps_moyen,
					ROUND(AVG(memoire_peak_moyenne_mo), 2) AS memoire_moyenne
			FROM ma_moulinette.vw_batch_profiling_weekly
			GROUP BY semaine
			ORDER BY semaine ASC;
		";

		$conn = $this->getEntityManager()->getConnection();

		try {
				$stmt = $conn->prepare(preg_replace(self::$removeReturnLine, ' ', $sql));
				$result = $stmt->executeQuery()->fetchAllAssociative();
		} catch (\Throwable $e) {
			return $this->handleDatabaseException($e);
		}
		return ['code' => 200, 'data' => $result, 'erreur' => ''];
	}

	/**
	 * [Description for getMonthlyTrend]
	 * Moyenne des performances par mois (pour graphique)
	 * @return array
	 *
	 * Created at: 13/11/2025 20:43:03 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	public function getMonthlyTrend(): array
	{
		$sql = "
			SELECT mois,
					ROUND(AVG(temps_total_moyen_s), 2) AS temps_moyen,
					ROUND(AVG(memoire_peak_moyenne_mo), 2) AS memoire_moyenne
			FROM ma_moulinette.vw_batch_profiling_monthly
			GROUP BY mois
			ORDER BY mois ASC";

		$conn = $this->getEntityManager()->getConnection();

		try {
				$stmt = $conn->prepare(preg_replace(self::$removeReturnLine, ' ', $sql));
				$result = $stmt->executeQuery()->fetchAllAssociative();
		} catch (\Throwable $e) {
			return $this->handleDatabaseException($e);
		}
		return ['code' => 200, 'data' => $result, 'erreur' => ''];
	}

	/**
	 * [Description for getPortfolioDistribution]
	 * Répartition moyenne par portefeuille
	 * @return array
	 *
	 * Created at: 13/11/2025 20:43:27 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	public function getPortfolioDistribution(): array
	{
		$sql = "
			SELECT portefeuille, ROUND(AVG(memoire_peak_moyenne_mo), 2) AS memoire_moyenne
			FROM ma_moulinette.vw_batch_profiling_global
			GROUP BY portefeuille
			ORDER BY memoire_moyenne DESC";

		$conn = $this->getEntityManager()->getConnection();

		try {
				$stmt = $conn->prepare(preg_replace(self::$removeReturnLine, ' ', $sql));
				$result = $stmt->executeQuery()->fetchAllAssociative();
		} catch (\Throwable $e) {
			return $this->handleDatabaseException($e);
		}
		return ['code' => 200, 'data' => $result, 'erreur' => ''];
	}

	/**
	 * [Description for getUserComparison]
	 * Comparaison de performance entre utilisateurs
	 * @return array
	 *
	 * Created at: 13/11/2025 20:43:44 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	public function getUserComparison(): array
	{
		$sql = "
			SELECT utilisateur, COUNT(*) AS nb_exec, ROUND(AVG(temps_total_moyen_s), 2) AS temps_moyen
			FROM ma_moulinette.vw_batch_profiling_global
			GROUP BY utilisateur
			ORDER BY temps_moyen ASC";

		$conn = $this->getEntityManager()->getConnection();

		try {
				$stmt = $conn->prepare(preg_replace(self::$removeReturnLine, ' ', $sql));
				$result = $stmt->executeQuery()->fetchAllAssociative();
		} catch (\Throwable $e) {
			return $this->handleDatabaseException($e);
		}
		return ['code' => 200, 'data' => $result, 'erreur' => ''];
	}

	/**
	 * [Description for getGlobalKpi]
	 * KPI globaux (pour les cartes)
	 * @return array
	 *
	 * Created at: 13/11/2025 20:44:05 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com>
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
	 */
	public function getGlobalKpi(): array
	{
		$sql = "
			SELECT
				ROUND(AVG(temps_total_moyen_s), 2) AS temps_total_moyen_s,
				ROUND(AVG(temps_moyen_s), 2) AS temps_moyen_s,
				ROUND(AVG(memoire_peak_moyenne_mo), 2) AS memoire_peak_moyenne_mo,
				MAX(derniere_execution) AS derniere_execution
			FROM ma_moulinette.vw_batch_profiling_stats";

		$conn = $this->getEntityManager()->getConnection();

		try {
				$stmt = $conn->prepare(preg_replace(self::$removeReturnLine, ' ', $sql));
				$result = $stmt->executeQuery()->fetchAllAssociative();
		} catch (\Throwable $e) {
			return $this->handleDatabaseException($e);
		}
		return ['code' => 200, 'data' => $result, 'erreur' => ''];
	}

	/**
	 * [Description for findDashboardStats]
	 * Statistiques globales pour les cartes KPI.
	 * @return array
	 * 
	 * Created at: 13/11/2025 21:24:51 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com> 
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0. 
	 */
	public function findDashboardStats(): array
	{
		$sql = "
			SELECT
				COUNT(*) AS nb_exec,
				ROUND(AVG(temps_total_moyen_s), 2) AS temps_moyen,
				ROUND(AVG(memoire_peak_moyenne_mo), 2) AS memoire_moyenne,
				COUNT(DISTINCT utilisateur) AS nb_users
			FROM ma_moulinette.vw_batch_profiling_global
		";

		try {
			$conn = $this->getEntityManager()->getConnection();
			return $conn->fetchAssociative(preg_replace(self::$removeReturnLine, " ", $sql)) ?: [];
		} catch (\Throwable $e) {
			return ['nb_exec' => 0, 'temps_moyen' => 0, 'memoire_moyenne' => 0, 'nb_users' => 0];
		}
	}

	/**
	 * [Description for findWeeklyTrends]
	 * Évolution du temps moyen sur 4 dernières semaines.
	 * @return array
	 * 
	 * Created at: 13/11/2025 21:25:12 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com> 
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0. 
	 */
	public function findWeeklyTrends(): array
	{
		$sql = "
			SELECT semaine, ROUND(AVG(temps_total_moyen_s), 2) AS temps_moyen
			FROM ma_moulinette.vw_batch_profiling_weekly
			GROUP BY semaine
			ORDER BY semaine DESC
			LIMIT 4
		";

		$conn = $this->getEntityManager()->getConnection();
		$rows = $conn->fetchAllAssociative(preg_replace(self::$removeReturnLine, " ", $sql));

		return [
			'labels' => array_column($rows, 'semaine'),
			'values' => array_column($rows, 'temps_moyen'),
		];
	}

	/**
	 * [Description for findMemoryByPortefeuille]
	 * Moyenne mémoire par portefeuille.
	 * @return array
	 * 
	 * Created at: 13/11/2025 21:26:28 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com> 
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0. 
	 */
	public function findMemoryByPortefeuille(): array
	{
		$sql = "
			SELECT portefeuille, ROUND(AVG(memoire_peak_moyenne_mo), 2) AS memoire_moyenne
			FROM ma_moulinette.vw_batch_profiling_global
			GROUP BY portefeuille
			ORDER BY memoire_moyenne DESC
			LIMIT 8
		";

		$conn = $this->getEntityManager()->getConnection();
		$rows = $conn->fetchAllAssociative(preg_replace(self::$removeReturnLine, " ", $sql));

		return [
			'labels' => array_column($rows, 'portefeuille'),
			'values' => array_column($rows, 'memoire_moyenne'),
		];
	}

	/**
	 * [Description for findLastExecutions]
	 * 10 dernières exécutions.
	 * @param int $limit
	 * 
	 * @return array
	 * 
	 * Created at: 13/11/2025 21:26:58 (Europe/Paris)
	 * @author     Laurent HADJADJ <laurent_h@me.com> 
	 * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0. 
	 */
	public function findLastExecutions(int $limit = 10): array
	{
		$sql = "
			SELECT portefeuille, utilisateur, nb_projets, 
					temps_total_moyen_s, memoire_peak_moyenne_mo,
					derniere_execution
			FROM ma_moulinette.vw_batch_profiling_stats
			ORDER BY derniere_execution DESC
			LIMIT :limit
		";

		$conn = $this->getEntityManager()->getConnection();
		$stmt = $conn->prepare(preg_replace(self::$removeReturnLine, " ", $sql));
		$stmt->bindValue('limit', $limit, \PDO::PARAM_INT);
		return $stmt->executeQuery()->fetchAllAssociative();
	}
}
