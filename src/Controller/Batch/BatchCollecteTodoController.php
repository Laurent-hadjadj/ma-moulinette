<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2024.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Batch;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Todo;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteTodoController]
 */
class BatchCollecteTodoController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $request = "requête : ";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private Client $client
    ) {
        $this->em = $em;
        $this->client = $client;
    }

    /**
     * [Description for BatchCollecteTodo]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 31/05/2024 20:28:47 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteTodo(string $mavenKey): array
    {
        /** On instancie l'EntityRepository */
        $todoRepository = $this->em->getRepository(Todo::class);

        /** On créé un objet date. */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /** Appelle le client HTTP */
        $queryParams = ['componentKeys'=>$mavenKey,
        'rules'=> 'javascript:S1135,xml:S1135,typescript:S1135,Web:S1135,java:S1135,php:s1135,ruby:s1135,python:s1135', 'p'=>1, 'ps'=>500 ];

        /** On construit l'URL et on appel le WS. */
        $result = $this->client->http("$tempoUrl/api/issues/search?".http_build_query($queryParams));
         /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (isset($result['code']) && in_array($result['code'], [401, 404])) {
            return ['code' => $result['code']];
        }

        /** On crée un objet date */
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On supprime les résultats pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $delete=$todoRepository->deleteTodoMavenKey($map);
        if ($delete['code']!=200) {
            return ['code' => $delete['code'], static::$request=>'deleteTodoMavenKey'];
        }

        /** Si on a trouvé des to.do dans le code alors on les dénombre */
        $todo = 0;
        $mapData=[];
        if ($result["paging"]["total"] !== 0) {
            foreach ($result["issues"] as $issue) {
                $todo++;
                $component = str_replace("$mavenKey :", "", $issue["component"]);
                $line = empty($issue["line"]) ? 0 : $issue["line"];

                /** On créé la map */
                $mapData[] = [
                    'maven_key' => $mavenKey,
                    'rule' => $issue["rule"],
                    'component' => $component,
                    'line' => $line,
                    'date_enregistrement' => $date
                ];
            }
        } else {
            /** Il n'y a pas de Todo */
        }

        /* On enregistre */
        $request=$todoRepository->insertTodo($mapData);
        if ($request['code']!=200) {
            return ['code' => $request['code'],
            'error'=>[$request['erreur']],
            static::$request=>'insertTodo'];
        }
        /** On enregistre les données */

        return ['code' => 200, "message" =>['nombre'=>$todo,'Todo'=>$mapData]];
    }
}
