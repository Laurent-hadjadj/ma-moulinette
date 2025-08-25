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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Controller\Batch\BatchCollecteRepartitionController;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * [Description ApiRepartitionController]
 */
class ApiRepartitionController extends AbstractController
{
    private static $reference = "<strong>[Répartition-Module]</strong> ";
    private static $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";

    /**
     * [Description for __construct]
     *
     * Created at: 04/12/2022, 09:00:38 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private BatchCollecteRepartitionController $batchCollecteRepartition
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
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null ||
            !isset($data->maven_key, $data->category, $data->severity, $data->setup)) {
            return new JsonResponse(
                ['data' => $data, 'code' => 400, 'type' => 'alert',
                'message' => static::$reference . static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['code' => 403, 'type' => 'warning',
                'message' => static::$reference . static::$erreur403], Response::HTTP_OK);
        }

        $repartitionCollecte = $this->batchCollecteRepartition->batchCollecteRepartition($data->maven_key,  $data->category, $data->severity, $data->setup);

        if ($repartitionCollecte['code'] !== 200){
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
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null ||
            !isset($data->maven_key, $data->category, $data->severity, $data->setup)) {
            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message' => static::$reference . static::$erreur400
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$reference . static::$erreur403
            ], Response::HTTP_OK);
        }

        //BUG BLOCKER "1754316568042" "fr.ma-moulinette:ma-moulinette"
        $repartitionAnalyse = $this->batchCollecteRepartition->batchCollecteRepartitionAnalyse($data->maven_key, $data->category, $data->severity, $data->setup);

        if ($repartitionAnalyse['code'] !== 200){
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
            'inconnu' => $repartitionAnalyse['inconnu']
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
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null ||
            !isset($data->maven_key, $data->setup, $data->calcul)) {
            return new JsonResponse([
                'data' => $data,
                'code' => 400,
                'type' => 'alert',
                'message' => static::$reference . static::$erreur400
            ], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$reference . static::$erreur403
            ], Response::HTTP_OK);
        }

        $repartitionMaJ = $this->batchCollecteRepartition->batchCollecteRepartitionMaJ($data->maven_key,  $data->calcul, $data->setup);

        if ($repartitionMaJ['code'] !== 200){
            return new JsonResponse([
                'code' => $repartitionMaJ['code'],
                'type' => $repartitionMaJ['type'] ?? 'alert',
                'message' => $repartitionMaJ['message'] ?? $repartitionMaJ['erreur']
            ], Response::HTTP_OK);
        }

        return new JsonResponse(
            [
                'code' => 200,
                'message' => $repartitionMaJ['message']
            ], Response::HTTP_OK);
    }

}
