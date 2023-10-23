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
use App\Entity\Main\Mesures;
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
    public static $reference = "<strong>[PROJET-002]</strong>";
    public static $message = "Vous devez avoir le rôle COLLECTE pour réaliser cette action.";

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
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:29:58 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/mesures', name: 'projet_mesures', methods: ['GET'])]
    public function projetMesures(Request $request, Client $client): response
    {
        /** oN créé un objet réponse */
        $response = new JsonResponse();

        /** On on vérifie si on a activé le mode test */
        if (is_null($request->get('mode'))) {
            $mode = "null";
        } else {
            $mode = $request->get('mode');
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $response->setData([
              "mode" => $mode ,
              "type" => 'alert',
              "reference" => static::$reference,
              "message" => static::$message,
              Response::HTTP_OK]);
        }

        /** On bind les variables */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = $request->get('mavenKey');

        /** mesures globales */
        $url1 = "$tempoUrl/api/components/app?component=$mavenKey";

        /** on appel le client http */
        $result1 = $client->http($url1);
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** On ajoute les mesures dans la table mesures. */
        if (intval($result1["measures"]["lines"])) {
            $lines = intval($result1["measures"]["lines"]);
        } else {
            $lines = 0;
        }

        /** Warning: Undefined array key "coverage" */
        if (array_key_exists("coverage", $result1["measures"])) {
            $coverage = $result1["measures"]["coverage"];
        } else {
            $coverage = 0;
        }

        /** Warning: Undefined array key "duplicationDensity" */
        if (array_key_exists("duplicationDensity", $result1["measures"])) {
            $duplicationDensity = $result1["measures"]["duplicationDensity"];
        } else {
            $duplicationDensity = 0;
        }

        /** Warning: Undefined array key "measures" */
        if (array_key_exists("tests", $result1["measures"])) {
            $tests = intval($result1["measures"]["tests"]);
        } else {
            $tests = 0;
        }

        /** Warning: Undefined array key "issues" */
        if (array_key_exists("issues", $result1["measures"])) {
            $issues = intval($result1["measures"]["issues"]);
        } else {
            $issues = 0;
        }

        /** On récupère le nombre de ligne de code */
        $url2 = "$tempoUrl/api/measures/component?component=$mavenKey&metricKeys=ncloc";
        $result2 = $client->http($url2);

        if (array_key_exists("measures", $result2["component"])) {
            $ncloc = intval($result2["component"]["measures"][0]["value"]);
        } else {
            $ncloc = 0;
        }

        /** On enregistre */
        $mesure = new Mesures();
        $mesure->setMavenKey($mavenKey);
        $mesure->setProjectName($result1["projectName"]);
        $mesure->setLines($lines);
        $mesure->setNcloc($ncloc);
        $mesure->setCoverage($coverage);
        $mesure->setDuplicationDensity($duplicationDensity);
        $mesure->setTests(intval($tests));
        $mesure->setIssues(intval($issues));
        $mesure->setDateEnregistrement($date);
        $this->em->persist($mesure);
        if ($mode != "TEST") {
            $this->em->flush();
        }

        if ($mode = "TEST") {
            $mesures = ['coverage' => $coverage,
                      'duplicationDensity' => $duplicationDensity,
                      'tests' => $tests, 'issues' => $issues, 'ncloc' => $ncloc];
            return $response->setData(["mode" => $mode, 'mesures' => $mesures, Response::HTTP_OK]);
        }

        return $response->setData([Response::HTTP_OK]);
    }

}
