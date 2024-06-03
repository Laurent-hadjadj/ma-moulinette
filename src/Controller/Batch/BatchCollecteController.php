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
use Symfony\Bundle\SecurityBundle\Security;

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
    public static $erreur401 = "Vous devez avoir un compte utilisateur valide  (Erreur 401).";
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
        private Security $security,
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
        $this->security = $security;
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

    #[Route('/collecte', name: 'collecte', methods: ['POST'])]
    public function collecte(Request $request): Response
    {
        $response = new JsonResponse();

        /** On initialise la log */
        $collecte=[];

        /** Si on a pas de POST OK ou de maven_key ou de mode de collecte alors on sort */
        $data = json_decode($request->getContent());
        if ($data === null ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'mode_collecte')) {
                $collecte[]=["**** ERREUR : ".static::$erreur400];
            return $response->setData(
                [ 'data' => $data, 'code' => 400, 'message' => static::$erreur400, "Collecte" => $collecte], Response::HTTP_BAD_REQUEST);
        }

        /** On nettoie les variables du POST */
        $mavenKey = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');
        $modeCollecte = htmlspecialchars($data->mode_collecte, ENT_QUOTES, 'UTF-8');


        /** On contrôle le mode d'utilisation */
        $utilisateurCollecte = $this->security->getUser()->getCourriel() ?? 'null';

        if ($modeCollecte === 'MANUEL' && $utilisateurCollecte==='null') {
            $collecte = [
                "**** INFORMATION : Mode MANUEL détecté ****",
                "** ERREUR : L'utilisateur n'est pas connu"
            ];

            return $response->setData([
                'code' => 401,
                'message' => static::$erreur401,
                'collecte' => $collecte
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** On récupère les données du projet */
        /** Informations du projet (nom, type de version) */
        $informationProjet=$this->batchCollecteInformation->batchCollecteInformation($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($informationProjet['code']===200){
            $collecte[]=['01 - INFORMATION PROJET'=>$informationProjet['message']];
        } else {
            $collecte[]=[
                "**** ERREUR : INFORMATION PROJET ".$informationProjet['code']. "****",
                $informationProjet['message'] ?? $informationProjet['error']
            ];
            return $response->setData(["Collecte" => $collecte]);
        }

        /** Mesures du projet (ligne de code, couverture, dette, ...) */
        $mesure=$this->batchCollecteMesure->batchCollecteMesure($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($mesure['code']===200){
            $collecte[]=["02 - MESURE" => $mesure['message']];
        } else {
            $collecte[]=[
                "**** ERREUR : MESURE ".$mesure['code']. "****",
                $mesure['message'] ?? $mesure['error']
            ];
            return $response->setData(["Collecte" => $collecte]);
        }

        /** Notes du projet  (fiabilité, sécurité, mauvaise pratique) */
        $noteReliability=$this->batchCollecteNote->batchCollecteNote($mavenKey, $modeCollecte, $utilisateurCollecte, 'reliability');
        if ($noteReliability['code']===200){
            $collecte[]=["03 - NOTE RELIABILITY" => $noteReliability];
        } else { $collecte[]=[
                "**** ERREUR : NOTE RELIBILITY".$noteReliability['code'], $noteReliability['message'] ?? $noteReliability['error']
            ];
        }

        $noteSecurity=$this->batchCollecteNote->batchCollecteNote($mavenKey, $modeCollecte, $utilisateurCollecte, 'security');
        if ($noteSecurity['code']===200){
            $collecte[]=["03 - NOTE SECURITY"=> $noteSecurity['message']];
        } else { $collecte[]=[
                "**** ERREUR : NOTE SECURITY".$noteSecurity['code'], $noteSecurity['message'] ?? $noteSecurity['error']
            ];
        }

        $noteSqale=$this->batchCollecteNote->batchCollecteNote($mavenKey, $modeCollecte, $utilisateurCollecte, 'sqale');
        if ($noteSecurity['code']===200){
            $collecte[]=["03 - NOTE SQALE" => $noteSqale['message']];
        } else {
             $collecte[]=["**** ERREUR : NOTE SQALE".$noteSqale['code'], $noteSqale['message'] ?? $noteSqale['error']
            ];
        }

        /** Signalement Anomalies pour le projet  */
        $anomalie=$this->batchCollecteAnomalie->batchCollecteAnomalie($mavenKey, $modeCollecte, $utilisateurCollecte, $utilisateurCollecte);
        if ($anomalie['code']===200){
            $collecte[]=["04 - ANOMALIE" => $anomalie['message']];
        } else {
                $collecte[]=["**** ERREUR : ANOMALIE ".$anomalie['code'],
                $anomalie['message'] ?? $anomalie['error']
            ];
        }

        /** Signalement Hotspots pour le projet  */
        $hotspot=$this->batchCollecteHotspot->batchCollecteHotspot($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($hotspot['code']===200){
            $collecte[]=["05 - HOTSPOT" => $hotspot['message']];
        } else {
                $collecte[]=["**** ERREUR : HOTSPOT ".$hotspot['code'],
                $hotspot['message'] ?? $hotspot['error']
            ];
        }

        /** Signalement du détail des Hotspots pour le projet */
        $hotspotDetails=$this->batchCollecteHotspotDetail->batchCollecteHotspotDetail($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($hotspotDetails['code']===200){
            $collecte[]=["06 - HOTSPOT DETAIL" => $hotspotDetails['message']];
        } else {
            $collecte[]=[
                "**** ERREUR : HOTSPOT DETAIL " .$hotspotDetails['code'],
                $hotspotDetails['message'] ?? $hotspotDetails['error']
            ];
        }

        /** Signalement OWASP et nombre d'issue par type pour le projet  */
        $owasp=$this->batchCollecteOwasp->batchCollecteOwasp($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($owasp['code']===200){
            $collecte[]=["07 - OWASP" => $owasp['message']];
        } else {
            $collecte[]=[
                "**** ERREUR : OWASP ".$owasp['code'],
                $owasp['message'] ?? $owasp['error']
            ];
        }

        /** Signalement HotspotOwasp pour le projet */
        $owaspKeys = ['a0', 'a1', 'a2', 'a3', 'a4', 'a5', 'a6', 'a7', 'a8', 'a9', 'a10'];
        foreach ($owaspKeys as $owaspKey) {
            $hotspotOwasp = $this->batchCollecteHotspotOwasp->batchCollecteHotspotOwasp($mavenKey, $modeCollecte, $utilisateurCollecte, $owaspKey);
            if ($hotspotOwasp['code'] === 200) {
                $collecte[] = ["08 - HOTSPOT OWASP" . strtoupper($owaspKey) => $hotspotOwasp['message']];
            } else {
                $collecte[]=[
                    "**** ERREUR : HOTSPOT OWASP ".$hotspotOwasp['code'],
                    $hotspotOwasp['message'] ?? $hotspotOwasp['error']
                ];
            }
        }

        /** Signalement NoSonar et suppressWarning pour les projets Java */
        $noSonar=$this->batchCollecteNoSonar->batchCollecteNoSonar($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($noSonar['code']===200){
            $collecte[]=["09 - NOSONAR" => $noSonar['message']];
        } else {
            $collecte[]=[
                    "**** ERREUR : NOSONAR ".$noSonar['code'],
                    $noSonar['message'] ?? $noSonar['error']
                    ];
        }

        /** Signalement des to.do pour le projet */
        $todo=$this->batchCollecteTodo->batchCollecteTodo($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($todo['code']===200){
            $collecte[]=["11 - TODO" => $todo['message']];
        } else {
            $collecte[]=[
                    "**** ERREUR : TODO ".$todo['code'],
                    $todo['message'] ?? $todo['error']
                ];
        }

        /** Rapport de collecte */
        return $response->setData(["Collecte" => $collecte, Response::HTTP_OK]);

    }

}
