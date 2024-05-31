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
use App\Entity\Anomalie;
use App\Entity\AnomalieDetails;

use Doctrine\ORM\EntityManagerInterface;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

/** Import des services */
use App\Service\ExtractName;
use App\Service\DateTools;

/**
 * [Description ApiAnomalieController]
 */
class ApiAnomalieController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $dateFormat = "Y-m-d H:i:s";
    public static $europeParis = "Europe/Paris";
    public static $removeReturnline = "/\s+/u";
    public static $reference = "<strong>[PROJET-003]</strong>";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur401 = "Erreur d\'Authentification. La clé n\'est pas correcte (Erreur 401).";
    public static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    public static $erreur404 = "L'appel à l'API n'a pas abouti (Erreur 404).";

    public static $apiIssuesSearch = "/api/issues/search?componentKeys=";
    public static $statuses = "OPEN,REOPENED";
    public static $statusesMin = "OPEN,CONFIRMED,REOPENED,RESOLVED";
    public static $statusesAll = "OPEN, CONFIRMED, REOPENED, RESOLVED, CLOSED";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private ExtractName $serviceExtractName,
    ) {
        $this->logger = $logger;
        $this->em = $em;
        $this->serviceExtractName = $serviceExtractName;
    }

    /**
     * [Description for projetAnomalieCollect]
     * Récupère le total des anomalies, avec un filtre par répertoire, sévérité et types.
     * https://{URL}/api/projet/anomalie
     *
     * Phase 06
     *
     * {maven_key} : Clé du projet
     *
     * @param Request $request
     * @param Client $client
     * @param DateTools $dateTools
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:32:28 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/anomalie', name: 'projet_anomalie_collect', methods: ['POST'])]
    public function projetAnomalieCollect(Request $request, Client $client, DateTools $dateTools): response
    {
        /** On instancie l'EntityRepository */
        $anomalieEntity = $this->em->getRepository(Anomalie::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de response JSON */
        $response = new JsonResponse();

       /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
        return $response->setData(
            ['data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $response->setData([
                'type'=>'warning',
                'code' => 403,
                'reference' => static::$reference,
                'message' => static::$erreur403,
                Response::HTTP_OK]);
        }

        /** On construit l'URL de base. */
        $tempoUrlLong = $this->getParameter(static::$sonarUrl) . static::$apiIssuesSearch;

        /** On créé un objet date. */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /**
         * On choisi le type de status des anomalies : [OPEN, CONFIRMED, REOPENED, RESOLVED, CLOSED]
         * Type : statuses, statusesMin et statusesAll
         */
        $typeStatuses = static::$statuses;
        $url1 = "$tempoUrlLong"."$data->maven_key&facets=directories,types,severities&p=1&ps=1&statuses=$typeStatuses";

        /** On récupère le total de la Dette technique pour les BUG. */
        $url2 = "$tempoUrlLong"."$data->maven_key&types=BUG&p=1&ps=1";

        /** On récupère le total de la Dette technique pour les VULNERABILITY. */
        $url3 = "$tempoUrlLong"."$data->maven_key&types=VULNERABILITY&p=1&ps=1";

        /** On récupère le total de la Dette technique pour les CODE_SMELL. */
        $url4 = "$tempoUrlLong"."$data->maven_key&types=CODE_SMELL&p=1&ps=1";

        /** On appel le client http pour les requêtes 1 à 4 (2 à 4 pour la dette). */
        $result1 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url1)));
        $result2 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url2)));
        $result3 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url3)));
        $result4 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url4)));
        /** On catch les erreurs HTTP 400, 401 et 404, si possible :) */
        if (array_key_exists('code', $result1)||
            array_key_exists('code', $result2)||
            array_key_exists('code', $result3)||
            array_key_exists('code', $result4) ){
            if ($result1['code']===401||
                $result2['code']===401||
                $result3['code']===401||
                $result4['code']===401) {
            return $response->setData([
                'type'=>'warning',
                'code' => 401,
                'reference' => static::$reference,
                'message' => static::$erreur401,
                Response::HTTP_OK]);
            }
            if ($result1['code']===404||
                $result2['code']===404||
                $result3['code']===404||
                $result4['code']===404) {
                return $response->setData([
                    'type'=>'alert',
                    "code" => 404,
                    "reference" => static::$reference,
                    "message" => static::$erreur404,
                    Response::HTTP_OK]);
                }
        }

        $app=$this->serviceExtractName->extractNameFromMavenKey($data->maven_key);

        if ($result1['paging']['total'] != 0) {
            /** On supprime les anomalies pour la maven_key. */
            $map=['maven_key'=>$data->maven_key];
            $request=$anomalieEntity->deleteAnomalieMavenKey($map);
            if ($request['code']!=200) {
                return $response->setData([
                    'type' => 'alert',
                    'reference' => static::$reference,
                    'code' => $request['code'],
                    'message'=>$request['erreur'],
                    Response::HTTP_OK]);
            }

            $anomalieTotal = $result1['total'];
            $detteMinute = $result1['effortTotal'];
            $dette = $dateTools->minutesTo($detteMinute);
            $detteReliabilityMinute = $result2['effortTotal'];
            $detteReliability = $dateTools->minutesTo($detteReliabilityMinute);
            $detteVulnerabilityMinute = $result3['effortTotal'];
            $detteVulnerability = $dateTools->minutesTo($detteVulnerabilityMinute);
            $detteCodeSmellMinute = $result4['effortTotal'];
            $detteCodeSmell = $dateTools->minutesTo($detteCodeSmellMinute);

            $facets = $result1['facets'];
            /** Modules. */
            $frontend = $backend = $autre = $nombreAnomalie = 0;
            foreach ($facets as $facet) {
                $nombreAnomalie++;
                /** On récupère le nombre de signalement par sévérité. */
                if ($facet['property'] == 'severities') {
                    foreach ($facet['values'] as $severity) {
                        switch ($severity['val']) {
                            case 'BLOCKER' : $blocker = $severity['count'];
                                break;
                            case 'CRITICAL' : $critical = $severity['count'];
                                break;
                            case 'MAJOR' : $major = $severity['count'];
                                break;
                            case 'INFO' : $info = $severity['count'];
                                break;
                            case 'MINOR' : $minor = $severity['count'];
                                break;
                            default: $this->logger->INFO('Référentiel severité !');
                        }
                    }
                }
                /** On récupère le nombre de signalement par type. */
                if ($facet['property'] == 'types') {
                    foreach ($facet['values'] as $type) {
                        switch ($type['val']) {
                            case 'BUG' : $bug = $type['count'];
                                break;
                            case 'VULNERABILITY' : $vulnerability = $type['count'];
                                break;
                            case 'CODE_SMELL' : $codeSmell = $type['count'];
                                break;
                            default: $this->logger->INFO('Référentiel Type !');
                        }
                    }
                }
                /** On récupère le nombre de signalement par module. */
                if ($facet['property'] == 'directories') {
                    foreach ($facet['values'] as $directory) {
                        $file = str_replace($data->maven_key . ':', "", $directory['val']);
                        $module = explode('/', $file);
                        if ($module[0] === 'du-presentation' || $module[0] === 'rs-presentation') {
                            $frontend = $frontend + $directory['count'];
                        }
                        if ($module[0] === $app . '-presentation' || $module[0] === $app . '-presentation-commun' ||
                            $module[0] === $app . '-presentation-ear' || $module[0] === $app . '-webapp') {
                            $frontend = $frontend + 1;
                        }
                        if ($module[0] === 'rs-metier') {
                            $backend = $backend + $directory['count'];
                        }
                        if ($module[0] === $app . '-metier' || $module[0] === $app . '-common' ||
                            $module[0] === $app . '-api' || $module[0] === $app . '-dao') {
                            $backend = $backend + $directory['count'];
                        }
                        if ($module[0] === $app . '-metier-ear' || $module[0] === $app . "-service" ||
                            $module[0] === $app . '-serviceweb' || $module[0] === $app . "-middleoffice") {
                            $backend = $backend + $directory['count'];
                        }
                        if ($module[0] === $app . '-metier-rest' || $module[0] === $app . "-entite" ||
                            $module[0] === $app . '-serviceweb-client') {
                            $backend = $backend + $directory['count'];
                        }
                        if ($module[0] === $app . '-batch' || $module[0] === $app . '-batchs' ||
                            $module[0] === $app . '-batch-envoi-dem-aval' || $module[0] === $app . '-batch-import-billets') {
                            $autre = $autre + $directory['count'];
                        }
                        if ($module[0] === $app . '-rdd') {
                            $autre = $autre + $directory['count'];
                        }
                    }
                }
            }
            /** Enregistrement dans la table Anomalie. */
            $issue = new Anomalie();
            $issue->setMavenKey($data->maven_key);
            $app=$this->serviceExtractName->extractNameFromMavenKey($data->maven_key);

            $issue->setProjectName($app);
            $issue->setAnomalieTotal($anomalieTotal);
            $issue->setDette($dette);
            $issue->setDetteMinute($detteMinute);
            $issue->setDetteReliability($detteReliability);
            $issue->setDetteReliabilityMinute($detteReliabilityMinute);
            $issue->setDetteVulnerability($detteVulnerability);
            $issue->setDetteVulnerabilityMinute($detteVulnerabilityMinute);
            $issue->setDetteCodeSmell($detteCodeSmell);
            $issue->setDetteCodeSmellMinute($detteCodeSmellMinute);
            $issue->setFrontend($frontend);
            $issue->setBackend($backend);
            $issue->setAutre($autre);
            $issue->setBlocker($blocker);
            $issue->setCritical($critical);
            $issue->setMajor($major);
            $issue->setInfo($info);
            $issue->setMinor($minor);
            $issue->setBug($bug);
            $issue->setVulnerability($vulnerability);
            $issue->setCodeSmell($codeSmell);
            $issue->setDateEnregistrement($date);

            $this->em->persist($issue);
            $this->em->flush();

        }

        $info = "Enregistrement des défauts (" . $nombreAnomalie . ") correctement effectué.";
        return $response->setData(['code'=>200, "info" => $info, Response::HTTP_OK]);
    }


    /**
     * [Description for projetAnomalieDetails]
     * Récupère le détails des sévérités pour chaque type
     * https://{URL}/api/projet/anomalie/details
     *
     * Phase 07
     *
     * {maven_key} : Clé du projet
     *
     * @param Request $request
     * @param Client $client
     * @param DateTools $dateTools
     *
     * @param Request $request
     * @param Client $client
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:35:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/anomalie/details', name: 'projet_anomalie_details_collect', methods: ['POST'])]
    public function projetAnomalieDetailCollect(Request $request, Client $client): response
    {
        /** On instancie l'EntityRepository */
        $anomalieDetailsEntity = $this->em->getRepository(AnomalieDetails::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return $response->setData(
                ['data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                    'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $response->setData([
                'type'=>'warning',
                'code' => 403,
                'reference' => static::$reference,
                'message' => static::$erreur403,
                Response::HTTP_OK]);
        }

        /** On construit l'URL */
        $tempoUrlLong = $this->getParameter(static::$sonarUrl) . static::$apiIssuesSearch;

        /** Pour les Bug. */
        $url1 = "$tempoUrlLong"."$data->maven_key&facets=severities&types=BUG&ps=1&p=1&statuses=OPEN";

        /** Pour les Vulnérabilités. */
        $url2 = "$tempoUrlLong"."$data->maven_key&facets=severities&types=VULNERABILITY&ps=1&p=1&statuses=OPEN";

        /** Pour les mauvaises pratiques. */
        $url3 = "$tempoUrlLong"."$data->maven_key&facets=severities&types=CODE_SMELL&ps=1&p=1&statuses=OPEN";

        /** On appel le client http pour les requête 1 à 3. */
        $result1 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url1)));
        $result2 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url2)));
        $result3 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url3)));
        /** On catch les erreurs HTTP 400, 401 et 404, si possible :) */
        if (array_key_exists('code', $result1)||
            array_key_exists('code', $result2)||
            array_key_exists('code', $result3)){
            if ($result1['code']===401||
                $result2['code']===401||
                $result3['code']===403) {
            return $response->setData([
                'type'=>'warning',
                'code' => 401,
                'reference' => static::$reference,
                'message' => static::$erreur401,
                Response::HTTP_OK]);
            }
            if ($result1['code']===404||
                $result2['code']===404||
                $result3['code']===404) {
                return $response->setData([
                    'type'=>'alert',
                    "code" => 404,
                    "reference" => static::$reference,
                    "message" => static::$erreur404,
                    Response::HTTP_OK]);
                }
        }

        $total1 = $result1['paging']['total'];
        $total2 = $result2['paging']['total'];
        $total3 = $result3['paging']['total'];

        if ($total1 !== 0 || $total2 !== 0 || $total3 !== 0) {
            /** On supprime le detail des anomalies pour la maven_key. */
            $map=['maven_key'=>$data->maven_key];
            $request=$anomalieDetailsEntity->deleteAnomalieDetailsMavenKey($map);
            if ($request['code']!=200) {
                return $response->setData([
                    'type' => 'alert',
                    'reference' => static::$reference,
                    'code' => $request['code'],
                    'message'=>$request['erreur'],
                    Response::HTTP_OK]);
            }

            $date = new DateTime();
            $date->setTimezone(new DateTimeZone(static::$europeParis));
            $r1 = $result1['facets'];
            $r2 = $result2['facets'];
            $r3 = $result3['facets'];

            foreach ($r1[0]['values'] as $severity) {
                if ($severity['val'] === 'BLOCKER') {
                    $bugBlocker = $severity['count'];
                }
                if ($severity['val'] === 'CRITICAL') {
                    $bugCritical = $severity['count'];
                }
                if ($severity['val'] === 'MAJOR') {
                    $bugMajor = $severity['count'];
                }
                if ($severity['val'] === 'MINOR') {
                    $bugMinor = $severity['count'];
                }
                if ($severity['val'] === 'INFO') {
                    $bugInfo = $severity['count'];
                }
            }

            foreach ($r2[0]['values'] as $severity) {
                if ($severity['val'] === 'BLOCKER') {
                    $vulnerabilityBlocker = $severity['count'];
                }
                if ($severity['val'] === 'CRITICAL') {
                    $vulnerabilityCritical = $severity['count'];
                }
                if ($severity['val'] === 'MAJOR') {
                    $vulnerabilityMajor = $severity['count'];
                }
                if ($severity['val'] === 'MINOR') {
                    $vulnerabilityMinor = $severity['count'];
                }
                if ($severity['val'] === 'INFO') {
                    $vulnerabilityInfo = $severity['count'];
                }
            }

            foreach ($r3[0]['values'] as $severity) {
                if ($severity['val'] === 'BLOCKER') {
                    $codeSmellBlocker = $severity['count'];
                }
                if ($severity['val'] === 'CRITICAL') {
                    $codeSmellCritical = $severity['count'];
                }
                if ($severity['val'] === 'MAJOR') {
                    $codeSmellMajor = $severity['count'];
                }
                if ($severity['val'] === 'MINOR') {
                    $codeSmellMinor = $severity['count'];
                }
                if ($severity['val'] === 'INFO') {
                    $codeSmellInfo = $severity['count'];
                }
            }

            /** On enregistre en base. */
            $details = new AnomalieDetails();
            $details->setMavenKey($data->maven_key);
            $app=$this->serviceExtractName->extractNameFromMavenKey($data->maven_key);
            $details->setName($app);

            $details->setBugBlocker($bugBlocker);
            $details->setBugCritical($bugCritical);
            $details->setBugMajor($bugMajor);
            $details->setBugMinor($bugMinor);
            $details->setBugInfo($bugInfo);

            $details->setVulnerabilityBlocker($vulnerabilityBlocker);
            $details->setVulnerabilityCritical($vulnerabilityCritical);
            $details->setVulnerabilityMajor($vulnerabilityMajor);
            $details->setVulnerabilityMinor($vulnerabilityMinor);
            $details->setVulnerabilityInfo($vulnerabilityInfo);

            $details->setCodeSmellBlocker($codeSmellBlocker);
            $details->setCodeSmellCritical($codeSmellCritical);
            $details->setCodeSmellMajor($codeSmellMajor);
            $details->setCodeSmellMinor($codeSmellMinor);
            $details->setCodeSmellInfo($codeSmellInfo);

            $details->setDateEnregistrement($date);
            $this->em->persist($details);

            /** On catch l'erreur sur la clé composite : maven_key, version, date_version. */
            try {
                    $this->em->flush();
            } catch (\Doctrine\DBAL\Exception $e) {
                return $response->setData(['erreur' => $e->getCode(), Response::HTTP_OK]);
            }
        }
        return $response->setData(['code' => 200, Response::HTTP_OK]);
    }

}
