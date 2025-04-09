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
use App\Service\Client;

/** Accès aux tables */
use App\Entity\Actuator;
use App\Entity\ActuatorInfo;

/** Client HTTP */
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

/**
 * [Description BatchCollecteActuatorController]
 */
class BatchCollecteActuatorController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";

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
     * [Description for BatchCollecteActuatorInfo]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 25/06/2024 14:50:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteActuatorInfo(string $mavenKey): array
    {
        /** On instancie l'EntityRepository */
        $actuatorRepository = $this->em->getRepository(Actuator::class);

        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');
        /** On regarde si, il y a une point d'accès défini pour le projet */
        $map = ['maven_key' => $mavenKey];
        $actuatorEndpoint = $actuatorRepository->findActuatorMavenKey($map);
        if (isset($actuatorEndpoint['code']) && in_array($actuatorEndpoint['code'], [23502, 23505, 500, 503])) {
            return [
                'code' => $actuatorEndpoint['code'],
                'erreur' => $actuatorEndpoint['erreur']
            ];
        }

        /** Il n'y a pas de endpoint pour ce projet */
        if ($actuatorEndpoint['code'] === 404){
            return ['code' => 404,
                    'message' => "Il n'y a pas de point-d'accès défini pour ce projet (Erreur 404)."];
        }

        /** On construit l'URL */
        $actuatorUser = $actuatorEndpoint['user'];
        $actuatorPassword = $actuatorEndpoint['password'];
        $baseUrl = $actuatorEndpoint['url'];
        $ressource = 'actuator/info';
        $url = "$baseUrl/$ressource";

        /** On nettoie l'URL */
        $url = htmlspecialchars($url, ENT_QUOTES, 'UTF-8');

         /** Appelle le clientActuator HTTP */
        $queryParams = [];
        $actuatorInfo = $this->client->httpActuator("$url".http_build_query($queryParams), $actuatorUser, $actuatorPassword);

        $data = $actuatorInfo['json'];

        /** On catch les erreurs HTTP  */
        if (isset($data['code']) && in_array($data['code'], [400, 401, 403, 404, 500])) {
                return ['code' => $data['code'], 'erreur' => [$data['erreur']]];
        }

        /** On renvoi les résultats */
        return ['code' => 200, 'message' => $data];
    }

}
