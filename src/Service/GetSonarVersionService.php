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

    /** Nom du paramètre de requête SonarQube désignant le projet sur `/api/hotspots/search`. */
    private static string $paramProjectKey = 'projectKey';
    private static string $paramProject = 'project';

    /** Paramètre de filtre par statut sur `/api/issues/search` (déprécié depuis la 10.4). */
    private static string $paramStatuses = 'statuses';
    private static string $valueStatusesActives = 'OPEN,CONFIRMED,REOPENED';
    private static string $paramIssueStatuses = 'issueStatuses';
    private static string $valueIssueStatusesActives = 'OPEN,CONFIRMED,ACCEPTED';

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

    /**
     * Détermine la clé de requête SonarQube à utiliser pour cibler un projet dans
     * `/api/hotspots/search` (`projectKey` vs `project`, même renommage depuis la 10.2
     * que `componentKeyParam()` sur `/api/issues/search` — confirmé par les messages de
     * dépréciation du serveur). `projectKey` est **supprimé** (pas juste déprécié) depuis
     * la 2026.10 : vérifié en direct, `projectKey=...` renvoie HTTP 400 sur cette version,
     * `project=...` renvoie 200.
     *
     * Cas nominal : `project` (les serveurs réellement utilisés aujourd'hui sont en 10 ou
     * plus). `projectKey` est l'exception, réservée aux serveurs 8/9 — y compris quand la
     * version n'a pas pu être déterminée (on ne bascule sur l'ancienne clé que sur
     * confirmation positive), même logique que `componentKeyParam()`.
     *
     * @return string
     *
     * Created at: 16/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function hotspotProjectParam(): string
    {
        $version = $this->getSonarQubeVersion();

        if (is_array($version)) {
            return self::$paramProject;
        }

        $major = (int) strtok($version, '.');

        return ($major >= 1 && $major < 10) ? self::$paramProjectKey : self::$paramProject;
    }

    /**
     * Détermine le filtre de statut à utiliser sur `/api/issues/search` pour ne compter
     * que les anomalies encore actives (`statuses` vs `issueStatuses`, paramètre déprécié
     * depuis la 10.4 — remplacé y compris pour les serveurs encore en version 10, cf.
     * mkDocs/docs/ma-moulinette/application/projet.md pour la règle complète et sa
     * vérification en conditions réelles).
     *
     * Ancien modèle (8, 9, 10) : `statuses=OPEN,CONFIRMED,REOPENED` (RESOLVED/CLOSED
     * exclus). Nouveau modèle (2024+) : `issueStatuses=OPEN,CONFIRMED,ACCEPTED`
     * (FALSE_POSITIVE/FIXED exclus — ACCEPTED est inclus : un défaut « accepté » reste
     * réel et non corrigé, juste reporté, ce n'est pas une clôture).
     *
     * Cas nominal (version non déterminée) : nouveau modèle, mêmes raisons que
     * componentKeyParam() (les serveurs réellement utilisés aujourd'hui sont ≥ 10).
     *
     * @return array{param: string, value: string}
     *
     * Created at: 11/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function issueStatusParam(): array
    {
        $version = $this->getSonarQubeVersion();

        if (!is_array($version)) {
            $major = (int) strtok($version, '.');
            if ($major >= 1 && $major <= 10) {
                return ['param' => self::$paramStatuses, 'value' => self::$valueStatusesActives];
            }
        }

        return ['param' => self::$paramIssueStatuses, 'value' => self::$valueIssueStatusesActives];
    }
}
