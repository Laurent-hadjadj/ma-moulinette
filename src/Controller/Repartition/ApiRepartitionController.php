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

namespace App\Controller\Repartition;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Exception;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Repartition;
use App\Entity\RepartitionTemp;
use App\Controller\Batch\BatchCollecteRepartitionController;

/**
 * [Description ApiRepartitionController]
 */
class ApiRepartitionController extends AbstractController
{
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    private static $loggerE403 = "[Répartition-Module] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_COLLECTE).";

    /**
     * [Description for __construct]
     *
     * Created at: 04/12/2022, 09:00:38 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private BatchCollecteRepartitionController $batchCollecteRepartition,
        private LoggerInterface $logger,
        private Security $security,
    ) {
        $this->batchCollecteRepartition = $batchCollecteRepartition;
    }

    /**
     * [Description for projetRepartitionCollecte]
     * Calcul la répartition entre front, back et autre pour tout les type et les sévérités.
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 04/12/2022, 09:04:35 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/repartition/collecte', name: 'api_repartition_collecte', methods: ['PUT'])]
    public function apiRepartitionCollecte(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/repartition/collecte");

        $user = $this->security->getUser();

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null ||
            !isset($data->maven_key, $data->category, $data->severity, $data->setup)) {
            $this->logger->error("[Répartition-Collecte] ❌ Requête invalide : clé 'maven_key', 'category', 'severity' ou 'setup' manquante ou JSON mal formé.", [ 'payload' => $data ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->error(static::$loggerE403, [ 'user' => $user ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403
            ], Response::HTTP_OK);
        }

        $repartitionCollecte = $this->batchCollecteRepartition->batchCollecteRepartition($data->maven_key,  $data->category, $data->severity, $data->setup);

        if ($repartitionCollecte['code'] !== 200){
            $this->logger->error("[Répartition-Collecte] ❌ Échec de la répartition par module (batchCollecteRepartition).", [
                'maven_key' => $data->maven_key,
                'category' => $data->category,
                'severity' => $data->severity,
                'setup' => $data->setup
            ]);

            return new JsonResponse([
                'code' => $repartitionCollecte['code'],
                'type' => $repartitionCollecte['type'] ?? 'alert',
                'message' => $repartitionCollecte['message'] ?? $repartitionCollecte['erreur']
            ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'code' => 200,
            'total' => $repartitionCollecte['data']['total'],
            'category' => $data->category,
            'severity' => $data->severity,
            'setup' => $data->setup,
            'temps' => $repartitionCollecte['data']['temps']
        ], Response::HTTP_OK);
    }

    /**
     * [Description for projetRepartitionAnalyse]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 04/12/2022, 09:05:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/repartition/analyse', name: 'repartition_analyse', methods: ['PUT'])]
    public function apiRepartitionAnalyse(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/repartition/analyse");

        $user = $this->security->getUser();

        // Instanciation des repositories
        $repartitionTempRepos = $this->em->getRepository(RepartitionTemp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null ||
            !isset($data->maven_key, $data->category, $data->severity, $data->setup)) {
            $this->logger->error("[Répartition-Analyse] ❌ Requête invalide : clé 'maven_key', 'category', 'severity' ou 'setup' manquante ou JSON mal formé.", [ 'payload' => $data ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->error(static::$loggerE403, [ 'user' => $user ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403
            ], Response::HTTP_OK);
        }

        /** On vérifie qu'une collecte a été lancé pour ce setup */
        $checkIfExistSetup = $repartitionTempRepos->findOneBy(['setup' => $data->setup]);

        if(is_null($checkIfExistSetup)){
            $this->logger->warning("[Répartition Analyse] ⚠️ La collecte n'a pas été lancée pour ce setup (Erreur 404).", [
                'maven_key' => $data->maven_key,
                'setup' => $data->setup
            ]);

            return new JsonResponse([
                    'code' => 404,
                    'type' => 'warning',
                    'message' => "La collecte n'a pas été lancée pour ce setup (Erreur 404).",
                    'trace' => null
                ]);
        }

        /** on retourne un code 202 pour lancer l'analyse */
        if ($data->category === 'CHECK') {
            $this->logger->info("[Répartition-Analyse] 📌 Il existe des données collectées pour ce setup.",[
                    'maven_key', $data->maven_key,
                    'setup' => $data->setup
                ]);

            /** On lance l'analyse par catégorie */
            return new JsonResponse([
                'code' => 202,
                'category' => 'CHECK'
            ], Response::HTTP_OK);
        }

        //BUG BLOCKER "1754316568042" "fr.ma-moulinette:ma-moulinette"
        try {
                $repartitionAnalyse = $this->batchCollecteRepartition->batchCollecteRepartitionAnalyse(
                    $data->maven_key, $data->category, $data->severity, $data->setup);

                if ($repartitionAnalyse['code'] !== 200){
                    $this->logger->error("[Répartition-Analyse] ❌ Échec de la collecte des anomalies.", [
                        'maven_key' => $data->maven_key,
                        'category' => $data->category,
                        'severity' => $data->severity,
                        'setup' => $data->setup
                    ]);

                    return new JsonResponse([
                        'code' => $repartitionAnalyse['code'],
                        'type' => $repartitionAnalyse['type'] ?? 'alert',
                        'message' => $repartitionAnalyse['message'] ?? $repartitionAnalyse['erreur']
                    ], Response::HTTP_OK);
                }

                return new JsonResponse([
                    'code' => 200,
                    'frontend' => $repartitionAnalyse['frontend'],
                    'backend' => $repartitionAnalyse['backend'],
                    'autre' => $repartitionAnalyse['autre'],
                    'inconnu' => $repartitionAnalyse['inconnu'],
                    'total' => $repartitionAnalyse['total'],
                    'mode' => 'Manuel',
                ], Response::HTTP_OK);
        } catch (Exception $trace) {
            $this->logger->critical("[Répartition-Analyse] 🔴 Exception lors du traitement de répartition des anomalies par module (Erreur 500).", [
                'exception' => $trace->getMessage()
            ]);

            $message = "Une erreur lors du calcul de la répartition pour la catégorie <strong>$data->category</strong> a été rencontrée (Erreur 500).";
            return new JsonResponse([
                'code' => 500,
                'type' => 'alert',
                'message' => $message,
                'trace' => $trace->getMessage()
            ]);
        }
    }

    /**
     * [Description for apiRepartitionHistorique]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 01/09/2025 11:19:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/repartition/historique', name: 'repartition_historique', methods: ['PUT'])]
    public function apiRepartitionHistorique(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/repartition/historique");

        $user = $this->security->getUser();

        // Instanciation des repositories
        $repartitionRepos = $this->em->getRepository(Repartition::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null ||
            !isset($data->maven_key)) {
            $this->logger->error("[Répartition-Analyse] ❌ Requête invalide : clé 'maven_key' manquante ou JSON mal formé.", [ 'payload' => $data ]);

            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400,
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->error(static::$loggerE403, [ 'user' => $user ]);

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403
            ], Response::HTTP_OK);
        }

        /** On récupère la dernière analyse complète disponible pour le projet */
        $checkIfExistHistorique = $repartitionRepos->findLatestMavenKeyWithControl($data->maven_key);

        if ($checkIfExistHistorique['code'] != 200){
            $this->logger->error("[Répartition-Analyse] ❌ Échec de récupération des données de répartition.",
            ['maven_key', $data->maven_key]);

            return new JsonResponse([
                'code' => $checkIfExistHistorique['code'],
                'type' => 'alert',
                'message' => "Échec de récupération des données de répartition (Erreur {$checkIfExistHistorique['code']}).",
                'trace' => $checkIfExistHistorique['erreur']
            ], Response::HTTP_OK);
        }

        /** Si on à une result avec un résultat, alors on a des données alors on affiche les résultats déjà présent dans la table répartition. */
        if (isset($checkIfExistHistorique['result']) && $checkIfExistHistorique['result'] === []){
            $message = "Il n'y a pas de données historisées disponibles. Vous devez lancer une nouvelle collecte et lancer une analyse (Erreur 404).";

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => $message,
            ],  Response::HTTP_OK);
        }

        /** On a des données à envoyer */
        $data = $checkIfExistHistorique['result'][0];
        $date = (new \DateTime($data['date_enregistrement']))->format('Y-m-d H:i');
        $message = "Récupération des données de répartition pour le setup <strong>{$data['setup']}</strong> en date du <strong>{$date}</strong>";

        return new JsonResponse([
            'code' => 201,
            'type' => 'primary',
            'message' => $message,
            'data' => $data,
            'mode' => 'Historique',
            'date_enregistrement' => $date
        ],  Response::HTTP_OK);
    }

    /**
     * [Description for apiRepartitionAnalyseMaj]
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 18/02/2025 09:14:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/repartition/analyse/mise-a-jour', name: 'repartition_analyse_maj', methods: ['PUT'])]
    public function apiRepartitionAnalyseMaj(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/repartition/analyse/mise-a-jour");

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null ||
            !isset($data->maven_key, $data->setup, $data->calcul)) {
            $this->logger->error("[Répartition-Mise-a-jour] ❌  Requête invalide : clé 'maven_key', 'setup' ou 'calcul' manquante ou JSON mal formé.", [
                'payload' => $data
            ]);

            return new JsonResponse([
                'data' => $data,
                'code' => 400,
                'type' => 'alert',
                'message' => static::$erreur400
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            $this->logger->warning("[Répartition-Mise-a-jour] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_COLLECTE).");

            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$erreur403
            ], Response::HTTP_OK);
        }

        /** Calcul = category, severity, frontend(2), backend(3), autre(4), inconnu(5) */
        $repartitionMaJ = $this->batchCollecteRepartition->batchCollecteRepartitionMaJ($data->maven_key,  $data->calcul, $data->setup);

        if ($repartitionMaJ['code'] !== 200){
            $this->logger->error('[Répartition-Mise-a-jour] ❌ Échec de la mise à jour des informations de répartition.', [
                'maven_key' => $data->maven_key,
                'calcul' => $data->calcul,
                'setup' => $data->setup
            ]);

            return new JsonResponse([
                'code' => $repartitionMaJ['code'],
                'type' => $repartitionMaJ['type'] ?? 'alert',
                'message' => $repartitionMaJ['message'] ?? $repartitionMaJ['erreur']
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Répartition-Mise-a-jour] ℹ️ Enregistrement des données effectué.');

        return new JsonResponse(
            [
                'code' => 200,
                'message' => $repartitionMaJ['message']
            ], Response::HTTP_OK);
    }

}
