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
use App\Entity\Main\NoSonar;
use App\Entity\Main\Todo;

use Doctrine\ORM\EntityManagerInterface;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

class ApiNosonarController extends AbstractController
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
     * [Description for projetNosonarAjout]
     * On récupère la liste des fichiers ayant fait l'objet d'un
     * @@supresswarning ou d'un noSONAR
     * http://{url}api/issues/search?componentKeys={key}&rules={rules}&ps=500&p=1
     * {key} = la clé du projet
     * {rules} = java:S1309 et java:NoSonar
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:42:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/nosonar/details', name: 'projet_nosonar', methods: ['GET'])]
    public function projetNosonarAjout(Client $client, Request $request): response
    {
        /** On créé un objet response */
        $response = new JsonResponse();

        /** On bind les variables. */
        $mode = $request->get('mode');
        $mavenKey = $request->get('mavenKey');
        $tempoUrl = $this->getParameter(static::$sonarUrl);

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

        /** On construit l'URL et on appel le WS. */
        $url = "$tempoUrl/api/issues/search?componentKeys=$mavenKey
            &rules=java:S1309,java:NoSonar&ps=500&p=1";

        $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** On supprime les données du projet de la table NoSonar. */
        $sql = "DELETE FROM no_sonar WHERE maven_key='$mavenKey'";
        if($mode !== "TEST") {
            $this->em->getConnection()->prepare($sql)->executeQuery();
        }

        /**
         * Si on a trouvé des @notations de type nosSonar ou SuprressWarning.
         * dans le code alors on les dénombre
         */
        if ($result["paging"]["total"] !== 0) {
            foreach ($result["issues"] as $issue) {
                $nosonar = new NoSonar();
                $nosonar->setMavenKey($request->get('mavenKey'));
                $nosonar->setRule($issue["rule"]);
                $component = str_replace("$mavenKey :", "", $issue["component"]);
                $nosonar->setComponent($component);
                if (empty($issue["line"])) {
                    $line = 0;
                } else {
                    $line = $issue["line"];
                }
                $nosonar->setLine($line);
                $nosonar->setDateEnregistrement($date);

                $this->em->persist($nosonar);
                if ($mode != "TEST") {
                    $this->em->flush();
                }
            }
        } else {
            /** Il n'y a pas de noSOnar ou de suppressWarning */
        }

        return $response->setData(["mode" => $mode,"nosonar" => $result["paging"]["total"], Response::HTTP_OK]);
    }

    /**
     * [Description for projetNosonarAjout]
     * On récupère la liste des fichiers ayant fait l'objet d'un "To do"
     * http://{url}api/issues/search?componentKeys={key}&rules={rules}&ps=500&p=1
     * {key} = la clé du projet
     * {rules} = javascript:S1135, xml:S1135, typescript:S1135, Web:S1135, java:S1135
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 10/04/2023, 15:18:45 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/todo/details', name: 'projet_todo', methods: ['GET'])]
    public function projetTodoAjout(Client $client, Request $request): response
    {
        /** On créé un objet response */
        $response = new JsonResponse();

        /** On bind les variables. */
        $mode = $request->get('mode');
        $mavenKey = $request->get('mavenKey');
        $tempoUrl = $this->getParameter(static::$sonarUrl);

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

        /** On construit l'URL et on appel le WS. */
        $url = "$tempoUrl/api/issues/search?componentKeys=$mavenKey
            &rules=javascript:S1135,xml:S1135,typescript:S1135,Web:S1135,java:S1135&ps=500&p=1";

        $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** On supprime les données du projet de la table to do. */
        $sql = "DELETE FROM todo WHERE maven_key='$mavenKey'";
        if($mode !== "TEST") {
            $this->em->getConnection()->prepare($sql)->executeQuery();
        }

        /**
         * Si on a trouvé des to do dans le code alors on les dénombre
         */
        if ($result["paging"]["total"] !== 0) {
            foreach ($result["issues"] as $issue) {
                $nosonar = new Todo();
                $nosonar->setMavenKey($request->get('mavenKey'));
                $nosonar->setRule($issue["rule"]);
                $component = str_replace("$mavenKey :", "", $issue["component"]);
                $nosonar->setComponent($component);
                if (empty($issue["line"])) {
                    $line = 0;
                } else {
                    $line = $issue["line"];
                }
                $nosonar->setLine($line);
                $nosonar->setDateEnregistrement($date);

                $this->em->persist($nosonar);
                if ($mode != "TEST") {
                    $this->em->flush();
                }
            }
        } else {
            /** Il n'y a pas de to do */
        }

        return $response->setData(["mode" => $mode,"todo" => $result["paging"]["total"], Response::HTTP_OK]);
    }

}
