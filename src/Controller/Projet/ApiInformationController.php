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
use App\Entity\Main\InformationProjet;

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
    public static $removeReturnline = "/\s+/u";
    public static $reference = "<strong>[PROJET-001]</strong>";
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
     * [Description for projetAnalyses]
     * Récupère les informations du projet (id de l'enregistrement, date de l'analyse, version, type de version).
     * http://{url}/api/project_analyses/search?project={key}
     *
     * @param Request $request
     * @param Client $client
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:29:13 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/information', name: 'projet_information_collecte', methods: ['POST'])]
    public function projetInformationCollecte(Request $request, Client $client): response
    {
        /** On instancie l'EntityRepository */
        $informationProjet = $this->em->getRepository(InformationProjet::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, 'type'=>'alert', 'reference'=> static::$reference,
            'message' => static::$erreur400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, 'type'=>'alert','reference'=> static::$reference,'message' => static::$erreur400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, 'type'=>'alert','reference'=> static::$reference,'message' => static::$erreur400, Response::HTTP_BAD_REQUEST]); }

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

        /** On forge la requête */
        $url = $this->getParameter(static::$sonarUrl) .
                "/api/project_analyses/search?project=" . $data->maven_key;

        /** On appel le client http */
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
                    'mode' => $data->mode ,
                    'code' => 404,
                    'reference' => static::$reference,
                    'message' => static::$erreur404,
                    Response::HTTP_OK]);
                }
        }

        /** On créé un objet date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));

        /** On supprime les informations pour la maven_key. */
        $map=['maven_key'=>$data->maven_key];
        $request=$informationProjet->deleteInformationProjetMavenKey($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'type'=>'alert',
                'mode' => $data->mode,
                'reference' => static::$reference,
                'code' => $request['code'],
                'message'=>$request['erreur'],
                Response::HTTP_OK]);
        }

        /** On ajoute les informations du projet dans la table information_projet. */
        $nombreVersion = 0;

        foreach ($result["analyses"] as $information) {
            $nombreVersion++;
            /**
             *  La version du projet doit être xxx-release, xxx-snapshot ou xxx
             *  Dans ce cas le tableau renvoi toujours [0] pour la version et
             *  [1] pour le type de version (release, snaphot ou null)
             */
            $explode = explode('-', $information['projectVersion']);
            if (empty($explode[1])) {
                $explode[1] = 'N.C';
            }

            $informationProjet = new InformationProjet();
            $informationProjet->setMavenKey($data->maven_key);
            $informationProjet->setAnalyseKey($information['key']);
            $informationProjet->setDate(new DateTime($information['date']));
            $informationProjet->setProjectVersion($information['projectVersion']);
            $informationProjet->setType(strtoupper($explode[1]));
            $informationProjet->setDateEnregistrement($date);
            $this->em->persist($informationProjet);
            if ($data->mode != 'TEST') {
                $this->em->flush();
            }
        }

        return $response->setData(['mode' => $data->mode ,
            'code'=> 200, 'nombreVersion' => $nombreVersion, Response::HTTP_OK]);
    }

}
