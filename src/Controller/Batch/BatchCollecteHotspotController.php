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
use App\Entity\Hotspots;
use App\Entity\InformationProjet;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteInformationProjetController]
 */
class BatchCollecteHotspotController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $request = "requête : ";
    public static $erreur404 = "L'appel à l'API n'a pas abouti (Erreur 404).";

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
     * [Description for vulnerabilityProbability]
     *
     * @param string $probability
     *
     * @return int
     *
     * Created at: 26/05/2024 16:04:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function vulnerabilityProbability(string $probability): int
    {
        $levels = [
            'HIGH' => 1,
            'MEDIUM' => 2,
            'LOW' => 3,
        ];
        /** si on a pas de probabilité alors on renvoi -1 */
        return $levels[$probability] ?? -1;
    }

    /**
     * [Description for BatchCollecteHotspot]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 21/05/2024 22:25:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteHotspot(string $mavenKey): array
    {
        /** On instancie l'EntityRepository */
        $hotspotsRepository = $this->em->getRepository(Hotspots::class);
        $informationProjet = $this->em->getRepository(InformationProjet::class);

         /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map=['maven_key'=>$mavenKey];
        $select=$informationProjet->selectInformationProjetProjectVersion($map);
        if ($select['code']!=200) {
            return ['code' => $select['code'], 'message'=>$select['erreur']];
        }

        if (!$select['info']) {
            return ['code' => 404, 'message' => static::$erreur404];
        }

        /** On reconstruit la date de version au format dateTime */
        $dateVersion = new \DateTime($select['info'][0]['date']);
        $dateVersion->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /** Construit l'URL en utilisant http_build_query pour les paramètres de la requête */
        $queryParams = [
            'projectKey' => $mavenKey,
            'ps'=>500,
            'p'=>1
        ];
        /** Appelle le client HTTP */
        $result = $this->client->http("$tempoUrl/api/hotspots/search?".http_build_query($queryParams));
        /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (isset($result['code']) && in_array($result['code'], [401, 404])) {
            return ['code' => $result['code']];
        }

       /** Création de la date du jour */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On supprime les résultats pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $delete=$hotspotsRepository->deleteHotspotsMavenKey($map);
        if ($delete['code']!=200) {
            return ['code' => $delete['code'], static::$request=>'deleteHotspotsMavenKey'];
        }

        $map = [];
        /** On a pas trouvé de hotspot, c'est une bonne chose */
        $niveau = $this->vulnerabilityProbability('NC');
        /** Ajout des hotspots à la liste à insérer */
        $map[] = [
            'maven_key' => $mavenKey,
            'version' => $select['info'][0]['project_version'],
            'date_version' => $dateVersion,
            'key' => 'NC',
            'security_category' => 'NC',
            'probability' => 'NC',
            'status' => 'NC',
            'status' => '',
            'niveau' => $niveau,
            'date_enregistrement' => $date
        ];

        /** On traite les hotspots */
        if ($result['paging']['total'] !== 0) {
            $hotspots = [];
            foreach ($result['hotspots'] as $value) {
                // Traitement de la probabilité de vulnérabilité
                $niveau = $this->vulnerabilityProbability($value['vulnerabilityProbability']);
                /** Ajout des hotspots à la liste à insérer */
                $hotspots[] = [
                    'maven_key' => $mavenKey,
                    'version' => $select['info'][0]['project_version'],
                    'date_version' => $dateVersion,
                    'key' => $value['key'] ?? 'NC',
                    'security_category' => $value['securityCategory'] ?? 'NC',
                    'probability' => $value['vulnerabilityProbability'],
                    'status' => $value['status'] ?? 'NC',
                    'resolution' => $value['resolution'] ?? '',
                    'niveau' => $niveau,
                    'date_enregistrement' => $date
                ];
            }
        }

         /** On enregistre les données */
        $insert = $hotspotsRepository->insertHotspots($map[0]);
        if ($insert['code'] !== 200) {
            return [
                'code' => $insert['code'],
                static::$request => 'insertHotspot'
            ];
        }
    return ['code' => 200, 'map' => $map[0]];
    }
}
