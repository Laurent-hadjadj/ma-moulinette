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
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use App\Service\{ClientService, UrlBuilderService};

/**
 * [Description GetSonarVersionService]
 */
class GetSonarVersionService extends AbstractController
{
    /** Définition des constantes */
    private static string $sonarUrl = "sonar.url";
    private static string $erreurSonarInconnue = 'Erreur SonarQube inconnue.';

    /** Nom du paramètre de requête SonarQube désignant le(s) projet(s) ciblé(s). */
    private static string $paramComponentKeys = 'componentKeys';
    private static string $paramComponents = 'components';

    public function __construct(
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger,
        private CacheInterface $cache
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
        // ⏱️ TTL 5 minutes : la version du serveur SonarQube ne change pour ainsi dire jamais
        // en cours de journée, on évite de la re-demander avant chaque appel API.
        return $this->cache->get('sonarqube_server_version', function (ItemInterface $item): array|string {
            $item->expiresAfter(300);

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
                    // On ne met pas en cache un échec (serveur peut-être temporairement indisponible).
                    $item->expiresAfter(0);
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

                $item->expiresAfter(0);
                return [
                    'code' => 500,
                    'erreur' => $e->getMessage()
                ];
            } finally {
                $this->logger->info("[Version Sonar] ✅ Fin de la récupération de la version de l'application SonarQube.");
            }
        });
    }

    /**
     * Détermine la clé de requête SonarQube à utiliser pour cibler un projet dans
     * `/api/issues/search` (`componentKeys` vs `components`, renommage depuis la 10.2).
     *
     * Cas nominal : `components` (les serveurs réellement utilisés aujourd'hui sont en
     * 10 ou plus, pour disposer d'OWASP 2021). `componentKeys` est l'exception, réservée
     * aux serveurs 8/9 encore en place — y compris quand la version n'a pas pu être
     * déterminée (on ne bascule sur l'ancienne clé que sur confirmation positive).
     *
     * @return string
     *
     * Created at: 11/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function componentKeyParam(): string
    {
        $version = $this->getSonarQubeVersion();

        if (is_array($version)) {
            return self::$paramComponents;
        }

        $major = (int) strtok($version, '.');

        return ($major >= 1 && $major < 10) ? self::$paramComponentKeys : self::$paramComponents;
    }
}
