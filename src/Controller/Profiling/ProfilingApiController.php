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
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{BatchProfiling};

/**
 * [Description ProfilingApiController]
 */
class ProfilingApiController extends AbstractController
{
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";

    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
    ) {}

    /**
     * [Description for formatChartData]
     * Transforme un tableau de stats en format Chart.js
     *
     * @param array $rows
     * @param string $xKey
     * @param array $yKeys
     * @param mixed|null groupKey
     * @param bool $round
     *
     * @return array
     *
     * Created at: 16/11/2025 09:08:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function formatChartData(array $rows, string $xKey,
        array $yKeys, ? string $groupKey = null, bool $round = true): array
    {
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

    #[Route('/api/secure/profiling/indicateur', name: 'profiling_indicateur', methods:['POST'])]
    public function indicateur(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/indicateur");

        /** On décode le body */
        $data = json_decode($request->getContent());

        $authorize_indicateur = [
            'utilisateur', 'portefeuille', 'granularite',
            'periode', 'nb_exec', 'derniere_execution'
        ];

        /** On teste si la clé est valide */
        if ($data === null ||
                !in_array($data->indicateur, $authorize_indicateur)) {
            $this->logger->error("[Profil] ❌ Requête invalide : 'langage' manquant ou malformé", ['data' => $data]);

            return new JsonResponse([
                    'code' => 400,
                    'type' => 'alert',
                    'message' => static::$erreur400
                ], Response::HTTP_OK);
        }

        $repo = $this->em->getRepository(BatchProfiling::class);
        $data = $repo->findGlobalSummary($data->indicateur);
        return $this->json(['indicateur' => $data ?? []]);
    }

    /**
     * [Description for summary]
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:12:27 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/summary', name: 'profiling_summary', methods:['GET'])]
    public function summary(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/summary");
        $repo = $this->em->getRepository(BatchProfiling::class);
        $data = $repo->getGlobalKpi();
        $summary = $data['summary'][0] ?? [];
        return $this->json(['summary' => $summary]);
    }

    /**
     * [Description for latest]
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:11:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/latest', name: 'latest', methods:['GET'])]
    public function latest(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/latest");
        $repo = $this->em->getRepository(BatchProfiling::class);
        $data = $repo->findLatest(10);
        return $this->json($data);
    }

    /**
     * [Description for apiLastExecutions]
     *
     * @param BatchProfiling $repo
     *
     * @return Response
     *
     * Created at: 16/11/2025 20:11:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/profiling/last-executions', name: 'api_profiling_last_executions', methods: ['GET'])]
    public function apiLastExecutions(BatchProfiling $repo): Response
    {
        $data = $repo->findLastExecutions(10);
        return $this->json($data);
    }

    /**
     * [Description for weekly]
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:12:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/weekly/all', name: 'weekly_all', methods:['GET'])]
    public function weekly(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/weekly/all");

        $repo = $this->em->getRepository(BatchProfiling::class);
        $rows = $repo->findWeeklyStats();
        return $this->json($this->formatChartData($rows,
            'semaine',
            ['average_time', 'average_memory'],
            'portefeuille'));
    }

    /**
     * [Description for monthlyAll]
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:12:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/monthly/all', name: 'monthly_all', methods:['GET'])]
    public function monthlyAll(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/monthly/all");

        $repo = $this->em->getRepository(BatchProfiling::class);
        $rows = $repo->findMonthlyStats();
        return $this->json($this->formatChartData($rows,
            'mois',
            ['average_time', 'average_memory'],
            'portefeuille'));
    }

    /**
     * [Description for users]
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:12:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/users/all', name: 'users_all', methods:['GET'])]
    public function users(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/users/all");

        $repo = $this->em->getRepository(BatchProfiling::class);
        $rows = $repo->findUsersStats();
        return $this->json($this->formatChartData($rows,
            'utilisateur',
            ['average_time', 'average_memory'],
            null));
    }

    /**
     * [Description for allPortefeuille]
     *
     * @return JsonResponse
     *
     * Created at: 16/11/2025 20:12:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/profiling/portefeuille/all', name: 'portefeuille_all', methods:['GET'])]
    public function allPortefeuille(): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/secure/profiling/portefeuille/all");

        $repo = $this->em->getRepository(BatchProfiling::class);
        $rows = $repo->findStatsByPortefeuille();
        return $this->json(
            $this->formatChartData(
                $rows,
                'portefeuille',
                ['average_time', 'average_memory'],
                'portefeuille'
            )
        );
    }

}
