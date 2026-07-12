<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller\HealthCheck;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request};
use Symfony\Component\Routing\Attribute\Route;
/* MODIF 2026-05-05 : programme sur l'interface
 * RateLimiterFactoryInterface (la classe concrete final RateLimiterFactory n'est pas mockable). Le service autowire `limiter.healthcheck` implémente l'interface. */
use Symfony\Component\RateLimiter\RateLimiterFactoryInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Psr\Log\LoggerInterface;
use App\Service\HealthCheckService;

/**
 * [Description HealthCheckController]
 */
class HealthCheckController extends AbstractController
{
    public function __construct(
        #[Autowire(service: 'limiter.healthcheck')]
        private RateLimiterFactoryInterface $healthcheckLimiter,
        private HealthCheckService $healthCheckService,
        private LoggerInterface $logger
    ) {}

    /**
     * [Description for status]
     * Page de healthCheck
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 21/04/2026 19:50:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/public/healthcheck/status', name: 'healthcheck_status', methods: ['GET'])]
    public function status(Request $request): JsonResponse
    {
        // Rate limiting
        $key = $request->getClientIp() ?? 'anonymous';
        $limiter = $this->healthcheckLimiter->create($key);

        if (!$limiter->consume()->isAccepted()) {
            $this->logger->warning('[HealthCheck] ⚠️ nombre de tentatives dépassé', [
                'client_ip' => $key,
            ]);
            return $this->json([
                'codeRetour' => 'KO',
                'listMessage' => ['nombre de tentatives dépassé, veuillez réessayer plus tard.'],
            ], 429);
        }

        // Healthcheck métier
        $errors = $this->healthCheckService->check();

        $status = empty($errors) ? 200 : 503;

        return $this->json([
            'codeRetour' => empty($errors) ? 'OK' : 'KO',
            'listMessage' => $errors,
        ], $status);
    }
}
