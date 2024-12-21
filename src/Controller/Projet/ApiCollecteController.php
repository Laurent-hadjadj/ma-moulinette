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

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Bundle\SecurityBundle\Security;

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

/** Collecte */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * [Description ApiMesureController]
 */
class ApiCollecteController extends AbstractController
{
    /** Définition des constantes */
    public static $reference = "[Projet]";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";

    private $security;
    private $batchCollecteMesure;
    private $batchCollecteNote;
    private $batchCollecteOwasp;
    private $batchCollecteHotspot;
    private $batchCollecteAnomalie;
    private $batchCollecteAnomalieDetail;
    private $batchCollecteHotspotOwasp;
    private $batchCollecteHotspotDetail;
    private $batchCollecteNoSonar;
    private $batchCollecteTodo;
    private $batchCollecteActuator;
    private $batchCollecteInformation;
    private $batchCollecteLogger;

    public function __construct(
        BatchCollecteInformationProjetController $batchCollecteInformation,
        BatchCollecteMesureController $batchCollecteMesure,
        BatchCollecteNoteController $batchCollecteNote,
        BatchCollecteOwaspController $batchCollecteOwasp,
        BatchCollecteHotspotController $batchCollecteHotspot,
        BatchCollecteAnomalieController $batchCollecteAnomalie,
        BatchCollecteAnomalieDetailController $batchCollecteAnomalieDetail,
        BatchCollecteHotspotOwaspController $batchCollecteHotspotOwasp,
        BatchCollecteHotspotDetailController $batchCollecteHotspotDetail,
        BatchCollecteNoSonarController $batchCollecteNoSonar,
        BatchCollecteTodoController $batchCollecteTodo,
        BatchCollecteActuatorController $batchCollecteActuator,
        BatchCollecteLoggerController $batchCollecteLogger,
        Security $security,
    ) {
        $this->batchCollecteInformation = $batchCollecteInformation;
        $this->batchCollecteMesure = $batchCollecteMesure;
        $this->batchCollecteNote = $batchCollecteNote;
        $this->batchCollecteOwasp = $batchCollecteOwasp;
        $this->batchCollecteHotspot = $batchCollecteHotspot;
        $this->batchCollecteAnomalie = $batchCollecteAnomalie;
        $this->batchCollecteAnomalieDetail = $batchCollecteAnomalieDetail;
        $this->batchCollecteHotspotOwasp = $batchCollecteHotspotOwasp;
        $this->batchCollecteHotspotDetail = $batchCollecteHotspotDetail;
        $this->batchCollecteNoSonar = $batchCollecteNoSonar;
        $this->batchCollecteTodo = $batchCollecteTodo;
        $this->batchCollecteActuator = $batchCollecteActuator;
        $this->batchCollecteLogger = $batchCollecteLogger;
        $this->security = $security;
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
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return new JsonResponse(
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** Information générales sur le projet */
        $information=$this->batchCollecteInformation->batchCollecteInformation($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($information['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $information['code'],
                'reference' => static::$reference,
                'message' => $information['message'] ?? $information['erreur']],
                Response::HTTP_OK);
        }
        return new JsonResponse(['code' => 200, 'message' => [
            'projet'=>$information['message']['projet'],
            'release'=>$information['message']['release'],
            'snapshot'=>$information['message']['snapshot'],
            'autre'=>$information['message']['autre']]],
            Response::HTTP_OK);
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
            return new JsonResponse(
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400],
                Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** Mesures du projet (ligne de code, coverage, dette, ...) */
        $mesure=$this->batchCollecteMesure->batchCollecteMesure($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($mesure['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $mesure['code'],
                'reference' => static::$reference,
                'message' => $mesure['message'] ?? $mesure['erreur']], Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200, 'message'=>['issues'=>$mesure['message']['issues']]], Response::HTTP_OK);
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
        if ($data === null ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'type')) {
            return new JsonResponse(
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** Notes du projet  (fiabilité, sécurité, mauvaise pratique) */
        $note=$this->batchCollecteNote->batchCollecteNote($data->maven_key, 'COLLECTE', $utilisateur_collecte, $data->type);
        if ($note['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $note['code'],
                'reference' => static::$reference,
                'message' => $note['message'] ?? $note['erreur']],
                Response::HTTP_OK);
        }
        return new JsonResponse(['code' => 200, 'type' => $data->type,
        'message'=> ['note' => $note['message']['value']]], Response::HTTP_OK);
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
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** Signalement des Anomalies pour le projet */
        $owasp=$this->batchCollecteOwasp->batchCollecteOwasp($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($owasp['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $owasp['code'],
                'reference' => static::$reference,
                'message' => $owasp['message'] ?? $owasp['erreur']],
                Response::HTTP_OK);
        }
        return new JsonResponse(['code' => 200, 'owasp2017' => $owasp['owasp2017'], 'owasp2021' => $owasp['owasp2021'], 'message'=>['Nombre de faille OWASP 2017 : ' => $owasp['owasp2017'], 'Nombre de faille OWASP 2021 : ' => $owasp['owasp2021']]], Response::HTTP_OK);
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
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** Signalement Hotspots pour le projet */
        $hotspot=$this->batchCollecteHotspot->batchCollecteHotspot($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($hotspot['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $hotspot['code'],
                'reference' => static::$reference,
                'message' => $hotspot['message'] ?? $hotspot['erreur']], Response::HTTP_OK);
        }

        return new JsonResponse([
                'code' => 200, 'nombre'=>$hotspot['data']['nombre_hotspot'],
                'message'=>[
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
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** Signalement des Anomalies pour le projet */
        $anomalie=$this->batchCollecteAnomalie->BatchCollecteAnomalie($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($anomalie['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $anomalie['code'],
                'reference' => static::$reference,
                'message' => $anomalie['message'] ?? $anomalie['erreur']], Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200, 'info'=>$anomalie['info'],'message'=>[
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
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** collecte des signalements du détail des Anomalies pour le projet */
        $anomalieDetail=$this->batchCollecteAnomalieDetail->BatchCollecteAnomalieDetail($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($anomalieDetail['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $anomalieDetail['code'],
                'reference' => static::$reference,
                'message' => $anomalieDetail['message'] ?? $anomalieDetail['erreur']], Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200, 'message'=>['chargement des anomalies details']], Response::HTTP_OK);
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
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        $hotspotOwasp=$this->batchCollecteHotspotOwasp->BatchCollecteHotspotOwasp($data->maven_key, 'COLLECTE', $utilisateur_collecte, $data->menace);

        if ($hotspotOwasp['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $hotspotOwasp['code'],
                'reference' => static::$reference,
                'message' => $hotspotOwasp['message'] ?? $hotspotOwasp['erreur']], Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200, 'info' => $hotspotOwasp['info'],
        'owasp2017'=>$hotspotOwasp['owasp_2017'] ?? 'nc',
        'owasp2021'=>$hotspotOwasp['owasp_2021'] ?? 'nc',
        'message'=>$hotspotOwasp['message']], Response::HTTP_OK);
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
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** collecte du détail des hotspots pour le projet */
        $hotspotDetail=$this->batchCollecteHotspotDetail->BatchCollecteHotspotDetail($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($hotspotDetail['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $hotspotDetail['code'],
                'reference' => static::$reference,
                'message' => $hotspotDetail['message'] ?? $hotspotDetail['erreur']], Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200, 'nombre'=>count($hotspotDetail),'message'=>''], Response::HTTP_OK);
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
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** collecte des suppressWarning et noSonar du projet */
        $noSonar=$this->batchCollecteNoSonar->BatchCollecteNoSonar($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($noSonar['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $noSonar['code'],
                'reference' => static::$reference,
                'message' => $noSonar['message'] ?? $noSonar['erreur']], Response::HTTP_OK);
        }

        $nombre=$noSonar['message']['suppress_warning']??0 + $noSonar['message']['no_sonar']??0;
        return new JsonResponse(['code' => 200, 'nombre' => $nombre, 'message'=>[
            'suppress_warning' => $noSonar['message']['suppress_warning']??0,
            'no_sonar' => $noSonar['message']['no_sonar']??0]], Response::HTTP_OK);
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
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** collecte des to.do présent dans le projet */
        $todo=$this->batchCollecteTodo->BatchCollecteTodo($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($todo['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $todo['code'],
                'reference' => static::$reference,
                'message' => $todo['message'] ?? $todo['erreur']], Response::HTTP_OK);
        }
        return new JsonResponse(['code' => 200, 'nombre' => $todo['nombre'], 'message'=>''], Response::HTTP_OK);
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
        if ($data === null ||
            !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** collecte des to.do présent dans le projet */
        $actuatorInfo=$this->batchCollecteActuator->BatchCollecteActuatorInfo($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($actuatorInfo['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $actuatorInfo['code'],
                'reference' => static::$reference,
                'message' => $actuatorInfo['message'] ?? $actuatorInfo['erreur']], Response::HTTP_OK);
        }
        return new JsonResponse(['code' => 200, 'message'=>[
            'json' => $actuatorInfo['json']]], Response::HTTP_OK);
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
        if ($data === null ||
            !property_exists($data, 'maven_key')) {
            return new JsonResponse(
                ['data'=>$data,'code'=>400, 'type'=>'alert',
                'reference'=> static::$reference, 'message'=> static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse(
                ['type'=>'warning', 'code' => 403, 'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On contrôle le mode d'utilisation */
        $utilisateur_collecte = $this->security->getUser()->getCourriel() ?? 'null';

        /** collecte des Logger présent dans le projet */
        $logger=$this->batchCollecteLogger->BatchCollecteLogger($data->maven_key, 'COLLECTE', $utilisateur_collecte);
        if ($logger['code']!=200){
            return new JsonResponse([
                'type' => 'erreur', 'code' => $logger['code'],
                'reference' => static::$reference,
                'message' => $logger['message'] ?? $logger['erreur']], Response::HTTP_OK);
        }
        return new JsonResponse(['code' => 200, 'message'=>$logger['data']], Response::HTTP_OK);
    }
}
