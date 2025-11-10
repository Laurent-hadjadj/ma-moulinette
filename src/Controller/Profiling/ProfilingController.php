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
use Symfony\Component\HttpFoundation\{JsonResponse, Response};
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\DBAL\Connection;

/**
 * [Description ProfilingController]
 */
class ProfilingController extends AbstractController
{
    #[Route('/profiling/dashboard', name: 'profiling_dashboard', methods: ['GET'])]
    public function dashboard(): Response
    {
        return $this->render('batch/profiling_dashboard.html.twig');
    }

    #[Route('/api/secure/profiling/stats', name: 'profiling_stats', methods: ['GET'])]
    public function stats(Connection $conn): JsonResponse
    {
        $sql = <<<SQL
        SELECT *
        FROM ma_moulinette.vw_batch_profiling_summary
        ORDER BY portefeuille, utilisateur, granularite, periode DESC
        SQL;

        $data = $conn->fetchAllAssociative($sql);

        return new JsonResponse([
            'code' => 200,
            'count' => count($data),
            'data' => $data
        ]);
    }
}
