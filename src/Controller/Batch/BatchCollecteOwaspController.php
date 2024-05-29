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
use App\Entity\Owasp;
use App\Entity\InformationProjet;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteInformationProjetController]
 */
class BatchCollecteOwaspController extends AbstractController
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
        private Client $client,
    ) {
        $this->em = $em;
        $this->client = $client;
    }

    /**
     * [Description for BatchCollecteMesure]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 21/05/2024 23:48:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteOwasp(string $mavenKey): array
    {
        /** On instancie l'EntityRepository */
        $informationProjet = $this->em->getRepository(InformationProjet::class);
        $owaspRepository = $this->em->getRepository(Owasp::class);

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');
        /** Construit l'URL en utilisant http_build_query pour les paramètres de la requête */
        $queryParams = [
            'componentKeys' => $mavenKey,
            'facets' => 'owaspTop10',
            'owaspTop10' => 'a1,a2,a3,a4,a5,a6,a7,a8,a9,a10'
        ];
        $queryString = http_build_query($queryParams);

       /** Appelle le client HTTP */
        $result = $this->client->http("$tempoUrl/api/issues/search?$queryString");

        /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (isset($result['code']) && in_array($result['code'], [401, 404])) {
            return ['code' => $result['code']];
        }

        /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map=['maven_key'=>$mavenKey];
        $select=$informationProjet->selectInformationProjetProjectVersion($map);
        if ($select['code']!=200) {
            return ['code' => $select['code'], 'message'=>$select['erreur']];
        }

        if (!$select['info']) {
            return ['code' => 404, 'message' => static::$erreur404];
        }

        /** On reconstruit des date au format dateTime */
        $dateVersion = new \DateTimeImmutable($select['info'][0]['date']);
        $dateVersion->setTimezone(new \DateTimeZone(static::$europeParis));

        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On initialise un tableau avec comme valeur 0 */
        $nombre = array_fill(1, 10, 0);
        $nombre[0] = $result['total'];
        $effortTotal = $result['effortTotal'];

        /** Pour chaque signalement OWASP a1, a2, a3,... */
        foreach ($result["facets"][0]["values"] as $value) {
            $index = substr($value["val"], 1);
            $nombre[$index] = $value["count"];
        }

        /** On remplie le tableau pour les signalement a1 à a10 pour les clés de sévérité */
        $owaspIssues = array_fill_keys(range(1, 10), array_fill_keys(['blocker', 'critical', 'major', 'info', 'minor'], 0));

        /** Calcul du nombre d'issue par type de signalement OWASP et par type de sévérité */
        if ($result['total'] != 0) {
            foreach ($result['issues'] as $issue) {
                if (in_array($issue['status'], ['OPEN', 'CONFIRMED', 'REOPENED'])) {
                    foreach ($issue['tags'] as $tag) {
                        if (preg_match("/owasp-a(\d+)/", $tag, $matches)) {
                            $owaspIndex = (int)$matches[1];
                            $severity = strtolower($issue['severity']);
                            if (isset($owaspIssues[$owaspIndex][$severity])) {
                                $owaspIssues[$owaspIndex][$severity]++;
                            }
                        }
                    }
                }
            }
        }

        /** On supprime les informations sur le projet pour la dernière analyse. */
        $map=['maven_key'=>$mavenKey];
        $delete=$owaspRepository->deleteOwaspMavenKey($map);
        if ($delete['code']!=200) {
            return ['code' => $delete['code'], static::$request=>'deleteOwaspMavenKey'];
        }

        /** préparation des données avant l'enregistrement. */
        $map = [
            'maven_key' => $mavenKey,
            'version' => $select['info'][0]['project_version'],
            'date_version' => $dateVersion,
            'effort_total' => $effortTotal,
            'date_enregistrement' => $date
        ];

        /** On ajoute les valeurs de a1 à a10 */
        for ($i = 1; $i <= 10; $i++) {
            $map["a$i"] = $nombre[$i];
        }

        /** Ajoute le nombre de cas par gravité pour chaque catégorie OWASP */
        foreach ($owaspIssues as $index => $severities) {
            foreach ($severities as $severity => $count) {
                $map["a{$index}_{$severity}"] = $count;
            }
        }
        /** On on enregistre */
        $request=$owaspRepository->insertOwasp($map);
        if ($request['code']!=200) {
            return ['code' => $request['code'], 'erreur' => $request['erreur'],static::$request=>'insertNote'];
        }

        return [
            'code' => 200, 'owasp' => [
                'nombre' => $nombre,
                'effortTotal' => $effortTotal,
                'issues' => $map,
            ]
        ];
    }
}
