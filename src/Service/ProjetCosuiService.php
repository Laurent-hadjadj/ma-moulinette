<?php

namespace App\Service;

use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Repartition;
use App\Entity\Historique;

/**
 * [Description ProjetCosuiService]
 */
class ProjetCosuiService
{
    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    private static $erreurInconnue = 'Erreur inconnue';
    private static $defaultVersion = '0.0.0';

    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly ParameterBagInterface $params,
        private EntityManagerInterface $em
    ) {
        $this->logoEntreprise = $this->params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $this->params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $this->params->get('marque.entreprise.long');
        $this->environnement = $this->params->get('environnement');
        $this->version = $this->params->get('version');
        $this->dateCopyright = date('Y');
    }

    /**
     * Génère le tableau final de rendu COSUI pour un projet donné.
     *
     * @param string   $maven_key        Clé du projet
     * @param callable $fetchNotes      Fonction callback pour récupérer les notes actuelles
     * @param callable $fetchReference  Fonction callback pour récupérer les notes de référence
     * @param callable $fetchSetup      Fonction callback pour récupérer le setup
     * @param callable $traitement      Fonction callback pour récupérer les répartitions
     *
     * @return array
     */
    public function generateRender(string $maven_key): array
    {
        $this->logger->info("ℹ️ [COSUI] Démarrage génération du rendu pour {$maven_key}");

        $render = array_merge(
            self::genericRender(),
            self::initializeNotesRender(),
            self::initializeReferenceRender(),
            self::initializeEvolutionRender(),
            self::initializeChartRender()
        );

        $this->logger->debug("🛠️ [COSUI] Structure initiale du render générée.");

        // 1. Données projet actuel
        $n = $this->notes($maven_key);
        if ($n['code'] !== 200) {
            $message = '❌ Problème récupération des données projet actuel (notes)';
            $messageLog = '❌ [COSUI] Problème récupération des données projet actuel (notes)';
            $this->logger->error($messageLog);
            return [
                'code' => $n['code'],
                'type' => 'error',
                'message' => $message,
                'trace' => $n['erreur'] ?? static::$erreurInconnue
            ];
        }

        if ($n['code'] == 200 && $n['result'] === false) {
            $message = '⚠️ Aucune données présentent dans la table historique pour ce projet (Erreur 404).';
            $messageLog = '⚠️ [COSUI] Aucune données présentent dans la table historique pour ce projet (Erreur 404).';
            $this->logger->warning($messageLog);
            return [
                'code' => 404,
                'type' => 'warning',
                'message' => $message,
                'trace' => null
            ];
        }

        $this->injectNotesActuelles($render, $n);

        // 2. Données de référence
        $nn = $this->reference($maven_key);
        if ($nn['code'] !== 200) {
            $message = "❌ Échec récupération des données de référence.";
            $messageLog = "❌ [COSUI] Échec récupération des données de référence.";
            $this->logger->error($messageLog);
            return [
                'code' => $nn['code'],
                'type' => 'error',
                'message' => $message,
                'trace' => $nn['erreur'] ?? static::$erreurInconnue
            ];
        }

        if ($nn['code'] == 200 && $nn['result'] === false) {
            $message = '⚠️ Aucune données de références présentent dans la table historique pour ce projet (Erreur 404).';
            $messageLog = '⚠️ [COSUI] Aucune données de références présentent dans la table historique pour ce projet (Erreur 404).';
            $this->logger->warning($messageLog);
        } else {
            $this->injectNotesReference($render, $nn);
        }

        // 3. Setup
        try {
                $setup = $this->setup($maven_key);
                $render['setup'] = $setup[0] ?? 'NoN';
            } catch (\Throwable $e) {
                $render['setup'] = 'inconnu';
                $message = '🔴 Erreur lors de la récupération du setup';
                $messageLog = '🔴 [COSUI] Erreur lors de la récupération du setup';
                $this->logger->critical($messageLog, ['exception' => $e->getMessage()]);
                return [
                        'code' => 500,
                        'type' => 'critical',
                        'message' => $message,
                        'trace' => null
                ];
            }

        // 4. Traitement des anomalies
        try {
            foreach (['frontend', 'backend'] as $module) {
                foreach (['bug', 'vulnerability', 'code_smell'] as $prefix) {
                    foreach (['Blocker', 'Critical', 'Major'] as $severity) {
                        $result = $this->traitement($maven_key, $render['setup'], $module, $prefix, $severity);
                        $bloc = ($module == 'frontend') ? 'nombre_presentation' : 'nombre_metier';
                        $value = $module.ucfirst($prefix).$severity;
                        $map = [
                                'module' => $bloc,
                                'prefix' => $prefix,
                                'severity' => strtolower($severity),
                                'data' => $result[0][$value] ?? 0
                            ];
                        $this->injectRepartition($render, $map);
                    }
                }
            }

        } catch (\Throwable $e) {
        $message = '🔴 Erreur durant le traitement des anomalies.';
        $messageLog = '🔴 [COSUI] Erreur durant le traitement des anomalies.';
        $this->logger->critical($messageLog, ['exception' => $e->getMessage()]);
        return [
            'code' => 500,
            'type' => 'critical',
            'message' => $message,
            'trace' => null
        ];
    }

        $this->calculerEvolutions($render);
        $this->construireRadarChart($render);

        $this->logger->info("ℹ️ [COSUI] Rendu généré avec succès pour {$maven_key}");
        return $render;
    }

    /**
     * [Description for initialRender]
     *
     * @return array
     *
     * Created at: 29/07/2025 19:40:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function initialRender(): array
    {
        $this->logger->debug("🛠️ [COSUI] Initialisation du rendu par défaut.");
        return array_merge(
            self::genericRender(),
            self::initializeNotesRender(),
            self::initializeReferenceRender(),
            self::initializeEvolutionRender(),
            self::initializeChartRender(),
            self::chartDataRender()
        );
    }

    /**
     * [Description for genericRender]
     *
     * Retourne les paramètres génériques de rendu (logo, marque, version, etc.).
     *
     * @return array<string, mixed>
     *
     * Created at: 29/07/2025 19:39:10 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        $data = [
        'type_footer' => null,
        'logo_entreprise' => $this->logoEntreprise,
        'marque_entreprise_short' => $this->marqueEntrepriseShort,
        'marque_entreprise_long' => $this->marqueEntrepriseLong,
        'env' => $this->environnement,
        'version' => $this->version,
        'date_copyright' => $this->dateCopyright,
        ];

        $this->logger->debug('🛠️ [COSUI] Préparation des paramètres de rendu générique', $data);

        if (empty($this->logoEntreprise)) {
            $this->logger->warning('⚠️ [COSUI] Logo entreprise non défini dans genericRender()');
        }

        if (empty($this->marqueEntrepriseShort) || empty($this->marqueEntrepriseLong)) {
            $this->logger->warning('⚠️ [COSUI] Nom de marque incomplet dans genericRender()');
        }

        return $data;
    }

    /**
     * [Description for chartDataRender]
     *
     * @return array
     *
     * Created at: 30/07/2025 15:12:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function chartDataRender(): array
    {
        return [
            'dataset1' => '',
            'dataset2' => '',
            'label1' => '',
            'label2' => '',
        ];
    }

    /**
     * [Description for initializeNotesRender]
     *
     * Initialise le tableau de rendu des notes avec des valeurs par défaut.
     *
     * @return array<string, mixed>
     *
     * Created at: 29/07/2025 20:09:30 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function initializeNotesRender(): array
    {
        $defaultString = 'NaN';
        $defaultDate = '01/01/1980';
        $defaultNote = 'F';
        $defaultCount = 0;
        $defaultMetier = '--';

        $render = [
            'monApplication' => $defaultString,
            'version_application' => $defaultString,
            'type_application' => $defaultString,
            'date_application' => $defaultDate,
            'setup' => $defaultString,

            'bug_blocker' => $defaultCount,
            'bug_critical' => $defaultCount,
            'bug_major' => $defaultCount,
            'vulnerability_blocker' => $defaultCount,
            'vulnerability_critical' => $defaultCount,
            'vulnerability_major' => $defaultCount,
            'code_smell_blocker' => $defaultCount,
            'code_smell_critical' => $defaultCount,
            'code_smell_major' => $defaultCount,

            'note_code_smell' => $defaultNote,
            'note_reliability' => $defaultNote,
            'note_security' => $defaultNote,
            'note_hotspot' => $defaultNote,

            'hotspot' => $defaultCount,
            'coverage' => $defaultCount,
            'sqale_debt_ratio' => $defaultCount,
        ];

        $types = ['reliability', 'vulnerability', 'code_smell'];
        $severities = ['blocker', 'critical', 'major'];

        foreach ($types as $type) {
            foreach ($severities as $severity) {
                $render["{$type}_{$severity}"] = $defaultCount;
                $render["nombre_presentation_{$type}_{$severity}"] = $defaultMetier;
                $render["nombre_metier_{$type}_{$severity}"] = $defaultMetier;
            }
        }

        return $render;
    }

    /**
     * [Description for initializeReferenceRender]
     *
     * Initialise les données de référence avec des valeurs par défaut.
     *
     * @return array<string, mixed>
     *
     * Created at: 29/07/2025 20:10:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function initializeReferenceRender(): array
    {
        $defaultString = static::$defaultVersion;
        $defaultDate = '01/01/1980';
        $defaultNote = 'F';
        $defaultCount = 0;
        $defaultDebtRatio = 100;

        $render = [
            'initial_version_application' => $defaultString,
            'initial_date_application' => $defaultDate,
            'initial_note_code_smell' => $defaultNote,
            'initial_note_reliability' => $defaultNote,
            'initial_note_security' => $defaultNote,
            'initial_note_hotspot' => $defaultNote,
            'initial_nombre_hotspot' => $defaultCount,
            'initial_coverage' => $defaultCount,
            'initial_sqale_debt_ratio' => $defaultDebtRatio,
        ];

        $types = ['bug', 'vulnerability', 'code_smell'];
        $severities = ['blocker', 'critical', 'major'];

        // Partie initiale
        foreach ($types as $type) {
            foreach ($severities as $severity) {
                $render["initial_{$type}_{$severity}"] = $defaultCount;
            }
        }

        // Partie modale
        foreach ($types as $type) {
            foreach ($severities as $severity) {
                $render["modal_initial_{$type}_{$severity}"] = $defaultCount;
            }
        }

        $render['modal_initial_hotspot'] = $defaultCount;

        return $render;
    }

    /**
     * [Description for initializeEvolutionRender]
     *
     * Initialise les valeurs d’évolution entre deux versions (référence vs actuelle).
     *
     * @return array<string, int>
     *
     * Created at: 29/07/2025 20:12:18 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function initializeEvolutionRender(): array
    {
        $defaultValue = 0;
        $render = [];

        $types = ['hotspot', 'code_smell', 'vulnerability', 'bug'];
        $severities = ['blocker', 'critical', 'major'];

        // Cas particulier : hotspot est seul, pas de sévérité
        $render['evolution_hotspot'] = $defaultValue;

        // Pour les autres types avec sévérité
        foreach ($types as $type) {
            foreach ($severities as $severity) {
                $render["evolution_{$type}_{$severity}"] = $defaultValue;
            }
        }

        return $render;
    }

    /**
     * [Description for initializeChartRender]
     *
     * Initialise les données de rendu d’un graphique avec deux jeux de données vides.
     *
     * @return array<string, string>
     *
     * Created at: 29/07/2025 20:14:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public static function initializeChartRender(): array
    {
        $defaultDataset = '0,0,0,0,0,0';
        $defaultLabel = 'NaN';

        return [
            'dataset1' => $defaultDataset,
            'dataset2' => $defaultDataset,
            'label1' => $defaultLabel,
            'label2' => $defaultLabel,
        ];
    }

    /**
     * [Description for injectNotesActuelles]
     *
     * @param array $render
     * @param array $n
     *
     * @return void
     *
     * Created at: 30/07/2025 15:14:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function injectNotesActuelles(array &$render, array $n): void
    {
        $this->logger->debug("🛠️ [COSUI] Injection des données actuelles.");
        $render = array_merge($render, [
            'monApplication' => $n['name'],
            'version_application' => $n['version'],
            'type_application' => $n['type'],
            'date_application' => $n['date_version'],
            'note_code_smell' => $n['note_code_smell'],
            'note_reliability' => $n['note_reliability'],
            'note_security' => $n['note_security'],
            'note_hotspot' => $n['note_hotspot'],
            'bug_blocker' => $n['bug_blocker'],
            'bug_critical' => $n['bug_critical'],
            'bug_major' => $n['bug_major'],
            'vulnerability_blocker' => $n['vulnerability_blocker'],
            'vulnerability_critical' => $n['vulnerability_critical'],
            'vulnerability_major' => $n['vulnerability_major'],
            'code_smell_blocker' => $n['code_smell_blocker'],
            'code_smell_critical' => $n['code_smell_critical'],
            'code_smell_major' => $n['code_smell_major'],
            'hotspot' => $n['nombre_hotspot'],
            'coverage' => $n['coverage'],
            'sqale_debt_ratio' => $n['sqale_debt_ratio'],
        ]);
    }

    /**
     * [Description for injectNotesReference]
     *
     * @param array $render
     * @param array $nn
     *
     * @return void
     *
     * Created at: 30/07/2025 15:14:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function injectNotesReference(array &$render, array $nn): void
    {
        $this->logger->debug("🛠️ [COSUI] Injection des données de référence.");
        foreach ($nn as $key => $value) {
            $render[$key] = $value;
            if (str_starts_with($key, 'initial_')) {
                $render['modal_' . $key] = $value;
            }
        }
    }

    /**
     * [Description for injectRepartition]
     *
     * @param array $render
     * @param array $data
     * @param string $prefix
     * @param string $severity
     *
     * @return void
     *
     * Created at: 30/07/2025 15:14:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function injectRepartition(array &$render, array $map): void
    {
        $render["{$map['module']}_{$map['prefix']}_{$map['severity']}"] = $map['data'] ?? '--';
        //if ($map['prefix'] == 'bug' && $map['severity'] == 'major') { dd($render); }
    }

    /**
     * [Description for calculerEvolutions]
     *
     * @param array $render
     *
     * @return void
     *
     * Created at: 30/07/2025 15:11:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function calculerEvolutions(array &$render): void
    {
        $render['evolution_hotspot'] = self::variation($render['initial_nombre_hotspot'], $render['hotspot']);
        foreach (['code_smell', 'vulnerability', 'bug'] as $type) {
            foreach (['blocker', 'critical', 'major'] as $severity) {
                $render["evolution_{$type}_{$severity}"] = self::variation(
                    $render["initial_{$type}_{$severity}"],
                    $render["{$type}_{$severity}"]
                );
            }
        }
    }

    /**
     * [Description for construireRadarChart]
     *
     * @param array $render
     *
     * @return void
     *
     * Created at: 30/07/2025 15:11:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function construireRadarChart(array &$render): void
    {
        $d1 = self::note2point($render['initial_note_reliability']);
        $d2 = self::note2point($render['initial_note_security']);
        $d3 = self::note2point($render['initial_note_hotspot']);
        $d4 = self::note2point($render['initial_note_code_smell']);
        $d5 = $render['initial_coverage'];
        $d6 = max(0, min(100, 100 - $render['initial_sqale_debt_ratio']));

        $c1 = self::note2point($render['note_reliability']);
        $c2 = self::note2point($render['note_security']);
        $c3 = self::note2point($render['note_hotspot']);
        $c4 = self::note2point($render['note_code_smell']);
        $c5 = $render['coverage'];
        $c6 = max(0, min(100, 100 - $render['sqale_debt_ratio']));

        $render['dataset1'] = "{$d1},{$d2},{$d3},{$d4},{$d5},{$d6}";
        $render['dataset2'] = "{$c1},{$c2},{$c3},{$c4},{$c5},{$c6}";
        $render['label1'] = $render['initial_version_application'];
        $render['label2'] = $render['version_application'];
    }

    /**
     * [Description for setup]
     *
     * Récupère la version du dernier setup pour un projet donné via son mavenKey.
     *
     * @param string $maven_key
     * @return string
     *
     * Created at: 29/07/2025 19:55:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function setup(string $maven_key): array
    {
        $this->logger->debug("🛠️ [COSUI] Recherche du dernier setup pour le projet", ['maven_key' => $maven_key]);
        $repartitionRepos = $this->em->getRepository(Repartition::class);
        $getSetup = $repartitionRepos->findLatestSetupByMavenKey($maven_key);

        if ($getSetup['code'] != 200) {
            $message = "❌ Échec de récupération du dernier setup pour le projet.";
            $messageLog = "❌ [COSUI] Échec de récupération du dernier setup pour le projet.";
            $this->logger->error($messageLog, $getSetup['erreur']);
            return [
                'code' => $getSetup['code'],
                'type' => 'alert',
                'message' => $message,
                'trace' => $getSetup['erreur'] ?? static::$erreurInconnue
            ];
        }

        if ($getSetup['result'] === 'NaN') {
            $this->logger->warning("⚠️ [COSUI] Aucun setup trouvé pour ce maven_key", ['mavenKey' => $maven_key]);
            return ['NaN'];
        }

        $setup = $getSetup['result'];

        if ($setup === null) {
            $this->logger->error("❌ [COSUI] Setup null dans l'entité Repartition pour ce maven_key", ['maven_key' => $maven_key]);
            return ['NaN'];
        }

        $this->logger->info("ℹ️ [COSUI] Dernier setup récupéré avec succès",
            ['mavenKey' => $maven_key, 'setup' => $setup]);
        return [$setup];
    }

    /**
     * [Description for note2point]
     *
     * Convertit une note lettre (A à F) en points sur 100.
     *
     * @param string $note
     * @return int
     *
     * Created at: 29/07/2025 19:41:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function note2point(string $note): int
    {
        $map = [
            'A' => 100,
            'B' => 80,
            'C' => 60,
            'D' => 30,
            'E' => 10,
            'F' => 0,
        ];

        $note = strtoupper(trim($note));
        $point = $map[$note] ?? 0;

        if (!array_key_exists($note, $map)) {
            $this->logger->warning("⚠️ [COSUI] Note inconnue reçue dans note2point()", ['note' => $note]);
        } else {
            $this->logger->debug("🛠️ [COSUI] Conversion de note : $note → $point points");
        }

        return $point;
    }

    /**
     * Détermine la tendance d'évolution d'une métrique (ex : anomalies) entre deux versions.
     * Une baisse est positive → "up", une hausse est négative → "down".
     *
     * @param int|float $ancienneValeur
     * @param int|float $nouvelleValeur
     * @return string 'up', 'down' ou 'equal'
     */
    private function variation(float|int $ancienneValeur, float|int $nouvelleValeur): string
    {
        if ($ancienneValeur === 0) {
            $this->logger->warning("⚠️ [COSUI] Division par zéro évitée dans variation()", [
                'ancienne' => $ancienneValeur,
                'nouvelle' => $nouvelleValeur,
            ]);
            return 'equal';
        }

        $result = (($nouvelleValeur - $ancienneValeur) / $ancienneValeur) * 100;

        $this->logger->debug("🛠️ [COSUI] Variation calculée", [
            'ancienne' => $ancienneValeur,
            'nouvelle' => $nouvelleValeur,
            'variation_pct' => $result,
        ]);

        if ($result < 0) {
            return 'up'; // Moins d'anomalies → tendance positive
        } elseif ($result > 0) {
            return 'down'; // Plus d'anomalies → tendance négative
        }

        return 'equal';
    }

    /**
     * [Description for notes]
     *
     * Récupère les indicateurs qualité du dernier historique pour un projet donné.
     *
     * @param string $maven_key
     * @return array<string, mixed>
     *
     * Created at: 29/07/2025 20:00:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function notes(string $maven_key): array
    {
        $this->logger->debug("🛠️ [COSUI] Recherche des notes pour le projet", ['maven_key' => $maven_key]);

        $historiqueRepos = $this->em->getRepository(Historique::class);
        $request = $historiqueRepos->selectHistoriqueProjetLast(['maven_key' => $maven_key]);

        if ($request['code'] !== 200) {
            $message = "❌ Erreur lors de la récupération des notes.";
            $messageLog = "❌ [COSUI] Erreur lors de la récupération des notes.";
            $this->logger->error($messageLog, [
                'maven_key' => $maven_key,
                'code' => $request['code'],
                'erreur' => $request['erreur'] ?? static::$erreurInconnue
            ]);
            return [
                'code' => $request['code'],
                'message' => $message,
                'trace' => $request['erreur'] ?? static::$erreurInconnue
            ];
        }

        if (!is_array($request['infos']) || count($request['infos']) === 0) {
            $this->logger->warning("⚠️ [COSUI] Aucune information trouvée pour le projet", ['maven_key' => $maven_key]);
            return ['code' => 200, 'result' => false];
        }

        $info = $request['infos'][0];

        // Vérification du format de version
        $versionSplit = explode('-', $info['version']);
        $version = $versionSplit[0] ?? static::$defaultVersion;
        $type = $versionSplit[1] ?? 'undefined';

        $this->logger->info("ℹ️ [COSUI] Indicateurs qualité récupérés avec succès", [
            'mavenKey' => $maven_key,
            'version' => $version,
            'type' => $type
        ]);

        return [
            'code' => 200,
            'result' => true,
            'name' => $info['name'] ?? '',
            'version' => $version,
            'type' => $type,
            'date_version' => $info['date_version'] ?? '',
            'note_reliability' => $info['note_reliability'] ?? null,
            'note_security' => $info['note_security'] ?? null,
            'note_hotspot' => $info['note_hotspot'] ?? null,
            'note_code_smell' => $info['note_sqale'] ?? null,
            'bug_blocker' => $info['bug_blocker'] ?? 0,
            'bug_critical' => $info['bug_critical'] ?? 0,
            'bug_major' => $info['bug_major'] ?? 0,
            'vulnerability_blocker' => $info['vulnerability_blocker'] ?? 0,
            'vulnerability_critical' => $info['vulnerability_critical'] ?? 0,
            'vulnerability_major' => $info['vulnerability_major'] ?? 0,
            'code_smell_blocker' => $info['code_smell_blocker'] ?? 0,
            'code_smell_critical' => $info['code_smell_critical'] ?? 0,
            'code_smell_major' => $info['code_smell_major'] ?? 0,
            'nombre_hotspot' => $info['nombre_hotspot'] ?? 0,
            'coverage' => $info['coverage'] ?? 0,
            'sqale_debt_ratio' => $info['sqale_debt_ratio'] ?? 0
        ];
    }

    /**
     * [Description for reference]
     *
     * Récupère les indicateurs du projet de référence à partir de l’historique.
     *
     * @param string $maven_key
     * @return array<string, mixed>
     *
     * Created at: 29/07/2025 20:02:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function reference(string $maven_key): array
    {
        $this->logger->debug("🛠️ [COSUI] Recherche du projet de référence", ['maven_key' => $maven_key]);

        $historiqueRepos = $this->em->getRepository(Historique::class);
        $request = $historiqueRepos->selectHistoriqueProjetReference(['maven_key' => $maven_key]);

        if ($request['code'] !== 200) {
            $this->logger->error("❌ [COSUI] Erreur lors de la récupération du projet de référence", [
                'mavenKey' => $maven_key,
                'code' => $request['code'],
                'erreur' => $request['erreur'] ?? static::$erreurInconnue
            ]);
            return [
                'maven_key' => $maven_key,
                'code' => $request['code'],
                'trace' => $request['erreur'] ?? static::$erreurInconnue
            ];
        }

        if (empty($request['reference']) || !isset($request['reference'][0])) {
            $this->logger->warning("⚠️ [COSUI] Aucune donnée de référence trouvée", ['mavenKey' => $maven_key]);
            return ['code' => 200, 'result' => false];
        }

        $ref = $request['reference'][0];
        $versionSplit = explode('-', $ref['version'] ?? '');
        $version = $versionSplit[0] ?? static::$defaultVersion;
        $type = $versionSplit[1] ?? 'undefined';

        $this->logger->info("ℹ️ [COSUI] Données de référence récupérées", ['mavenKey' => $maven_key, 'version' => $version, 'type' => $type]);

        return [
            'code' => 200,
            'result' => true,
            'initial_version_application' => $version,
            'initial_date_application' => $ref['date_version'] ?? '',
            'initial_note_reliability' => $ref['note_reliability'] ?? null,
            'initial_note_security' => $ref['note_security'] ?? null,
            'initial_note_hotspot' => $ref['note_hotspot'] ?? null,
            'initial_note_code_smell' => $ref['note_sqale'] ?? null,
            'initial_bug_blocker' => $ref['bug_blocker'] ?? 0,
            'initial_bug_critical' => $ref['bug_critical'] ?? 0,
            'initial_bug_major' => $ref['bug_major'] ?? 0,
            'initial_vulnerability_blocker' => $ref['vulnerability_blocker'] ?? -1,
            'initial_vulnerability_critical' => $ref['vulnerability_critical'] ?? -1,
            'initial_vulnerability_major' => $ref['vulnerability_major'] ?? -1,
            'initial_code_smell_blocker' => $ref['code_smell_blocker'] ?? -1,
            'initial_code_smell_critical' => $ref['code_smell_critical'] ?? -1,
            'initial_code_smell_major' => $ref['code_smell_major'] ?? -1,
            'initial_nombre_hotspot' => $ref['nombre_hotspot'] ?? 0,
            'initial_coverage' => $ref['coverage'] ?? 0,
            'initial_sqale_debt_ratio' => $ref['sqale_debt_ratio'] ?? 0
        ];
    }

    /**
     * [Description for traitement]
     *
     * @param string $maven_key
     * @param string $setup
     * @param string $type
     * @param string $severity
     *
     * @return array
     *
     * Created at: 15/12/2022, 22:17:46 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function traitement(string $maven_key, string $setup, string $module, string $prefix, string $severity): array
    {
        if ($setup === 'NaN'){ return []; }
        /**
         * On récupère la répartition frontend, backend, autre et inconnu
         *  des BUG, VULNERABILITY, CODE_SMELL
         *  pour la severity : BLOCKER, CRITICAL, MAJOR,..
         */
        try {
                $prefix = ($prefix == 'code_smell') ? 'CodeSmell' : ucfirst($prefix);
                $severity = ucfirst($severity);
                $columnName = "{$module}{$prefix}{$severity}";

                $qb = $this->em->createQueryBuilder()
                        ->select("r.$columnName")
                        ->from(Repartition::class, 'r')
                        ->where('r.mavenKey = :mavenKey')
                        ->andWhere('r.setup = :setup')
                        ->setParameter('mavenKey', $maven_key)
                        ->setParameter('setup', $setup);

                return $qb->getQuery()->getArrayResult();
        } catch(\Throwable $e) {
            $this->logger->critical('Erreur dans le createQueryBuilder : ' . $e->getMessage());
        }
        return [];
    }

}
