<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Batch;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Repartition;
use App\Entity\RepartitionTemp;

/** Client HTTP */
use App\Service\Client;
use Exception;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteNoSonarController]
 */
class BatchCollecteRepartitionController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    private static $reference = "<strong>[Répartition-Module]</strong> ";
    private static $beginRegEx = '/\b(?:';
    private static $endRegEx = ')\b/i';

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private Client $client,
        private UrlBuilderService $urlBuilder
    ) {
        $this->em = $em;
        $this->client = $client;
    }

        /**
     * [Description for batchAnalyseAnomalie]
     *
     * @param mixed $elements
     * @param mixed $mavenKey
     *
     * @return ['frontend'=>$frontend, 'backend'=>$backend, 'autre'=>$autre];
     *
     * Created at: 04/12/2022, 09:00:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     *
     */
    private function batchAnalyseAnomalie($elements, $mavenKey)
    {
        /** On calcule la répartition pour les application java et ?Php */
        $scoreFrontend = $scoreBackend = $scoreAutre = $scoreInconnue = 0;

        /** on récupère la liste des clés pour chaque module */
        $listeFrontend = $this->getParameter('module.frontend');
        $listeBackend = $this->getParameter('module.backend');
        $listeAutre = $this->getParameter('module.autre');

        $frontendKeywords = array_map('strtolower', array_map('trim', explode(',', $listeFrontend)));
        $backendKeywords = array_map('strtolower', array_map('trim', explode(',', $listeBackend)));
        $autreKeywords = array_map('strtolower', array_map('trim', explode(',', $listeAutre)));

        // On prépare les regex pour une vérification rapide
        // Utilisation de preg_quote pour échapper d'éventuels caractères spéciaux dans les mots-clés
        $regexFrontend = static::$beginRegEx . implode('|', array_map('preg_quote', $frontendKeywords)) . static::$endRegEx;
        $regexBackend  = static::$beginRegEx . implode('|', array_map('preg_quote', $backendKeywords)) . static::$endRegEx;
        $regexAutre  = static::$beginRegEx . implode('|', array_map('preg_quote', $autreKeywords)) . static::$endRegEx;

        try {
            foreach ($elements as $element) {
                $path = str_replace($mavenKey . ':', '', $element['component']);

                // On vérifie si le chemin correspond à un module frontend
                if (preg_match($regexFrontend, $path)) {
                    $scoreFrontend++;
                }
                // Sinon, on vérifie s'il correspond à un module backend
                elseif (preg_match($regexBackend, $path)) {
                    $scoreBackend++;
                } // Sinon, on vérifie s'il correspond à un module autre
                elseif (preg_match($regexAutre, $path)) {
                    $scoreAutre++;
                } // Si aucune des regex ne matche, c'est considéré comme "inconnue"
                else {
                    $scoreInconnue++;
                }
            }
        } catch (Exception $e) {
            return ['code' => 500,
                    'type' => 'alert',
                    'message' => 'Une erreur inétendue lors de la réparation par module (<strong>batchAnalyseAnomalie</strong>) est survenue (Erreur 500).', 'debug' => $e];
        }

        return [
                'code' => 200,
                'frontend' => $scoreFrontend,
                'backend' => $scoreBackend,
                'autre' => $scoreAutre,
                'inconnue' => $scoreInconnue
            ];
    }

    /**
     * [Description for batchCollecteAnomalie]
     * Fonction qui permet de parser les anomalies par category selon le nombre
     * de page disponible.
     * $pageSize = 1 à 500
     * $index = 1 à 20 max ==> 10000 anomalies
     * $category = BUG,VULNERABILITY,CODE_SMELL
     * $severity = INFO,MINOR,MAJOR,CRITICAL,BLOCKER
     * http://{url}/api/issues/search?componentKeys={key}&statuses=OPEN,CONFIRMED,REOPENED
     * &s=STATUS&asc=no&types={type}&severities={severite}=&ps={pageSize}&p=
     *
     * @param string $mavenKey
     * @param int $index
     * @param int $batchSize
     * @param string $category
     * @param string $severity
     *
     * @return array
     *
     * Created at: 04/12/2022, 09:02:29 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function batchCollecteAnomalie($mavenKey, $index, $batchSize, $category, $severity) :array
    {

         /** Sécurisation de l'URL */
        $maven_key = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/issues/search',
            [
                'componentKeys' => $maven_key,
                'statuses' => 'OPEN,CONFIRMED,REOPENED',
                's' => 'STATUS',
                'asc' => 'no',
                'types' => $category,
                'severities' => $severity,
                'p' => $index, 'ps' => $batchSize
            ]
        );

        $result = $this->client->httpSonarQube($url);

        /** On catch les erreurs HTTP :) */
        if (isset($result['code']) && in_array($result['code'], [401, 403, 404, 500, 503])) {
            return ['code' => $result['code'], 'erreur' => $result['erreur']];
        }

        /** On appel l'Api et on renvoie le résultat */
        return $result['json'];
    }

    /**
     * [Description for batchCollecteInformation]
     *
     * @param mixed $mavenKey
     * @param mixed $type
     *
     * @return array
     *
     * Created at: 11/02/2025 14:09:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function batchCollecteInformation($mavenKey, $category): array
    {
        /** Sécurisation de l'URL */
        $maven_key = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/issues/search',
            [
            'componentKeys' => $maven_key,
            'statuses' => 'OPEN,CONFIRMED,REOPENED',
            'types' => $category,
            'facets' => 'severities',
            'p' => 1, 'ps' => 1
            ]
        );

        $result = $this->client->httpSonarQube($url);
        /** On catch les erreurs HTTP :) */
        if (isset($result['code']) && in_array($result['code'], [401, 403, 404, 500, 503])) {
            return ['code' => $result['code'], 'erreur' => $result['erreur']];
        }

        /** On appel l'Api et on renvoie le résultat */
        return $result['json']['facets'][0];
    }

    /**
     * [Description for apiRepartitionModule]
     * Récupère le total des anomalies par sévérité.
     *
     * @param string $maven_key
     * @param string $type
     *
     * @return array
     * INFO,MINOR,MAJOR,CRITICAL,BLOCKER
     *
     * Created at: 04/12/2022, 09:03:46 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function collecteRepartitionModule(string $maven_key, string $category): array
    {
        /** On récupère le nombre d'anomalie pour la category */
        $total = 0;
        $result = $this->batchCollecteInformation($maven_key, $category);
        if (isset($result['values'])){
            foreach($result['values'] as $value){
                if ($value['val'] === 'INFO') {
                    $info = $value['count'] ?? 0;
                }
                if ($value['val'] === 'MINOR') {
                    $minor = $value['count'] ?? 0;
                }
                if ($value['val'] === 'MAJOR') {
                    $major = $value['count'] ?? 0;
                }
                if ($value['val'] === 'CRITICAL') {
                    $critical = $value['count'] ?? 0;
                }
                if ($value['val'] === 'BLOCKER') {
                    $blocker = $value['count'] ?? 0;
                }
            }
            $total = $info + $minor + $major + $critical + $blocker;
        }

        return [
                'code' => 200,
                'total' => $total ?? 0,
                'category' => $category,
                'blocker' => $blocker ?? 0,
                'critical' => $critical ?? 0,
                'major' => $major ?? 0,
                'minor' => $minor ?? 0,
                'info' => $info ?? 0
            ];
    }

    /**
     * [Description for flattenGroupData]
     *
     * @param array $groupData
     * @param array $fields
     * @param array $map
     *
     * @return [type]
     *
     * Created at: 17/02/2025 17:09:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function flattenGroupData(array $groupData, array $fields, array &$map){
        // Fonction d'aplatissement qui, pour une category donné, affecte chaque valeur aux clés correspondantes
        // Chaque category doit contenir 5 sous-tableaux de 4 valeurs = 20 valeurs
        if (count($groupData) === 5) {
            foreach ($groupData as $severityIndex => $values) {
                foreach ($values as $valueIndex => $value) {
                    // Calcul de l'index dans le tableau des champs
                    $fieldIndex = $severityIndex * 4 + $valueIndex;
                    // Affectation
                    $map[$fields[$fieldIndex]] = $value;
                }
            }
        } else {
            // Si les données ne sont pas complètes, on affecte -1 pour chacun des champs
            foreach ($fields as $field) {
                $map[$field] = -1;
            }
        }
    }

    /**
     * [Description for BatchCollecteRepartition]
     *
     * @param string $mavenKey
     * @param string $modeCollecte
     * @param string $utilisateurCollecte
     *
     * @return array
     *
     * Created at: 21/05/2024 22:25:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function batchCollecteRepartition(string $mavenKey, string $category, string $severity, string $setup): array
    {
        /** On instancie l'entityRepository */
        $repartitionTempRepository = $this->em->getRepository(RepartitionTemp::class);

        /** Initialisation des variables */
        $dateStart = time();
        /** Récupération de la première page */
        $result = $this->batchCollecteAnomalie($mavenKey, 1, 1, $category, $severity);
        $totalIssues = $result['total'] ?? 0;
        $batchSize = 500;
        $maxPages = 20;

        /** Si pas d'issues, on arrête */
        if (empty($result['issues'])) {
            return [
                    'code' => 200,
                    'total' => 0,
                    'category' => $category,
                    'severity' => $severity,
                    'setup' => $setup,
                    'temps' => 2
                    ];
        }

        // Avant d'insérer les nouveaux enregistrements :
        $map = ['maven_key' => $mavenKey, 'setup' => $setup];
        $delete = $repartitionTempRepository->deleteOldRecords($map);
        if ($delete['code'] !== 200) {
            return ['code' => $delete['code'], 'type' => 'alert',
                    'message' => static::$reference . $delete['erreur']];
        }

        $issues = [];
        for ($i = 1; $i <= $maxPages; $i++) {
            // Appel de l'API pour la page $i
            $result = $this->batchCollecteAnomalie($mavenKey, $i, $batchSize, $category, $severity);

            // S'il n'y a plus d'issues, on sort de la boucle
            if (empty($result['issues'])) {
                break;
            }

            foreach ($result['issues'] as $issueData) {
                $issues[] = [
                    'maven_key' => $mavenKey,
                    'component' => $issueData['component'],
                    'category' => $category,
                    'severity' => $issueData['severity'],
                    'setup' => $setup,
                ];
            }

            // Insérer les issues récupérées pour cette page
            $batchInsert = $repartitionTempRepository->batchInsertIssuesSQL($issues);
            if ($batchInsert['code'] !== 200) {
                return [
                    'code' => $batchInsert['code'], 'type' => 'alert',
                    'message' => static::$reference . $batchInsert['erreur']];
            }

            // Réinitialise le tableau pour le prochain batch.
            $issues = [];
        }

        /** Calcul du temps écoulé */
        $dateEnd = time();

        /** On prépare les données pour l'historique */
        $data = [
                    'total' => $totalIssues,
                    'category' => $category,
                    'severity' => $severity,
                    'setup' => $setup,
                    'temps' => abs($dateStart - $dateEnd) + 2
                ];
        $message =  "Collecte de " . $totalIssues . " pour la catégorie " . $category . " pour le setup : ". $setup;
        return ['code' => 200, 'message' => $message, 'data' => $data];
    }


    public function batchCollecteRepartitionAnalyse(string $mavenKey, string $category, string $severity, string $setup): array
    {
        /** On instancie l'entityRepository */
        $repartitionTempRepository = $this->em->getRepository(RepartitionTemp::class);
        $map = ['maven_key' => $mavenKey, 'category' => $category, 'severity' => $severity, 'setup' => $setup];
        $repartition = $repartitionTempRepository->selectRepartitionByTypeAndSeverity($map);
        if ($repartition['code'] != 200) {
            return [
                    'code' => $repartition['code'],
                    'erreur' => $repartition['erreur']
                    ];
        }

        /** on appelle le service d'analyse */
        $batchAnalyseAnomalie = $this->batchAnalyseAnomalie($repartition['liste'], $mavenKey);
        if ($batchAnalyseAnomalie['code'] != 200) {
            return [
                'code' => $batchAnalyseAnomalie['code'],
                'type' => 'alert',
                'message' => static::$reference . $batchAnalyseAnomalie['message']
            ];
        }
        $message = 'Répartition des anomalies par module terminée. ' . $setup;
        return ['code' => 200, 'message' => $message, 'data' => $batchAnalyseAnomalie];
    }

    /**
     * [Description for batchCollecteRepartitionMaJ]
     *
     * @param string $mavenKey
     * @param array $calcul
     * @param string $setup
     *
     * @return array
     *
     * Created at: 18/02/2025 09:11:57 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function batchCollecteRepartitionMaJ(string $mavenKey, array $calcul, string $setup): array
    {
        /** On instancie l'entityRepository */
        $repartitionRepository = $this->em->getRepository(Repartition::class);

        /** On détermine si le set est complet : 15 enregistrements : BUG, VULNERABILITY, CODE_SMELL  */
        $expectedCategories = ['BUG', 'VULNERABILITY', 'CODE_SMELL'];

        // Initialisation du tableau de regroupement
        $groupedData = [
            'BUG'            => [],
            'VULNERABILITY'  => [],
            'CODE_SMELL'     => [],
        ];

        // Parcourir le tableau et répartir les données selon leur catégorie
        foreach ($calcul as $subArray) {
            // On suppose que la catégorie se trouve à l'indice 0 de chaque sous-tableau
            $category = $subArray[0];

            // Si la category est reconnu, on ajoute le sous-tableau à la série correspondante
            if (in_array($category, $expectedCategories)) {
                // Supprimer les valeurs aux index 0 et 1
                unset($subArray[0], $subArray[1]);
                $groupedData[$category][] = array_values($subArray);
            }
        }

        // Variable pour suivre le statut "partiel" : nombre de jeux incomplets (0, 1 ou 2, voire 3)
        $missingSets = 0;

        // On parcourt chaque category pour vérifier la complétude de ses données
        foreach ($expectedCategories as $category) {
            if (count($groupedData[$category]) !== 5) {
                // On considère que cette category est incomplete
                $missingSets++;
                // On vide les données de cette category pour éviter de les enregistrer
                $groupedData[$category] = [];
            }
        }

        // On construit le statut à enregistrer dans l'attribut 'control'
        $controlStatut = [ 'complet (100%)', 'partiel (66%)', 'partiel (33%)' ];
        $control = $controlStatut[$missingSets];

        // Préparation des tableaux de champs pour chaque category
        $fieldsBug = [
        'frontend_bug_blocker', 'backend_bug_blocker', 'autre_bug_blocker', 'inconnue_bug_blocker',
        'frontend_bug_critical', 'backend_bug_critical', 'autre_bug_critical', 'inconnue_bug_critical',
        'frontend_bug_major', 'backend_bug_major', 'autre_bug_major', 'inconnue_bug_major',
        'frontend_bug_minor', 'backend_bug_minor', 'autre_bug_minor', 'inconnue_bug_minor',
        'frontend_bug_info', 'backend_bug_info', 'autre_bug_info', 'inconnue_bug_info',
        ];

        $fieldsVulnerability = [
            'frontend_vulnerability_blocker', 'backend_vulnerability_blocker', 'autre_vulnerability_blocker', 'inconnue_vulnerability_blocker',
            'frontend_vulnerability_critical', 'backend_vulnerability_critical', 'autre_vulnerability_critical', 'inconnue_vulnerability_critical',
            'frontend_vulnerability_major', 'backend_vulnerability_major', 'autre_vulnerability_major', 'inconnue_vulnerability_major',
            'frontend_vulnerability_minor', 'backend_vulnerability_minor', 'autre_vulnerability_minor', 'inconnue_vulnerability_minor',
            'frontend_vulnerability_info', 'backend_vulnerability_info', 'autre_vulnerability_info', 'inconnue_vulnerability_info',
        ];

        $fieldsCodeSmell = [
            'frontend_code_smell_blocker', 'backend_code_smell_blocker', 'autre_code_smell_blocker', 'inconnue_code_smell_blocker',
            'frontend_code_smell_critical', 'backend_code_smell_critical', 'autre_code_smell_critical', 'inconnue_code_smell_critical',
            'frontend_code_smell_major', 'backend_code_smell_major', 'autre_code_smell_major', 'inconnue_code_smell_major',
            'frontend_code_smell_minor', 'backend_code_smell_minor', 'autre_code_smell_minor', 'inconnue_code_smell_minor',
            'frontend_code_smell_info', 'backend_code_smell_info', 'autre_code_smell_info', 'inconnue_code_smell_info',
        ];

        // Initialisation du tableau $map qui contiendra toutes les clés attendues par la requête
        $map = [
                'maven_key' => $mavenKey,
                'setup' => $setup,
                'control' => $control,
                'date_enregistrement' => new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'))];

        // Aplatir les données pour chaque category
        $this->flattenGroupData($groupedData['BUG'], $fieldsBug, $map);
        $this->flattenGroupData($groupedData['VULNERABILITY'], $fieldsVulnerability, $map);
        $this->flattenGroupData($groupedData['CODE_SMELL'], $fieldsCodeSmell, $map);
        /** On met à jour la table */
        $repartition = $repartitionRepository->updateRepartition($map);
        if ($repartition['code'] != 200) {
            return [
                'code' => $repartition['code'],
                'type' => 'alert',
                'erreur' => $repartition['erreur']
            ];
        }
        $message = 'Mise à jour des données pour le setup ' . $setup;
        return ['code' => 200, 'message' => $message];
    }
}
