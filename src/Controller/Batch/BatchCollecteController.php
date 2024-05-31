<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Batch;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

// Gestion de accès aux API
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** gestion du journal d'activité */
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Finder\Finder;

/** Class API Batch */
use App\Controller\Batch\BatchCollecteInformationProjetController;
use App\Controller\Batch\BatchCollecteMesureController;
use App\Controller\Batch\BatchCollecteNoteController;
use App\Controller\Batch\BatchCollecteOwaspController;
use App\Controller\Batch\BatchCollecteHotspotController;
use App\Controller\Batch\BatchCollecteAnomalieController;
use App\Controller\Batch\BatchCollecteHotspotOwaspController;
use App\Controller\Batch\BatchCollecteHotspotDetailController;
use App\Controller\Batch\BatchCollecteNoSonarController;
use App\Controller\Batch\BatchCollecteTodoController;

use App\Service\FileLogger;

/**
 * [Description BatchController]
 */
class BatchCollecteController extends AbstractController
{
    public static $dateFormat = "Y-m-d H:i:s";
    public static $dateFormatMini = "Y-m-d";
    public static $timeFormat = "%H:%I:%S";

    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur404 = "L'appel à l'API n'a pas abouti (Erreur 404).";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private FileLogger $logger,
        private BatchCollecteInformationProjetController $batchCollecteInformation,
        private BatchCollecteMesureController $batchCollecteMesure,
        private BatchCollecteNoteController $batchCollecteNote,
        private BatchCollecteOwaspController $batchCollecteOwasp,
        private BatchCollecteHotspotController $batchCollecteHotspot,
        private BatchCollecteAnomalieController $batchCollecteAnomalie,
        private BatchCollecteHotspotOwaspController $batchCollecteHotspotOwasp,
        private BatchCollecteHotspotDetailController $batchCollecteHotspotDetail,
        private BatchCollecteNoSonarController $batchCollecteNoSonar,
        private BatchCollecteTodoController $batchCollecteTodo
    ) {
        $this->logger = $logger;
        $this->batchCollecteInformation = $batchCollecteInformation;
        $this->batchCollecteMesure = $batchCollecteMesure;
        $this->batchCollecteNote = $batchCollecteNote;
        $this->batchCollecteOwasp = $batchCollecteOwasp;
        $this->batchCollecteHotspot = $batchCollecteHotspot;
        $this->batchCollecteAnomalie = $batchCollecteAnomalie;
        $this->batchCollecteHotspotOwasp = $batchCollecteHotspotOwasp;
        $this->batchCollecteHotspotDetail = $batchCollecteHotspotDetail;
        $this->batchCollecteNoSonar = $batchCollecteNoSonar;
        $this->batchCollecteTodo = $batchCollecteTodo;
    }

        //await projetTodo(idProject);                  /*(12)*/
    #[Route('/collecte', name: 'collecte', methods: ['POST'])]
    public function collecte(Request $request): Response
    {
        $response = new JsonResponse();

        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key')) {
            return $response->setData(
                [ 'data' => $data, 'code' => 400, 'message' => static::$erreur400
            ], Response::HTTP_BAD_REQUEST);
        }

        /** On récupère les données du projet */
        $collecte=[];
        /** Informations du projet (nom, type de version) */
        $informationProjet=$this->batchCollecteInformation->batchCollecteInformation($data->maven_key);
        if ($informationProjet['code']===200){
            $collecte[]=['informationProjet'=>$informationProjet['message']];
        }

        /** Mesures du projet (ligne de code, couverture, dette, ...) */
        $mesure=$this->batchCollecteMesure->batchCollecteMesure($data->maven_key);
        if ($mesure['code']===200){
            $collecte[]=["Mesures" => $mesure['message']];
        }

        /** Notes du projet  (fiabilité, sécurité, mauvaise pratique) */
        $noteReliability=$this->batchCollecteNote->batchCollecteNote($data->maven_key, 'reliability');
        if ($noteReliability['code']===200){
            $collecte[]=["note_reliability" => $noteReliability];
        }
        $noteSecurity=$this->batchCollecteNote->batchCollecteNote($data->maven_key, 'security');
        if ($noteSecurity['code']===200){
            $collecte[]=["note_security"=> $noteSecurity['message']];
        }
        $noteSqale=$this->batchCollecteNote->batchCollecteNote($data->maven_key, 'sqale');
        if ($noteSecurity['code']===200){
            $collecte[]=["note_sqale" => $noteSqale['message']];
        }

        /** Signalement Anomalies pour le projet  */
        $anomalie=$this->batchCollecteAnomalie->batchCollecteAnomalie($data->maven_key);
        if ($anomalie['code']===200){
            $collecte[]=["Anomalie" => $anomalie['message']];
        }

        /** Signalement Hotspots pour le projet  */
        $hotspot=$this->batchCollecteHotspot->batchCollecteHotspot($data->maven_key);
        if ($hotspot['code']===200){
            $collecte[]=["Hotspot" => $hotspot['message']];
        } else { $collecte[]=["**** Erreur : Hotspot ****" => [$hotspot['code'], $hotspot['message'] ?? $hotspot['error']]]; }
        //: "oops j'ai oublié de mettre un message pour les développeurs !!!"

        /** Signalement du détail des Hotspots pour le projet */
        $hotspotDetails=$this->batchCollecteHotspotDetail->batchCollecteHotspotDetail($data->maven_key);
        if ($hotspotDetails['code']===200){
            $collecte[]=["Hotspot Détail" => $hotspotDetails['message']];
        } else { $collecte[]=["**** Erreur : Hotspot Détail ****" => [$hotspotDetails['code'],$hotspotDetails['message'] ?? $hotspotDetails['error']]]; }

        /** Signalement OWASP et nombre d'issue par type pour le projet  */
        $owasp=$this->batchCollecteOwasp->batchCollecteOwasp($data->maven_key);
        if ($owasp['code']===200){
            $collecte[]=["Owasp" => $owasp['message']];
        }

        /** Signalement HotspotOwasp pour le projet */
        $owaspKeys = ['a0', 'a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8', 'a9', 'a10'];
        foreach ($owaspKeys as $owaspKey) {
            $hotspotOwasp = $this->batchCollecteHotspotOwasp->batchCollecteHotspotOwasp($data->maven_key, $owaspKey);
            if ($hotspotOwasp['code'] === 200) {
                $collecte[] = ["Hotspot-Owasp " . strtoupper($owaspKey) => $hotspotOwasp['message']];
            } else {
                $collecte[]=["**** Erreur : Hotspots OWASP **** " => [$hotspot['code'], $hotspot['message'] ?? $hotspot['error']]];
            }
        }

        /** Signalement NoSonar et suppressWarning pour les projets Java */
        $noSonar=$this->batchCollecteNoSonar->batchCollecteNoSonar($data->maven_key);
        if ($noSonar['code']===200){
            $collecte[]=["NoSonar" => $noSonar['message']];
        } else {
            $collecte[]=["**** Erreur : NoSonar **** " => [$noSonar['code'], $noSonar['message'] ?? $noSonar['error']]];
        }

        /** Signalement des to.do pour le projet */
        $todo=$this->batchCollecteTodo->batchCollecteTodo($data->maven_key);
        if ($todo['code']===200){
            $collecte[]=["Todo" => $todo['message']];
        } else {
            $collecte[]=["**** Erreur : Todo **** " => [$todo['code'], $todo['message'] ?? $todo['error']]];
        }

        /** Rapport de collecte */
        return $response->setData(["Collecte" => $collecte, Response::HTTP_OK]);

    }

}
