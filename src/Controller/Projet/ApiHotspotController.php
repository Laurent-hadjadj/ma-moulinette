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

class ApiHotspotController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $regex = "/\s+/u";
    public static $erreurMavenKey = "La clé maven est vide!";
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
        private LoggerInterface $logger,
        private EntityManagerInterface $em
    ) {
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * [Description for hotspotAjout]
     * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
     * http://{url}/api/hotspots/search?projectKey={key}&ps=500&p=1
     * On récupère les failles a examiner.
     * Les clés sont uniques (i.e. on ne se base pas sur les tags).
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:39:21 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/hotspot', name: 'projet_hotspot', methods: ['GET'])]
    public function projetHotspot(Request $request, Client $client): response
    {
        /** On créé un objet response */
        $response = new JsonResponse();

        /** On bind les variables. */
        $mode = $request->get('mode');
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = $request->get('mavenKey');

        /** On teste si la clé est valide */
        if ($mavenKey === "null" && $mode === "TEST") {
            return $response->setData([
              "mode" => $mode, "mavenKey" => $mavenKey,
              "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
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

        /** On construit l'URL */
        $url = "$tempoUrl/api/hotspots/search?projectKey=$mavenKey&ps=500&p=1";

        /** On appel l'Api */
        $result = $client->http($url);

        /** On créé un objet Date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $niveau = 0;

        /** On supprime  les enregistrements correspondant à la clé */
        $sql = "DELETE FROM hotspots WHERE maven_key='$mavenKey'";
        if ($mode != "TEST") {
            $this->em->getConnection()->prepare($sql)->executeQuery();
        }

        if ($result["paging"]["total"] != 0) {
            foreach ($result["hotspots"] as $value) {
                if ($value["vulnerabilityProbability"] == "HIGH") {
                    $niveau = 1;
                }
                if ($value["vulnerabilityProbability"] == "MEDIUM") {
                    $niveau = 2;
                }
                if ($value["vulnerabilityProbability"] == "LOW") {
                    $niveau = 3;
                }

                $hotspot = new  Hotspots();
                $hotspot->setMavenKey($request->get('mavenKey'));
                $hotspot->setKey($value["key"]);
                $hotspot->setProbability($value["vulnerabilityProbability"]);
                $hotspot->setStatus($value["status"]);
                $hotspot->setNiveau($niveau);
                $hotspot->setDateEnregistrement($date);

                if ($mode !== "TEST") {
                    $this->em->persist($hotspot);
                    $this->em->flush();
                }
            }
        }

        return $response->setData(
            ["mode" => $mode,"hotspots" => $result["paging"]["total"], Response::HTTP_OK]
        );
    }

    /**
     * [Description for hotspotOwaspAjout]
     * Traitement des hotspots de type owasp pour sonarqube 8.9 et >
     * http://{url}/api/hotspots/search?projectKey={key}{owasp}&ps=500&p=1
     * {key} = la clé du projet
     * {owasp} = le type de faille (a1, a2, etc...)
     * si le paramètre owasp est égale à a0 alors on supprime les enregistrements pour la clé
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:39:44 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/hotspot/owasp', name: 'projet_hotspot_owasp', methods: ['GET'])]
    public function projetHotspotOwasp(Request $request, Client $client): response
    {
        /** On créé un objet response */
        $response = new JsonResponse();

        /** On bind les variables. */
        $mode = $request->get('mode');
        $mavenKey = $request->get('mavenKey');
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $owasp = $request->get('owasp');

        /** On teste si la clé est valide */
        if ($mavenKey === "null" && $mode === "TEST") {
            return $response->setData([
              "owasp" => $owasp,
              "mode" => $mode, "mavenKey" => $mavenKey,
              "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
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

        if ($request->get('owasp') == 'a0') {
            /** On supprime  les enregistrements correspondant à la clé. */
            $sql = "DELETE FROM hotspot_owasp
              WHERE maven_key='$mavenKey'";
            $this->em->getConnection()->prepare($sql)->executeQuery();
            return $response->setData(["info" => "effacement", Response::HTTP_OK]);
        }

        /** On construit l'Url. */
        $url = "$tempoUrl/api/hotspots/search?projectKey=$mavenKey
            &owaspTop10=$owasp&ps=500&p=1";

        /** On appel l'URL. */
        $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));

        /** On créé un objet Date. */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $niveau = 0;

        /** On fleche la vulnérabilité */
        if ($result["paging"]["total"] != 0) {
            foreach ($result["hotspots"] as $value) {
                if ($value["vulnerabilityProbability"] == "HIGH") {
                    $niveau = 1;
                }
                if ($value["vulnerabilityProbability"] == "MEDIUM") {
                    $niveau = 2;
                }
                if ($value["vulnerabilityProbability"] == "LOW") {
                    $niveau = 3;
                }

                $hotspot = new  HotspotOwasp();
                $hotspot->setMavenKey($mavenKey);
                $hotspot->setMenace($owasp);
                $hotspot->setProbability($value["vulnerabilityProbability"]);
                $hotspot->setStatus($value["status"]);
                $hotspot->setNiveau($niveau);
                $hotspot->setDateEnregistrement($date);

                $this->em->persist($hotspot);
                if ($mode !== "TEST") {
                    $this->em->flush();
                }
            }
        } else {
            $hotspot = new  HotspotOwasp();
            $hotspot->setMavenKey($request->get('mavenKey'));
            $hotspot->setMenace($request->get('owasp'));
            $hotspot->setProbability("NC");
            $hotspot->setStatus("NC");
            $hotspot->setNiveau("0");
            $hotspot->setDateEnregistrement($date);
            $this->em->persist($hotspot);
            if ($mode !== "TEST") {
                $this->em->flush();
            }
        }

        return $response->setData(
            [ "mode" => $mode,
            "info" => "enregistrement",
            "hotspots" => $result["paging"]["total"], Response::HTTP_OK
      ]
        );
    }

    /**
     * [Description for hotspotDetails]
     * Fonction privée qui récupère le détail d'un hotspot en fonction de sa clé.
     *
     * @param mixed $mavenKey
     * @param mixed $key
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
        if (empty($hotspot["rule"]["vulnerabilityProbability"])) {
            $severity = "MEDIUM";
        } else {
            $severity = $hotspot["rule"]["vulnerabilityProbability"];
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
        /** nom du projet. */
        $app = explode(":", $mavenKey);

        $status = $hotspot["status"];
        $file = str_replace($mavenKey . ":", "", $hotspot["component"]["key"]);
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
        if ($module[0] == $app[1] . "-presentation") {
            $frontend++;
        }
        if ($module[0] == $app[1] . "-presentation-commun") {
            $frontend++;
        }
        if ($module[0] == $app[1] . "-presentation-ear") {
            $frontend++;
        }
        if ($module[0] == $app[1] . "-webapp") {
            $frontend++;
        }

        /**
         * Application Backend
         */
        if ($module[0] == $app[1] . "-metier") {
            $backend++;
        }
        if ($module[0] == $app[1] . "-common") {
            $backend++;
        }
        if ($module[0] == $app[1] . "-api") {
            $backend++;
        }
        if ($module[0] == $app[1] . "-dao") {
            $backend++;
        }
        if ($module[0] == $app[1] . "-metier-ear") {
            $backend++;
        }
        if ($module[0] == $app[1] . "-service") {
            $backend++;
        }
        // Application : Legacy
        if ($module[0] == $app[1] . "-serviceweb") {
            $backend++;
        }
        if ($module[0] == $app[1] . "-middleoffice") {
            $backend++;
        }
        // Application : Starter-Kit
        if ($module[0] == $app[1] . "-metier-rest") {
            $backend++;
        }
        // Application : Legacy
        if ($module[0] == $app[1] . "-entite") {
            $backend++;
        }
        // Application : Legacy
        if ($module[0] == $app[1] . "-serviceweb-client") {
            $backend++;
        }

        /**
         * Application Batch et Autres
         */
        if ($module[0] == $app[1] . "-batch") {
            $autre++;
        }
        if ($module[0] == $app[1] . "-batch") {
            $autre++;
        }
        if ($module[0] == $app[1] . "-batch-envoi-dem-aval") {
            $autre++;
        }
        if ($module[0] == $app[1] . "-batch-import-billets") {
            $autre++;
        }
        if ($module[0] == $app[1] . "-rdd") {
            $autre++;
        }

        if (empty($hotspot["line"])) {
            $line = 0;
        } else {
            $line = $hotspot["line"];
        }
        $rule = $hotspot["rule"] ? $hotspot["rule"]["name"] : "/";
        $message2 = $hotspot["message"];
        /**
         * On affiche pas la description, même si on la en base,
         * car on pointe sur le serveur sonarqube directement
         * $description=$hotspot["rule"]["riskDescription"];
         */
        $hotspotKey = $hotspot["key"];
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
     * [Description for hotspotDetailsAjout]
     * Récupère le détails des hotspots et les enregistre dans la table Hotspots_details
     * http://{url}/api/projet/hotspot/details{maven_key};
     * {maven_key} = la clé du projet
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:41:45 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/hotspot/details', name: 'projet_hotspot_details', methods: ['GET'])]
    public function hotspotDetailsAjout(Request $request, Client $client): response
    {
        /** On créé un objet response */
        $response = new JsonResponse();

        /** On bind les variables. */
        $mode = $request->get('mode');
        $mavenKey = $request->get('mavenKey');

        /** On teste si la clé est valide */
        if ($mavenKey === "null" && $mode === "TEST") {
            return $response->setData([
              "mode" => $mode, "mavenKey" => $mavenKey,
              "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
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

        /** On récupère la liste des hotspots */
        $sql = "SELECT * FROM hotspots
            WHERE maven_key='$mavenKey'
            AND status='TO_REVIEW' ORDER BY niveau";

        $r = $this->em->getConnection()->prepare($sql)->executeQuery();
        $liste = $r->fetchAllAssociative();

        // On supprime les données de la table hotspots_details pour le projet
        $sql = "DELETE FROM hotspot_details
            WHERE maven_key='$mavenKey'";
        if ($mode != "TEST") {
            $this->em->getConnection()->prepare($sql)->executeQuery();
        }

        /** Si la liste des vide on envoi un code 406 */
        if (empty($liste)) {
            return $response->setData(["mode" => $mode, "code" => 406, Response::HTTP_OK]);
        }

        /**
         * On boucle sur les clés pour récupérer le détails du hotspot.
         * On envoie la clé du projet et la clé du hotspot.
         */
        $ligne = 0;
        foreach ($liste as $elt) {

            $ligne++;
            $key = $this->hotspotDetails($mavenKey, $elt["key"], $client);
            $details = new  HotspotDetails();
            $details->setMavenKey($mavenKey);
            $details->setSeverity($key["severity"]);
            $details->setNiveau($key["niveau"]);
            $details->setStatus($key["status"]);
            $details->setFrontend($key["frontend"]);
            $details->setBackend($key["backend"]);
            $details->setAutre($key["autre"]);
            $details->setFile($key["file"]);
            $details->setLine($key["line"]);
            $details->setRule($key["rule"]);
            $details->setMessage($key["message"]);
            $details->setKey($key["key"]);
            $details->setDateEnregistrement($key["date_enregistrement"]);

            $this->em->persist($details);
            if ($mode != "TEST") {
                $this->em->flush();
            }
        }
        return $response->setData(["mode" => $mode,"ligne" => $ligne, Response::HTTP_OK]);
    }

}
