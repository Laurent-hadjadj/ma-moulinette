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

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

/** Les services */
use App\Service\FileLogger;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Historique;

/** Class API Batch */
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

/**
 * [Description BatchController]
 */
class BatchCollecteController extends AbstractController
{
    public static $dateFormat = "Y-m-d H:i:s";
    public static $dateFormatMini = "Y-m-d";
    public static $europeParis = "Europe/Paris";

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
        private Security $security,
        private EntityManagerInterface $em,
        private FileLogger $logger,
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
        private BatchCollecteTodoController $batchCollecteTodo
    ) {
        $this->security = $security;
        $this->em = $em;
        $this->logger = $logger;
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
    }

    #[Route('/api/collectes', name: 'api_collectes', methods: ['POST', 'GET'])]
    public function collecte(Request $request): Response
    {
        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);

        // récupérer les données transmises
        $maven_key = $request->request->get('maven_key');
        $mode_collecte = $request->request->get('mode_collecte');
        /** Si on a pas de POST OK ou de maven_key ou de mode de collecte alors on sort */
        $data = json_decode($request->getContent());

        if ($data !== null) {
            // écraser les données transmises dans l'URL de la requête avec les données transmises dans le corps de la requête HTTP
            $maven_key = $data->maven_key ?? $maven_key;
            $mode_collecte = $data->mode_collecte ?? $mode_collecte;
        }

        $response = new JsonResponse();

        if ($data === null ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'mode_collecte')) {
                $collecte[]=["**** ERREUR : ".static::$erreur400];
            return $response->setData(
                [ 'data' => $data, 'code' => 400, 'message' => static::$erreur400, "Collecte" => $collecte], Response::HTTP_BAD_REQUEST);
        }
        dd('ok');
        /** On initialise la log */
        $collecte=[];

        /** On nettoie les variables du POST */
        $mavenKey = htmlspecialchars($data->maven_key, ENT_QUOTES, 'UTF-8');
        $modeCollecte = htmlspecialchars($data->mode_collecte, ENT_QUOTES, 'UTF-8');

        /** On démarre la mesure du traitement */
        $debutTraitement = new \DateTime();
        $debutTraitement->setTimezone(new \DateTimeZone(static::$europeParis));
        $collecte[]=[
            "********************* DEBUT DU TRAITEMENT ********************",
            "Projet : ".$mavenKey,
            "Date :".$debutTraitement->format(static::$dateFormat)
        ];

        /** On contrôle le mode d'utilisation */
        $utilisateurCollecte = $this->security->getUser()->getCourriel() ?? 'null';

        if ($modeCollecte === 'MANUEL' && $utilisateurCollecte==='null') {
            $collecte = [
                "**** INFORMATION : Mode MANUEL détecté ****",
                "** ERREUR : L'utilisateur n'est pas connu"
            ];
            $trace=$this->logger->file($mavenKey,$collecte);
            /** L'utilisateur n'est pas autorisé */
            return $response->setData(['code' => 401,
                'message' => static::$erreur401, 'collecte' => $collecte, 'trace'=>$trace
            ], Response::HTTP_UNAUTHORIZED);
        }

        /** On récupère les données du projet */
        $mapMerged=[];

        /** Informations du projet (nom, type de version) */
        $informationProjet=$this->batchCollecteInformation->batchCollecteInformation($mavenKey, $modeCollecte, $utilisateurCollecte);

        /** Tout vas bien, on peut lancer la collecte */
        if ($informationProjet['code']===200){
            $collecte[]=['01 - INFORMATION PROJET'=>$informationProjet['message']];
            $mapMerged=array_merge($mapMerged, $informationProjet['data']);
        }

        /** Le projet existe déjà, il est à jour. On arrête la collecte */
        if ($informationProjet['code']===100){
            $collecte[]=['01 - INFORMATION PROJET'=>$informationProjet['message'], $informationProjet['data']];
            return $response->setData(["Collecte" => $collecte]);
        }
        /** Pour les autres messages d'erreur */
        if (!in_array($informationProjet['code'], ['200', '100'])) {
            $collecte[]=[
                "**** ERREUR : INFORMATION PROJET ".$informationProjet['code']. "****",
                $informationProjet['message'] ?? $informationProjet['error']
            ];
            $trace=$this->logger->file($mavenKey, $collecte);
            return $response->setData(["Collecte" => $collecte, 'trace'=>$trace]);
        }

        /** Mesures du projet (ligne de code, couverture, dette, ...) */
        $mesure=$this->batchCollecteMesure->batchCollecteMesure($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($mesure['code']===200){
            $collecte[]=["02 - MESURE" => $mesure['message']];
            $mapMerged=array_merge($mapMerged, $mesure['data']);
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
            $mapMerged=array_merge($mapMerged, $noteReliability['data']);
        } else { $collecte[]=[
                "**** ERREUR : NOTE RELIBILITY".$noteReliability['code'], $noteReliability['message'] ?? $noteReliability['error']
            ];
        }

        $noteSecurity=$this->batchCollecteNote->batchCollecteNote($mavenKey, $modeCollecte, $utilisateurCollecte, 'security');
        if ($noteSecurity['code']===200){
            $collecte[]=["03 - NOTE SECURITY"=> $noteSecurity['message']];
            $mapMerged=array_merge($mapMerged, $noteSecurity['data']);
        } else { $collecte[]=[
                "**** ERREUR : NOTE SECURITY".$noteSecurity['code'], $noteSecurity['message'] ?? $noteSecurity['error']
            ];
        }

        $noteSqale=$this->batchCollecteNote->batchCollecteNote($mavenKey, $modeCollecte, $utilisateurCollecte, 'sqale');
        if ($noteSecurity['code']===200){
            $collecte[]=["03 - NOTE SQALE" => $noteSqale['message']];
            $mapMerged=array_merge($mapMerged, $noteSqale['data']);
        } else {
            $collecte[]=["**** ERREUR : NOTE SQALE".$noteSqale['code'], $noteSqale['message'] ?? $noteSqale['error']
            ];
        }

        /** Signalement des Anomalies pour le projet  */
        $anomalie=$this->batchCollecteAnomalie->batchCollecteAnomalie($mavenKey, $modeCollecte, $utilisateurCollecte, $utilisateurCollecte);
        if ($anomalie['code']===200){
            $collecte[]=["04 - ANOMALIE" => $anomalie['message']];
            $mapMerged=array_merge($mapMerged, $anomalie['data']);
        } else {
                $collecte[]=["**** ERREUR : ANOMALIE ".$anomalie['code'],
                $anomalie['message'] ?? $anomalie['error']
            ];
        }

        /** Signalement du détails des Anomalies pour le projet  */
        $anomalieDetail=$this->batchCollecteAnomalieDetail->BatchCollecteAnomalieDetail($mavenKey, $modeCollecte, $utilisateurCollecte, $utilisateurCollecte);
        if ($anomalieDetail['code']===200){
            $collecte[]=["05 - ANOMALIE DETAIL" => $anomalieDetail['message']];
            $mapMerged=array_merge($mapMerged, $anomalieDetail['data']);
        } else {
                $collecte[]=["**** ERREUR : ANOMALIE DETAIL ".$anomalieDetail['code'],
                $anomalieDetail['message'] ?? $anomalieDetail['error']
            ];
        }
        /** Signalement Hotspots pour le projet  */
        $hotspot=$this->batchCollecteHotspot->batchCollecteHotspot($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($hotspot['code']===200){
            $collecte[]=["06 - HOTSPOT" => $hotspot['message']];
            $mapMerged=array_merge($mapMerged, $hotspot['data']);
        } else {
                $collecte[]=["**** ERREUR : HOTSPOT ".$hotspot['code'],
                $hotspot['message'] ?? $hotspot['error']
            ];
        }

        /** On calcule la note pour les hotspot */
        $noteHotspot=$this->batchCollecteNote->batchCollecteNoteHotspot($mavenKey);
        if ($hotspot['code']===200){
                $collecte[]=["07 - NOTE HOTSPOT" => $noteHotspot['message']];
                $mapMerged=array_merge($mapMerged, $noteHotspot['data']);
            } else {
                    $collecte[]=["**** ERREUR : NOTE HOTSPOT ".$noteHotspot['code'],
                    $noteHotspot['message'] ?? $noteHotspot['error']
                ];
            }

        /** Signalement du détail des Hotspots pour le projet */
        $hotspotDetails=$this->batchCollecteHotspotDetail->batchCollecteHotspotDetail($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($hotspotDetails['code']===200){
            $collecte[]=["08 - HOTSPOT DETAIL" => $hotspotDetails['message']];
        } else {
            $collecte[]=[
                "**** ERREUR : HOTSPOT DETAIL " .$hotspotDetails['code'],
                $hotspotDetails['message'] ?? $hotspotDetails['error']
            ];
        }
        /** Signalement OWASP et nombre d'issue par type pour le projet  */
        $owasp=$this->batchCollecteOwasp->batchCollecteOwasp($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($owasp['code']===200){
            $collecte[]=["09 - OWASP" => $owasp['message']];
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
                $collecte[] = ["10 - HOTSPOT OWASP" . strtoupper($owaspKey) => $hotspotOwasp['message']];
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
            $collecte[]=["11 - NOSONAR" => $noSonar['message']];
            $mapMerged=array_merge($mapMerged, $noSonar['data']);
        } else {
            $collecte[]=[
                    "**** ERREUR : NOSONAR ".$noSonar['code'],
                    $noSonar['message'] ?? $noSonar['error']
                    ];
        }

        /** Signalement des to.do pour le projet */
        $todo=$this->batchCollecteTodo->batchCollecteTodo($mavenKey, $modeCollecte, $utilisateurCollecte);
        if ($todo['code']===200){
            $collecte[]=["12 - TODO" => $todo['message']];
            $mapMerged=array_merge($mapMerged, $todo['data']);
        } else {
            $collecte[]=[
                    "**** ERREUR : TODO ".$todo['code'],
                    $todo['message'] ?? $todo['error']
                ];
        }

        /** On créé un objet date. */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** Consolidation des données */
        $mapMerged=array_merge($mapMerged, [
            'maven_key' => $mavenKey,
            'initial' => 0,
            'mode_collecte'=>$modeCollecte, 'utilisateur_collecte'=>$utilisateurCollecte,
            'date_enregistrement' => $date]);
        /** Enregistrement dans le table historique */
        $historique=$historiqueRepository->insertHistoriqueAjoutProjet($mapMerged);
        if ($historique['code']===200){
            $collecte[]=["13 - HISTORIQUE" => $mapMerged];
        } else {
            $collecte[]=[
                "**** ERREUR : HISTORIQUE ".$historique['code'],
                $historique['message'] ?? $historique['erreur']
            ];
        }

        /** Fin du traitement */
        $finTraitement = new \DateTime();
        $finTraitement->setTimezone(new \DateTimeZone(static::$europeParis));
        $interval = $debutTraitement->diff($finTraitement);
        $temps = $interval->format("%H:%i:%s.%f");
        $collecte[]=[
            "************* FIN DU TRAITEMENT ***************",
            "Date : ".$finTraitement->format(static::$dateFormat),
            "Temps d'exécution : ".$temps,
        ];

        /** Rapport de collecte */
        $trace=$this->logger->file($mavenKey, $collecte);
        return $response->setData(["Collecte" => $collecte, 'trace'=>$trace, Response::HTTP_OK]);
    }

}
