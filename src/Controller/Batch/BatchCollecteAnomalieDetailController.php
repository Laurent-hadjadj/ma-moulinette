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
use App\Entity\AnomalieDetails;

/** Import des services */
use App\Service\ExtractName;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteAnomalieDetailController]
 */
class BatchCollecteAnomalieDetailController extends AbstractController
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
        private Client $client,
        private ExtractName $serviceExtractName
    ) {
        $this->em = $em;
        $this->client = $client;
        $this->serviceExtractName = $serviceExtractName;
    }

    /**
     * [Description for BatchCollecteAnomalieDetail]
     *
     * @param string $mavenKey
     * @param string $modeCollecte
     * @param string $utilisateurCollecte
     *
     * @return array
     *
     * Created at: 03/06/2024 14:14:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteAnomalieDetail(string $mavenKey, string $modeCollecte, string $utilisateurCollecte): array
    {
        /** On instancie l'EntityRepository */
        $anomalieDetailsRepository = $this->em->getRepository(AnomalieDetails::class);

        /** On créé un objet date. */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /* Fonction générique pour executer une requête et retourner le résultat dans un tableau */
        $makeRequest = function($queryParams) use ($tempoUrl) {
            $queryString = http_build_query($queryParams);
            $result = $this->client->http("$tempoUrl/api/issues/search?$queryString");
            if (isset($result['code']) && in_array($result['code'], [401, 404])) {
                return ['error' => $result['code'], $result['erreur']];
            }
            return $result;
        };

        /* Tableau des paramètres pour les requêtes HTTP */
        $queryParamsList = [
            'BUG' => [ 'componentKeys' => $mavenKey, 'facets' => 'severities', 'types' => 'BUG', 'statuses' => 'OPEN', 'p' => 1, 'ps' => 1 ],
            'VULNERABILITY' => [ 'componentKeys' => $mavenKey, 'facets' => 'severities', 'types' => 'VULNERABILITY', 'statuses' => 'OPEN', 'p' => 1, 'ps' => 1 ],
            'CODE_SMELL' => [ 'componentKeys' => $mavenKey, 'facets' => 'severities', 'types' => 'CODE_SMELL', 'statuses' => 'OPEN', 'p' => 1, 'ps' => 1 ]
        ];

        /* On appelle les API en passant les querryParams à la fonction générique */
        $results = [];
        foreach ($queryParamsList as $key => $queryParams) {
            $results[$key] = $makeRequest($queryParams);
            if (isset($results[$key]['error'])) {
                return ['code' => $results[$key]['error'], 'error'=>$results['erreur']];
            }
        }
        $issueTypes = ['BUG', 'VULNERABILITY', 'CODE_SMELL'];
        $pagingTotals = [];
 
        /** On map pour chaque type les tableau de résultats qui nous interesse */
        foreach ($issueTypes as $type) {
            if (isset($results[$type]['paging']['total'])) {
                $pagingTotals[$type] = $results[$type]['paging']['total'];
            }
        }

        /** Si la somme du total des anomalies est égale à zéro, on sort */
        if (array_sum($pagingTotals) === 0) {
            return ['code' => 200, 'message' => "Pas d'anomalie trouvée", 'data' => [] ];
        }

        /** On supprime l'enregistrement correspondant à la clé */
        $map=['maven_key'=>$mavenKey];
        $delete=$anomalieDetailsRepository->deleteAnomalieDetailsMavenKey($map);
        if ($delete['code']!=200) {
            return ['code' => $delete['code'], 'error'=>[$delete['erreur'], static::$request=>'deleteAnomalieDetailsMavenKey']];
        }

        /** On crée un objet DateTime */
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** Fonction pour mapper les résultats */
        function mapSeverities($severities) {
            // Initialisation du tableau de sévérités avec des valeurs par défaut
            $severityMap = ['BLOCKER' => 0, 'CRITICAL' => 0, 'MAJOR' => 0, 'MINOR' => 0, 'INFO' => 0];
            if (isset($severities['values'])) {
                foreach ($severities['values'] as $severity) {
                    $severityMap[$severity['val']] = $severity['count'];
                }
            }
            return $severityMap;
        }

        /** On bind les résultats */
        $bugSeverities = mapSeverities($results['BUG']['facets'][0]);
        $vulnerabilitySeverities = mapSeverities($results['VULNERABILITY']['facets'][0]);
        $codeSmellSeverities = mapSeverities($results['CODE_SMELL']['facets'][0]);

        /** On prépare les données */
        $mapData = [
            'maven_key' => $mavenKey,
            'name' =>$this->serviceExtractName->extractNameFromMavenKey($mavenKey),
            'bug_blocker' => $bugSeverities['BLOCKER'],
            'bug_critical' => $bugSeverities['CRITICAL'],
            'bug_major' => $bugSeverities['MAJOR'],
            'bug_minor' => $bugSeverities['MINOR'],
            'bug_info' => $bugSeverities['INFO'],

            'vulnerability_blocker' => $vulnerabilitySeverities['BLOCKER'],
            'vulnerability_critical' => $vulnerabilitySeverities['CRITICAL'],
            'vulnerability_major' => $vulnerabilitySeverities['MAJOR'],
            'vulnerability_minor' => $vulnerabilitySeverities['MINOR'],
            'vulnerability_info' => $vulnerabilitySeverities['INFO'],

            'code_smell_blocker' => $codeSmellSeverities['BLOCKER'],
            'code_smell_critical' => $codeSmellSeverities['CRITICAL'],
            'code_smell_major' => $codeSmellSeverities['MAJOR'],
            'code_smell_minor' => $codeSmellSeverities['MINOR'],
            'code_smell_info' => $codeSmellSeverities['INFO'],

            'mode_collecte' => $modeCollecte,
            'utilisateur_collecte' => $utilisateurCollecte,
            'date_enregistrement' => $date];

            $insert=$anomalieDetailsRepository->insertAnomalieDetail($mapData);
            if ($insert['code'] !== 200) {
                return ['code' => $insert['code'],
                        'error'=>[$insert['erreur'],
                        static::$request => 'insertAnomalieDetail']];
            }
            $data =['bug_blocker' => $bugSeverities['BLOCKER'],
                    'bug_critical' => $bugSeverities['CRITICAL'],
                    'bug_major' => $bugSeverities['MAJOR'],
                    'bug_minor' => $bugSeverities['MINOR'],
                    'bug_info' => $bugSeverities['INFO'],
                    'vulnerability_blocker' => $vulnerabilitySeverities['BLOCKER'],
                    'vulnerability_critical' => $vulnerabilitySeverities['CRITICAL'],
                    'vulnerability_major' => $vulnerabilitySeverities['MAJOR'],
                    'vulnerability_minor' => $vulnerabilitySeverities['MINOR'],
                    'vulnerability_info' => $vulnerabilitySeverities['INFO'],
                    'code_smell_blocker' => $codeSmellSeverities['BLOCKER'],
                    'code_smell_critical' => $codeSmellSeverities['CRITICAL'],
                    'code_smell_major' => $codeSmellSeverities['MAJOR'],
                    'code_smell_minor' => $codeSmellSeverities['MINOR'],
                    'code_smell_info' => $codeSmellSeverities['INFO']];

        return ['code' => 200, 'message' => $mapData, 'data' => $data ];
    }
}
