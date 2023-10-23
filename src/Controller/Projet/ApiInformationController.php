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
use App\Entity\Main\InformationProjet;

use Doctrine\ORM\EntityManagerInterface;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description ApiInformationController]
 */
class ApiInformationController extends AbstractController
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
     * [Description for projetAnalyses]
     * Récupère les informations du projet (id de l'enregistrement, date de l'analyse, version, type de version).
     * http://{url}/api/project_analyses/search?project={key}
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:29:13 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/analyses', name: 'projet_analyses', methods: ['GET'])]
    public function projetAnalyses(Request $request, Client $client): response
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

        $url = $this->getParameter(static::$sonarUrl) .
          "/api/project_analyses/search?project=" . $request->get('mavenKey');

        /** On appel le client http */
        $result = $client->http($url);
        /** On récupère le manager de BD */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $mavenKey = $request->get('mavenKey');

        /** On supprime les informations sur le projet */
        $sql = "DELETE FROM information_projet WHERE maven_key='$mavenKey'";
        if ($mode != "TEST") {
            $this->em->getConnection()->prepare($sql)->executeQuery();
        }

        /** On ajoute les informations du projets dans la table information_projet. */
        $nombreVersion = 0;

        foreach ($result["analyses"] as $analyse) {
            $nombreVersion++;
            /**
             *  La version du projet doit être xxx-release, xxx-snapshot ou xxx
             *  Dans ce cas le tableau renvoi toujours [0] pour la version et
             *  [1] pour le type de version (release, snaphot ou null)
             */
            $explode = explode("-", $analyse["projectVersion"]);
            if (empty($explode[1])) {
                $explode[1] = 'N.C';
            }

            $informationProjet = new InformationProjet();
            $informationProjet->setMavenKey($mavenKey);
            $informationProjet->setAnalyseKey($analyse["key"]);
            $informationProjet->setDate(new DateTime($analyse["date"]));
            $informationProjet->setProjectVersion($analyse["projectVersion"]);
            $informationProjet->setType(strtoupper($explode[1]));
            $informationProjet->setDateEnregistrement($date);
            $this->em->persist($informationProjet);
            if ($mode != "TEST") {
                $this->em->flush();
            }
        }

        return $response->setData(["mode" => $mode ,"nombreVersion" => $nombreVersion, Response::HTTP_OK]);
    }

}
