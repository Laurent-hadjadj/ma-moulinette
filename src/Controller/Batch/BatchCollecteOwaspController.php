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
use App\Service\{ClientService, UrlBuilderService, GetSonarVersionService};

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
    /* MODIF 2026-07-18 : liste des tags de secours (owasp-aXX) utilisée en
     * complément de la facette officielle owaspTop10/owaspTop10-2021, catégorie
     * par catégorie (cf. mkDocs/docs/ma-moulinette/application/owasp.md).
     * MODIF 26/10/2026 : BUG CORRIGÉ — zéro-padding retiré (`owasp-a01` →
     * `owasp-a1`). Vérifié en direct sur une vraie instance SonarQube (26.10,
     * Community) : le tag réellement posé par les règles (ex. plugin
     * findsecbugs) est `owasp-a6`, jamais `owasp-a06`. Avec le zéro-padding,
     * `tags=owasp-a06` ne matchait STRICTEMENT AUCUNE issue (confirmé :
     * total=0), alors que `tags=owasp-a6` en trouvait 9 — le secours par tag
     * était donc silencieusement aveugle pour A1 à A9 depuis son introduction,
     * seul A10 pouvait fonctionner par coïncidence de format. */
    private static string $owaspTagCategories =
        'owasp-a1,owasp-a2,owasp-a3,owasp-a4,owasp-a5,owasp-a6,owasp-a7,owasp-a8,owasp-a9,owasp-a10';

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
        private GetSonarVersionService $sonarVersion,
        private LoggerInterface $logger
        ) {
    }

    /**
     * [Description for fetchCategorySeverityBreakdown]
     * Répartition par sévérité d'une seule catégorie OWASP, classée via la
     * facette officielle SonarQube — un appel ciblé (`owaspTop10`/
     * `owaspTop10-2021=aN` seul, pas la liste des 10) isole exactement les
     * issues de cette catégorie, confirmé en direct (SonarQube 26.10) :
     * contrairement à la requête combinée sur les 10 catégories, on peut
     * alors lire la vraie sévérité de chaque issue sans avoir besoin d'un tag.
     *
     * @param string $facetParam 'owaspTop10' (2017) ou 'owaspTop10-2021' (2021)
     * @param string $maven_key
     * @param int $category 1 à 10
     * @param string $componentKeyParam
     * @param string $statusField
     * @param string[] $allowedStatuses
     *
     * @return array<string, int>
     *
     * Created at: 26/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function fetchCategorySeverityBreakdown(
        string $facetParam,
        string $maven_key,
        int $category,
        string $componentKeyParam,
        string $statusField,
        array $allowedStatuses
    ): array {
        $severities = ['blocker' => 0, 'critical' => 0, 'major' => 0, 'info' => 0, 'minor' => 0];

        $url = $this->urlBuilder->build(
            $this->getParameter(self::$sonarUrl),
            '/api/issues/search',
            [$componentKeyParam => $maven_key, $facetParam => "a{$category}", 'ps' => 500]
        );
        $this->logger->debug("[Batch OWASP] 🛠️ Appel sévérité catégorie officielle A{$category} → {$url}");
        $response = $this->client->httpSonarQube($url);

        if (isset($response['code']) && in_array($response['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->warning("[Batch OWASP] ⚠️ Échec de l'appel sévérité catégorie officielle A{$category} : {$response['code']}");
            return $severities;
        }

        foreach ($response['json']['issues'] ?? [] as $issue) {
            if (!in_array($issue[$statusField] ?? null, $allowedStatuses, true)) {
                continue;
            }
            $severity = strtolower($issue['severity'] ?? '');
            if (isset($severities[$severity])) {
                $severities[$severity]++;
            }
        }

        return $severities;
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

        /** On récupère la version du serveur SonarQube (propriété applicative, choix du référentiel OWASP) */
        $sonar_version = $this->getParameter('sonar.version');

        /* MODIF 2026-10-11 : componentKeys renommée components depuis SonarQube 10.2 —
         * résolu séparément, à partir de la vraie version serveur (GetSonarVersionService),
         * pas de la propriété sonar.version ci-dessus qui reste réservée au choix OWASP. */
        $componentKeyParam = $this->sonarVersion->componentKeyParam();

        /* MODIF 2026-10-11 : ce controller ne passe pas 'statuses'/'issueStatuses' en
         * paramètre de requête (il filtre côté client sur le statut de chaque issue
         * retournée, pas via l'API) — mais le champ à lire sur l'issue ('status' vs
         * 'issueStatus') et les valeurs considérées comme « anomalie active » suivent
         * la même règle que partout ailleurs (cf. GetSonarVersionService::issueStatusParam()
         * et projet.md). Les deux champs coexistent sur chaque issue quelle que soit la
         * version du serveur (vérifié en live sur 26.9.0), donc l'un des deux est toujours
         * fiable selon le modèle résolu. */
        $issueStatus = $this->sonarVersion->issueStatusParam();
        $statusField = $issueStatus['param'] === 'statuses' ? 'status' : 'issueStatus';
        $allowedStatuses = explode(',', $issueStatus['value']);

        /** Tableau des paramètres pour la requête HTTP */
        $queryParamsList = [
            'owasp2017' => [$componentKeyParam => $maven_key, 'facets' => 'owaspTop10', 'owaspTop10' => self::$owaspCategories],
            'owasp2021' => [$componentKeyParam => $maven_key, 'facets' => 'owaspTop10-2021', 'owaspTop10-2021' => self::$owaspCategories],
            'owasp2025' => [$componentKeyParam => $maven_key, 'facets' => 'owaspTop10-2025', 'owaspTop10-2025' => self::$owaspCategories]
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
        $fetchTagFallback = function () use (&$tagFallback, $maven_key, $componentKeyParam) {
            if ($tagFallback !== null) {
                return $tagFallback;
            }
            $url = $this->urlBuilder->build(
                $this->getParameter(self::$sonarUrl),
                '/api/issues/search',
                [$componentKeyParam => $maven_key, 'tags' => self::$owaspTagCategories, 'ps' => 500]
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

        $prepareOwaspData = function ($referential, $facetParam) use (
            $maven_key,
            $date_version,
            $date,
            $select_information,
            $mode_collecte,
            $utilisateur_collecte,
            $fetchTagFallback,
            $statusField,
            $allowedStatuses,
            $componentKeyParam
        ) {
            $nombre = array_fill(1, 10, 0);
            $owaspIssues = array_fill_keys(range(1, 10), array_fill_keys(
                ['blocker', 'critical', 'major', 'info', 'minor'], 0));

            /** Comptage par catégorie via la facette officielle SonarQube. */
            $facetCounts = array_fill(1, 10, 0);
            $facetValues = $referential['facets'][0]['values'] ?? null;
            if ($facetValues !== null) {
                foreach ($facetValues as $value) {
                    $index = (int) substr($value['val'], 1);
                    if ($index >= 1 && $index <= 10) {
                        $facetCounts[$index] = (int) $value['count'];
                    }
                }
            }

            /* MODIF 26/10/2026 : BUG CORRIGÉ — le secours par tag `owasp-aXX`
             * était auparavant utilisé seulement quand la facette officielle
             * était ENTIÈREMENT vide pour tout le référentiel. Cas réel
             * vérifié en direct (SonarQube 26.10 Community) :
             * la facette owaspTop10-2021 classe correctement A5/A7 (2 règles
             * cœur SonarQube qui portent la taxonomie officielle, même en
             * Community) mais ignore totalement A6 — pourtant réellement
             * détecté par le plugin tiers findsecbugs, qui pose son propre tag
             * `owasp-a6` sans jamais être repris dans la taxonomie officielle.
             * Comme le total de la facette n'était pas nul (A5+A7 = 3),
             * l'ancienne logique prenait la branche "officielle" pour TOUT le
             * référentiel et ne regardait jamais le secours par tag — les 9
             * anomalies A6 restaient invisibles. Le secours par tag est donc
             * désormais calculé systématiquement (même appel HTTP mémorisé
             * qu'avant, un seul pour 2017 ET 2021), et la fusion se fait
             * catégorie par catégorie plus bas : facette si elle a classé
             * quelque chose pour CETTE catégorie, tag sinon. */
            $fallback = $fetchTagFallback();
            $tagCounts = array_fill(1, 10, 0);
            $tagSeverities = array_fill_keys(range(1, 10), array_fill_keys(
                ['blocker', 'critical', 'major', 'info', 'minor'], 0));
            foreach ($fallback['issues'] ?? [] as $issue) {
                if (!in_array($issue[$statusField] ?? null, $allowedStatuses, true)) {
                    continue;
                }
                foreach ($issue['tags'] ?? [] as $tag) {
                    if (preg_match('/^owasp-a(\d{1,2})$/', $tag, $matches)) {
                        $owaspIndex = (int) $matches[1];
                        if ($owaspIndex < 1 || $owaspIndex > 10) {
                            continue;
                        }
                        $tagCounts[$owaspIndex]++;
                        $severity = strtolower($issue['severity']);
                        if (isset($tagSeverities[$owaspIndex][$severity])) {
                            $tagSeverities[$owaspIndex][$severity]++;
                        }
                    }
                }
            }

            $hasFacetCategory = false;
            $hasTagCategory = false;
            for ($i = 1; $i <= 10; $i++) {
                if ($facetCounts[$i] > 0) {
                    $nombre[$i] = $facetCounts[$i];
                    $hasFacetCategory = true;
                    /* MODIF 26/10/2026 : BUG CORRIGÉ — la répartition par
                     * sévérité était construite en cherchant un tag `owasp-aX`
                     * sur les issues renvoyées par la facette officielle, alors
                     * que ces issues n'en portent jamais (vérifié en direct :
                     * tags réels `cwe,secret` / `cwe,former-hotspot`, aucun
                     * `owasp-aX`) — la répartition restait donc structurellement
                     * à 0 partout dès que la branche officielle était prise.
                     * La facette officielle ne dit jamais à quelle(s) issue(s)
                     * précise(s) correspond son compte agrégé (combiné sur les
                     * 10 catégories demandées en une seule requête) : seul un
                     * appel ciblé sur cette seule catégorie permet de retrouver
                     * la vraie répartition par sévérité. */
                    $owaspIssues[$i] = $this->fetchCategorySeverityBreakdown(
                        $facetParam,
                        $maven_key,
                        $i,
                        $componentKeyParam,
                        $statusField,
                        $allowedStatuses
                    );
                } elseif ($tagCounts[$i] > 0) {
                    $nombre[$i] = $tagCounts[$i];
                    $owaspIssues[$i] = $tagSeverities[$i];
                    $hasTagCategory = true;
                }
            }

            /* Une seule colonne `source` par référentiel (pas par catégorie,
             * schéma non prévu pour ça) : 'mixed' documente honnêtement le cas
             * où certaines catégories viennent de la facette et d'autres du
             * tag — cf. owasp.md. */
            $source = match (true) {
                $hasFacetCategory && $hasTagCategory => 'mixed',
                $hasTagCategory => 'tag',
                default => 'facet',
            };

            /* MODIF 26/10/2026 : effort_total reste une simplification
             * assumée (pas de recalcul par catégorie, qui demanderait de
             * parser la durée `debt` de chaque issue individuellement) — il
             * ne reflète que la source principale (facette si au moins une
             * catégorie en vient, tag sinon), pas la somme exacte des deux en
             * cas de 'mixed'. Documenté ici plutôt que laissé silencieux. */
            $effort_total = $hasFacetCategory
                ? (int) ($referential['effortTotal'] ?? 0)
                : (int) ($fallback['effortTotal'] ?? 0);

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
            $owaspDataList[] = $prepareOwaspData($owasp2017['json'], 'owaspTop10');
            $owaspDataList[0]['referential_owasp'] = 2017;
            $total_2017 = $owaspDataList[0]['total'];
        }

        $total_2021 = 'NC';
        /** $owasp2021 = ['NC'] on a pas de données pour le référentiel 2021 */
        if (isset($owasp2021['json']) && array_key_exists('total', $owasp2021['json'])) {
            /* MODIF 2026-05-07 : index dynamique + lecture sous-clé 'json' (cohérent avec 2017). */
            $owaspDataList[] = $prepareOwaspData($owasp2021['json'], 'owaspTop10-2021');
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
