<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller\Projet;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\JsonResponse;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Owasp;
use App\Entity\InformationProjet;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description ApiOwaspController]
 */
class ApiOwaspController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $apiIssuesSearch = "/api/issues/search?componentKeys=";
    public static $removeReturnline = "/\s+/u";
    public static $reference = "<strong>[PROJET-011]</strong>";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur401 = "Erreur d\'Authentification. La clé n\'est pas correcte (Erreur 401).";
    public static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    public static $erreur404 = "L'appel à l'API n'a pas abouti (Erreur 404).";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em
    ) {
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * [Description for issuesOwasp]
     * Récupère le top 10 OWASP
     * http://{url}/api/issues/search?componentKeys={key}&facets=owaspTop10&owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10
     * Attention une faille peut être comptée deux fois ou plus, cela dépend du tag.
     * Donc il  est possible d'avoir pour la clé une faille de type OWASP-A3 et OWASP-A10
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:37:54 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/issues/owasp/2017', name: 'projet_issues_owasp_2017', methods: ['POST'])]
    public function projetIssuesOwasp2017(Request $request, Client $client): response
    {
        /** On instancie l'entityRepository */
        $informationProjet = $this->em->getRepository(InformationProjet::class);
        $owasp = $this->em->getRepository(Owasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On bind les variables. */
        $tempoUrlLong = $this->getParameter(static::$sonarUrl).static::$apiIssuesSearch;

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') || !property_exists($data, 'referentiel_version')) {
            return $response->setData(
                ['data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                    'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $response->setData([
                "code" => 403,
                "reference" => static::$reference,
                "message" => static::$erreur403,
                Response::HTTP_OK]);
        }

        /** URL de l'appel. */
        $url = "$tempoUrlLong$data->maven_key&facets=owaspTop10
                &owaspTop10=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10";

        /** On appel l'API. */
        $result2017 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url)));
        /** on catch les erreurs HTTP 400, 401 et 404, si possible :) */
        if (array_key_exists('code', $result2017)){
            if ($result2017['code']===401) {
            return $response->setData([
                "code" => 401,
                "reference" => static::$reference,
                "message" => static::$erreur401,
                Response::HTTP_OK]);
            }
            if ($result2017['code']===404){
                return $response->setData([
                    "code" => 404,
                    "reference" => static::$reference,
                    "message" => static::$erreur404,
                    Response::HTTP_OK]);
                }
        }

        /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map=['maven_key'=>$data->maven_key];
        $select=$informationProjet->selectInformationProjetProjectVersion($map);
        if ($select['code']!=200) {
            return $response->setData(
                ['code' => $select['code'], 'message'=>$select['erreur'], Response::HTTP_OK]);
        }

        if (!$select['info']) {
            return $response->setData(
                ['code' => 404, 'reference' => static::$reference, 'message' => static::$erreur404,
                Response::HTTP_OK]);
        }

        /** On converti la date de la version en dateTime */
        $dateVersion= new DateTime($select['info'][0]['date']);
        $dateVersion->setTimezone(new DateTimeZone(static::$europeParis));


        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $nombre2017 = [$result2017['total']];
        $effortTotal2017 = $result2017["effortTotal"];

        for ($a = 0; $a < 10; $a++) {
            switch ($result2017["facets"][0]["values"][$a]["val"]) {
                case 'a1':
                    $nombre2017[1] = $result2017["facets"][0]["values"][$a]["count"];
                    break;
                case 'a2':
                    $nombre2017[2] = $result2017["facets"][0]["values"][$a]["count"];
                    break;
                case 'a3':
                    $nombre2017[3] = $result2017["facets"][0]["values"][$a]["count"];
                    break;
                case 'a4':
                    $nombre2017[4] = $result2017["facets"][0]["values"][$a]["count"];
                    break;
                case 'a5':
                    $nombre2017[5] = $result2017["facets"][0]["values"][$a]["count"];
                    break;
                case 'a6':
                    $nombre2017[6] = $result2017["facets"][0]["values"][$a]["count"];
                    break;
                case 'a7':
                    $nombre2017[7] = $result2017["facets"][0]["values"][$a]["count"];
                    break;
                case 'a8':
                    $nombre2017[8] = $result2017["facets"][0]["values"][$a]["count"];
                    break;
                case 'a9':
                    $nombre2017[9] = $result2017["facets"][0]["values"][$a]["count"];
                    break;
                case 'a10':
                    $nombre2017[10] = $result2017["facets"][0]["values"][$a]["count"];
                    break;
                default:
                    $this->logger->NOTICE("HoneyPot : Référentiel OWASP !");
            }
        }

        $a1Blocker = $a1Critical = $a1Major = $a1Info = $a1Minor = 0;
        $a2Blocker = $a2Critical = $a2Major = $a2Info = $a2Minor = 0;
        $a3Blocker = $a3Critical = $a3Major = $a3Info = $a3Minor = 0;
        $a4Blocker = $a4Critical = $a4Major = $a4Info = $a4Minor = 0;
        $a5Blocker = $a5Critical = $a5Major = $a5Info = $a5Minor = 0;
        $a6Blocker = $a6Critical = $a6Major = $a6Info = $a6Minor = 0;
        $a7Blocker = $a7Critical = $a7Major = $a7Info = $a7Minor = 0;
        $a8Blocker = $a8Critical = $a8Major = $a8Info = $a8Minor = 0;
        $a9Blocker = $a9Critical = $a9Major = $a9Info = $a9Minor = 0;
        $a10Blocker = $a10Critical = $a10Major = $a10Info = $a10Minor = 0;

        if ($result2017['total'] != 0) {
            foreach ($result2017['issues'] as $issue) {
                $severity = $issue['severity'];
                if (
                    $issue['status'] == 'OPEN' ||
                    $issue['status'] == 'CONFIRMED' ||
                    $issue['status'] == 'REOPENED'
                ) {
                    $tagMatch = preg_match("/owasp-a/is", var_export($issue["tags"], true));
                    if ($tagMatch != 0) {
                        foreach ($issue['tags'] as $tag) {
                            switch ($tag) {
                                case "owasp-a1":
                                    if ($severity == 'BLOCKER') {
                                        $a1Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a1Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a1Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a1Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a1Minor++;
                                    }
                                    break;
                                case "owasp-a2":
                                    if ($severity == 'BLOCKER') {
                                        $a2Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a2Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a2Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a2Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a2Minor++;
                                    }
                                    break;
                                case "owasp-a3":
                                    if ($severity == 'BLOCKER') {
                                        $a3Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a3Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a3Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a3Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a3Minor++;
                                    }
                                    break;
                                case "owasp-a4":
                                    if ($severity == 'BLOCKER') {
                                        $a4Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a4Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a4Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a4Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a4Minor++;
                                    }
                                    break;
                                case "owasp-a5":
                                    if ($severity == 'BLOCKER') {
                                        $a5Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a5Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a5Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a5Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a5Minor++;
                                    }
                                    break;
                                case "owasp-a6":
                                    if ($severity == 'BLOCKER') {
                                        $a6Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a6Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a6Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a6Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a6Minor++;
                                    }
                                    break;
                                case "owasp-a7":
                                    if ($severity == 'BLOCKER') {
                                        $a7Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a7Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a7Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a7Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a7Minor++;
                                    }
                                    break;
                                case "owasp-a8":
                                    if ($severity == 'BLOCKER') {
                                        $a8Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a8Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a8Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a8Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a8Minor++;
                                    }
                                    break;
                                case "owasp-a9":
                                    if ($severity == 'BLOCKER') {
                                        $a9Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a9Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a9Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a9Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a9Minor++;
                                    }
                                    break;
                                case "owasp-a10":
                                    if ($severity == 'BLOCKER') {
                                        $a10Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a10Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a10Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a10Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a10Minor++;
                                    }
                                    break;
                                default:
                                    $this->logger->NOTICE("HoneyPot : Référentiel OWASP !");
                            }
                        }
                    }
                }
            }
        }

        /** On supprime les informations sur le projet pour la dernière analyse. */
        $map=['maven_key'=>$data->maven_key];
        $delete=$owasp->deleteOwaspMavenKey($map);
        if ($delete['code']!=200) {
            return $response->setData(
                ['code' => $delete['code'], 'message'=>$delete['erreur'], Response::HTTP_OK]);
        }

        /** Enregistre en base. */
        $owaspTop10Ref2017 = new Owasp();
        $owaspTop10Ref2017->setMavenKey($data->maven_key);
        $owaspTop10Ref2017->setVersion($select['info'][0]['project_version']);
        $owaspTop10Ref2017->setDateVersion($dateVersion);
        $owaspTop10Ref2017->setEffortTotal($effortTotal2017);
        $owaspTop10Ref2017->setA1($nombre2017[1]);
        $owaspTop10Ref2017->setA2($nombre2017[2]);
        $owaspTop10Ref2017->setA3($nombre2017[3]);
        $owaspTop10Ref2017->setA4($nombre2017[4]);
        $owaspTop10Ref2017->setA5($nombre2017[5]);
        $owaspTop10Ref2017->setA6($nombre2017[6]);
        $owaspTop10Ref2017->setA7($nombre2017[7]);
        $owaspTop10Ref2017->setA8($nombre2017[8]);
        $owaspTop10Ref2017->setA9($nombre2017[9]);
        $owaspTop10Ref2017->setA10($nombre2017[10]);

        $owaspTop10Ref2017->setA1Blocker($a1Blocker);
        $owaspTop10Ref2017->setA1Critical($a1Critical);
        $owaspTop10Ref2017->setA1Major($a1Major);
        $owaspTop10Ref2017->setA1Info($a1Info);
        $owaspTop10Ref2017->setA1Minor($a1Minor);

        $owaspTop10Ref2017->setA2Blocker($a2Blocker);
        $owaspTop10Ref2017->setA2Critical($a2Critical);
        $owaspTop10Ref2017->setA2Major($a2Major);
        $owaspTop10Ref2017->setA2Info($a2Info);
        $owaspTop10Ref2017->setA2Minor($a2Minor);

        $owaspTop10Ref2017->setA3Blocker($a3Blocker);
        $owaspTop10Ref2017->setA3Critical($a3Critical);
        $owaspTop10Ref2017->setA3Major($a3Major);
        $owaspTop10Ref2017->setA3Info($a3Info);
        $owaspTop10Ref2017->setA3Minor($a3Minor);

        $owaspTop10Ref2017->setA4Blocker($a4Blocker);
        $owaspTop10Ref2017->setA4Critical($a4Critical);
        $owaspTop10Ref2017->setA4Major($a4Major);
        $owaspTop10Ref2017->setA4Info($a4Info);
        $owaspTop10Ref2017->setA4Minor($a4Minor);

        $owaspTop10Ref2017->setA5Blocker($a5Blocker);
        $owaspTop10Ref2017->setA5Critical($a5Critical);
        $owaspTop10Ref2017->setA5Major($a5Major);
        $owaspTop10Ref2017->setA5Info($a5Info);
        $owaspTop10Ref2017->setA5Minor($a5Minor);

        $owaspTop10Ref2017->setA6Blocker($a6Blocker);
        $owaspTop10Ref2017->setA6Critical($a6Critical);
        $owaspTop10Ref2017->setA6Major($a6Major);
        $owaspTop10Ref2017->setA6Info($a6Info);
        $owaspTop10Ref2017->setA6Minor($a6Minor);

        $owaspTop10Ref2017->setA7Blocker($a7Blocker);
        $owaspTop10Ref2017->setA7Critical($a7Critical);
        $owaspTop10Ref2017->setA7Major($a7Major);
        $owaspTop10Ref2017->setA7Info($a7Info);
        $owaspTop10Ref2017->setA7Minor($a7Minor);

        $owaspTop10Ref2017->setA8Blocker($a8Blocker);
        $owaspTop10Ref2017->setA8Critical($a8Critical);
        $owaspTop10Ref2017->setA8Major($a8Major);
        $owaspTop10Ref2017->setA8Info($a8Info);
        $owaspTop10Ref2017->setA8Minor($a8Minor);

        $owaspTop10Ref2017->setA9Blocker($a9Blocker);
        $owaspTop10Ref2017->setA9Critical($a9Critical);
        $owaspTop10Ref2017->setA9Major($a9Major);
        $owaspTop10Ref2017->setA9Info($a9Info);
        $owaspTop10Ref2017->setA9Minor($a9Minor);

        $owaspTop10Ref2017->setA10Blocker($a10Blocker);
        $owaspTop10Ref2017->setA10Critical($a10Critical);
        $owaspTop10Ref2017->setA10Major($a10Major);
        $owaspTop10Ref2017->setA10Info($a10Info);
        $owaspTop10Ref2017->setA10Minor($a10Minor);

        $owaspTop10Ref2017->setReferentielVersion('2017');
        $owaspTop10Ref2017->setDateEnregistrement($date);

        $this->em->persist($owaspTop10Ref2017);
        $this->em->flush();


        return $response->setData(['code' => 200, 'owasp' => $result2017["total"], Response::HTTP_OK]);
    }


    #[Route('/api/projet/issues/owasp/2021', name: 'projet_issues_owasp_2021', methods: ['POST'])]
    public function projetIssuesOwasp2021(Request $request, Client $client): response
    {
        /** On instancie l'entityRepository */
        $informationProjet = $this->em->getRepository(InformationProjet::class);
        $owasp = $this->em->getRepository(Owasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On bind les variables. */
        $tempoUrlLong = $this->getParameter(static::$sonarUrl).static::$apiIssuesSearch;

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key')) {
            return $response->setData(
                ['data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                    'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $response->setData([
                "code" => 403,
                "reference" => static::$reference,
                "message" => static::$erreur403,
                Response::HTTP_OK]);
        }

        /** URL de l'appel. */
        $url = "$tempoUrlLong$data->maven_key&facets=owaspTop10-2021
                &owaspTop10-2021=a1,a2,a3,a4,a5,a6,a7,a8,a9,a10";

        /** On appel l'API. */
        $result2021 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url)));
        /** on catch les erreurs HTTP 400, 401 et 404, si possible :) */
        if (array_key_exists('code', $result2021)){
            if ($result2021['code']===401) {
            return $response->setData([
                "code" => 401,
                "reference" => static::$reference,
                "message" => static::$erreur401,
                Response::HTTP_OK]);
            }
            if ($result2021['code']===404){
                return $response->setData([
                    "code" => 404,
                    "reference" => static::$reference,
                    "message" => static::$erreur404,
                    Response::HTTP_OK]);
                }
        }

        /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map=['maven_key'=>$data->maven_key];
        $select=$informationProjet->selectInformationProjetProjectVersion($map);
        if ($select['code']!=200) {
            return $response->setData(
                ['code' => $select['code'], 'message'=>$select['erreur'], Response::HTTP_OK]);
        }

        if (!$select['info']) {
            return $response->setData(
                ['code' => 404, 'reference' => static::$reference, 'message' => static::$erreur404,
                Response::HTTP_OK]);
        }
        
        /** On converti la date de la version en dateTime */
        $dateVersion= new DateTime($select['info'][0]['date']);
        $dateVersion->setTimezone(new DateTimeZone(static::$europeParis));


        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $nombre2021 = [$result2021['total']];
        $effortTotal2021 = $result2021["effortTotal"];

        for ($a = 0; $a < 10; $a++) {
            switch ($result2021["facets"][0]["values"][$a]["val"]) {
                case 'a1':
                    $nombre2021[1] = $result2021["facets"][0]["values"][$a]["count"];
                    break;
                case 'a2':
                    $nombre2021[2] = $result2021["facets"][0]["values"][$a]["count"];
                    break;
                case 'a3':
                    $nombre2021[3] = $result2021["facets"][0]["values"][$a]["count"];
                    break;
                case 'a4':
                    $nombre2021[4] = $result2021["facets"][0]["values"][$a]["count"];
                    break;
                case 'a5':
                    $nombre2021[5] = $result2021["facets"][0]["values"][$a]["count"];
                    break;
                case 'a6':
                    $nombre2021[6] = $result2021["facets"][0]["values"][$a]["count"];
                    break;
                case 'a7':
                    $nombre2021[7] = $result2021["facets"][0]["values"][$a]["count"];
                    break;
                case 'a8':
                    $nombre2021[8] = $result2021["facets"][0]["values"][$a]["count"];
                    break;
                case 'a9':
                    $nombre2021[9] = $result2021["facets"][0]["values"][$a]["count"];
                    break;
                case 'a10':
                    $nombre2021[10] = $result2021["facets"][0]["values"][$a]["count"];
                    break;
                default:
                    $this->logger->NOTICE("HoneyPot : Référentiel OWASP !");
            }
        }

        $a1Blocker = $a1Critical = $a1Major = $a1Info = $a1Minor = 0;
        $a2Blocker = $a2Critical = $a2Major = $a2Info = $a2Minor = 0;
        $a3Blocker = $a3Critical = $a3Major = $a3Info = $a3Minor = 0;
        $a4Blocker = $a4Critical = $a4Major = $a4Info = $a4Minor = 0;
        $a5Blocker = $a5Critical = $a5Major = $a5Info = $a5Minor = 0;
        $a6Blocker = $a6Critical = $a6Major = $a6Info = $a6Minor = 0;
        $a7Blocker = $a7Critical = $a7Major = $a7Info = $a7Minor = 0;
        $a8Blocker = $a8Critical = $a8Major = $a8Info = $a8Minor = 0;
        $a9Blocker = $a9Critical = $a9Major = $a9Info = $a9Minor = 0;
        $a10Blocker = $a10Critical = $a10Major = $a10Info = $a10Minor = 0;

        if ($result2021['total'] != 0) {
            foreach ($result2021['issues'] as $issue) {
                $severity = $issue['severity'];
                if (
                    $issue['status'] == 'OPEN' ||
                    $issue['status'] == 'CONFIRMED' ||
                    $issue['status'] == 'REOPENED'
                ) {
                    $tagMatch = preg_match("/owasp-a/is", var_export($issue["tags"], true));
                    if ($tagMatch != 0) {
                        foreach ($issue['tags'] as $tag) {
                            switch ($tag) {
                                case "owasp-a1":
                                    if ($severity == 'BLOCKER') {
                                        $a1Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a1Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a1Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a1Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a1Minor++;
                                    }
                                    break;
                                case "owasp-a2":
                                    if ($severity == 'BLOCKER') {
                                        $a2Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a2Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a2Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a2Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a2Minor++;
                                    }
                                    break;
                                case "owasp-a3":
                                    if ($severity == 'BLOCKER') {
                                        $a3Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a3Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a3Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a3Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a3Minor++;
                                    }
                                    break;
                                case "owasp-a4":
                                    if ($severity == 'BLOCKER') {
                                        $a4Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a4Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a4Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a4Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a4Minor++;
                                    }
                                    break;
                                case "owasp-a5":
                                    if ($severity == 'BLOCKER') {
                                        $a5Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a5Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a5Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a5Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a5Minor++;
                                    }
                                    break;
                                case "owasp-a6":
                                    if ($severity == 'BLOCKER') {
                                        $a6Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a6Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a6Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a6Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a6Minor++;
                                    }
                                    break;
                                case "owasp-a7":
                                    if ($severity == 'BLOCKER') {
                                        $a7Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a7Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a7Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a7Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a7Minor++;
                                    }
                                    break;
                                case "owasp-a8":
                                    if ($severity == 'BLOCKER') {
                                        $a8Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a8Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a8Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a8Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a8Minor++;
                                    }
                                    break;
                                case "owasp-a9":
                                    if ($severity == 'BLOCKER') {
                                        $a9Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a9Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a9Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a9Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a9Minor++;
                                    }
                                    break;
                                case "owasp-a10":
                                    if ($severity == 'BLOCKER') {
                                        $a10Blocker++;
                                    }
                                    if ($severity == 'CRITICAL') {
                                        $a10Critical++;
                                    }
                                    if ($severity == 'MAJOR') {
                                        $a10Major++;
                                    }
                                    if ($severity == 'INFO') {
                                        $a10Info++;
                                    }
                                    if ($severity == 'MINOR') {
                                        $a10Minor++;
                                    }
                                    break;
                                default:
                                    $this->logger->NOTICE("HoneyPot : Référentiel OWASP !");
                            }
                        }
                    }
                }
            }
        }

        /** On supprime les informations sur le projet pour la dernière analyse. */
        $map=['maven_key'=>$data->maven_key];
        $delete=$owasp->deleteOwaspMavenKey($map);
        if ($delete['code']!=200) {
            return $response->setData(
                ['code' => $delete['code'], 'message'=>$delete['erreur'], Response::HTTP_OK]);
        }

        /** Enregistre en base. */
        $owaspTop10Ref2021 = new Owasp();
        $owaspTop10Ref2021->setMavenKey($data->maven_key);
        $owaspTop10Ref2021->setVersion($select['info'][0]['project_version']);
        $owaspTop10Ref2021->setDateVersion($dateVersion);
        $owaspTop10Ref2021->setEffortTotal($effortTotal2021);
        $owaspTop10Ref2021->setA1($nombre2021[1]);
        $owaspTop10Ref2021->setA2($nombre2021[2]);
        $owaspTop10Ref2021->setA3($nombre2021[3]);
        $owaspTop10Ref2021->setA4($nombre2021[4]);
        $owaspTop10Ref2021->setA5($nombre2021[5]);
        $owaspTop10Ref2021->setA6($nombre2021[6]);
        $owaspTop10Ref2021->setA7($nombre2021[7]);
        $owaspTop10Ref2021->setA8($nombre2021[8]);
        $owaspTop10Ref2021->setA9($nombre2021[9]);
        $owaspTop10Ref2021->setA10($nombre2021[10]);

        $owaspTop10Ref2021->setA1Blocker($a1Blocker);
        $owaspTop10Ref2021->setA1Critical($a1Critical);
        $owaspTop10Ref2021->setA1Major($a1Major);
        $owaspTop10Ref2021->setA1Info($a1Info);
        $owaspTop10Ref2021->setA1Minor($a1Minor);

        $owaspTop10Ref2021->setA2Blocker($a2Blocker);
        $owaspTop10Ref2021->setA2Critical($a2Critical);
        $owaspTop10Ref2021->setA2Major($a2Major);
        $owaspTop10Ref2021->setA2Info($a2Info);
        $owaspTop10Ref2021->setA2Minor($a2Minor);

        $owaspTop10Ref2021->setA3Blocker($a3Blocker);
        $owaspTop10Ref2021->setA3Critical($a3Critical);
        $owaspTop10Ref2021->setA3Major($a3Major);
        $owaspTop10Ref2021->setA3Info($a3Info);
        $owaspTop10Ref2021->setA3Minor($a3Minor);

        $owaspTop10Ref2021->setA4Blocker($a4Blocker);
        $owaspTop10Ref2021->setA4Critical($a4Critical);
        $owaspTop10Ref2021->setA4Major($a4Major);
        $owaspTop10Ref2021->setA4Info($a4Info);
        $owaspTop10Ref2021->setA4Minor($a4Minor);

        $owaspTop10Ref2021->setA5Blocker($a5Blocker);
        $owaspTop10Ref2021->setA5Critical($a5Critical);
        $owaspTop10Ref2021->setA5Major($a5Major);
        $owaspTop10Ref2021->setA5Info($a5Info);
        $owaspTop10Ref2021->setA5Minor($a5Minor);

        $owaspTop10Ref2021->setA6Blocker($a6Blocker);
        $owaspTop10Ref2021->setA6Critical($a6Critical);
        $owaspTop10Ref2021->setA6Major($a6Major);
        $owaspTop10Ref2021->setA6Info($a6Info);
        $owaspTop10Ref2021->setA6Minor($a6Minor);

        $owaspTop10Ref2021->setA7Blocker($a7Blocker);
        $owaspTop10Ref2021->setA7Critical($a7Critical);
        $owaspTop10Ref2021->setA7Major($a7Major);
        $owaspTop10Ref2021->setA7Info($a7Info);
        $owaspTop10Ref2021->setA7Minor($a7Minor);

        $owaspTop10Ref2021->setA8Blocker($a8Blocker);
        $owaspTop10Ref2021->setA8Critical($a8Critical);
        $owaspTop10Ref2021->setA8Major($a8Major);
        $owaspTop10Ref2021->setA8Info($a8Info);
        $owaspTop10Ref2021->setA8Minor($a8Minor);

        $owaspTop10Ref2021->setA9Blocker($a9Blocker);
        $owaspTop10Ref2021->setA9Critical($a9Critical);
        $owaspTop10Ref2021->setA9Major($a9Major);
        $owaspTop10Ref2021->setA9Info($a9Info);
        $owaspTop10Ref2021->setA9Minor($a9Minor);

        $owaspTop10Ref2021->setA10Blocker($a10Blocker);
        $owaspTop10Ref2021->setA10Critical($a10Critical);
        $owaspTop10Ref2021->setA10Major($a10Major);
        $owaspTop10Ref2021->setA10Info($a10Info);
        $owaspTop10Ref2021->setA10Minor($a10Minor);

        $owaspTop10Ref2021->setReferentielVersion('2021');
        $owaspTop10Ref2021->setDateEnregistrement($date);

        $this->em->persist($owaspTop10Ref2021);
        $this->em->flush();


        return $response->setData(['code' => 200, 'owasp' => $result2021["total"], Response::HTTP_OK]);
    }

}
