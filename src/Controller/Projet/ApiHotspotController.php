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
use App\Entity\Main\Hotspots;
use App\Entity\Main\HotspotOwasp;
use App\Entity\Main\HotspotDetails;

use Doctrine\ORM\EntityManagerInterface;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;
use App\Tests\Entity\Main\HotspotsTest;

class ApiHotspotController extends AbstractController
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
     * [Description for extractNameFromMavenKey]
     * Extrait le nom du projet de la clé
     *
     * @param mixed $mavenKey
     *
     * @return string
     *
     * Created at: 13/03/2024 21:47:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractNameFromMavenKey($mavenKey): string
    {
        /**
         * On récupère le nom de l'application depuis la clé mavenKey
         * [fr.ma-petite-entreprise] : [ma-moulinette]
         */
        $app = explode(":", $mavenKey);
        if (count($app)===1) {
            /** La clé maven n'est pas conforme, on ne peut pas déduire le nom de l'application */
            $name=$mavenKey;
        } else {
            $name=$app[1];
        }
        return $name;
    }

    /**
     * [Description for vulnerabilityProbability]
     * Retourne le niveau (numérique) de la menace en fonction de sa valeur (high, medium, low)
     *
     * @param mixed $probability
     *
     * @return int
     *
     * Created at: 14/03/2024 08:06:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function vulnerabilityProbability($probability): int
    {
        $niveau=0;
        if ($probability == 'HIGH') {
            $niveau = 1;
        }
        if ($probability == 'MEDIUM') {
            $niveau = 2;
        }
        if ($probability == 'LOW') {
            $niveau = 3;
        }
        return $niveau;
    }

    /**
     * [Description for projetHotspotCollect]
     * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
     * On récupère les failles a examiner.
     * Les clés sont uniques (i.e. on ne se base pas sur les tags).
     * http://{url}/api/hotspots/search?projectKey={key}&ps=500&p=1
     *
     * Phase 05
     *
     * {mode} = null | TEST
     * {maven_key} = la clé du projet
     *
     * @param Request $request
     * @param Clent $client
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:39:21 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/hotspot', name: 'projet_hotspot_collect', methods: ['POST'])]
    public function projetHotspotCollect(Request $request, Client $client): response
    {
        /** On instancie l'EntityRepository */
        $hotspots = $this->em->getRepository(Hotspots::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'type'=>'alert', 'code'=>400, "message" => static::$erreur400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'type'=>'alert', 'code'=>400, "message" => static::$erreur400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'type'=>'alert', 'code'=>400, "message" => static::$erreur400, Response::HTTP_BAD_REQUEST]); }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $response->setData([
                'type'=>'warning',
                'mode' => $data->mode ,
                'code' => 403,
                'reference' => static::$reference,
                'message' => static::$erreur403,
                Response::HTTP_OK]);
        }

        /** On récupère l'URL du serveur */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** On construit l'URL */
        $url = "$tempoUrl/api/hotspots/search?projectKey=$data->maven_key&ps=500&p=1";

        /** On appel l'Api */
        $result = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url)));
        /** On catch les erreurs HTTP 400, 401 et 404, si possible :) */
        if (array_key_exists('code', $result)){
            if ($result['code']===401) {
            return $response->setData([
                'type'=>'warning',
                'mode' => $data->mode ,
                'code' => 401,
                'reference' => static::$reference,
                'message' => static::$erreur401,
                Response::HTTP_OK]);
            }
            if ($result['code']===404){
                return $response->setData([
                    'type'=>'alert',
                    "mode" => $data->mode ,
                    "code" => 404,
                    "reference" => static::$reference,
                    "message" => static::$erreur404,
                    Response::HTTP_OK]);
                }
        }

        /** On créé un objet Date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $niveau = 0;

        /** On supprime les hotspots pour la maven_key. */
        $map=['maven_key'=>$data->maven_key];
        $request=$hotspots->deleteHotspotsMavenKey($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'type' => 'alert',
                'mode' => $data->mode,
                'reference' => static::$reference,
                'code' => $request['code'],
                'message'=>$request['erreur'],
                Response::HTTP_OK]);
        }

        /** on traite les hotspots */
        $value['key']='NC';
        $value['vulnerabilityProbability']=0;
        $value['status']='NC';
        $niveau=0;
        if ($result['paging']['total'] !== 0) {
            foreach ($result['hotspots'] as $value) {
                static::vulnerabilityProbability($value['vulnerabilityProbability']);
            }
        }

        /** On enregistre les données */
        $hotspot = new  Hotspots();
        $hotspot->setMavenKey($data->maven_key);
        $hotspot->setKey($value['key']);
        $hotspot->setProbability($value['vulnerabilityProbability']);
        $hotspot->setStatus($value['status']);
        $hotspot->setNiveau($niveau);
        $hotspot->setDateEnregistrement($date);
        $this->em->persist($hotspot);

        if ($data->mode !== "TEST") {
            $this->em->flush();
        }

        return $response->setData(
            ['mode' => $data->mode, 'code' => 200, 'hotspots' => $result['paging']['total'], Response::HTTP_OK]
        );
    }

    /**
     * [Description for projetHotspotOwaspCollect]
     * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
     * si le paramètre owasp est égale à a0 alors on supprime les enregistrements pour la clé
     * http://{url}/api/hotspots/search?projectKey={key}{owasp}&ps=500&p=1
     *
     * Phase 08 et 09
     *
     * {mode} = null | TEST
     * {mavenKey} = la clé du projet
     * {owasp} = le type de faille (a1, a2, etc...)
     *
     * @param Request $request
     * @param Clent $client
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:39:44 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/hotspot/owasp', name: 'projet_hotspot_owasp_collect', methods: ['POST'])]
    public function projetHotspotOwaspCollect(Request $request, Client $client): response
    {
        /** On instancie l'EntityRepository */
        $hotspotOwasp = $this->em->getRepository(HotspotOwasp::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'type'=>'alert', 'code'=>400, "message" => static::$erreur400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'type'=>'alert', 'code'=>400, "message" => static::$erreur400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'type'=>'alert', 'code'=>400, "message" => static::$erreur400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'owasp')) {
            return $response->setData(['owasp' => null, 'type'=>'alert', 'code'=>400, "message" => static::$erreur400, Response::HTTP_BAD_REQUEST]); }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $response->setData([
                'type'=>'warning',
                'mode' => $data->mode ,
                'code' => 403,
                'reference' => static::$reference,
                'message' => static::$erreur403,
                Response::HTTP_OK]);
        }

        if ($data->owasp == 'a0') {
            /** On supprime les hotspots pour la maven_key. */
            $map=['maven_key'=>$data->maven_key];
            $request=$hotspotOwasp->deleteHotspotOwaspMavenKey($data->mode, $map);
            if ($request['code']!=200) {
                return $response->setData([
                    'type' => 'alert',
                    'mode' => $data->mode,
                    'reference' => static::$reference,
                    'code' => $request['code'],
                    'message'=>$request['erreur'],
                    Response::HTTP_OK]);
            }

            return $response->setData(['mode'=>$data->mode, 'code'=>200, 'info' => 'effacement', Response::HTTP_OK]);
        }

        /** On récupère l'URL du serveur */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** On construit l'Url. */
        $url = "$tempoUrl/api/hotspots/search?projectKey=$data->maven_key
            &owaspTop10=$data->owasp&ps=500&p=1";

        /** On appel l'Api */
        $result = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url)));
        /** On catch les erreurs HTTP 400, 401 et 404, si possible :) */
        if (array_key_exists('code', $result)){
            if ($result['code']===401) {
            return $response->setData([
                'type'=>'warning',
                'mode' => $data->mode ,
                'code' => 401,
                'reference' => static::$reference,
                'message' => static::$erreur401,
                Response::HTTP_OK]);
            }
            if ($result['code']===404){
                return $response->setData([
                    'type'=>'alert',
                    "mode" => $data->mode ,
                    "code" => 404,
                    "reference" => static::$reference,
                    "message" => static::$erreur404,
                    Response::HTTP_OK]);
                }
        }

        /** On créé un objet Date. */
        $dateEnregistement = new DateTime();
        $dateEnregistement->setTimezone(new DateTimeZone(static::$europeParis));
        $niveau = 0;

        /** On fleche la vulnérabilité */
        if ($result['paging']['total'] != 0) {
            foreach ($result['hotspots'] as $value) {
                static::vulnerabilityProbability($value['vulnerabilityProbability']);

                $hotspot = new  HotspotOwasp();
                $hotspot->setMavenKey($data->mavenKey);
                $hotspot->setMenace($data->owasp);
                $hotspot->setProbability($value['vulnerabilityProbability']);
                $hotspot->setStatus($value['status']);
                $hotspot->setNiveau($niveau);
                $hotspot->setDateEnregistrement($dateEnregistement);

                $this->em->persist($hotspot);
                if ($data->mode !== 'TEST') {
                    $this->em->flush();
                }
            }
        } else {
            $hotspot = new  HotspotOwasp();
            $hotspot->setMavenKey($data->maven_key);
            $hotspot->setMenace($data->owasp);
            $hotspot->setProbability('NC');
            $hotspot->setStatus('NC');
            $hotspot->setNiveau('0');
            $hotspot->setDateEnregistrement($dateEnregistement);
            $this->em->persist($hotspot);
            if ($data->mode !== 'TEST') {
                $this->em->flush();
            }
        }

        return $response->setData(
            [ 'mode' => $data->mode, 'code'=>200, 'info' => 'enregistrement',
                'hotspots' => $result['paging']['total'], Response::HTTP_OK
            ]);
    }

    /**
     * [Description for hotspotDetails]
     * Fonction privée qui récupère le détail d'un hotspot en fonction de sa clé.
     * http://{$URL}/api/hotspots/show?hotspot={$key}
     *
     * {mavenKey} = la clé du projet
     * {key} = la clé de l'analyse
     *
     * @param string $mavenKey
     * @param string $key
     * @param Client $client
     *
     * @return array
     *
     * Created at: 15/12/2022, 21:40:47 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function hotspotDetails($mavenKey, $key, Client $client): array
    {
        /** On bind les variables. */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $url = "$tempoUrl/api/hotspots/show?hotspot=$key";
        $hotspot = $client->http($url);
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** Si le niveau de sévérité n'est pas connu, on lui affecte la valeur MEDIUM. */
        if (empty($hotspot['rule']['vulnerabilityProbability'])) {
            $severity = "MEDIUM";
        } else {
            $severity = $hotspot['rule']['vulnerabilityProbability'];
        }

        // On affecte le niveau en fonction de la sévérité
        switch ($severity) {
            case "HIGH":
                $niveau = 1;
                break;
            case "MEDIUM":
                $niveau = 2;
                break;
            case "LOW":
                $niveau = 3;
                break;
            default:
                $this->logger->NOTICE("HoneyPot : Liste des sévérités !");
        }

        $frontend = $backend = $autre = 0;
        $app=static::extractNameFromMavenKey($mavenKey);

        $status = $hotspot['status'];
        $file = str_replace($mavenKey . ":", "", $hotspot['component']['key']);
        $module = explode("/", $file);

        /**
         * Cas particulier pour l'application RS et DU
         * Le nom du projet ne correspond pas à l'artifactId du module
         * Par exemple la clé maven it.cool:monapplication et un module de
         * type : cool-presentation au lieu de monapplication-presentation
         */
        if ($module[0] == "du-presentation") {
            $frontend++;
        }
        if ($module[0] == "rs-presentation") {
            $frontend++;
        }
        if ($module[0] == "rs-metier") {
            $backend++;
        }

        /**
         *  Application Frontend
         */
        if ($module[0] == $app . "-presentation") {
            $frontend++;
        }
        if ($module[0] == $app . "-presentation-commun") {
            $frontend++;
        }
        if ($module[0] == $app . "-presentation-ear") {
            $frontend++;
        }
        if ($module[0] == $app . "-webapp") {
            $frontend++;
        }

        /**
         * Application Backend
         */
        if ($module[0] == $app . "-metier") {
            $backend++;
        }
        if ($module[0] == $app . "-common") {
            $backend++;
        }
        if ($module[0] == $app . "-api") {
            $backend++;
        }
        if ($module[0] == $app . "-dao") {
            $backend++;
        }
        if ($module[0] == $app . "-metier-ear") {
            $backend++;
        }
        if ($module[0] == $app . "-service") {
            $backend++;
        }
        // Application : Legacy
        if ($module[0] == $app . "-serviceweb") {
            $backend++;
        }
        if ($module[0] == $app . "-middleoffice") {
            $backend++;
        }
        // Application : Starter-Kit
        if ($module[0] == $app . "-metier-rest") {
            $backend++;
        }
        // Application : Legacy
        if ($module[0] == $app . "-entite") {
            $backend++;
        }
        // Application : Legacy
        if ($module[0] == $app . "-serviceweb-client") {
            $backend++;
        }

        /**
         * Application Batch et Autres
         */
        if ($module[0] == $app . "-batch") {
            $autre++;
        }
        if ($module[0] == $app . "-batch") {
            $autre++;
        }
        if ($module[0] == $app . "-batch-envoi-dem-aval") {
            $autre++;
        }
        if ($module[0] == $app . "-batch-import-billets") {
            $autre++;
        }
        if ($module[0] == $app . "-rdd") {
            $autre++;
        }

        if (empty($hotspot['line'])) {
            $line = 0;
        } else {
            $line = $hotspot['line'];
        }
        $rule = $hotspot['rule'] ? $hotspot['rule']['name'] : "/";
        $message2 = $hotspot['message'];
        /**
         * On affiche pas la description, même si on la en base,
         * car on pointe sur le serveur sonarqube directement
         * $description=$hotspot['rule']['riskDescription'];
         */
        $hotspotKey = $hotspot['key'];
        $dateEnregistrement = $date;

        return [
            "niveau" => $niveau,
            "severity" => $severity, "status" => $status,
            "frontend" => $frontend, "backend" => $backend,
            "autre" => $autre, "file" => $file,
            "line" => $line, "rule" => $rule,
            "message" => $message2, "key" => $hotspotKey,
            "date_enregistrement" => $dateEnregistrement
        ];
    }

    /**
     * [Description for hotspotDetailsCollect]
     * Récupère le détails des hotspots et les enregistre dans la table Hotspots_details
     * http://{url}/api/projet/hotspot/details
     *
     * Phase 10
     *
     * {mode} = null | TEST
     * {mavenKey} = la clé du projet
     *
     * @param string $mavenKey
     *
     * @param Request $request
     * @param Client $client
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:41:45 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/hotspot/details', name: 'projet_hotspot_details_collect', methods: ['POST'])]
    public function hotspotDetailsCollect(Request $request, Client $client): response
    {
        /** On instancie l'EntityRepository */
        $hotspots = $this->em->getRepository(Hotspots::class);
        $hotspotDetails = $this->em->getRepository(HotspotDetails::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'type'=>'alert', 'code'=>400, "message" => static::$erreur400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'type'=>'alert', 'code'=>400, "message" => static::$erreur400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'type'=>'alert', 'code'=>400, "message" => static::$erreur400, Response::HTTP_BAD_REQUEST]); }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $response->setData([
                'type'=>'warning',
                'mode' => $data->mode ,
                'code' => 403,
                'reference' => static::$reference,
                'message' => static::$erreur403,
                Response::HTTP_OK]);
        }

        /** On récupère la liste des hotspots au status TO_REVIEW */
        $map=['maven_key'=>$data->maven_key];
        $request=$hotspots->selectHotspotsToReview($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'type' => 'alert',
                'mode' => $data->mode,
                'reference' => static::$reference,
                'code' => $request['code'],
                'message'=>$request['erreur'],
                Response::HTTP_OK]);
        }

        /** On supprime le details des hotspots pour la maven_key. */
        $map=['maven_key'=>$data->maven_key];
        $request=$hotspotDetails->deleteHotspotDetailsMavenKey($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'type' => 'alert',
                'mode' => $data->mode,
                'reference' => static::$reference,
                'code' => $request['code'],
                'message'=>$request['erreur'],
                Response::HTTP_OK]);
        }

        /** Si la liste des hotspots est vide on envoi un code http 406 */
        if (empty($liste)) {
            return $response->setData(['mode' => $data->mode, 'code' => 406, Response::HTTP_OK]);
        }

        /**
         * On boucle sur les clés pour récupérer le détails du hotspot.
         * On envoie la clé du projet et la clé du hotspot.
         */
        $ligne = 0;
        foreach ($liste as $elt) {
            $ligne++;
            $key = $this->hotspotDetails($data->maven_key, $elt['key'], $client);
            $details = new  HotspotDetails();
            $details->setMavenKey($data->mavenKey);
            $details->setSeverity($key['severity']);
            $details->setNiveau($key['niveau']);
            $details->setStatus($key['status']);
            $details->setFrontend($key['frontend']);
            $details->setBackend($key['backend']);
            $details->setAutre($key['autre']);
            $details->setFile($key['file']);
            $details->setLine($key['line']);
            $details->setRule($key['rule']);
            $details->setMessage($key['message']);
            $details->setKey($key['key']);
            $details->setDateEnregistrement($key['date_enregistrement']);

            $this->em->persist($details);
            if ($data->mode != 'TEST') {
                $this->em->flush();
            }
        }
        return $response->setData(['mode' => $data->mode, 'code'=>200, 'ligne' => $ligne, Response::HTTP_OK]);
    }

}
