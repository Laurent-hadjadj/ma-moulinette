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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Mesures;
use App\Service\Client;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteMesureController]
 */
class BatchCollecteMesureController extends AbstractController
{
    /** Définition des constantes */
    private static $sonarUrl = "sonar.url";

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
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for BatchCollecteMesure]
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array
     *
     * Created at: 21/05/2024 23:48:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteMesure(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $mesuresRepos = $this->em->getRepository(Mesures::class);
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');

        $this->logger->info('[Batch Mesure] ℹ️ Début de collecte', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        // [1] Récupération du projet
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/components/app',
            ['component' => $maven_key]
        );

        $this->logger->debug('[Batch Mesure] 🛠️ Appel API SonarQube', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Batch Mesure] ❌  Erreur SonarQube', [
                'url' => $url,
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
            ]);

            return [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
            ];
        }

        // [2] Suppression des anciennes mesures
        $delete = $mesuresRepos->deleteMesuresMavenKey([ 'maven_key' => $maven_key ]);
        if ($delete['code'] !== 200) {
            $this->logger->error('[Batch Mesure] ❌ Échec de suppression des mesures', [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ]);

            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        $this->logger->info('[Batch Mesure] ℹ️ Suppression des anciennes mesures OK', [
            'maven_key' => $maven_key
        ]);

        // [3] Collecte secondaire : lignes de code, fichiers...
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/measures/component', [
                'component' => $maven_key,
                'metricKeys' => 'ncloc,ncloc_language_distribution,classes,functions,files'
                ]
        );

        $this->logger->debug('[Batch Mesure] 🛠️ Appel à SonarQube /measures/component (bloc 1)', ['url' => $url]);
        $result2 = $this->client->httpSonarQube($url);

        $ncloc = $classes = $functions = $files = 0;
        $distribution = [];

        foreach ($result2['json']['component']['measures'] ?? [] as $mesure) {
            switch ($mesure['metric']) {
                case 'ncloc':
                    $ncloc = intval($mesure['value'] ?? 0);
                    break;
                case 'classes':
                    $classes = intval($mesure['value'] ?? 0);
                    break;
                case 'functions':
                    $functions = intval($mesure['value'] ?? 0);
                    break;
                case 'files':
                    $files = intval($mesure['value'] ?? 0);
                    break;
                case 'ncloc_language_distribution':
                    $asArr = explode(';', $mesure['value'] ?? '');
                    foreach ($asArr as $language) {
                        $tmp = explode('=', $language);
                        if (count($tmp) === 2) {
                            $distribution[$tmp[0]] = intval($tmp[1]);
                        }
                    }
                    arsort($distribution);
                    break;
                default :
                    $this->logger->error('[Batch Mesure] ❌ Erreur switch improbable. tu peux jouer au LoTo.');
                    break;
            }
        }

        $this->logger->debug('[Batch Mesure] 🛠️ Résumé des métriques secondaires extraites', [
            'ncloc' => $ncloc,
            'classes' => $classes,
            'functions' => $functions,
            'files' => $files,
            'distribution' => $distribution
        ]);

        // [4] Récupération du SQALE ratio
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/measures/component', [
                'component' => $maven_key,
                'metricKeys' => 'sqale_debt_ratio'
            ]
        );

        $this->logger->debug('[Batch Mesure] 🛠️ Appel à SonarQube /measures/component (SQALE)', ['url' => $url]);
        $result3 = $this->client->httpSonarQube($url);
        $sqaleRatio = floatval($result3['json']['component']['measures'][0]['value'] ?? -1);

        // [5] Création des données mesures
        $date = new \DateTimeImmutable('now', new \DateTimeZone("Europe/Paris"));
        $lines = intval($result['json']['measures']['lines'] ?? 0);
        $coverage = floatval($result['json']['measures']['coverage'] ?? 0);
        $duplicatedLinesDensity = floatval($result['json']['measures']['duplicationDensity'] ?? 0);
        $tests = intval($result['json']['measures']['tests'] ?? 0);
        $issues = intval($result['json']['measures']['issues'] ?? 0);

        $mesureData = [
            'maven_key' => $maven_key,
            'project_name' => $result['json']['projectName'] ?? 'inconnu',
            'lines' => $lines,
            'ncloc' => $ncloc,
            'classes' => $classes,
            'functions' => $functions,
            'files' => $files,
            'language_distribution' => $distribution,
            'sqale_debt_ratio' => $sqaleRatio,
            'coverage' => $coverage,
            'duplicated_lines_density' => $duplicatedLinesDensity,
            'tests' => $tests,
            'issues' => $issues,
            'mode_collecte' => $mode_collecte,
            'utilisateur_collecte' => $utilisateur_collecte,
            'date_enregistrement' => $date
        ];

        $insert = $mesuresRepos->insertMesures($mesureData);
        if ($insert['code'] !== 200) {
            $this->logger->error('[Batch Mesure] ❌ Échec de la requête insertMesures', [
                'maven_key' => $maven_key,
                'erreur' => $insert['erreur']
            ]);

            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        $this->logger->info('[Batch Mesure] ℹ️ Insertion mesures OK', [
            'maven_key' => $maven_key,
            'lines' => $lines,
            'coverage' => $coverage
        ]);

        return [
            'code' => 200,
            'message' => "La mise à jour des mesures pour le projet est terminées.",
            'data' => $mesureData,
            'historique' => [
                'nom_projet' => strtolower($result['json']['projectName'] ?? 'inconnu'),
                'nombre_ligne' => $lines,
                'nombre_ligne_code' => $ncloc,
                'nombre_classes' => $classes,
                'nombre_fonctions' => $functions,
                'nombre_files' => $files,
                'language_distribution' => $distribution,
                'coverage' => $coverage,
                'sqale_debt_ratio' => $sqaleRatio,
                'duplicated_lines_density' => $duplicatedLinesDensity,
                'tests' => $tests,
                'issues' => $issues
            ]
        ];
    }

}
