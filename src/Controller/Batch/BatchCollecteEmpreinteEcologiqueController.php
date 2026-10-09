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
use App\Entity\EcoConception;
use App\Service\{ClientService, DateTools, LanguageDistributionService, UrlBuilderService};

/**
 * [Description BatchCollecteEmpreinteEcologiqueController]
 *
 * MODIF 2026-10-08 : Empreinte Écologique — plugins SonarQube Creedengo
 * (creedengo-java/javascript/php/python, détection d'anti-patterns
 * éco-conception), activable via la propriété applicative CREEDENGO_ENABLED
 * (même principe que l'extension track-logger-method, cf.
 * BatchCollecteLoggerController). Pas tous les serveurs SonarQube de
 * l'écosystème n'ont ces plugins installés.
 *
 * `/api/issues/search` n'accepte pas de filtre `repositories` (ignoré
 * silencieusement) : la liste exacte des clés de règles creedengo-* est
 * récupérée via `/api/rules/search` (champ `lang` de la réponse, qui donne
 * directement le regroupement par langage SonarQube : java/js/php/py) puis
 * passée en paramètre `rules=`, un appel par langage — pas de clé codée en
 * dur en PHP (évite la dérive si Creedengo ajoute des règles). La facette
 * `rules` de `/api/issues/search` est plafonnée (top 100 valeurs) et
 * sous-compte sur un projet avec beaucoup d'anomalies : ne jamais l'utiliser
 * pour ce total, uniquement la liste de règles explicite .
 *
 * Note A-E par langage et globale, sur le modèle SQALE
 * déjà utilisé par le RCI (BuildMeasuresRciService::getRCIRating(), seuils
 * 5/10/20/50%) : ratio = dette (minutes) / (ncloc * coût de développement
 * d'une ligne, 30 min par défaut SonarQube). Nécessite le ncloc du projet,
 * global et par langage (`ncloc_language_distribution`), récupéré via
 * `/api/measures/component` et parsé par LanguageDistributionService
 * (déjà utilisé pour la distribution langage de la page Suivi).
 */
class BatchCollecteEmpreinteEcologiqueController extends AbstractController
{
    /** Définition des constantes */
    private static string $sonarUrl = "sonar.url";
    private static string $creedengoRepositories = "creedengo-java,creedengo-javascript,creedengo-php,creedengo-python";
    private static string $europeParis = "Europe/Paris";
    /** Coût de développement d'une ligne de code (minutes), convention SonarQube par défaut. */
    private static int $costPerLine = 30;
    private static string $erreurSonarInconnue = "Erreur Sonar inconnue.";

    /**
     * Langages Creedengo suivis : code SonarQube => suffixe de colonne/clé.
     *
     * @var array<string, string>
     */
    private static array $langues = [
        'java' => 'java',
        'js' => 'javascript',
        'php' => 'php',
        'py' => 'python',
    ];

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 08/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private DateTools $serviceDateTools,
        private LanguageDistributionService $languageDistribution,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for makeRequest]
     * Fonction générique pour exécuter une requête SonarQube et retourner le JSON décodé.
     *
     * @param array<int|string, mixed> $queryParams
     *
     * @return array<int|string, mixed>
     *
     * Created at: 08/10/2026 (Europe/Paris)
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

        $this->logger->debug('[Batch Empreinte Écologique] 🛠️ Appel API SonarQube', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Batch Empreinte Écologique] ❌ Erreur API SonarQube', [
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
     * [Description for getEcoRating]
     * Note A-E dérivée du ratio dette/ncloc, mêmes seuils SQALE que le RCI
     * (BuildMeasuresRciService::getRCIRating()) : 5/10/20/50%.
     *
     * @param int|null $detteMinute
     * @param int|null $ncloc
     *
     * @return string|null null si ncloc est nul/absent (ratio non calculable)
     *
     * Created at: 08/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getEcoRating(?int $detteMinute, ?int $ncloc): ?string
    {
        if ($ncloc === null || $ncloc <= 0) {
            return null;
        }

        $ratio = (($detteMinute ?? 0) / ($ncloc * self::$costPerLine)) * 100;

        if ($ratio <= 5) { return 'A'; }
        if ($ratio <= 10) { return 'B'; }
        if ($ratio <= 20) { return 'C'; }
        if ($ratio <= 50) { return 'D'; }
        return 'E';
    }

    /**
     * [Description for BatchCollecteEmpreinteEcologique]
     * Calcule l'Empreinte Écologique (nombre d'anomalies, dette estimée et
     * note A-E, globale et par langage) à partir des plugins SonarQube
     * Creedengo. Désactivé par défaut (CREEDENGO_ENABLED=false) : renvoie
     * alors des valeurs `null`, pas `0` — un `0` laisserait croire à
     * « zéro anomalie mesurée » (donnée fausse), `null` déclenche
     * l'affichage `-` déjà standard côté page Projet (convention « mesure
     * absente ≠ mesure à 0 »).
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array<int|string, mixed>
     *
     * Created at: 08/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteEmpreinteEcologique(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $ecoConceptionRepos = $this->em->getRepository(EcoConception::class);

        $this->logger->info('[Batch Empreinte Écologique] ℹ️ Début de la collecte.', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** Valeurs neutres (plugin désactivé, pas de règles, etc.) : null partout. */
        $valeursNulles = ['eco_anomalies' => null, 'eco_dette_minute' => null, 'eco_rating' => null];
        foreach (self::$langues as $suffixe) {
            $valeursNulles["eco_{$suffixe}_anomalies"] = null;
            $valeursNulles["eco_{$suffixe}_dette_minute"] = null;
            $valeursNulles["eco_{$suffixe}_rating"] = null;
        }

        /** On regarde si les plugins Creedengo sont activés */
        $creedengoEnabled = $this->getParameter('creedengo.enabled');
        if ((bool) $creedengoEnabled === false || $creedengoEnabled === 'false' || $creedengoEnabled === 'False') {
            $this->logger->info('[Batch Empreinte Écologique] ⚠️ Collecte non lancée : plugins désactivés (CREEDENGO_ENABLED=false)', [
                'maven_key' => $maven_key
            ]);

            return [
                'code' => 404,
                'message' => "La collecte de l'Empreinte Écologique n'a pas été lancée. (CREEDENGO_ENABLED=false).",
                'historique' => $valeursNulles
            ];
        }

        /** 1 - On récupère la liste exacte des clés de règles creedengo-*, groupées par langage (un seul appel, 4 repositories en CSV) */
        $rulesResult = self::makeRequest('/api/rules/search', [
            'repositories' => self::$creedengoRepositories,
            'ps' => 500,
            'f' => 'lang',
        ]);
        if (isset($rulesResult['code'])) {
            $this->logger->error('[Batch Empreinte Écologique] ❌ Erreur lors de la récupération des règles Creedengo', $rulesResult);
            return [
                'code' => $rulesResult['code'],
                'erreur' => $rulesResult['erreur'] ?? self::$erreurSonarInconnue
            ];
        }

        $rulesByLang = [];
        foreach ($rulesResult['rules'] ?? [] as $rule) {
            $lang = (string) ($rule['lang'] ?? '');
            if (!isset(self::$langues[$lang])) {
                continue; // langage hors périmètre (ex. creedengo-typescript, pas dans la liste jar de l'utilisateur)
            }
            $rulesByLang[$lang][] = (string) $rule['key'];
        }

        if ($rulesByLang === []) {
            $this->logger->warning('[Batch Empreinte Écologique] ⚠️ Aucune règle Creedengo trouvée sur le serveur SonarQube.', [
                'maven_key' => $maven_key
            ]);
            return [
                'code' => 200,
                'message' => "Aucune règle Creedengo disponible sur ce serveur SonarQube.",
                'historique' => $valeursNulles
            ];
        }

        /** 2 - On récupère le ncloc global et par langage (pour la note A-E) */
        $measuresResult = self::makeRequest('/api/measures/component', [
            'component' => $maven_key,
            'metricKeys' => 'ncloc,ncloc_language_distribution',
        ]);
        if (isset($measuresResult['code'])) {
            $this->logger->error('[Batch Empreinte Écologique] ❌ Erreur lors de la récupération du ncloc', $measuresResult);
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

        /** 3 - Un appel /api/issues/search par langage ayant des règles Creedengo (total + effortTotal exacts, pas de facette) */
        $parLangue = [];
        $ecoAnomaliesTotal = 0;
        $ecoDetteMinuteTotal = 0;

        foreach ($rulesByLang as $lang => $ruleKeys) {
            $suffixe = self::$langues[$lang];

            $issuesResult = self::makeRequest('/api/issues/search', [
                'componentKeys' => $maven_key,
                'statuses' => 'OPEN,REOPENED',
                'rules' => implode(',', $ruleKeys),
                'ps' => 1,
            ]);
            if (isset($issuesResult['code'])) {
                $this->logger->error("[Batch Empreinte Écologique] ❌ Erreur lors de la récupération des anomalies ({$lang})", $issuesResult);
                return [
                    'code' => $issuesResult['code'],
                    'erreur' => $issuesResult['erreur'] ?? self::$erreurSonarInconnue
                ];
            }

            $anomalies = (int) ($issuesResult['total'] ?? 0);
            $detteMinute = (int) ($issuesResult['effortTotal'] ?? 0);
            $nclocLangue = $nclocParLangue[$lang] ?? null;

            $parLangue[$suffixe] = [
                'anomalies' => $anomalies,
                'dette_minute' => $detteMinute,
                'rating' => $this->getEcoRating($detteMinute, $nclocLangue),
            ];

            $ecoAnomaliesTotal += $anomalies;
            $ecoDetteMinuteTotal += $detteMinute;
        }

        $ecoRating = $this->getEcoRating($ecoDetteMinuteTotal, $ncloc);

        /** Suppression de l'ancien enregistrement puis insertion du nouveau (snapshot courant) */
        $map = ['maven_key' => $maven_key];
        $delete = $ecoConceptionRepos->deleteEcoConceptionMavenKey($map);
        if ($delete['code'] !== 200) {
            $this->logger->error('[Batch Empreinte Écologique] ❌ Échec de la requête deleteEcoConceptionMavenKey', [
                'maven_key' => $maven_key,
                'erreur' => $delete['erreur']
            ]);
            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        $historique = [
            'eco_anomalies' => $ecoAnomaliesTotal,
            'eco_dette_minute' => $ecoDetteMinuteTotal,
            'eco_rating' => $ecoRating,
        ];
        foreach (self::$langues as $suffixe) {
            $donnees = $parLangue[$suffixe] ?? null;
            $historique["eco_{$suffixe}_anomalies"] = $donnees['anomalies'] ?? null;
            $historique["eco_{$suffixe}_dette_minute"] = $donnees['dette_minute'] ?? null;
            $historique["eco_{$suffixe}_rating"] = $donnees['rating'] ?? null;
        }

        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));
        $insert = $ecoConceptionRepos->insertEcoConception(array_merge($historique, [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur_collecte' => $utilisateur_collecte,
            'date_enregistrement' => $date,
        ]));
        if ($insert['code'] !== 200) {
            $this->logger->error('[Batch Empreinte Écologique] ❌ Échec de la requête insertEcoConception', [
                'maven_key' => $maven_key,
                'erreur' => $insert['erreur']
            ]);
            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        $dette = $this->serviceDateTools->minutesTo($ecoDetteMinuteTotal);
        $this->logger->info('[Batch Empreinte Écologique] ✅ Collecte terminée.', [
            'maven_key' => $maven_key,
            'eco_anomalies' => $ecoAnomaliesTotal,
            'eco_dette_minute' => $ecoDetteMinuteTotal,
            'eco_rating' => $ecoRating,
            'dette' => $dette,
        ]);

        return [
            'code' => 200,
            'message' => "Empreinte Écologique collectée : {$ecoAnomaliesTotal} anomalie(s), note {$ecoRating}, dette estimée {$dette}.",
            'historique' => $historique
        ];
    }
}
