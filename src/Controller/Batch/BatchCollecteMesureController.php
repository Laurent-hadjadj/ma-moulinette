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
use App\Service\{ClientService, UrlBuilderService};
use App\Service\CommandRebuildHistorique\BuildMapHistoryService;
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
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger,
        private BuildMapHistoryService $apiBuildRequest
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

        // [1] Récupération du projet de base pour vérifier son existence
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/components/app',
            ['component' => $maven_key]
        );

        $this->logger->debug('[Batch Mesure] 🛠️ Appel API SonarQube', ['url' => $url]);
        $analysis = $this->client->httpSonarQube($url);

        if (isset($analysis['code']) && in_array($analysis['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Batch Mesure] ❌ Erreur SonarQube', [
                'url' => $url,
                'code' => $analysis['code'],
                'erreur' => $analysis['erreur'] ?? 'Erreur Sonar inconnue.'
            ]);

            return [
                'code' => $analysis['code'],
                'erreur' => $analysis['erreur'] ?? 'Erreur Sonar inconnue.'
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

        // [3] Collecte des mesures secondaires : lignes de code, fichiers, classes, fonctions, distribution par langage...
        // Par défaut, on suppose une version 8 si la variable d'environnement n'est pas définie
        $versionSonar = getenv('SONAR_VERSION') ?: 8;
        $metrics = $this->apiBuildRequest->metricsKey($versionSonar);
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/measures/component', [
                'component' => $maven_key,
                'metricKeys' => $metrics
                ]
        );

        $this->logger->debug('[Batch Mesure] 🛠️ Appel à SonarQube /measures/component', ['url' => $url]);
        /** On récupère les mesures depuis l'API */
        $results = $this->client->httpSonarQube($url);
        $measures = $results['json']['component']['measures'] ?? [];

        // On construit un tableau clé-valeur à partir des mesures récupérées
        foreach ($measures as $measure) {
            $result[$measure['metric']] = $measure['value'] ?? 0;
        }

        // On récupère le tableau des mesures reconstruites par le service BuildMapHistoryService.
        $rebuild_metrics = $this->apiBuildRequest->metricsRebuild(
            $result,
            [
                'analysisKey' => null,
                'version' => null,
                'date' => null
            ],
            $maven_key,
            'measures'
        );

        $this->logger->debug('[Batch Mesure] 🛠️ Résumé des métriques extraites', $rebuild_metrics);

        $date = new \DateTimeImmutable('now', new \DateTimeZone("Europe/Paris"));

        $mesureData = array_merge ($rebuild_metrics, [
            'maven_key' => $maven_key,
            'project_name' => strtolower($analysis['json']['projectName'] ?? 'inconnu'),
            'mode_collecte' => $mode_collecte,
            'utilisateur_collecte' => $utilisateur_collecte,
            'date_enregistrement' => $date
        ]);

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
            'nombre_mesures' => count($rebuild_metrics)
        ]);

        return [
            'code' => 200,
            'message' => "La mise à jour des mesures pour le projet est terminées.",
            'data' => $mesureData,
            'historique' => $mesureData
        ];
    }

}
