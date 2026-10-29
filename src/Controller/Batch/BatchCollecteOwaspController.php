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
use App\Entity\{Owasp, OwaspDetails, InformationProjet};
use App\Service\{ClientService, UrlBuilderService, GetSonarVersionService};
use App\Service\Owasp\OwaspRuleClassifierService;

/**
 * [Description BatchCollecteOwaspController]
 *
 * MODIF 27/10/2026 : remplace la boucle par catégorie (facette officielle
 * owaspTop10/owaspTop10-2021) + le secours par tag (owasp-aXX) par UN SEUL
 * appel paginé /api/issues/search?rules=<~126 clés core+findsecbugs>, classé
 * localement par OwaspRuleClassifierService. Raison (vérifiée en direct sur
 * une instance SonarQube 26.10 réelle, cf. mkDocs/.../application/owasp.md) :
 * la facette officielle n'indexe jamais les règles FindSecBugs (tag texte
 * libre owasp-aXX, jamais le bloc structuré securityStandards) — le secours
 * par tag comblait les comptages mais jamais le détail par sévérité d'une
 * catégorie classée uniquement par tag (tableau de détail vide côté front).
 * Les Security Hotspots ne sont pas concernés par ce changement (cf.
 * BatchCollecteHotspotOwaspController, inchangé).
 */
class BatchCollecteOwaspController extends AbstractController
{
    /** Définition des constantes */
    private static string $sonarUrl = "sonar.url";
    private static string $europeParis = "Europe/Paris";
    private static string $erreur404 = "Je n'ai pas trouvé le projet dans l'application (Erreur 404).";
    private static int $pageSize = 500;
    /** @var list<400|401|403|404|407|414|418|422|429|500|502|503|504|505> */
    private static array $httpErrorCodes = [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505];

    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private GetSonarVersionService $sonarVersion,
        private OwaspRuleClassifierService $ruleClassifier,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for fetchAllOwaspIssues]
     * Récupère, paginée (p/ps, pattern SonarAnalysisFetcherService::fetchAnalyses()),
     * la totalité des issues SonarQube pour les règles couvrant OWASP
     * (core + findsecbugs, cf. OwaspRuleClassifierService::allRuleKeys()).
     *
     * @return array{code: 200, issues: array<int, array{rule: string, component: string, severity: string, line: ?int, message: string}>, effortTotal: int}|array{code: 400|401|403|404|407|414|418|422|429|500|502|503|504|505, erreur: string}
     *
     * Created at: 27/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function fetchAllOwaspIssues(string $maven_key, string $componentKeyParam, string $statusParam, string $statusValue): array
    {
        $rules = implode(',', $this->ruleClassifier->allRuleKeys());
        $issues = [];
        $effortTotal = 0;
        $page = 1;
        $total = 0;

        do {
            $url = $this->urlBuilder->build(
                $this->getParameter(self::$sonarUrl),
                '/api/issues/search',
                [
                    $componentKeyParam => $maven_key,
                    'rules' => $rules,
                    $statusParam => $statusValue,
                    'ps' => self::$pageSize,
                    'p' => $page,
                ]
            );
            $this->logger->debug("[Batch OWASP] 🛠️ Appel issues OWASP (page {$page}) → {$url}");
            $response = $this->client->httpSonarQube($url);

            if (isset($response['code']) && in_array($response['code'], self::$httpErrorCodes, true)) {
                $this->logger->error("[Batch OWASP] ❌ Erreur appel issues OWASP (page {$page}) pour {$maven_key} : {$response['code']}");
                return ['code' => $response['code'], 'erreur' => (string) ($response['erreur'] ?? '')];
            }

            foreach ($response['json']['issues'] ?? [] as $issue) {
                $issues[] = [
                    'rule' => (string) ($issue['rule'] ?? ''),
                    'component' => (string) ($issue['component'] ?? ''),
                    'severity' => (string) ($issue['severity'] ?? ''),
                    /* MODIF 28/10/2026 : line/message déjà présents dans la réponse
                     * SonarQube, jusqu'ici jamais capturés — nécessaires au détail
                     * individuel des vulnérabilités (table owasp_details). */
                    'line' => isset($issue['line']) ? (int) $issue['line'] : null,
                    'message' => (string) ($issue['message'] ?? ''),
                ];
            }
            if ($page === 1) {
                $effortTotal = (int) ($response['json']['effortTotal'] ?? 0);
            }
            $total = (int) ($response['json']['paging']['total'] ?? 0);
            $page++;
        } while (($page - 1) * self::$pageSize < $total);

        return ['code' => 200, 'issues' => $issues, 'effortTotal' => $effortTotal];
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
        $owaspDetailsRepos = $this->em->getRepository(OwaspDetails::class);

        /** On récupère la version du serveur SonarQube (propriété applicative) : seul usage restant,
         *  décider si une ligne OWASP 2021 doit être construite (même seuil que le bouton front,
         *  cf. templates/owasp/index.html.twig) — les deux référentiels sont désormais classés depuis
         *  le même appel HTTP, plus besoin de ce paramètre pour choisir quel appel lancer. */
        $sonar_version = $this->getParameter('sonar.version');

        /* MODIF 2026-10-11 : componentKeys renommée components depuis SonarQube 10.2. */
        $componentKeyParam = $this->sonarVersion->componentKeyParam();
        $issueStatus = $this->sonarVersion->issueStatusParam();

        $fetchResult = $this->fetchAllOwaspIssues($maven_key, $componentKeyParam, $issueStatus['param'], $issueStatus['value']);
        if ($fetchResult['code'] !== 200) {
            return [
                'code' => $fetchResult['code'],
                'erreur' => $fetchResult['erreur']
            ];
        }

        /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map = [ 'maven_key' => $maven_key ];
        $select_information = $informationProjetRepos->selectInformationProjetVersion($map);
        if ($select_information['code'] != 200) {
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
                'erreur' => self::$erreur404
            ];
        }

        /** On reconstruit la date de collecte au format dateTime */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));
        $date_version = new \DateTimeImmutable($select_information['info']['date'], new \DateTimeZone(self::$europeParis));

        /** Classification locale (core + findsecbugs) par catégorie/sévérité/module. */
        $classified = $this->ruleClassifier->classify($fetchResult['issues']);

        $buildRow = function (int $referential) use ($classified, $maven_key, $select_information, $date_version, $fetchResult, $mode_collecte, $utilisateur_collecte, $date): array {
            return [
                'referential_owasp' => $referential,
                'maven_key' => $maven_key,
                'version' => $select_information['info']['version'] ?? 'N.C',
                'date_version' => $date_version,
                /* MODIF 27/10/2026 : effort_total reste une simplification assumée (cf. historique du
                 * fichier) — l'appel étant désormais unique pour les deux référentiels, la même valeur
                 * globale (toutes règles OWASP confondues) est reportée sur chaque ligne construite,
                 * plutôt qu'un effort recalculé règle par règle et séparé par référentiel. */
                'effort_total' => $fetchResult['effortTotal'],
                'categories' => $classified[$referential],
                'module_frontend' => $classified['module'][$referential]['frontend'],
                'module_backend' => $classified['module'][$referential]['backend'],
                'module_autre' => $classified['module'][$referential]['autre'],
                'mode_collecte' => $mode_collecte,
                'utilisateur_collecte' => $utilisateur_collecte,
                'date_enregistrement' => $date,
            ];
        };

        /* 2017 : toujours construite (même comportement que l'ancienne facette owaspTop10, toujours
         * disponible quelle que soit la version du serveur). 2021 : seulement si le serveur la supporte
         * (même seuil que le bouton front) — une ligne « tout à zéro » est construite même sans aucune
         * catégorie touchée, pour que la page affiche un vrai 0 plutôt qu'un 406 "aucune donnée". */
        $owaspDataList = [$buildRow(2017)];
        $total_2017 = array_sum(array_column($classified[2017], 'total'));

        $total_2021 = 'NC';
        if ((int) $sonar_version > 8) {
            $owaspDataList[] = $buildRow(2021);
            $total_2021 = array_sum(array_column($classified[2021], 'total'));
        }

        /* MODIF 28/10/2026 : détail individuel de chaque vulnérabilité classée
         * (table owasp_details) — mêmes gardes 2017/2021 que $owaspDataList
         * ci-dessus, construit à partir de $classified['details'] (déjà
         * calculé par OwaspRuleClassifierService::classify(), aucun appel
         * SonarQube supplémentaire). */
        /* MODIF 28/10/2026 : BUG CORRIGÉ — le message d'une issue SonarQube (texte
         * libre, parfois long sur FindSecBugs) n'était jamais borné avant
         * l'insertion, alors que owasp_details.message est VARCHAR(255) : une
         * collecte réelle sur un projet avec des messages longs faisait échouer
         * tout l'INSERT (transaction annulée) avec "valeur trop longue pour le
         * type character varying(255)". file_path potentiellement concerné pour
         * la même raison (chemin de fichier arbitraire) — tronqué par prudence. */
        $buildDetailRows = function (int $referential) use ($classified, $maven_key, $select_information, $date_version, $mode_collecte, $utilisateur_collecte, $date): array {
            $rows = [];
            foreach ($classified['details'][$referential] as $issue) {
                $rows[] = [
                    'referential_owasp' => $referential,
                    'maven_key' => $maven_key,
                    'version' => $select_information['info']['version'] ?? 'N.C',
                    'date_version' => $date_version,
                    'rule_key' => $issue['rule'],
                    'categories' => $issue['categories'],
                    'severity' => $issue['severity'],
                    'source' => $issue['source'],
                    'module' => $issue['module'],
                    'file_path' => mb_substr(str_replace($maven_key . ':', '', $issue['component']), 0, 255),
                    'line' => $issue['line'],
                    'message' => mb_substr($issue['message'], 0, 255),
                    'mode_collecte' => $mode_collecte,
                    'utilisateur_collecte' => $utilisateur_collecte,
                    'date_enregistrement' => $date,
                ];
            }
            return $rows;
        };

        $owaspDetailsDataList = $buildDetailRows(2017);
        if ((int) $sonar_version > 8) {
            $owaspDetailsDataList = array_merge($owaspDetailsDataList, $buildDetailRows(2021));
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

        /** On supprime puis on enregistre le détail des vulnérabilités (table owasp_details). */
        $this->logger->debug("[Batch OWASP] 🛠️ Suppression owasp_details existant pour {$maven_key}");
        $deleteDetails = $owaspDetailsRepos->deleteOwaspDetailsMavenKey($map);
        if ($deleteDetails['code'] != 200) {
            $this->logger->error("[Batch OWASP] ❌ Échec de la requête suppression owasp_details : {$deleteDetails['erreur']}");
            return [
                'code' => $deleteDetails['code'],
                'erreur' => $deleteDetails['erreur']
            ];
        }

        $this->logger->debug("[Batch OWASP] 💾 Insertion du détail owasp_details pour {$maven_key}");
        $insertDetails = $owaspDetailsRepos->insertOwaspDetails($owaspDetailsDataList);
        if ($insertDetails['code'] != 200) {
            $this->logger->error("[Batch OWASP] ❌ Échec de la requête insertOwaspDetails : {$insertDetails['erreur']}");
            return [
                'code' => $insertDetails['code'],
                'erreur' => $insertDetails['erreur']
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
