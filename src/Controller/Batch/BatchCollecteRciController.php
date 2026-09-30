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

use App\Entity\Rci;
use App\Service\RciMeasures\BuildMeasuresRciService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * [Description BatchCollecteRciController]
 * Calcule le RCI (Risk Criticality Index, plugin sonar-rci) global et par module
 * (frontend/backend/autre).
 *
 */
class BatchCollecteRciController
{
    /** Définition des constantes */
    private static string $sonarVersion = 'sonar.version';
    private static string $erreurSonarInconnue = 'Erreur SonarQube inconnue.';

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $params,
        private BuildMeasuresRciService $buildMeasuresRci,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for buildRciMeasures]
     * Retourne le RCI par module (frontend/backend/autre) pour un projet, de
     * façon autonome : ncloc et sévérités récupérés auprès de SonarQube par
     * cette classe, sans dépendre d'une collecte de répartition préalable.
     *
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array<int|string, mixed>
     *
     * Created at: 25/09/2026 13:11:25 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function buildRciMeasures(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $rciRepos = $this->em->getRepository(Rci::class);

        $this->logger->info('[Batch RCI] ℹ️ Début de la collecte', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        // On sécurise la maven_key
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        // On récupère la version du serveur SonarQube
        $version = (int) trim((string) $this->params->get(self::$sonarVersion));

        /** On appel le service de calcul du nombre d'issues par module */
        $global_measures = $this->buildMeasuresRci->calculateGlobalMeasures($maven_key, $version);

        if (isset($global_measures['code']) && $global_measures['code'] !== 200) {
            $this->logger->error('[Batch RCI - Global] ❌ Échec de la récupération des indicateurs RCI pour le projet', [
                'maven_key' => $maven_key,
                'code' => $global_measures['code'],
                'erreur' => $global_measures['erreur'] ?? self::$erreurSonarInconnue,
            ]);

            return [
                    'code' => $global_measures['code'],
                    'erreur' => $global_measures['erreur'] ?? self::$erreurSonarInconnue
            ];
        }

        /** On appel le service de calcul du nombre d'issues par module */
        $module_measures = $this->buildMeasuresRci->calculateMeasuresByModule($maven_key, $version);
        if (isset($module_measures['code']) && $module_measures['code'] !== 200) {
            $this->logger->error('[Batch RCI - Module] ❌ Échec de la récupération des indicateurs RCI par module', [
                'maven_key' => $maven_key,
                'code' => $module_measures['code'],
                'erreur' => $module_measures['erreur'] ?? self::$erreurSonarInconnue,
            ]);

            return [
                    'code' => $module_measures['code'],
                    'erreur' => $module_measures['erreur'] ?? self::$erreurSonarInconnue
            ];
        }

        /**  ======== On s'occupe des mesures globales ========= */
        // On calcul le poids total de la dette
        // MODIF 2026-09-28 : on lit désormais $global_measures['severities'],
        // déjà normalisé (BLOCKER/CRITICAL/MAJOR/MINOR/INFO) par le service
        // selon la bonne taxonomie — lire les clés brutes de global_measures
        // (ex. blocker_violations) codait en dur l'ancienne taxonomie et
        // renvoyait toujours un poids de 0 sur un serveur SonarQube 2024+.
        $severities = $global_measures['severities'];
        $ncloc = $global_measures['global_measures']['ncloc'];

        $global_weight = $this->buildMeasuresRci->weightedWeights(
                $severities['BLOCKER'],
                $severities['CRITICAL'],
                $severities['MAJOR'],
                $severities['MINOR'],
                $severities['INFO']
            );

        // On calcul l'indicateur RCI
        $global_rci = $this->buildMeasuresRci->calculateRci($ncloc, $global_weight);

        // On calcul la note
        $global_rating = $this->buildMeasuresRci->getRCIRating($global_rci);

        // On consolide les données pour les indicateurs RCI de l'application.
        $data_global_collecte = ['ncloc' => $ncloc, 'indicator' => $global_rci, 'weight' => $global_weight, 'rating' => $global_rating];

        /**  ======== On s'occupe des mesures par module ========= */
        $data_module_collecte = [];
        foreach (['frontend', 'backend', 'autre', 'inconnu'] as $module) {
            $severities = $module_measures['severities'][$module];
            $module_ncloc = (int) $module_measures['ncloc'][$module];
            // Pour chaque module on calcul le poids de la dette
            $module_weight = $this->buildMeasuresRci->weightedWeights(
                $severities['BLOCKER'],
                $severities['CRITICAL'],
                $severities['MAJOR'],
                $severities['MINOR'],
                $severities['INFO']
            );

            // On calcul le RCI pour chaque module
            $module_rci = $this->buildMeasuresRci->calculateRci($module_ncloc, $module_weight);

            /** On consolide les données avant l'enregistrement */
            $data_collecte = [
                $module.'_ncloc' => $module_ncloc,
                $module.'_weight' => $module_weight,
                $module.'_indicator' => $module_rci,
                // on calcul la note
                $module.'_rating' => $this->buildMeasuresRci->getRCIRating($module_rci),
            ];

            $data_module_collecte = array_merge($data_module_collecte, $data_collecte);
        }

        // Suppression des données de la table RCI
        $delete = $rciRepos->deleteRciMavenKey([ 'maven_key' => $maven_key ]);
        if ($delete['code'] !== 200) {
            $this->logger->error('[Batch RCI] ❌ Échec de suppression des indicateurs RCI', [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ]);

            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        $this->logger->info('[Batch Mesure] ℹ️ Suppression des indicateurs RCI OK', [
            'maven_key' => $maven_key
        ]);

        $date = new \DateTimeImmutable('now', new \DateTimeZone("Europe/Paris"));

        $data = array_merge (
            $data_global_collecte,
            $data_module_collecte,
            [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur_collecte' => $utilisateur_collecte,
            'date_enregistrement' => $date
        ]);

        $this->logger->info('[Batch RCI] ℹ️ RCI calculé par module', ['maven_key' => $maven_key]);

        $insert = $rciRepos->insertRci($data);
        if ($insert['code'] !== 200) {
            $this->logger->error('[Batch RCI] ❌ Échec de la requête insertRCI', [
                'maven_key' => $maven_key,
                'erreur' => $insert['erreur']
            ]);

            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        $this->logger->info('[Batch RCI] ℹ️ Insertion des indicateurs RCI OK', [
            'maven_key' => $maven_key
        ]);

        return [
            'code' => 200,
            'message' => "La mise à jour des indicateurs RCI pour le projet est terminées.",
            'historique' => $data
        ];
    }
}
