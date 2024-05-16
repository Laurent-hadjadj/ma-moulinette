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
use App\Entity\NoSonar;

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
    public static $apiIssuesSearch = "/api/issues/search?componentKeys=";
    public static $removeReturnline = "/\s+/u";
    public static $reference = "<strong>[PROJET-004]</strong>";
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
     * [Description for projetNosonarCollecte]
     * On récupère la liste des fichiers ayant fait l'objet d'un
     * @@supresswarning ou d'un noSONAR
     * http://{url}/api/projet/nosonar
     *
     * Phase 11
     *
     * {key} = la clé du projet
     * {rules} = java:S1309 et java:NoSonar
     *
     * @param Request $request
     * @param Client $client
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:42:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/nosonar', name: 'projet_nosonar_collect', methods: ['POST'])]
    public function projetNosonarCollect(Client $client, Request $request): response
    {
        /** On instancie l'entityRepository */
        $noSonarEntity = $this->em->getRepository(NoSonar::class);

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
                "code" => 403,
                "reference" => static::$reference,
                "message" => static::$erreur403,
                Response::HTTP_OK]);
        }

       /** On récupère l'url du serveur */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** On construit l'URL et on appel le WS. */
        $url = "$tempoUrl/api/issues/search?componentKeys=$data->maven_key
                &rules=java:S1309,java:NoSonar&ps=500&p=1";

        /** On récupère les données. */
        $result = $client->http(trim(preg_replace(static::$removeReturnline, " ", $url)));
        /** on catch les erreurs HTTP 400, 401 et 404, si possible :) */
        if (array_key_exists('code', $result)){
            if ($result['code']===401) {
            return $response->setData([
                'code' => 401,
                'reference' => static::$reference,
                'message' => static::$erreur401,
                Response::HTTP_OK]);
            }
            if ($result['code']===404){
                return $response->setData([
                    'code' => 404,
                    'reference' => static::$reference,
                    'message' => static::$erreur404,
                    Response::HTTP_OK]);
                }
        }

        /** On supprime les notes pour la maven_key. */
        $map=['maven_key'=>$data->maven_key];
        $request=$noSonarEntity->deleteNoSonarMavenKey($map);
        if ($request['code']!=200) {
            return $response->setData([
                'type' => 'alert',
                'reference' => static::$reference,
                'code' => $request['code'],
                'message'=>$request['erreur'],
                Response::HTTP_OK]);
        }

        /** On créé un objet date */
        $dateEnregistrement = new Datetime();
        $dateEnregistrement->setTimezone(new DateTimeZone(static::$europeParis));

        /**
         * Si on a trouvé des @notations de type nosSonar ou SuprressWarning.
         * dans le code alors on les dénombre
         */
        if ($result['paging']['total'] !== 0) {
            foreach ($result['issues'] as $issue) {
                $component = str_replace('$data->maven_key :', "", $issue['component']);
                $line = 0;
                if (!empty($issue['line'])) {
                    $line = $issue['line'];
                }

                $map=['maven_key'=>$data->maven_key, 'rule'=>$issue['rule'],
                'component'=>$component, 'line'=>$line,
                'date_enregistrement'=>$dateEnregistrement];
                $request=$noSonarEntity->InsertNoSonar($map);
                if ($request['code']!=200) {
                    return $response->setData([
                        'type' => 'alert',
                        'reference' => static::$reference,
                        'code' => $request['code'],
                        'message'=>$request['erreur'],
                        Response::HTTP_OK]);
                }
            }
        } else {
            /** Il n'y a pas de noSOnar ou de suppressWarning */
        }

        return $response->setData(['code'=>200, 'nosonar' => $result["paging"]['total'], Response::HTTP_OK]);
    }

}
