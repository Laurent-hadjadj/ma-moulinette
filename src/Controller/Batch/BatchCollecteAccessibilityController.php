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
use App\Entity\Accessibility;
use App\Service\{ClientService, DateTools, LanguageDistributionService, UrlBuilderService, GetSonarVersionService};

/**
 * [Description BatchCollecteAccessibilityController]
 *
 * MODIF 26/10/2026 : Accessibilité — basée sur le tag SonarQube
 * `accessibility` (css/web/javascript/typescript).
 *
 * Pas de facette officielle dédiée (confirmé en direct : `/api/issues/search
 * ?facets=wcag` renvoie 400 et liste exhaustivement les facettes
 * supportées — aucune n'est une facette d'accessibilité/WCAG, contrairement
 * à `owaspTop10`). Le tag `wcag` existe mais ne couvre que 6 règles très
 * récentes (numérotation S79xx) ; le tag `accessibility` (122 règles,
 * css/Web/javascript/typescript) est beaucoup plus représentatif, retenu
 * comme filtre. Pas de numéro de critère WCAG exploitable via l'API (la
 * référence n'existe qu'en HTML libre dans la doc de la règle) : pas de
 * détail par critère, seulement par règle/sévérité/langage.
 *
 * Note A-E par langage et globale, même ratio SQALE que RCI/Empreinte
 * Écologique (dette minutes / (ncloc * coût de développement d'une ligne,
 * 30 min par défaut SonarQube), seuils 5/10/20/50%).
 */
class BatchCollecteAccessibilityController extends AbstractController
{
    /** Définition des constantes */
    private static string $sonarUrl = "sonar.url";
    private static string $accessibilityTag = "accessibility";
    private static string $europeParis = "Europe/Paris";
    /** Coût de développement d'une ligne de code (minutes), convention SonarQube par défaut. */
    private static int $costPerLine = 30;
    private static string $erreurSonarInconnue = "Erreur Sonar inconnue.";

    /**
     * Langages Accessibilité suivis : code SonarQube => suffixe de colonne/clé.
     *
     * @var array<string, string>
     */
    private static array $langues = [
        'css' => 'css',
        'web' => 'web',
        'js' => 'javascript',
        'ts' => 'typescript',
    ];

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 26/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private DateTools $serviceDateTools,
        private LanguageDistributionService $languageDistribution,
        private UrlBuilderService $urlBuilder,
        private GetSonarVersionService $sonarVersion,
        private LoggerInterface $logger
    ) {}

    /**
     * [Description for makeRequest]
     * Fonction générique pour exécuter une requête SonarQube et retourner le JSON décodé.
     *
     * @param array<int|string, mixed> $queryParams
     *
     * @return array<int|string, mixed>
     *
     * Created at: 26/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function makeRequest(string $endpoint, array $queryParams): array
    {
        $url = $this->urlBuilder->build(
            $this->getParameter(self::$sonarUrl),
            $endpoint,
            $queryParams
        );

        $this->logger->debug('[Batch Accessibilité] 🛠️ Appel API SonarQube', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Batch Accessibilité] ❌ Erreur API SonarQube', [
                'url' => $url,
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$erreurSonarInconnue
            ]);

            return [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::$erreurSonarInconnue
            ];
        }

        return $result['json'] ?? [];
    }

    /**
     * [Description for getAccessibilityRating]
     * Note A-E dérivée du ratio dette/ncloc, mêmes seuils SQALE que le RCI
     * et l'Empreinte Écologique : 5/10/20/50%.
     *
     * @param int|null $detteMinute
     * @param int|null $ncloc
     *
     * @return string|null null si ncloc est nul/absent (ratio non calculable)
     *
     * Created at: 26/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getAccessibilityRating(?int $detteMinute, ?int $ncloc): ?string
    {
        if ($ncloc === null || $ncloc <= 0) {
            return null;
        }

        $ratio = (($detteMinute ?? 0) / ($ncloc * self::$costPerLine)) * 100;

        if ($ratio <= 5) {
            return 'A';
        }
        if ($ratio <= 10) {
            return 'B';
        }
        if ($ratio <= 20) {
            return 'C';
        }
        if ($ratio <= 50) {
            return 'D';
        }
        return 'E';
    }

    /**
     * [Description for BatchCollecteAccessibility]
     * Calcule l'Accessibilité (nombre d'anomalies, dette estimée et note A-E,
     * globale et par langage) à partir du tag SonarQube `accessibility`.
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array<int|string, mixed>
     *
     * Created at: 26/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteAccessibility(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $accessibilityRepos = $this->em->getRepository(Accessibility::class);

        $this->logger->info('[Batch Accessibilité] ℹ️ Début de la collecte.', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** 1 - On récupère le ncloc global et par langage (pour la note A-E) */
        $componentKeyParam = $this->sonarVersion->componentKeyParam();
        $measuresResult = self::makeRequest('/api/measures/component', [
            'component' => $maven_key,
            'metricKeys' => 'ncloc,ncloc_language_distribution',
        ]);
        if (isset($measuresResult['code'])) {
            $this->logger->error('[Batch Accessibilité] ❌ Erreur lors de la récupération du ncloc', $measuresResult);
            return [
                'code' => $measuresResult['code'],
                'erreur' => $measuresResult['erreur'] ?? self::$erreurSonarInconnue
            ];
        }

        $ncloc = null;
        $nclocParLangue = [];
        foreach ($measuresResult['component']['measures'] ?? [] as $mesure) {
            if (($mesure['metric'] ?? '') === 'ncloc') {
                $ncloc = (int) $mesure['value'];
            }
            if (($mesure['metric'] ?? '') === 'ncloc_language_distribution') {
                $nclocParLangue = $this->languageDistribution->parse($mesure['value'] ?? null);
            }
        }

        /* MODIF 26/10/2026 : componentKeys renommée components depuis SonarQube 10.2 ;
         * statuses renommée issueStatuses depuis la 10.4 (cf. GetSonarVersionService). */
        $issueStatus = $this->sonarVersion->issueStatusParam();

        /** 2 - Un appel /api/issues/search par langage (total + effortTotal exacts, pas de facette) */
        $parLangue = [];
        $accessibilityAnomaliesTotal = 0;
        $accessibilityDetteMinuteTotal = 0;

        foreach (self::$langues as $codeLangue => $suffixe) {
            $nclocLangue = $nclocParLangue[$codeLangue] ?? null;

            /* Langage absent du projet (0 ligne dans ncloc_language_distribution) : rien à
             * mesurer, pas d'appel API inutile. anomalies/dette_minute restent null (et non
             * 0, qui serait une vraie valeur mesurée) pour que le front affiche '-' via
             * displayNumber() plutôt qu'un 0 trompeur. */
            if ($nclocLangue === null || $nclocLangue <= 0) {
                $parLangue[$suffixe] = ['anomalies' => null, 'dette_minute' => null, 'rating' => null];
                continue;
            }

            $issuesResult = self::makeRequest('/api/issues/search', [
                $componentKeyParam => $maven_key,
                $issueStatus['param'] => $issueStatus['value'],
                'tags' => self::$accessibilityTag,
                'languages' => $codeLangue,
                'ps' => 1,
            ]);
            if (isset($issuesResult['code'])) {
                $this->logger->error("[Batch Accessibilité] ❌ Erreur lors de la récupération des anomalies ({$codeLangue})", $issuesResult);
                return [
                    'code' => $issuesResult['code'],
                    'erreur' => $issuesResult['erreur'] ?? self::$erreurSonarInconnue
                ];
            }

            $anomalies = (int) ($issuesResult['total'] ?? 0);
            $detteMinute = (int) ($issuesResult['effortTotal'] ?? 0);

            $parLangue[$suffixe] = [
                'anomalies' => $anomalies,
                'dette_minute' => $detteMinute,
                'rating' => $this->getAccessibilityRating($detteMinute, $nclocLangue),
            ];

            $accessibilityAnomaliesTotal += $anomalies;
            $accessibilityDetteMinuteTotal += $detteMinute;
        }

        $accessibilityRating = $this->getAccessibilityRating($accessibilityDetteMinuteTotal, $ncloc);

        /** Suppression de l'ancien enregistrement puis insertion du nouveau (snapshot courant) */
        $map = ['maven_key' => $maven_key];
        $delete = $accessibilityRepos->deleteAccessibilityMavenKey($map);
        if ($delete['code'] !== 200) {
            $this->logger->error('[Batch Accessibilité] ❌ Échec de la requête deleteAccessibilityMavenKey', [
                'maven_key' => $maven_key,
                'erreur' => $delete['erreur']
            ]);
            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        $historique = [
            'accessibility_anomalies' => $accessibilityAnomaliesTotal,
            'accessibility_dette_minute' => $accessibilityDetteMinuteTotal,
            'accessibility_rating' => $accessibilityRating,
        ];
        foreach (self::$langues as $suffixe) {
            $donnees = $parLangue[$suffixe] ?? null;
            $historique["accessibility_{$suffixe}_anomalies"] = $donnees['anomalies'] ?? null;
            $historique["accessibility_{$suffixe}_dette_minute"] = $donnees['dette_minute'] ?? null;
            $historique["accessibility_{$suffixe}_rating"] = $donnees['rating'] ?? null;
        }

        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));
        $insert = $accessibilityRepos->insertAccessibility(array_merge($historique, [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur_collecte' => $utilisateur_collecte,
            'date_enregistrement' => $date,
        ]));
        if ($insert['code'] !== 200) {
            $this->logger->error('[Batch Accessibilité] ❌ Échec de la requête insertAccessibility', [
                'maven_key' => $maven_key,
                'erreur' => $insert['erreur']
            ]);
            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        $dette = $this->serviceDateTools->minutesTo($accessibilityDetteMinuteTotal);
        $this->logger->info('[Batch Accessibilité] ✅ Collecte terminée.', [
            'maven_key' => $maven_key,
            'accessibility_anomalies' => $accessibilityAnomaliesTotal,
            'accessibility_dette_minute' => $accessibilityDetteMinuteTotal,
            'accessibility_rating' => $accessibilityRating,
            'dette' => $dette,
        ]);

        return [
            'code' => 200,
            'message' => "Accessibilité collectée : {$accessibilityAnomaliesTotal} anomalie(s), note {$accessibilityRating}, dette estimée {$dette}.",
            'historique' => $historique
        ];
    }
}
