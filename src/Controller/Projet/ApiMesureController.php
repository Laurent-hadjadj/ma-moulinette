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
use App\Entity\Mesures;
use Doctrine\ORM\EntityManagerInterface;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description ApiMesureController]
 */
class ApiMesureController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $removeReturnline = "/\s+/u";
    public static $reference = "<strong>[PROJET-002]</strong>";
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
        private EntityManagerInterface $em
    ) {
        $this->em = $em;
    }

    /**
     * [Description for projetMesures]
     * Récupère les indicateurs de mesures
     * http://{url}/api/components/app?component={key}
     * http://{URL}/api/measures/component?component={key}&metricKeys=ncloc
     *
     * @param Request $request
     * @param Client $client
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:29:58 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/mesure', name: 'projet_mesure_Collecte', methods: ['POST'])]
    public function projetMesureCollecte(Request $request, Client $client): response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return $response->setData(['data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                                        'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

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

        /** On forge l'URL */
        $url1 = "$tempoUrl/api/components/app?component=$data->maven_key";

        /** on appel le client http */
        $result1 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url1)));
        /** On catch les erreurs HTTP 400, 401 et 404, si possible :) */
        if (array_key_exists('code', $result1)){
            if ($result1['code']===401) {
            return $response->setData([
                'type'=>'warning',
                'mode' => $data->mode ,
                'code' => 401,
                'reference' => static::$reference,
                'message' => static::$erreur401,
                Response::HTTP_OK]);
            }
            if ($result1['code']===404){
                return $response->setData([
                    'type'=>'alert',
                    "mode" => $data->mode ,
                    "code" => 404,
                    "reference" => static::$reference,
                    "message" => static::$erreur404,
                    Response::HTTP_OK]);
                }
        }

        /** On créé un objet date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** On ajoute les mesures dans la table mesures. */
        $lines = 0;
        if (intval($result1['measures']['lines'])) {
            $lines = intval($result1['measures']['lines']);
        }

        /** Warning: Undefined array key "coverage" */
        $coverage = 0;
        if (array_key_exists('coverage', $result1['measures'])) {
            $coverage = $result1['measures']['coverage'];
        }

        /** Warning: Undefined array key "duplicationDensity" */
        $duplicationDensity = 0;
        if (array_key_exists('duplicationDensity', $result1['measures'])) {
            $duplicationDensity = $result1['measures']['duplicationDensity'];
        }

        /** Warning: Undefined array key "measures" */
        $tests = 0;
        if (array_key_exists('tests', $result1['measures'])) {
            $tests = intval($result1['measures']['tests']);
        }

        /** Warning: Undefined array key "issues" */
        $issues = 0;
        if (array_key_exists('issues', $result1['measures'])) {
            $issues = intval($result1['measures']['issues']);
        }

        /** On récupère le nombre de ligne de code */
        $url2 = "$tempoUrl/api/measures/component?component=$data->maven_key&metricKeys=ncloc";
        $result2 = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url2)));

        $ncloc = 0;
        if (array_key_exists('measures', $result2['component'])) {
            $ncloc = intval($result2['component']['measures'][0]['value']);
        }

        /** On récupère le ration de dette technique */
        $url3 = "$tempoUrl/api/measures/component?component=$data->maven_key&metricKeys=sqale_debt_ratio";
        $result3 = $client->http(preg_replace(static::$removeReturnline, " ", $url3));

        $sqaleRatio = -1;
        if (array_key_exists('measures', $result3['component'])) {
            $sqaleRatio = intval($result3['component']['measures'][0]['value']);
        }

        /** On enregistre */
        $mesure = new Mesures();
        $mesure->setMavenKey($data->maven_key);
        $mesure->setProjectName($result1["projectName"]);
        $mesure->setLines($lines);
        $mesure->setNcloc($ncloc);
        $mesure->setSqaleDebtRatio($sqaleRatio);
        $mesure->setCoverage($coverage);
        $mesure->setDuplicationDensity($duplicationDensity);
        $mesure->setTests(intval($tests));
        $mesure->setIssues(intval($issues));
        $mesure->setDateEnregistrement($date);
        $this->em->persist($mesure);
        if ($data->mode != 'TEST') {
            $this->em->flush();
        }

        return $response->setData(['mode' => $data->mode , 'code' => 200, Response::HTTP_OK]);
    }

}
