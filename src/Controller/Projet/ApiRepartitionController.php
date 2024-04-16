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

/** Accès aux tables SLQLite */
use App\Entity\Secondary\Repartition;
use Doctrine\Persistence\ManagerRegistry;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description ApiRepartitionController]
 */
class ApiRepartitionController extends AbstractController
{
    /**
     * [Description for __construct]
     *
     * Created at: 04/12/2022, 09:00:38 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private ManagerRegistry $doctrine,
        private LoggerInterface $logger
    ) {
        $this->doctrine = $doctrine;
        $this->logger = $logger;
    }

    public static $sonarUrl = "sonar.url";
    public static $strContentType = 'application/json';
    public static $apiIssuesSearch = "/api/issues/search?componentKeys=";
    public static $regex = "/\s+/u";

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
     * [Description for batch_Analyse]
     *
     * @param mixed $elements
     * @param mixed $mavenKey
     *
     * @return ['frontend'=>$frontend, 'backend'=>$backend, 'autre'=>$autre];
     *
     * Created at: 04/12/2022, 09:00:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     *
     */
    protected function batch_Analyse($elements, $mavenKey)
    {
        $frontend = $backend = $autre = 0;
        $app=static::extractNameFromMavenKey($mavenKey);

        foreach ($elements as $element) {
            $file = str_replace($mavenKey . ":", "", $element->getComponent());
            $module = explode("/", $file);
            if ($module[0] === "du-presentation" ||
                $module[0] === "rs-presentation") {
                $frontend = $frontend + 1;
            }
            if ($module[0] === $app . "-presentation" ||
                $module[0] === $app . "-presentation-commun" ||
                $module[0] === $app . "-presentation-ear" ||
                $module[0] === $app . "-webapp") {
                $frontend = $frontend + 1;
            }
            if ($module[0] === "rs-metier") {
                $backend = $backend + 1;
            }
            if ($module[0] === $app . "-metier" ||
                $module[0] === $app . "-common" ||
                $module[0] === $app . "-api" ||
                $module[0] === $app . "-dao") {
                $backend = $backend + 1;
            }
            if ($module[0] === $app . "-metier-ear" ||
                $module[0] === $app . "-service" ||
                $module[0] === $app . "-serviceweb" ||
                $module[0] === $app . "-middleoffice") {
                $backend = $backend + 1;
            }
            if ($module[0] === $app . "-metier-rest" ||
                $module[0] === $app . "-entite" ||
                $module[0] === $app . "-serviceweb-client") {
                $backend = $backend + 1;
            }
            if ($module[0] === $app . "-batch" ||
                $module[0] === $app . "-batchs" ||
                $module[0] === $app . "-batch-envoi-dem-aval" ||
                $module[0] === $app . "-batch-import-billets") {
                $autre = $autre + 1;
            }
            if ($module[0] === $app . "-rdd") {
                $autre = $autre + 1;
            }
        }
        return ['frontend' => $frontend, 'backend' => $backend, 'autre' => $autre];
    }

    /**
     * [Description for batch_anomalie]
     * Fonction qui permet de parser les anomalies par type selon le nombre
     * de page disponible.
     * $pageSize = 1 à 500
     * $index = 1 à 20 max ==> 10000 anomalies
     * $type = BUG,VULNERABILITY,CODE_SMELL
     * $severite = INFO,MINOR,MAJOR,CRITICAL,BLOCKER
     * http://{url}/api/issues/search?componentKeys={key}&statuses=OPEN,CONFIRMED,REOPENED&
     * resolutions=&s=STATUS&asc=no&types={type}&severities={severite}=&ps={pageSize}&p=
     *
     * @param mixed $mavenKey
     * @param mixed $index
     * @param mixed $pageSize
     * @param mixed $type
     * @param mixed $severity
     *
     * @return [type]
     *
     * Created at: 04/12/2022, 09:02:29 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    protected function batch_anomalie(Client $client, $mavenKey, $index, $pageSize, $type, $severity)
    {

        /** On bind les variables */
        $tempoPageSize = "&ps=$pageSize";
        $tempoPageindex = "&p=$index";
        $tempoStates = "&statuses=OPEN,CONFIRMED,REOPENED&resolutions=&s=STATUS&asc=no";
        $tempoType = "&types=$type";
        $tempoSeverity = "&severities=$severity";

        /** On bind les variables */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $tempoApi = static::$apiIssuesSearch;

        /** On construit l'URL */
        $url1 = "$tempoUrl$tempoApi$mavenKey$tempoStates$tempoType";
        $url2 = "$tempoSeverity$tempoPageSize$tempoPageindex";
        /** On appel l'Api et on renvoie le résultat */
        return $client->http($url1.$url2);
    }

    /**
     * [Description for projetRepartitionDetails]
     * Récupère le total des anomalies par severité.
     *
     * @param Request $request
     *
     * @return response
     * INFO,MINOR,MAJOR,CRITICAL,BLOCKER
     *
     * Created at: 04/12/2022, 09:03:46 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/repartition/details', name: 'projet_repartition_details', methods: ['GET'])]
    public function projetRepartitionDetails(Client $client, Request $request): response
    {
        $mavenKey = $request->get('mavenKey');
        $type = $request->get('type');

        $response = new JsonResponse();
        /** On teste si la clé est valide */
        if (is_null($mavenKey)) {
            return $response->setData(["message" => "La clé maven est vide!", Response::HTTP_BAD_REQUEST]);
        }

        /** On récupère le nombre d'anomalie pour le type */
        $severity = ['INFO','MINOR','MAJOR','CRITICAL','BLOCKER'];
        $total = 0;
        foreach ($severity as $value) {
            $result = $this->batch_anomalie($client, $mavenKey, 1, 1, $type, $value);
            if ($value === 'INFO') {
                $info = $result['total'];
            }
            if ($value === 'MINOR') {
                $minor = $result['total'];
            }
            if ($value === 'MAJOR') {
                $major = $result['total'];
            }
            if ($value === 'CRITICAL') {
                $critical = $result['total'];
            }
            if ($value === 'BLOCKER') {
                $blocker = $result['total'];
            }
            $total = $total + $result['total'];
        }

        return $response->setData(
            ["total" => $total,
            "type" => $type,
            "blocker" => $blocker,
            "critical" => $critical,
            "major" => $major,
            "minor" => $minor,
            "info" => $info,
            Response::HTTP_OK]
        );
    }

    /**
     * [Description for projetRepartitionCollecte]
     * Calcul la répartition entre front, back et autre pour tout les type et les sévérités.
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 04/12/2022, 09:04:35 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/repartition/collecte', name: 'projet_repartition_collecte', methods: ['PUT'])]
    public function projetRepartitionCollecte(Client $client, Request $request): response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On bind les variables */
        $mavenKey = $data->mavenKey;
        $type = $data->type;
        $severity = $data->severity;
        $setup = $data->setup;
        $mode = $data->mode;

        /** on créé un objet date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone('Europe/Paris'));

        /** On récupère le nombre d'anomalie pour le type */
        $result = $this->batch_anomalie($client, $mavenKey, 1, 1, $type, $severity);
        $i = 1;
        $date1 = time();
        while (!empty($result["issues"]) && $i < 21) {
            $result = $this->batch_anomalie($client, $mavenKey, $i, 500, $type, $severity);
            foreach ($result["issues"] as $issue) {
                $type = $issue["type"];
                $severity = $issue["severity"];
                $component = $issue["component"];

                $issue = new Repartition();
                $issue->setMavenKey($mavenKey);
                $issue->setName(static::extractNameFromMavenKey($mavenKey));
                $issue->setComponent($component);
                $issue->setType($type);
                $issue->setSeverity($severity);
                $issue->setSetup($setup);
                $issue->setDateEnregistrement($date);

                $manager = $this->doctrine->getManager('secondary');
                $manager->persist($issue);
                if ($mode !== "TEST") {
                    $manager->flush();
                }
            }
            $i++;
        }
        $date2 = time();
        $response = new JsonResponse();
        return $response->setData(
            [ "mode" => $mode,
            "total" => $result["total"],
            "type" => $type,
            "severity" => $severity,
            "setup" => $setup,
            "temps" => abs($date1 - $date2) + 2,
            Response::HTTP_OK]
        );
    }

    /**
     * [Description for projetRepartitionClear]
     * On fait un PUT pour un Delete
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 04/12/2022, 09:05:01 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/repartition/clear', name: 'projet_repartition_clear', methods: ['PUT'])]
    public function projetRepartitionClear(Request $request): Response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On bind les variables */
        $mavenKey = $data->mavenKey;
        $mode = $data->mode;

        /** On créé un nouvel objet Json */
        $response = new JsonResponse();

        /** On surprime de la table historique le projet */
        $sql = "DELETE FROM repartition WHERE maven_key='$mavenKey'";
        $conn = \Doctrine\DBAL\DriverManager::getConnection(['url' => $this->getParameter('sqlite.secondary.path')]);
        if ($mode != "TEST") {
            try {
                $conn->prepare($sql)->executeQuery();
            } catch (\Doctrine\DBAL\Exception $e) {
                return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
            }
        }
        return $response->setData(["mode" => $mode, "code" => "OK", Response::HTTP_OK]);
    }

    /**
     * [Description for projetRepartitionAnalyse]
     *
     * @param Request $request
     *
     * @return Response
     * ["code" => "OK", "repartition"=>$result, Response::HTTP_OK]
     *
     * Created at: 04/12/2022, 09:05:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/repartition/analyse', name: 'projet_repartition_analyse', methods: ['PUT', 'GET'])]
    public function projetRepartitionAnalyse(Request $request): Response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On bind les variables */
        $mavenKey = $data->mavenKey;
        $type = $data->type;
        $severity = $data->severity;
        $setup = $data->setup;
        $mode = $data->mode;

        /**$mode='null';
        $mavenKey = "fr.franceagrimer:rnm";
        $type = "CODE_SMELL";
        $severity = "MAJOR";
        $setup = 1685694576592;**/

        /** On créé un nouvel objet Json */
        $response = new JsonResponse();

        /** On récupère la liste des bugs */
        $liste = $this->doctrine
          ->getManager('secondary')
          ->getRepository(Repartition::class)
          ->findBy(
              [
              'mavenKey' => $mavenKey,
              'type' => $type,
              'severity' => $severity,
              'setup' => $setup]
          );
        /** on appelle le service d'analyse */
        $result = $this->batch_analyse($liste, $mavenKey);
        return $response->setData(["mode" => $mode,"code" => "OK", "repartition" => $result, Response::HTTP_OK]);
    }

}
