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

namespace App\Controller\Projet;

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use \Symfony\Component\Routing\Attribute\Route;
use Symfony\Bundle\SecurityBundle\Security;
use Psr\Log\LoggerInterface;

/** Gestion de accès aux API */
use App\Controller\Batch\{BatchCollecteInformationProjetController, BatchCollecteMesureController, BatchCollecteOwaspController, BatchCollecteHotspotController,BatchCollecteAnomalieController, BatchCollecteAnomalieDetailController, BatchCollecteHotspotOwaspController,BatchCollecteHotspotDetailController, BatchCollecteNoSonarController, BatchCollecteTodoController,BatchCollecteActuatorController, BatchCollecteLoggerController, BatchCollecteCleanCodeController};

/**
 * [Description ApiMesureController]
 */
class ApiCollecteController extends AbstractController
{
    use AppUserAware;


    /** Définition des constantes */
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    private static string $loggerE400 = "[Collecte] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.";
    private static string $loggerE403 = "[Collecte] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_COLLECTE).";
    private static string $noSpecify = 'non spécifiée';
    private static string $noData = 'Pas de données disponible.';

    public function __construct(
        private BatchCollecteInformationProjetController $batchCollecteInformation,
        private BatchCollecteMesureController $batchCollecteMesure,
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
        /* MODIF 2026-05-15 : indicateurs SonarQube 10+. */
        private BatchCollecteCleanCodeController $batchCollecteCleanCode,
        private Security $security,
        private LoggerInterface $logger,
    ) {}

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
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        // Vérifie si l'utilisateur a bien le rôle nécessaire
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'type' => 'warning',
                'code' => 403,
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        // Récupération du courriel pour la collecte
        $utilisateur_collecte = $this->appUser()->getCourriel();
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
                'trace' => $information['erreur'] ?? $information['trace'] ?? 'non fournie'
            ]);

            $message = 'Collecte des informations du projet.';
            return new JsonResponse([
                'code' => $information['code'] ?? 500,
                'type' => 'warning',
                'message' => $information['message'] ?? $message,
                'trace' => $information['erreur'] ?? $information['trace'] ?? self::$noData
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
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse(
                [
                    'code' => 400,
                    'type' => 'error',
                    'message' => self::$erreur400,
                    'trace' => null
                ],
                Response::HTTP_OK
            );
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse(
                [
                    'code' => 403,
                    'type' => 'warning',
                    'message' => self::$erreur403,
                    'trace' => null
                ],
                Response::HTTP_OK
            );
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel() ?? 'null';
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
                'trace' => $mesure['erreur'] ?? $mesure['trace'] ?? 'non fournie'
            ]);

            $message = 'Collecte des indicateurs de mesures du projet.';
            return new JsonResponse([
                'code' => $mesure['code'],
                'type' => 'error',
                'message' => $mesure['message'] ?? $message,
                'trace' => $mesure['erreur'] ?? $mesure['trace'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Mesures collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'project_name' => $mesure['data']['project_name']
        ]);

        /** On renvoie toutes les clés du dataset mesures pour que la vue
         *  puisse afficher les valeurs actuelles ET pré-remplir les data-*
         *  avant l'enregistrement dans historique. */
        return new JsonResponse([
            'code' => 200,
            'message' => $mesure['data']
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
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel();
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

        if ($owasp['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte des menaces OWASP du projet.', [
                'code' => $owasp['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $owasp['erreur'] ?? $owasp['trace'] ?? self::$noSpecify
            ]);

            $message = 'Collecte des menaces du projet.';
            return new JsonResponse([
                'code' => $owasp['code'],
                'type' => 'error',
                'message' => $owasp['message'] ?? $message,
                'trace' => $owasp['erreur'] ?? $owasp['trace'] ?? self::$noData
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
                'Nombre de faille OWASP 2017 : ' => $owasp['owasp2017'],
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
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel();
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

        if ($hotspot['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte des menaces potentielles du projet.', [
                'code' => $hotspot['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $hotspot['erreur'] ?? $hotspot['trace'] ?? self::$noSpecify
            ]);

            $message = 'Collecte des menaces potentielles du projet.';
            return new JsonResponse([
                'code' => $hotspot['code'],
                'type' => 'error',
                'message' => $hotspot['message'] ?? $message,
                'trace' => $hotspot['erreur'] ?? $hotspot['trace'] ?? self::$noData
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
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel();
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

        if ($anomalie['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte des anomalies du projet.', [
                'code' => $anomalie['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $anomalie['erreur'] ?? $anomalie['trace'] ?? self::$noSpecify
            ]);

            $message = 'Collecte des anomalies du projet.';
            return new JsonResponse([
                'code' => $anomalie['code'],
                'type' => 'error',
                'message' => $anomalie['message'] ?? $message,
                'trace' => $anomalie['erreur'] ?? $anomalie['trace'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Anomalies collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'violations' => $anomalie['historique']['violations'],
            'nombre_bug' => $anomalie['historique']['nombre_bug'],
            'nombre_vulnerability' => $anomalie['historique']['nombre_vulnerability'],
            'nombre_code_smell' => $anomalie['historique']['nombre_code_smell']
        ]);

        /* MODIF 2026-05-17 : collecte des indicateurs
         * SonarQube 10+ en tâche de fond silencieuse (n'impacte pas la réponse
         * en cas d'échec — SonarQube < 10 ou facettes non supportées). */
        $cleanCode = $this->batchCollecteCleanCode->BatchCollecteCleanCode(
            $data->maven_key,
            'COLLECTE',
            $utilisateur_collecte
        );
        if ($cleanCode['code'] !== 200) {
            $this->logger->warning('[Collecte] ⚠️ Collecte clean code partielle ou non supportée.', [
                'maven_key' => $data->maven_key,
                'code' => $cleanCode['code'],
                'erreur' => $cleanCode['erreur'] ?? ($cleanCode['info'] ?? 'non supporté'),
            ]);
        }

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
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel();
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

        if ($anomalieDetail['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte du détail des anomalies pour le projet.', [
                'code' => $anomalieDetail['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $anomalieDetail['erreur'] ?? $anomalieDetail['trace'] ?? self::$noSpecify
            ]);

            // $anomalieDetail['type'] ==> ['BUG', 'VULNERABILITY', 'CODE_SMELL']
            $message = 'Collecte du détail des anomalies du projet.';
            return new JsonResponse([
                'code' => $anomalieDetail['code'],
                'type' => 'error',
                'message' => $anomalieDetail['message'] ?? $message,
                'trace' => $anomalieDetail['erreur'] ?? $anomalieDetail['trace'] ?? self::$noData
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
        if (
            !is_object($data) ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'menace')
        ) {
            $this->logger->error(
                "[Collecte] ❌ Requête invalide : clé 'maven_key', 'menace', manquante ou JSON mal formé.",
                ['payload' => $data ?? self::$noData]
            );

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel();
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

        if ($hotspotOwasp['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte des menaces OWASP potentielles du projet.', [
                'code' => $hotspotOwasp['code'],
                'maven_key' => $data->maven_key,
                'menace' => $data->menace,
                'trace' => $hotspotOwasp['erreur'] ?? $hotspotOwasp['trace'] ?? self::$noSpecify
            ]);

            $message = 'Collecte des menaces OWASP potentielles du projet.';
            return new JsonResponse([
                'code' => $hotspotOwasp['code'],
                'type' => 'error',
                'message' => $hotspotOwasp['message'] ?? $message,
                'trace' => $hotspotOwasp['erreur'] ?? $hotspotOwasp['trace'] ?? self::$noData
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
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel();
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

        if ($hotspotDetail['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte du détail des menaces potentielles du projet.', [
                'code' => $hotspotDetail['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $hotspotDetail['erreur'] ?? $hotspotDetail['trace'] ?? self::$noSpecify
            ]);

            $message = 'Collecte des menaces potentielles du projet.';
            return new JsonResponse([
                'code' => $hotspotDetail['code'],
                'type' => 'error',
                'message' => $hotspotDetail['message'] ?? $message,
                'trace' => $hotspotDetail['erreur'] ?? $hotspotDetail['trace'] ?? self::$noData
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
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel() ?? 'null';
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

        if ($noSonar['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte des annotations noSonar et suppressWarning du projet.', [
                'code' => $noSonar['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $noSonar['erreur'] ?? $noSonar['trace'] ?? self::$noSpecify
            ]);

            $message = 'Collecte des annotations noSonar et suppressWarning du projet.';
            return new JsonResponse([
                'code' => $noSonar['code'],
                'type' => 'error',
                'message' => $noSonar['message'] ?? $message,
                'trace' => $noSonar['erreur'] ?? $noSonar['trace'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ NoSonar collectées avec succès.', [
            'maven_key' => $data->maven_key,
            'suppress_warning' => $noSonar['historique']['suppress_warning'] ?? 0,
            'total_no_sonar' => $noSonar['historique']['total_no_sonar'] ?? 0,
        ]);

        $nombre = ($noSonar['historique']['suppress_warning'] ?? 0)
                + ($noSonar['historique']['total_no_sonar'] ?? 0);
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
    public function apiCollecteTodo(Request $request): Response
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/collecte/todo");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if (!is_object($data) || !property_exists($data, 'maven_key')) {
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel() ?? 'null';
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

        if ($todo['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte des todos du projet.', [
                'code' => $todo['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $todo['erreur'] ?? $todo['trace'] ?? self::$noSpecify
            ]);

            $message = 'Collecte des todos du projet.';
            return new JsonResponse([
                'code' => $todo['code'],
                'type' => 'error',
                'message' => $todo['message'] ?? $message,
                'trace' => $todo['erreur'] ?? $todo['trace'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Todos collectés avec succès.', [
            'maven_key' => $data->maven_key,
            'todo' => $todo['nombre']
        ]);

        return new JsonResponse([
            'code' => 200,
            'nombre' => $todo['nombre'],
            'message' => 'Données enregistrées dans la table Todo.',
            'historique' => $todo['historique'] ?? null
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
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel();
        $this->logger->info('[Collecte] ℹ️ Début de collecte des informations Actuator du projet JAVA.', [
            'maven_key' => $data->maven_key,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** collecte des to.do présent dans le projet */
        $actuatorInfo = $this->batchCollecteActuator->BatchCollecteActuatorInfo($data->maven_key);

        if ($actuatorInfo['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte des informations Actuator du projet JAVA.', [
                'code' => $actuatorInfo['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $actuatorInfo['erreur'] ?? $actuatorInfo['trace'] ?? self::$noSpecify,
            ]);

            $message = 'Collecte des informations Actuator du projet JAVA.';
            return new JsonResponse([
                'code' => $actuatorInfo['code'],
                'type' => 'error',
                'message' => $actuatorInfo['message'] ?? $message,
                'trace' => $actuatorInfo['erreur'] ?? $actuatorInfo['trace'] ?? self::$noData
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
            $this->logger->error(self::$loggerE400, [
                'payload' => $data ?? self::$noData
            ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning(self::$loggerE403, [
                'maven_key' => $data->maven_key,
                'utilisateur' => $this->security->getUser()?->getUserIdentifier()
            ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => self::$erreur403,
                'trace' => null
            ], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->appUser()->getCourriel();
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

        if ($logger['code'] !== 200) {
            $this->logger->error('[Collecte] ❌ Échec de collecte des loggers du projet.', [
                'code' => $logger['code'],
                'maven_key' => $data->maven_key,
                'erreur' => $logger['erreur'] ?? $logger['trace'] ?? self::$noSpecify,
            ]);

            $message = 'Collecte des loggers du projet JAVA.';
            return new JsonResponse([
                'code' => $logger['code'],
                'type' => 'error',
                'message' => $logger['message'] ?? $message,
                'trace' => $logger['erreur'] ?? $logger['trace'] ?? self::$noData
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Collecte] ℹ️ Loggers collectés avec succès.', [
            'maven_key' => $data->maven_key,
        ]);

        return new JsonResponse([
            'code' => 200,
            'message' => 'Données enregistrées dans la table logger.',
            'data' => $logger['historique'] ?? null
        ], Response::HTTP_OK);
    }
}
