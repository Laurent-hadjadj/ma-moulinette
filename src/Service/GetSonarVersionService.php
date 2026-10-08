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

namespace App\Service;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use App\Service\{ClientService, UrlBuilderService};

/**
 * [Description GetSonarVersionService]
 */
class GetSonarVersionService extends AbstractController
{
    /** Définition des constantes */
    private static string $sonarUrl = "sonar.url";
    private static string $erreurSonarInconnue = 'Erreur SonarQube inconnue.';

    public function __construct(
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {}

    /**
     * [Description for getSonarQubeVersion]
     *
     * @return array<string, int|string>|string
     *
     * Created at: 08/10/2026 16:12:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getSonarQubeVersion(): array|string
    {
        $this->logger->info("[Version Sonar] ℹ️ Récupération de la version de l'application SonarQube.");

        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(self::$sonarUrl),
            '/api/server/version',
            []
        );

        try {
            $this->logger->debug('[Version Sonar] 🛠️ Appel API SonarQube', ['url' => $url]);
            $result = $this->client->httpSonarQube($url);

            /** On catch les erreurs HTTP :) */
            if (isset($result['code']) && in_array(
                $result['code'],
                [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505]
            )) {
                $this->logger->error('[Version Sonar] ❌ Erreur du serveur SonarQube', [
                    'url' => $url,
                    'code' => $result['code'],
                    'erreur' => $result['erreur'] ?? self::$erreurSonarInconnue
                ]);
                return [
                    'code' => $result['code'],
                    'erreur' => $result['erreur'] ?? self::$erreurSonarInconnue
                ];
            }

            /** On retourne le texte de la version */
            return $result['json']['texte'];
        } catch (\Throwable $e) {
            $this->logger->critical("[Version Sonar] 🔴 " . self::$erreurSonarInconnue, [
                'error' => $e->getMessage()
            ]);

            return [
                'code' => 500,
                'erreur' => $e->getMessage()
            ];
        } finally {
            $this->logger->info("[Version Sonar] ✅ Fin de la récupération de la version de l'application SonarQube.");
        }
    }
}
