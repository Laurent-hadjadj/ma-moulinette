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

namespace App\Controller\Batch;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Owasp, InformationProjet};
use App\Service\{ClientService, UrlBuilderService};

/**
 * [Description BatchCollecteOwaspController]
 */
class BatchCollecteOwaspController extends AbstractController
{
    /** Définition des constantes */
    private static string $sonarUrl = "sonar.url";
    private static string $europeParis = "Europe/Paris";
    private static string $erreur404 = "Je n'ai pas trouvé le projet dans l'application (Erreur 404).";
    // MODIF 2026-05-26 : extraction des string literals dupliqués (S1192).
    private static string $owaspCategories = 'a1,a2,a3,a4,a5,a6,a7,a8,a9,a10';
    /* MODIF 2026-07-18 : liste des tags de secours (owasp-aXX, zéro-paddés —
     * cf. mkDocs/docs/ma-moulinette/application/owasp.md) utilisée quand la
     * facette officielle owaspTop10/owaspTop10-2021 ne classe aucune issue. */
    private static string $owaspTagCategories =
        'owasp-a01,owasp-a02,owasp-a03,owasp-a04,owasp-a05,owasp-a06,owasp-a07,owasp-a08,owasp-a09,owasp-a10';

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
        ) {
    }

    /**
     * [Description for BatchCollecteOwasp]
     *
     * @param string $maven_key
     *
     * @return array<int|string, mixed>
     *
     * Created at: 21/05/2024 23:48:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteOwasp(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $this->logger->info("[Batch OWASP] 🛡️ Début de la collecte pour le projet {$maven_key}. (mode: {$mode_collecte}, user: {$utilisateur_collecte})");

        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');

        /** On instancie l'EntityRepository */
        $informationProjetRepos = $this->em->getRepository(InformationProjet::class);
        $owaspRepos = $this->em->getRepository(Owasp::class);

        /** On récupère la version du serveur SonarQube */
        $sonar_version = $this->getParameter('sonar.version');

        /** Tableau des paramètres pour la requête HTTP */
        $queryParamsList = [
            'owasp2017' => ['componentKeys' => $maven_key, 'facets' => 'owaspTop10', 'owaspTop10' => self::$owaspCategories],
            'owasp2021' => ['componentKeys' => $maven_key, 'facets' => 'owaspTop10-2021', 'owaspTop10-2021' => self::$owaspCategories],
            'owasp2025' => ['componentKeys' => $maven_key, 'facets' => 'owaspTop10-2025', 'owaspTop10-2025' => self::$owaspCategories]
        ];

        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(self::$sonarUrl),
            '/api/issues/search',
            $queryParamsList['owasp2017']
        );

        $this->logger->debug("[Batch OWASP] 🛠️ Appel OWASP 2017 → {$url}");
        /** On appelle les requêtes HTTP pour chaque référentiel */
        $owasp2017 = $this->client->httpSonarQube($url);

        /** Il ne peut pas y avoir de 404, l'API renvoie toujours une response 200*/
        if (isset($owasp2017['code']) && in_array($owasp2017['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error("[Batch OWASP] ❌ Erreur OWASP 2017 pour {$maven_key} : {$owasp2017['code']}");
            return [
                    'code' => $owasp2017['code'],
                    'erreur' => $owasp2017['erreur']
                ];
        }

        /** On execute si la version de SonarQube est >= 9 */
        $owasp2021 = ['NC'];
        if ((int) $sonar_version > 8){
            $url = $this->urlBuilder->build(
            $this->getParameter(self::$sonarUrl),
            '/api/issues/search',
            $queryParamsList['owasp2021']
            );

            $this->logger->debug("[Batch OWASP] 🛠️ Appel OWASP 2021 → {$url}");
            $owasp2021 = $this->client->httpSonarQube($url);
            if (isset($owasp2021['code']) && in_array($owasp2021['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
                $this->logger->error("[Batch OWASP] ❌ Erreur OWASP 2021 pour {$maven_key} : {$owasp2021['code']}");
                return [
                    'code' => $owasp2021['code'],
                    'erreur' => $owasp2021['erreur']
                ];
            }
        }

        /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map = [ 'maven_key' => $maven_key ];
        $select_information = $informationProjetRepos->selectInformationProjetVersion($map);
            if ($select_information['code'] != 200) {
                /* MODIF 2026-05-07 : log libellé corrigé (selectInformationProjetVersion, pas OWASP 2021). */
                $this->logger->error("[Batch OWASP] ❌ Erreur selectInformationProjetVersion pour {$maven_key} : {$select_information['code']}");
                return [
                    'code' => $select_information['code'],
                    'erreur' => $select_information['erreur']
                ];
        }

        /** Il n'y a pas de projet dans la table ou la collecte des informations du projet a planté ! */
        if (!$select_information['info']) {
            $this->logger->warning("[Batch OWASP] ⚠️ Aucun projet trouvé pour {$maven_key}");
            return [
                'code' => 404,
                'erreur' => self::$erreur404 // 'message' ?
            ];
        }

        /** On reconstruit la date de collecte au format dateTime */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));
        $date_version = new \DateTimeImmutable($select_information['info']['date'], new \DateTimeZone(self::$europeParis));

        /* MODIF 2026-07-18 : appel de secours (mémoïsé, un seul appel HTTP
         * même si 2017 ET 2021 en ont besoin) quand la facette officielle ne
         * classe aucune issue. Le tag `owasp-aXX` n'étant pas spécifique à un
         * référentiel (contrairement à la facette owaspTop10 vs
         * owaspTop10-2021), le même résultat sert aux deux. */
        $tagFallback = null;
        $fetchTagFallback = function () use (&$tagFallback, $maven_key) {
            if ($tagFallback !== null) {
                return $tagFallback;
            }
            $url = $this->urlBuilder->build(
                $this->getParameter(self::$sonarUrl),
                '/api/issues/search',
                ['componentKeys' => $maven_key, 'tags' => self::$owaspTagCategories, 'ps' => 500]
            );
            $this->logger->debug("[Batch OWASP] 🛠️ Appel de secours par tag (owasp-aXX) → {$url}");
            $response = $this->client->httpSonarQube($url);
            if (
                isset($response['code'])
                && in_array($response['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])
            ) {
                $this->logger->warning("[Batch OWASP] ⚠️ Échec de l'appel de secours par tag : {$response['code']}");
                $tagFallback = ['issues' => [], 'total' => 0, 'effortTotal' => 0];
                return $tagFallback;
            }
            $tagFallback = $response['json'] ?? ['issues' => [], 'total' => 0, 'effortTotal' => 0];
            return $tagFallback;
        };

        $prepareOwaspData = function ($referential) use (
            $maven_key,
            $date_version,
            $date,
            $select_information,
            $mode_collecte,
            $utilisateur_collecte,
            $fetchTagFallback
        ) {
            $source = 'facet';
            $nombre = array_fill(1, 10, 0);
            $owaspIssues = array_fill_keys(range(1, 10), array_fill_keys(
                ['blocker', 'critical', 'major', 'info', 'minor'], 0));
            $effort_total = (int) ($referential['effortTotal'] ?? 0);

            if ((int) ($referential['total'] ?? 0) !== 0) {
                /** Classification officielle SonarQube (facette owaspTop10/owaspTop10-2021) */
                foreach ($referential['facets'][0]['values'] as $value) {
                    $index = substr($value['val'], 1);
                    $nombre[$index] = $value['count'];
                }
                foreach ($referential['issues'] as $issue) {
                    if (in_array($issue['status'], ['OPEN', 'CONFIRMED', 'REOPENED'])) {
                        foreach ($issue['tags'] as $tag) {
                            if (preg_match("/owasp-a(\d+)/", $tag, $matches)) {
                                $owaspIndex = (int)$matches[1];
                                $severity = strtolower($issue['severity']);
                                if (isset($owaspIssues[$owaspIndex][$severity])) {
                                    $owaspIssues[$owaspIndex][$severity]++;
                                }
                            }
                        }
                    }
                }
            } else {
                /* MODIF 2026-07-18 : la facette officielle ne classe aucune
                 * issue pour ce référentiel — secours par tag `owasp-aXX`
                 * (cf. owasp.md, note "Pourquoi le tableau de synthèse peut
                 * sembler incohérent..."). Le tag n'étant pas spécifique à
                 * une année de référentiel, le comptage obtenu ici vaudra
                 * aussi bien pour 2017 que pour 2021. */
                $fallback = $fetchTagFallback();
                $effort_total = (int) ($fallback['effortTotal'] ?? $effort_total);
                foreach ($fallback['issues'] ?? [] as $issue) {
                    if (in_array($issue['status'], ['OPEN', 'CONFIRMED', 'REOPENED'])) {
                        foreach ($issue['tags'] ?? [] as $tag) {
                            if (preg_match("/owasp-a(\d+)/", $tag, $matches)) {
                                $owaspIndex = (int)$matches[1];
                                if ($owaspIndex < 1 || $owaspIndex > 10) {
                                    continue;
                                }
                                $nombre[$owaspIndex]++;
                                $severity = strtolower($issue['severity']);
                                if (isset($owaspIssues[$owaspIndex][$severity])) {
                                    $owaspIssues[$owaspIndex][$severity]++;
                                }
                            }
                        }
                    }
                }
                if (array_sum($nombre) > 0) {
                    $source = 'tag';
                }
            }

            $map = [
                    'total' => array_sum($nombre),
                    'maven_key' => $maven_key,
                    'version' => $select_information['info']['version'] ?? 'N.C',
                    'date_version' => $date_version,
                    'effort_total' => $effort_total,
                    'source' => $source,
                    'mode_collecte' => $mode_collecte,
                    'utilisateur_collecte' => $utilisateur_collecte,
                    'date_enregistrement' => $date
            ];

            /** On ajoute les valeurs de a1 à a10 */
            for ($i = 1; $i <= 10; $i++) {
                $map["a$i"] = $nombre[$i];
            }

            /** Ajoute le nombre de cas par gravité pour chaque catégorie OWASP */
            foreach ($owaspIssues as $index => $severities) {
                foreach ($severities as $severity => $count) {
                    $map["a{$index}_{$severity}"] = $count;
                }
            }
            /** On renvoi la collection pour le référentiel OWASP */
            return $map;
        };

        $owaspDataList = [];
        $total_2017 = 'NC';
        /* pour chaque référentiel 2017/2021 */
        if (array_key_exists('total', $owasp2017['json'])) {
            $owaspDataList[] = $prepareOwaspData($owasp2017['json']);
            $owaspDataList[0]['referential_owasp'] = 2017;
            $total_2017 = $owaspDataList[0]['total'];
        }

        $total_2021 = 'NC';
        /** $owasp2021 = ['NC'] on a pas de données pour le référentiel 2021 */
        if (isset($owasp2021['json']) && array_key_exists('total', $owasp2021['json'])) {
            /* MODIF 2026-05-07 : index dynamique + lecture sous-clé 'json' (cohérent avec 2017). */
            $owaspDataList[] = $prepareOwaspData($owasp2021['json']);
            $idx2021 = array_key_last($owaspDataList);
            $owaspDataList[$idx2021]['referential_owasp'] = 2021;
            $total_2021 = $owaspDataList[$idx2021]['total'];
        }

        /** On supprime les informations sur le projet pour la dernière analyse. */
        $this->logger->debug("[Batch OWASP] 🛠️ Suppression OWASP existant pour {$maven_key}");
        $map = [ 'maven_key' => $maven_key ];
        $delete = $owaspRepos->deleteOwaspMavenKey($map);
        if ($delete['code'] != 200) {
            $this->logger->error("[Batch OWASP] ❌ Échec de la requête suppression OWASP deleteOwaspMavenKey : {$delete['erreur']}");
            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        /** On enregistre */
        $this->logger->debug("[Batch OWASP] 💾 Insertion des données OWASP pour {$maven_key}");
        $insert = $owaspRepos->insertOwasp($owaspDataList);
        if ($insert['code'] != 200) {
            $this->logger->error("[Batch OWASP] ❌ Échec de la requête insertOwasp : {$insert['erreur']}");
            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        $this->logger->info("[Batch OWASP] ℹ️ Collecte terminée pour {$maven_key}. Total 2017: {$total_2017}, 2021: {$total_2021}");
        return [
            'code' => 200,
            'owasp2017' => $total_2017,
            'owasp2021' => $total_2021,
            'message' => "La collecte des menaces OWASP pour ce projet est terminée."
            ];
    }
}
