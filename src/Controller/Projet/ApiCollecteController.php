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

namespace App\Controller\Projet;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;

/** Gestion de accès aux API */
use App\Controller\Batch\{BatchCollecteInformationProjetController, BatchCollecteMesureController,BatchCollecteNoteController, BatchCollecteOwaspController, BatchCollecteHotspotController,BatchCollecteAnomalieController, BatchCollecteAnomalieDetailController, BatchCollecteHotspotOwaspController,BatchCollecteHotspotDetailController, BatchCollecteNoSonarController, BatchCollecteTodoController,BatchCollecteActuatorController, BatchCollecteLoggerController};

/**
 * [Description ApiMesureController]
 */
class ApiCollecteController extends AbstractController
{

    /** Définition des constantes */
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    private static $loggerE400 = "[Collecte] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.";
    private static $loggerE403 = "[Collecte] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_COLLECTE).";
    private static $noSpecify = 'non spécifiée';

    public function __construct(
        private BatchCollecteInformationProjetController $batchCollecteInformation,
        private BatchCollecteMesureController $batchCollecteMesure,
        private BatchCollecteNoteController $batchCollecteNote,
        private BatchCollecteOwaspController $batchCollecteOwasp,
        private BatchCollecteHotspotController $batchCollecteHotspot,
        private BatchCollecteAnomalieController $batchCollecteAnomalie,
        private BatchCollecteAnomalieDetailController $batchCollecteAnomalieDetail,
        private BatchCollecteHotspotOwaspController $batchCollecteHotspotOwasp,
        private BatchCollecteHotspotDetailController $batchCollecteHotspotDetail,
        private BatchCollecteNoSonarController $batchCollecteNoSonar,
        private BatchCollecteTodoController $batchCollecteTodo,
        private BatchCollecteActuatorController $batchCollecteActuator,
        private BatchCollecteLoggerController $batchCollecteLogger,
        private Security $security,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * [Description for apiCollecteInformation]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 30/06/2024 14:18:23 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/information', name: 'api_collecte_information', methods: ['POST'])]
    public function apiCollecteInformation(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/information");

        // On décode le body
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        // Vérifie si l'utilisateur a bien le rôle nécessaire
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'type' => 'warning',
                'code' => 403,
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        // Récupération du courriel pour la collecte
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte] ℹ️ Début de collecte des informations du projet.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        // Appel à la collecte
        $information = $this->batchCollecteInformation->batchCollecteInformation(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        // Gestion des erreurs de collecte
        if (($information['code'] ?? 500) !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de la collecte des informations du projet.', [
                'maven_key' => $data->maven_key,
                'code' => $information['code'] ?? 'inconnu',
                'message' => $information['message'] ?? 'absent',
                'trace' => $information['erreur'] ?? 'non fournie'
            ]);

            $message = 'Collecte des informations du projet.';
            return new JsonResponse([
                'code' => $information['code'] ?? 500,
                'type' => 'warning',
                'message' => $information['message'] ?? $message,
                'trace' => $information['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Informations collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'version' => $information['historique']['version']
        ]);

        $total_sonar =  $information['historique']['version_release_sonar'] +
                        $information['historique']['version_snapshot_sonar'] +
                        $information['historique']['version_autre_sonar'];

        return new JsonResponse([
            'code' => 200,
            'message' => [
                'projet_version' => $information['historique']['version'],
                'release' => $information['historique']['version_release'],
                'snapshot' => $information['historique']['version_snapshot'],
                'autre' => $information['historique']['version_autre'],
                'total_sonar' => $total_sonar,
                'release_sonar' => $information['historique']['version_release_sonar'],
                'snapshot_sonar' => $information['historique']['version_snapshot_sonar'],
                'autre_sonar' => $information['historique']['version_autre_sonar']
            ]
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteMesure]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 30/06/2024 18:09:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/mesure', name: 'api_collecte_mesure', methods: ['POST'])]
    public function apiCollecteMesure(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/mesure");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key') ) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse(
                [
                    'code' => 400,
                    'type' => 'alert',
                    'message' => static::$erreur400,
                    'trace' => null
                ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse(
                [
                    'code' => 403,
                    'type' => 'warning',
                    'message' => static::$erreur403,
                    'trace' => null
                ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';
        $this->logger->info('[Collecte] ℹ️ Début de collecte des mesures du projet.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** Mesures du projet (ligne de code, coverage, dette, ...) */
        $mesure = $this->batchCollecteMesure->batchCollecteMesure(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        if (($mesure['code'] ?? 500) !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de la collecte des mesures.', [
                'maven_key' => $data->maven_key,
                'code' => $mesure['code'] ?? 'inconnu',
                'message' => $mesure['message'] ?? 'absent',
                'trace' => $mesure['erreur'] ?? 'non fournie'
            ]);

            $message = 'Collecte des indicateurs de mesures du projet.';
            return new JsonResponse([
                'code' => $mesure['code'],
                'type' => 'alert',
                'message' => $mesure['message'] ?? $message,
                'trace' => $mesure['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Mesures collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'project_name' => $mesure['data']['project_name']
        ]);

        /** on balance ton quoi :) */
        $information = [
                    'maven_key' => $mesure['data']['maven_key'],
                    'project_name' => $mesure['data']['project_name'],
                    'lines' => $mesure['data']['lines'],
                    'ncloc' => $mesure['data']['ncloc'],
                    'classes' => $mesure['data']['classes'],
                    'functions' => $mesure['data']['functions'],
                    'files' => $mesure['data']['files'],
                    'language_distribution' => $mesure['data']['language_distribution'],
                    'sqale_debt_ratio' => $mesure['data']['sqale_debt_ratio'],
                    'coverage' => $mesure['data']['coverage'],
                    'duplicated_lines_density' => $mesure['data']['duplicated_lines_density'],
                    'tests' => $mesure['data']['tests'],
                    'issues' => $mesure['data']['issues']
                ];

        return new JsonResponse([
            'code' => 200,
            'message' => $information
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteNote]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 30/06/2024 18:36:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/note', name: 'api_collecte_note', methods: ['POST'])]
    public function apiCollecteNote(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/note");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (
            !is_object($data) ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'type') ||
            !in_array($data->type, ['reliability', 'security', 'sqale'])
        ) {
            $this->logger->alert("[Collecte] ❌ Requête invalide : clé 'maven_key', 'type', 'reliability', 'security', 'sqale' manquante ou JSON mal formé.",
            [ 'payload' => $data ?? null ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte] ℹ️ Début de collecte des notes du projet.', [
            'maven_key' => $data->maven_key,
            'type' => $data->type,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** Appel à la collecte de note (reliability, security, sqale) */
        $note = $this->batchCollecteNote->batchCollecteNote(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte,
            $data->type
        );

        if ($note['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte des notes du projet.', [
                'code' => $note['code'],
                'maven_key' => $data->maven_key,
                'type' => $data->type,
                'erreur' => $note['erreur'] ?? static::$noSpecify
            ]);

            return new JsonResponse([
                'code' => $note['code'],
                'type' => 'alert',
                'message' => $note['message'] ?? 'Collecte des notes du projet.',
                'trace' => $note['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Note collectée avec succès.', [
            'maven_key' => $data->maven_key,
            'type' => $data->type,
            'note' => $note['historique']["note_{$data->type}"] ?? null
        ]);

        return new JsonResponse([
            'code' => 200,
            'type' => $data->type,
            'message' => [ 'note' => $note['historique']["note_{$data->type}"] ]
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteOwasp]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 11/11/2024 11:05:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/owasp', name: 'api_collecte_owasp', methods: ['POST'])]
    public function apiCollecteOwasp(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/owasp");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte] ℹ️ Début de collecte des menaces OWASP du projet.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** Signalement des Anomalies pour le projet */
        $owasp = $this->batchCollecteOwasp->batchCollecteOwasp(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        if ($owasp['code'] !== 200){
            $this->logger->error('[Collecte] ❌ Échec de collecte des menaces OWASP du projet.', [
                'code' => $owasp['code'],
                'maven_key' => $data->maven_key,
                'type' => $data->type,
                'erreur' => $owasp['erreur'] ?? static::$noSpecify
            ]);

            $message = 'Collecte des menaces du projet.';
            return new JsonResponse([
                'code' => $owasp['code'],
                'type' => 'alert',
                'message' => $owasp['message'] ?? $message,
                'trace' => $owasp['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Menaces OWASP collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'OWASP2017' => $owasp['owasp2017'],
            'OWASP2021' => $owasp['owasp2021']
        ]);


        return new JsonResponse([
            'code' => 200,
            'owasp2017' => $owasp['owasp2017'],
            'owasp2021' => $owasp['owasp2021'],
            'message' => [
                'Nombre de faille OWASP 2017 : ' => $owasp['owasp2017' ],
                'Nombre de faille OWASP 2021 : ' => $owasp['owasp2021']
            ]
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteHotspot]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 30/06/2024 19:07:21 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/hotspot', name: 'api_collecte_hotspot', methods: ['POST'])]
    public function apiCollecteHotspot(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/hotspot");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
            $this->logger->info('[Collecte] ℹ️ Début de collecte des menaces potentielles du projet.', [
                'maven_key' => $data->maven_key,
                'utilisateur' => $utilisateur_collecte
            ]);

        /** Signalement Hotspots pour le projet */
        $hotspot = $this->batchCollecteHotspot->batchCollecteHotspot(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        if ($hotspot['code'] !== 200){
            $this->logger->error('[Collecte] ❌ Échec de collecte des menaces potentielles du projet.', [
                'code' => $hotspot['code'],
                'maven_key' => $data->maven_key,
                'type' => $data->type,
                'erreur' => $hotspot['erreur'] ?? static::$noSpecify
            ]);

            $message = 'Collecte des menaces potentielles du projet.';
            return new JsonResponse([
                'code' => $hotspot['code'],
                'type' => 'alert',
                'message' => $hotspot['message'] ?? $message,
                'trace' => $hotspot['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Menaces potentielles collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'hotspot_high' => $hotspot['historique']['hotspot_high'],
            'hotspot_medium' => $hotspot['historique']['hotspot_medium'],
            'hotspot_low' => $hotspot['historique']['hotspot_low'],
            'nombre_hotspot' => $hotspot['historique']['nombre_hotspot']
        ]);

        return new JsonResponse([
            'code' => 200,
            'nombre' => $hotspot['historique']['nombre_hotspot'],
            'message' => [
                'hotspot_high' => $hotspot['historique']['hotspot_high'],
                'hotspot_medium' => $hotspot['historique']['hotspot_medium'],
                'hotspot_low' => $hotspot['historique']['hotspot_low'],
                'nombre_hotspot' => $hotspot['historique']['nombre_hotspot']
                ]
            ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteAnomalie]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 30/06/2024 19:16:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/anomalie', name: 'api_collecte_anomalie', methods: ['POST'])]
    public function apiCollecteAnomalie(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/anomalie");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message'=> static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte] ℹ️ Début de collecte des anomalies du projet.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);


        /** Signalement des Anomalies pour le projet */
        $anomalie = $this->batchCollecteAnomalie->BatchCollecteAnomalie(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        if ($anomalie['code'] !== 200){
            $this->logger->error('[Collecte] ❌ Échec de collecte des anomalies du projet.', [
                'code' => $anomalie['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $anomalie['erreur'] ?? static::$noSpecify
            ]);

            $message = 'Collecte des anomalies du projet.';
            return new JsonResponse([
                'code' => $anomalie['code'],
                'type' => 'alert',
                'message' => $anomalie['message'] ?? $message,
                'trace' => $anomalie['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Anomalies collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'violations' => $anomalie['historique']['violations'],
            'nombre_bug' => $anomalie['historique']['nombre_bug'],
            'nombre_vulnerability' => $anomalie['historique']['nombre_vulnerability'],
            'nombre_code_smell' => $anomalie['historique']['nombre_code_smell']
        ]);

        return new JsonResponse([
            'code' => 200,
            'info' => $anomalie['info'],
            'message' => [
                'violations' => $anomalie['historique']['violations'],
                'nombre_bug' => $anomalie['historique']['nombre_bug'],
                'nombre_vulnerability' => $anomalie['historique']['nombre_vulnerability'],
                'nombre_code_smell' => $anomalie['historique']['nombre_code_smell']
            ]
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteAnomalieDetail]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 30/06/2024 19:30:01 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/anomalie/detail', name: 'api_collecte_anomalie_detail', methods: ['POST'])]
    public function apiCollecteAnomalieDetail(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/anomalie/detail");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message'=> static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte] ℹ️ Début de collecte du détail des anomalies pour le projet.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** collecte des signalements du détail des Anomalies pour le projet */
        $anomalieDetail = $this->batchCollecteAnomalieDetail->BatchCollecteAnomalieDetail(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        if ($anomalieDetail['code'] !== 200){
            $this->logger->error('[Collecte] ❌ Échec de collecte du détail des anomalies pour le projet.', [
                'code' => $anomalieDetail['code'],
                'maven_key' => $data->maven_key,
                'type' => $data->type,
                'erreur' => $anomalieDetail['erreur'] ?? static::$noSpecify
            ]);

            // $anomalieDetail['type'] ==> ['BUG', 'VULNERABILITY', 'CODE_SMELL']
            $message = 'Collecte du détail des anomalies du projet.';
            return new JsonResponse([
                'code' => $anomalieDetail['code'],
                'type' => 'alert',
                'message' => $anomalieDetail['message'] ?? $message,
                'trace' => $anomalieDetail['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Détails des anomalies collectées avec succès.', [
            'maven_key' => $data->maven_key,
        ]);

        return new JsonResponse([
            'code' => 200,
            'message' => ['chargement des anomalies détallées'],
            'data' => $anomalieDetail['historique']
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteHotspotOwasp]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 30/06/2024 21:55:15 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/hotspot/owasp', name: 'api_collecte_hotspot_owasp', methods: ['POST'])]
    public function apiCollecteHotspotOwasp(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/hotspot/owasp");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'menace')) {
            $this->logger->alert("[Collecte] ❌ Requête invalide : clé 'maven_key', 'menace', manquante ou JSON mal formé.",
            [ 'payload' => $data ?? null ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message'=> static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte] ℹ️ Début de collecte des menaces OWASP potentielles du projet.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        $hotspotOwasp = $this->batchCollecteHotspotOwasp->BatchCollecteHotspotOwasp(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte,
            $data->menace
        );

        if ($hotspotOwasp['code'] !== 200){
            $this->logger->error('[Collecte] ❌ Échec de collecte des menaces OWASP potentielles du projet.', [
                'code' => $hotspotOwasp['code'],
                'maven_key' => $data->maven_key,
                'type' => $data->type,
                'erreur' => $hotspotOwasp['erreur'] ?? static::$noSpecify
            ]);

            $message = 'Collecte des menaces OWASP potentielles du projet.';
            return new JsonResponse([
                'code' => $hotspotOwasp['code'],
                'type' => 'alert',
                'message' => $hotspotOwasp['message'] ?? $message,
                'trace' => $hotspotOwasp['erreur']
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Menaces OWASP potentielles collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'info' => $hotspotOwasp['info'],
            'owasp2017' => $hotspotOwasp['owasp_2017'] ?? 'NC',
            'owasp2021' => $hotspotOwasp['owasp_2021'] ?? 'NC',
        ]);

        return new JsonResponse([
            'code' => 200,
            'info' => $hotspotOwasp['info'],
            'owasp2017' => $hotspotOwasp['owasp_2017'] ?? 'NC',
            'owasp2021' => $hotspotOwasp['owasp_2021'] ?? 'NC',
            'message' => $hotspotOwasp['message'],
            'data' => $hotspotOwasp['data']
        ],  Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteHotspotDetail]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 02/07/2024 20:19:50 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/hotspot/detail', name: 'api_collecte_hotspot_detail', methods: ['POST'])]
    public function apiCollecteHotspotDetail(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/hotspot/detail");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte] ℹ️ Début de collecte du détail des menaces potentielle du projet.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** collecte du détail des hotspots pour le projet */
        $hotspotDetail = $this->batchCollecteHotspotDetail->BatchCollecteHotspotDetail(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        if ($hotspotDetail['code'] !== 200){
            $this->logger->error('[Collecte] ❌ Échec de collecte du détail des menaces potentielles du projet.', [
                'code' => $hotspotDetail['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $hotspotDetail['erreur'] ?? static::$noSpecify
            ]);

            $message = 'Collecte des menaces potentielles du projet.';
            return new JsonResponse([
                'code' => $hotspotDetail['code'],
                'type' => 'alert',
                'message' => $hotspotDetail['message'] ?? $message,
                'trace' => $hotspotDetail['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Menaces potentielles collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'nombre' => $hotspotDetail['nombre'],
        ]);

        return new JsonResponse([
            'code' => 200,
            'nombre' => $hotspotDetail['nombre'],
            'message' => 'Données enregistrées dans la table hotspotDetail.'
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteNoSonar]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 11/07/2024 22:05:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/nosonar', name: 'api_collecte_nosonar', methods: ['POST'])]
    public function apiCollecteNoSonar(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/nosonar");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';
        $this->logger->info('[Collecte] ℹ️ Début de collecte des noSonar du projet.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** collecte des suppressWarning et noSonar du projet */
        $noSonar = $this->batchCollecteNoSonar->BatchCollecteNoSonar(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        if ($noSonar['code'] !== 200){
            $this->logger->error('[Collecte] ❌ Échec de collecte des annotations noSonar et suppressWarning du projet.', [
                'code' => $noSonar['code'],
                'maven_key' => $data->maven_key,
                'type' => $data->type,
                'erreur' => $noSonar['erreur'] ?? static::$noSpecify
            ]);

            $message = 'Collecte des annotations noSonar et suppressWarning du projet.';
            return new JsonResponse([
                'code' => $noSonar['code'],
                'type' => 'alert',
                'message' => $noSonar['message'] ?? $message,
                'trace' => $noSonar['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ NoSonar collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'suppress_warning' => $noSonar['historique']['suppress_warning'] ?? 0,
            'no_sonar' => $noSonar['historique']['no_sonar'] ?? 0,
        ]);

        $nombre = $noSonar['historique']['suppress_warning'] + $noSonar['historique']['no_sonar'];
        return new JsonResponse([
            'code' => 200,
            'nombre' => $nombre,
            'historique' => $noSonar['historique']
            ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteTodo]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 11/07/2024 22:05:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/todo', name: 'api_collecte_todo', methods: ['POST'])]
    public function apiCollecteTodo(Request $request): response
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/todo");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';
        $this->logger->info('[Collecte] ℹ️ Début de collecte des todos du projet.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** collecte des to.do présent dans le projet */
        $todo = $this->batchCollecteTodo->BatchCollecteTodo(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        if ($todo['code'] !== 200){
            $this->logger->error('[Collecte] ❌ Échec de collecte des todos du projet.', [
                'code' => $todo['code'],
                'maven_key' => $data->maven_key,
                'type' => $data->type,
                'erreur' => $todo['erreur'] ?? static::$noSpecify
            ]);

            $message = 'Collecte des todos du projet.';
            return new JsonResponse([
                'code' => $todo['code'],
                'type' => 'alert',
                'message' => $todo['message'] ?? $message,
                'trace' => $todo['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Todos collectés avec succès.', [
            'maven_key' => $data->maven_key,
            'todo' => $todo['nombre']
        ]);

        return new JsonResponse([
            'code' => 200,
            'nombre' => $todo['nombre'],
            'message' => 'Données enregistrées dans la table Todo.'
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteActuator]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 11/07/2024 22:00:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/actuator/info', name: 'api_collecte_actuator_info', methods: ['POST'])]
    public function apiCollecteActuator(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/actuator/info");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte] ℹ️ Début de collecte des informations Actuator du projet JAVA.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** collecte des to.do présent dans le projet */
        $actuatorInfo = $this->batchCollecteActuator->BatchCollecteActuatorInfo(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        if ($actuatorInfo['code'] !== 200){
            $this->logger->error('[Collecte] ❌ Échec de collecte des informations Actuator du projet JAVA.', [
                'code' => $actuatorInfo['code'],
                'maven_key' => $data->maven_key,
                'type' => $data->type,
                'erreur' => $actuatorInfo['erreur'] ?? static::$noSpecify
            ]);

            $message = 'Collecte des informations Actuator du projet JAVA.';
            return new JsonResponse([
                'code' => $actuatorInfo['code'],
                'type' => 'alert',
                'message' => $actuatorInfo['message'] ?? $message,
                'trace' => $actuatorInfo['erreur']
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Actuator Infos collectés avec succès.', [
            'maven_key' => $data->maven_key,
        ]);

        return new JsonResponse([
            'code' => 200,
            'message' => 'Extraction des données Actuator.',
            'json' => $actuatorInfo['json']
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiCollecteLogger]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 11/07/2024 22:05:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/collecte/logger', name: 'api_collecte_logger', methods: ['POST'])]
    public function apiCollecteLogger(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/logger");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->alert(static::$loggerE400, [
                'payload' => $data ?? null
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(static::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte] ℹ️ Début de collecte des loggers du projet.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** collecte des Logger présent dans le projet */
        $logger = $this->batchCollecteLogger->BatchCollecteLogger(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        if ($logger['code'] !== 200){
            $this->logger->error('[Collecte] ❌ Échec de collecte des loggers du projet.', [
                'code' => $logger['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $logger['erreur'] ?? static::$noSpecify
            ]);

            $message = 'Collecte des loggers du projet JAVA.';
            return new JsonResponse([
                'code' => $logger['code'],
                'type' => 'alert',
                'message' => $logger['message'] ?? $message,
                'trace' => $logger['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Loggers collectés avec succès.', [
            'maven_key' => $data->maven_key,
        ]);

        return new JsonResponse([
            'code' => 200,
            'message' => 'Données enregistrées dans la table logger.',
            'data' => $logger['data']
        ], Response::HTTP_OK);
    }
}
