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

namespace App\Controller\Profiling;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{BatchProfiling};

/**
 * [Description ProfilingApiController]
 */
class ProfilingApiController extends AbstractController
{
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $erreur403 = "Vous devez avoir le rôle BATCH pour réaliser cette action (Erreur 403).";
    private static string $loggerE403 = "[Profiling] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_BATCH).";
    private static string $noData = 'Pas de données';

    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {}

    /**
     * [Description for formatChartData]
     * Transforme un tableau de stats en format Chart.js
     *
     * @param array<int|string, mixed> $rows
     * @param string $xKey
     * @param array<int|string, mixed> $yKeys
     * @param string|null $groupKey
     * @param bool $round
     *
     * @return array<int|string, mixed>
     *
     * Created at: 16/11/2025 09:08:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function formatChartData(
        array $rows,
        string $xKey,
        array $yKeys,
        ?string $groupKey = null,
        bool $round = true
    ): array {
        $labels = [];
        $series = [];

        // Normaliser les clés
        $rows = array_map(fn($row) => array_change_key_case($row, CASE_LOWER), $rows);

        foreach ($rows as $row) {
            $xValue = $row[strtolower($xKey)];

            if (!in_array($xValue, $labels, true)) {
                $labels[] = $xValue;
            }

            $group = $groupKey ? ($row[strtolower($groupKey)] ?? 'default') : $xValue;

            if (!isset($series[$group])) {
                $series[$group] = [
                    'label' => $group,
                    'time' => [],
                    'memory' => []
                ];
            }

            // Valeurs
            // Récupération brute (sans cast)
            $timeRaw = $row[strtolower($yKeys[0])] ?? null;
            $memoryRaw = $row[strtolower($yKeys[1])] ?? null;

            // Si la valeur existe → cast float et round
            // Si la valeur n'existe pas → null est conservé
            $timeValue = ($timeRaw !== null) ? ($round ? round((float)$timeRaw, 2) : (float)$timeRaw) : null;
            $memoryValue = ($memoryRaw !== null) ? ($round ? round((float)$memoryRaw, 2) : (float)$memoryRaw) : null;

            $series[$group]['time'][$xValue]   = $timeValue;
            $series[$group]['memory'][$xValue] = $memoryValue;
        }

        // Construire les datasets
        $datasetsTime = [];
        $datasetsMemory = [];

        foreach ($series as $s) {
            $datasetsTime[] = [
                'label' => $s['label'],
                'data' => array_map(fn($l) => $s['time'][$l] ?? null, $labels)
            ];
            $datasetsMemory[] = [
                'label' => $s['label'],
                'data' => array_map(fn($l) => $s['memory'][$l] ?? null, $labels)
            ];
        }

        return [
            'code' => 200,
            'labels' => $labels,
            'datasetsTime' => $datasetsTime,
            'datasetsMemory' => $datasetsMemory
        ];
    }

    /**
     * [Description for indicateur]
     * Retourne la liste d'indicateur extrait de findGlobalSummary()
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 12/07/2026 10:01:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/indicateur', name: 'profiling_indicateur', methods: ['POST'])]
    public function indicateur(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/indicateur");

        if (!$this->isGranted('ROLE_BATCH')) {
            $this->logger->error(self::$loggerE403);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'indicateur' => []
            ], Response::HTTP_OK);
        }

        /** On décode le body */
        $data = json_decode($request->getContent());

        $authorize_indicateur = [
            'utilisateur',
            'portefeuille',
            'granularite',
            'periode',
            'nb_exec',
            'derniere_execution'
        ];

        /** On teste si la clé est valide */
        if (
            $data === null
            || !property_exists($data, 'indicateur') || !is_string($data->indicateur)
            || !in_array($data->indicateur, $authorize_indicateur, true)
        ) {
            $this->logger->error("[Profiling] ❌ Requête invalide : 'indicateur' manquant ou non autorisé.", [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        $repo = $this->em->getRepository(BatchProfiling::class);
        $result = $repo->findGlobalSummary($data->indicateur);

        return new JsonResponse([
            'code' => 200,
            'indicateur' => $result
        ], Response::HTTP_OK);
    }

    /**
     * [Description for summary]
     * retourne les données macros extrait de la requête getGlobalKpi()
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:12:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/summary', name: 'profiling_summary', methods: ['GET'])]
    public function summary(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/summary");

        if (!$this->isGranted('ROLE_BATCH')) {
            $this->logger->error(self::$loggerE403);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'summary' => []
            ], Response::HTTP_OK);
        }

        $repo = $this->em->getRepository(BatchProfiling::class);
        $data = $repo->getGlobalKpi();

        return new JsonResponse([
            'code' => 200,
            'summary' => $data['summary'][0] ?? [],
        ], Response::HTTP_OK);
    }

    /**
     * [Description for latest]
     * Retourne les 10 derniers résultats.
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:11:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/latest', name: 'latest', methods: ['GET'])]
    public function latest(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/latest");

        if (!$this->isGranted('ROLE_BATCH')) {
            $this->logger->error(self::$loggerE403);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'latest' => []
            ], Response::HTTP_OK);
        }

        $repo = $this->em->getRepository(BatchProfiling::class);
        $data = $repo->findLatest(10);

        return new JsonResponse([
            'code' => 200,
            'latest' => $data
        ], Response::HTTP_OK);
    }

    /**
     * [Description for weekly]
     * Retourne les données agrégés sur une semaine.
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:12:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/weekly/all', name: 'weekly_all', methods: ['GET'])]
    public function weekly(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/weekly/all");

        if (!$this->isGranted('ROLE_BATCH')) {
            $this->logger->error(self::$loggerE403);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'weekly' => []
            ], Response::HTTP_OK);
        }

        $repo = $this->em->getRepository(BatchProfiling::class);
        $rows = $repo->findWeeklyStats();
        $data = $this->formatChartData(
            $rows,
            'semaine',
            ['average_time', 'average_memory'],
            'portefeuille'
        );

        return new JsonResponse([
            'code' => 200,
            'weekly' => $data
        ], Response::HTTP_OK);
    }

    /**
     * [Description for monthlyAll]
     * Retourne les données agrégés sur un mois.
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:12:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/monthly/all', name: 'monthly_all', methods: ['GET'])]
    public function monthlyAll(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/monthly/all");

        if (!$this->isGranted('ROLE_BATCH')) {
            $this->logger->error(self::$loggerE403);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'monthly' => []
            ], Response::HTTP_OK);
        }

        $repo = $this->em->getRepository(BatchProfiling::class);
        $rows = $repo->findMonthlyStats();
        $data = $this->formatChartData(
            $rows,
            'mois',
            ['average_time', 'average_memory'],
            'portefeuille'
        );

        return new JsonResponse([
            'code' => 200,
            'monthly' => $data
        ], Response::HTTP_OK);
    }

    /**
     * [Description for users]
     * Retourne les données agrégés par utilisateur.
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:12:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/users/all', name: 'users_all', methods: ['GET'])]
    public function users(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/users/all");

        if (!$this->isGranted('ROLE_BATCH')) {
            $this->logger->error(self::$loggerE403);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'user' => []
            ], Response::HTTP_OK);
        }

        $repo = $this->em->getRepository(BatchProfiling::class);
        $rows = $repo->findUsersStats();
        $data = $this->formatChartData(
            $rows,
            'utilisateur',
            ['average_time', 'average_memory'],
            null
        );

        return new JsonResponse([
            'code' => 200,
            'user' => $data
        ], Response::HTTP_OK);
    }

    /**
     * [Description for allPortefeuille]
     * Retourne les données agrégés par portefeuille d'application.
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:12:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/portefeuille/all', name: 'portefeuille_all', methods: ['GET'])]
    public function allPortefeuille(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/portefeuille/all");

        if (!$this->isGranted('ROLE_BATCH')) {
            $this->logger->error(self::$loggerE403);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'portefeuille' => []
            ], Response::HTTP_OK);
        }

        $repo = $this->em->getRepository(BatchProfiling::class);
        $rows = $repo->findStatsByPortefeuille();
        $data = $this->formatChartData(
            $rows,
            'portefeuille',
            ['average_time', 'average_memory'],
            'portefeuille'
        );

        return new JsonResponse([
            'code' => 200,
            'portefeuille' => $data
        ], Response::HTTP_OK);
    }
}
