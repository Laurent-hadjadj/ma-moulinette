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
    /** Seul message renvoyé au public quand le contrôle échoue ; le détail va dans les journaux. */
    private const MESSAGE_PUBLIC_INDISPONIBLE = 'Le service est temporairement indisponible.';

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

        if (empty($errors)) {
            return $this->json(['codeRetour' => 'OK', 'listMessage' => []], 200);
        }

        // Le détail (état de la base, nom d'une table…) reste dans les journaux : cette route est PUBLIQUE, elle ne
        // doit pas décrire l'infrastructure à un visiteur anonyme.
        $this->logger->warning('[HealthCheck] ⚠️ contrôle en échec', [
            'erreurs' => $errors,
            'client_ip' => $key,
        ]);

        return $this->json([
            'codeRetour' => 'KO',
            'listMessage' => [self::MESSAGE_PUBLIC_INDISPONIBLE],
        ], 503);
    }
}
