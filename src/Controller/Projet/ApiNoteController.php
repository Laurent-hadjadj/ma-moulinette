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

/** gestion des entity */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Notes;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description ApiNoteController]
 */
class ApiNoteController extends AbstractController
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
        private EntityManagerInterface $em
    ) {
        $this->em = $em;
    }


    /**
     * [Description for historiqueNoteAjout]
     * Récupère les notes pour la fiabilité, la sécurité et les mauvaises pratiques.
     * On récupère que la première page soit 1000 résultat max.
     * Les valeurs possibles pour {type} sont : reliability_rating,security_rating,sqale_rating
     * http://{url}https://{url}/api/measures/search_history?component={key}}&metrics={type}&ps=1000
     *
     * Phase 04
     *
     * @param Request $request
     * @param Client $client
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:36:58 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/note', name: 'projet_note_collecte', methods: ['POST'])]
    public function projetNoteCollecte(Client $client, Request $request): response
    {
        /** On instancie l'entityRepository */
        $notesEntity = $this->em->getRepository(Notes::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') || !property_exists($data, 'type')) {
            return $response->setData(['data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference, 'message'=> static::$erreur400,
            Response::HTTP_BAD_REQUEST]);
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

        /** On récupère l'URl du serveur. */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        /** On construit l'URL */
        $url = "$tempoUrl/api/measures/component?component=$data->maven_key&metricKeys=$data->type"."_rating";
        /** On appel le client http. */
        $result = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url)));
        /** On catch les erreurs HTTP 400, 401 et 404, si possible :) */
        if (array_key_exists('code', $result)){
            if ($result['code']===401) {
            return $response->setData([
                'type'=>'warning',
                'code' => 401,
                'reference' => static::$reference,
                'message' => static::$erreur401,
                Response::HTTP_OK]);
            }
            if ($result['code']===404){
                return $response->setData([
                    'type'=>'alert',
                    "code" => 404,
                    "reference" => static::$reference,
                    "message" => static::$erreur404,
                    Response::HTTP_OK]);
                }
        }

        /** On construit un objet date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $dateEnregistrement = $date->format(static::$dateFormat);

        /** On supprime les notes pour la maven_key. */
        $map=['maven_key'=>$data->maven_key, 'type'=>$data->type];
        $delete=$notesEntity->deleteNotesMavenKey($map);
        if ($delete['code']!=200) {
            return $response->setData([
                'type' => 'alert',
                'reference' => static::$reference,
                'code' => $delete['code'],
                'message'=>$delete['erreur'],
                Response::HTTP_OK]);
        }

        /** Enregistrement des nouvelles valeurs. */
        $note=$result['component']['measures'][0]['value'];
        $dateNote=$result['component']['measures'][0]['date'];
        $map=['maven_key'=>$data->maven_key, 'type'=>$data->type, 'date'=> new \DateTime($dateNote), 'value'=>$note, 'date_enregistrement'=>$dateEnregistrement];
        $request=$notesEntity->InsertNotes($map);

        if ($data->type == 'reliability') {
            $types = 'Fiabilité';
        }
        if ($data->type == 'security') {
            $types = 'Sécurité';
        }
        if ($data->type == 'sqale') {
            $types = 'Mauvaises Pratiques';
        }

        return $response->setData(['code' => 200, 'type' => $types, Response::HTTP_OK]);
    }

}
