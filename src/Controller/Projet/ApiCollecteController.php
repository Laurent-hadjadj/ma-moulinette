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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;

/** Gestion de accès aux API */
use App\Controller\Batch\BatchCollecteInformationProjetController;
use App\Controller\Batch\BatchCollecteMesureController;
use App\Controller\Batch\BatchCollecteNoteController;
use App\Controller\Batch\BatchCollecteOwaspController;
use App\Controller\Batch\BatchCollecteHotspotController;
use App\Controller\Batch\BatchCollecteAnomalieController;
use App\Controller\Batch\BatchCollecteAnomalieDetailController;
use App\Controller\Batch\BatchCollecteHotspotOwaspController;
use App\Controller\Batch\BatchCollecteHotspotDetailController;
use App\Controller\Batch\BatchCollecteNoSonarController;
use App\Controller\Batch\BatchCollecteTodoController;
use App\Controller\Batch\BatchCollecteActuatorController;
use App\Controller\Batch\BatchCollecteLoggerController;


/**
 * [Description ApiMesureController]
 */
class ApiCollecteController extends AbstractController
{
    /** Définition des constantes */
    public static $reference = "<strong>[Collecte]</strong> ";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";

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
    #[Route('/api/collecte/information', name: 'api_collecte_information', methods: ['POST'])]
    public function apiCollecteInformation(Request $request): JsonResponse
    {
        // On décode le body
        $data = json_decode($request->getContent());

        // Vérification de la validité du corps de la requête
        if ($data === null || !property_exists($data, 'maven_key')) {
            $this->logger->warning('[Collecte] Requête mal formée : maven_key manquant ou invalide.', [
                'data' => $request->getContent()
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$reference . static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        // Vérifie si l'utilisateur a bien le rôle nécessaire
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning('[Collecte] Accès refusé : rôle manquant.', [
                'maven_key' => $data->maven_key,
                'user' => $this->security->getUser()?->getUserIdentifier()
            ]);
            return new JsonResponse([
                'type' => 'warning',
                'code' => 403,
                'message' => static::$reference . static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        // Récupération du courriel pour la collecte
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte] Début de la collecte des informations.', [
            'maven_key' => $data->maven_key,
            'user' => $utilisateur_collecte
        ]);

        // Appel à la collecte
        $information = $this->batchCollecteInformation->batchCollecteInformation(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );

        // Gestion des erreurs de collecte
        if (($information['code'] ?? 500) !== 200) {
            $this->logger->error('[Collecte] Échec de la collecte des informations du projet.', [
                'code' => $information['code'] ?? 'inconnu',
                'message' => $information['message'] ?? 'message absent',
                'trace' => $information['erreur'] ?? 'non fournie',
                'maven_key' => $data->maven_key
            ]);

            $message = 'Collecte des informations du projet.';
            return new JsonResponse([
                'code' => $information['code'] ?? 500,
                'type' => 'warning',
                'message' => static::$reference . ($information['message'] ?? $message),
                'trace' => $information['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] Collecte des informations réussie.', [
            'projet' => $information['message']['projet'] ?? 'non défini',
            'maven_key' => $data->maven_key,
            'user' => $utilisateur_collecte
        ]);

        return new JsonResponse([
            'code' => 200,
            'message' => [
                'projet' => $information['message']['projet'],
                'release' => $information['message']['release'],
                'snapshot' => $information['message']['snapshot'],
                'autre' => $information['message']['autre'],
                'total_sonar' => $information['message']['version_sonar'],
                'release_sonar' => $information['message']['version_release_sonar'],
                'snapshot_sonar' => $information['message']['version_snapshot_sonar'],
                'autre_sonar' => $information['message']['version_autre_sonar']
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
    #[Route('/api/collecte/mesure', name: 'api_collecte_mesure', methods: ['POST'])]
    public function apiCollecteMesure(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            $this->logger->warning('[Collecte] Requête invalide : maven_key manquant ou
            malformé.', [
                'payload' => $request->getContent()
            ]);

            return new JsonResponse(
                [
                    'code' => 400,
                    'type' => 'alert',
                    'message' => static::$reference . static::$erreur400,
                    'trace' => null
                ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning('[Collecte] Accès refusé : rôle COLLECTE manquant.',
            [
                'maven_key' => $data->maven_key,
                'user' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse(
                [
                    'code' => 403,
                    'type' => 'warning',
                    'message' => static::$reference . static::$erreur403,
                    'trace' => null
                ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        $this->logger->info('[Collecte] Début de la collecte des mesures.',
        [
            'maven_key' => $data->maven_key,
            'user' => $utilisateur_collecte
        ]);

        /** Mesures du projet (ligne de code, coverage, dette, ...) */
        $mesure = $this->batchCollecteMesure->batchCollecteMesure($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if (($mesure['code'] ?? 500) !== 200) {
            $this->logger->error('[Collecte] Échec de la collecte des mesures.', [
                'maven_key' => $data->maven_key,
                'code' => $mesure['code'] ?? 'inconnu',
                'message' => $mesure['message'] ?? 'absent',
                'trace' => $mesure['erreur'] ?? 'non fournie'
            ]);

            $message = 'Collecte des indicateurs de mesures du projet.';
            return new JsonResponse([
                'code' => $mesure['code'],
                'type' => 'alert',
                'message' => static::$reference . ($mesure['message'] ?? $message),
                'trace' => $mesure['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] Mesures collectées avec succès.', [
            'maven_key' => $mesure['message']['maven_key'],
            'project_name' => $mesure['message']['project_name']
        ]);

        /** on balance ton quoi :) */
        $information = [
                    'maven_key' => $mesure['message']['maven_key'],
                    'project_name' => $mesure['message']['project_name'],
                    'lines' => $mesure['message']['lines'],
                    'ncloc' => $mesure['message']['ncloc'],
                    'classes' => $mesure['message']['classes'],
                    'functions' => $mesure['message']['functions'],
                    'files' => $mesure['message']['files'],
                    'language_distribution' => $mesure['message']['language_distribution'],
                    'sqale_debt_ratio' => $mesure['message']['sqale_debt_ratio'],
                    'coverage' => $mesure['message']['coverage'],
                    'duplicated_lines_density' => $mesure['message']['duplicated_lines_density'],
                    'tests' => $mesure['message']['tests'],
                    'issues' => $mesure['message']['issues']
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
    #[Route('/api/collecte/note', name: 'api_collecte_note', methods: ['POST'])]
    public function apiCollecteNote(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (
            $data === null ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'type') ||
            !in_array($data->type, ['reliability', 'security', 'sqale'])
        ) {
            $this->logger->warning('[Collecte][Note] Requête invalide reçue', [
                'body' => $request->getContent()
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$reference . static::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning('[Collecte][Note] Accès refusé - ROLE_COLLECTE manquant', [
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$reference . static::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();
        $this->logger->info('[Collecte][Note] Début de collecte', [
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

        if ($note['code'] != 200) {
            $this->logger->error('[Collecte][Note] Échec de collecte', [
                'code' => $note['code'],
                'maven_key' => $data->maven_key,
                'type' => $data->type,
                'erreur' => $note['erreur'] ?? 'non spécifiée'
            ]);

            return new JsonResponse([
                'code' => $note['code'],
                'type' => 'alert',
                'message' => static::$reference . ($note['message'] ?? 'Collecte des notes du projet.'),
                'trace' => $note['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte][Note] Succès de collecte', [
            'maven_key' => $data->maven_key,
            'type' => $data->type,
            'note' => $note['message']['value'] ?? null
        ]);

        return new JsonResponse([
            'code' => 200,
            'type' => $data->type,
            'message' => ['note' => $note['message']['value']]
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
    #[Route('/api/collecte/owasp', name: 'api_collecte_owasp', methods: ['POST'])]
    public function apiCollecteOwasp(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data' => $data, 'code' => 400, 'type' => 'alert',
                'message' => static::$reference . static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403],
                Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        /** Signalement des Anomalies pour le projet */
        $owasp = $this->batchCollecteOwasp->batchCollecteOwasp($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($owasp['code'] != 200){
            return new JsonResponse([
                'code' => $owasp['code'], 'type' => 'alert',
                'message' => static::$reference . ($owasp['message'] ?? $owasp['erreur'])],
                Response::HTTP_OK);
        }
        return new JsonResponse([
            'code' => 200, 'owasp2017' => $owasp['owasp2017'],
            'owasp2021' => $owasp['owasp2021'],
            'message' => ['Nombre de faille OWASP 2017 : ' => $owasp['owasp2017'],
            'Nombre de faille OWASP 2021 : ' => $owasp['owasp2021']]],
            Response::HTTP_OK);
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
    #[Route('/api/collecte/hotspot', name: 'api_collecte_hotspot', methods: ['POST'])]
    public function apiCollecteHotspot(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data' => $data,'code' => 400, 'type' => 'alert',
                'message'=> static::$reference . static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403],
                Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        /** Signalement Hotspots pour le projet */
        $hotspot = $this->batchCollecteHotspot->batchCollecteHotspot($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($hotspot['code'] != 200){
            return new JsonResponse([
                'code' => $hotspot['code'], 'type' => 'alert',
                'message' => static::$reference . ($hotspot['message'] ?? $hotspot['erreur'])],
                Response::HTTP_OK);
        }

        return new JsonResponse([
                'code' => 200, 'nombre' => $hotspot['data']['nombre_hotspot'],
                'message' => [
                    'hotspot_high' => $hotspot['data']['hotspot_high'],
                    'hotspot_medium' => $hotspot['data']['hotspot_medium'],
                    'hotspot_low' => $hotspot['data']['hotspot_low'],
                    'nombre_hotspot' => $hotspot['data']['nombre_hotspot']]], Response::HTTP_OK);
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
    #[Route('/api/collecte/anomalie', name: 'api_collecte_anomalie', methods: ['POST'])]
    public function apiCollecteAnomalie(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data' => $data, 'code' => 400, 'type' => 'alert',
                'message'=> static::$reference . static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403],
                Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        /** Signalement des Anomalies pour le projet */
        $anomalie = $this->batchCollecteAnomalie->BatchCollecteAnomalie($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($anomalie['code'] != 200){
            return new JsonResponse([
                'code' => $anomalie['code'], 'type' => 'alert',
                'message' => static::$reference . ($anomalie['message'] ?? $anomalie['erreur'])],
                Response::HTTP_OK);
        }

        return new JsonResponse([
            'code' => 200,
            'info' => $anomalie['info'],
            'message' => [
                'violations' => $anomalie['data']['violations'],
                'nombre_bug' => $anomalie['data']['nombre_bug'],
                'nombre_vulnerability' => $anomalie['data']['nombre_vulnerability'],
                'nombre_code_smell' => $anomalie['data']['nombre_code_smell']]],
            Response::HTTP_OK);
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
    #[Route('/api/collecte/anomalie/detail', name: 'api_collecte_anomalie_detail', methods: ['POST'])]
    public function apiCollecteAnomalieDetail(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data' => $data,'code' => 400, 'type' => 'alert',
                'message'=> static::$reference . static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403],
                Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        /** collecte des signalements du détail des Anomalies pour le projet */
        $anomalieDetail = $this->batchCollecteAnomalieDetail->BatchCollecteAnomalieDetail($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($anomalieDetail['code'] != 200){
            // $anomalieDetail['type'] ==> ['BUG', 'VULNERABILITY', 'CODE_SMELL']
            return new JsonResponse([
                'code' => $anomalieDetail['code'], 'type' => 'alert',
                'message' => static::$reference . ($anomalieDetail['message'] ?? $anomalieDetail['erreur'])],
                Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200, 'message' => ['chargement des anomalies détallées'], 'data' => $anomalieDetail['data']], Response::HTTP_OK);
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
    #[Route('/api/collecte/hotspot/owasp', name: 'api_collecte_hotspot_owasp', methods: ['POST'])]
    public function apiCollecteHotspotOwasp(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'menace')) {
            return new JsonResponse(
                ['data' => $data,'code' => 400, 'type' => 'alert',
                'message'=> static::$reference . static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403],
                Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        $hotspotOwasp = $this->batchCollecteHotspotOwasp->BatchCollecteHotspotOwasp($data->maven_key, 'COLLECTE', $utilisateur_collecte, $data->menace);
        if ($hotspotOwasp['code'] != 200){
            return new JsonResponse([
                'code' => $hotspotOwasp['code'], 'type' => 'alert',
                'message' => static::$reference . ($hotspotOwasp['message'] ?? $hotspotOwasp['erreur'])],
                Response::HTTP_OK);
        }

        return new JsonResponse([
            'code' => 200,
            'info' => $hotspotOwasp['info'],
            'owasp2017' => $hotspotOwasp['owasp_2017'] ?? 'NC',
            'owasp2021' => $hotspotOwasp['owasp_2021'] ?? 'NC',
            'message' => $hotspotOwasp['message'],
            'data' => $hotspotOwasp['data']],
            Response::HTTP_OK);
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
    #[Route('/api/collecte/hotspot/detail', name: 'api_collecte_hotspot_detail', methods: ['POST'])]
    public function apiCollecteHotspotDetail(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data' => $data,'code' => 400, 'type' => 'alert',
                'message' => static::$reference . static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403],
                Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        /** collecte du détail des hotspots pour le projet */
        $hotspotDetail = $this->batchCollecteHotspotDetail->BatchCollecteHotspotDetail($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($hotspotDetail['code'] != 200){
            return new JsonResponse([
                'code' => $hotspotDetail['code'], 'type' => 'alert',
                'message' => static::$reference . ($hotspotDetail['message'] ?? $hotspotDetail['erreur'])],
                Response::HTTP_OK);
        }

        return new JsonResponse([
            'code' => 200,
            'nombre' => $hotspotDetail['nombre'],
            'message' => 'Données enregistrées dans la table hotspotDetail.'],
            Response::HTTP_OK);
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
    #[Route('/api/collecte/nosonar', name: 'api_collecte_nosonar', methods: ['POST'])]
    public function apiCollecteNoSonar(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data' => $data,'code' => 400, 'type' => 'alert',
                'message' => static::$reference . static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403],
                Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** collecte des suppressWarning et noSonar du projet */
        $noSonar = $this->batchCollecteNoSonar->BatchCollecteNoSonar($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($noSonar['code'] != 200){
            return new JsonResponse([
                'code' => $noSonar['code'], 'type' => 'alert',
                'message' => static::$reference . ($noSonar['message'] ?? $noSonar['erreur'])], Response::HTTP_OK);
        }

        $nombre = $noSonar['message']['suppress_warning'] ?? 0 + $noSonar['message']['no_sonar'] ?? 0;
        return new JsonResponse([
            'code' => 200,
            'nombre' => $nombre,
            'message' => [
                'suppress_warning' => $noSonar['message']['suppress_warning'] ?? 0,
                'no_sonar' => $noSonar['message']['no_sonar'] ?? 0]],
                Response::HTTP_OK);
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
    #[Route('/api/collecte/todo', name: 'api_collecte_todo', methods: ['POST'])]
    public function apiCollecteTodo(Request $request): response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data' => $data, 'code' => 400, 'type' => 'alert',
                'message' => static::$reference . static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403],
                Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** collecte des to.do présent dans le projet */
        $todo = $this->batchCollecteTodo->BatchCollecteTodo($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($todo['code'] != 200){
            return new JsonResponse([
                'code' => $todo['code'], 'type' => 'alert',
                'message' => static::$reference . ($todo['message'] ?? $todo['erreur'])],
                Response::HTTP_OK);
        }
        return new JsonResponse([
            'code' => 200, 'nombre' => $todo['nombre'],
            'message' => 'Données enregistrées dans la table Todo.'],
            Response::HTTP_OK);
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
    #[Route('/api/collecte/actuator/info', name: 'api_collecte_actuator_info', methods: ['POST'])]
    public function apiCollecteActuator(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data' => $data, 'code' => 400, 'type' => 'alert',
                'message' => static::$reference . static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403],
                Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        /** collecte des to.do présent dans le projet */
        $actuatorInfo = $this->batchCollecteActuator->BatchCollecteActuatorInfo($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($actuatorInfo['code'] !== 200){
            return new JsonResponse([
                'code' => $actuatorInfo['code'], 'type' => 'alert',
                'message' => static::$reference . ($actuatorInfo['message'] ?? $actuatorInfo['erreur'])],
                Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200, 'message' => 'Extraction des données Actuator.',  'json' => $actuatorInfo['json']], Response::HTTP_OK);
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
    #[Route('/api/collecte/logger', name: 'api_collecte_logger', methods: ['POST'])]
    public function apiCollecteLogger(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data' => $data,'code' => 400, 'type' => 'alert',
                'message' => static::$reference . static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403],
                Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel();

        /** collecte des Logger présent dans le projet */
        $logger = $this->batchCollecteLogger->BatchCollecteLogger($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($logger['code'] != 200){
            return new JsonResponse([
                'code' => $logger['code'], 'type' => 'alert',
                'message' => static::$reference . ($logger['message'] ?? $logger['erreur'])],
                Response::HTTP_OK);
        }
        return new JsonResponse(['code' => 200, 'message' => 'Données enregistrées dans la table logger.',
        'data' => $logger['data']], Response::HTTP_OK);
    }
}
