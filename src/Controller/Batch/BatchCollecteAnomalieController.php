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
use App\Entity\Anomalie;

/** Import des services */
use App\Service\ExtractName;
use App\Service\DateTools;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description BatchCollecteAnomalieController]
 */
class BatchCollecteAnomalieController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $request = "requête : ";
    public static $statuses = "OPEN,REOPENED";
    public static $statusesMin = "OPEN,CONFIRMED,REOPENED,RESOLVED";
    public static $statusesAll = "OPEN, CONFIRMED, REOPENED, RESOLVED, CLOSED";

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
        private ExtractName $serviceExtractName,
        private DateTools $serviceDateTools,
    ) {
        $this->em = $em;
        $this->client = $client;
        $this->serviceExtractName = $serviceExtractName;
        $this->serviceDateTools = $serviceDateTools;
    }


    /**
     * [Description for BatchCollecteAnomalie]
     *
     * @param string $mavenKey
     * @param string $modeColecte
     * @param string $utilisateurCollecte
     *
     * @return array
     *
     * Created at: 21/05/2024 23:48:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function BatchCollecteAnomalie(string $mavenKey, string $modeCollecte, string $utilisateurCollecte): array
    {
        /** On instancie l'EntityRepository */
        $anomalieRepository = $this->em->getRepository(Anomalie::class);

        /** On créé un objet date. */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On récupère le nom du projet */
        $app=$this->serviceExtractName->extractNameFromMavenKey($mavenKey);

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
            'general' => [ 'componentKeys' => $mavenKey, 'facets' => 'directories,types,severities',
                'p' => 1, 'ps' => 1, 'statuses' => static::$statuses ],
            'BUG' => [ 'componentKeys' => $mavenKey, 'types' => 'BUG', 'p' => 1, 'ps' => 1 ],
            'VULNERABILITY' => [ 'componentKeys' => $mavenKey, 'types' => 'VULNERABILITY',
                'p' => 1, 'ps' => 1 ],
            'CODE_SMELL' => [ 'componentKeys' => $mavenKey, 'types' => 'CODE_SMELL',
                'p' => 1, 'ps' => 1 ]
        ];

        /* On appelle les API en passant les querryParams à la fonction générique */
        $results = [];
        foreach ($queryParamsList as $key => $queryParams) {
            $results[$key] = $makeRequest($queryParams);
            if (isset($results[$key]['error'])) {
                return ['code' => $results[$key]['error'], 'error'=>$results['erreur']];
            }
        }

        if ($results['general']['paging']['total'] != 0) {
            /** On supprime les résultats pour la maven_key. */
            $map=['maven_key'=>$mavenKey];
            $delete=$anomalieRepository->deleteAnomalieMavenKey($map);
            if ($delete['code']!=200) {
                return ['code' => $delete['code'],'error'=>[$delete['erreur'], static::$request=>'deleteAnomalieMavenKey']];
            }

            //** On récupère le nombre d'anomalie et la dette technique */
            $anomalieTotal = $results['general']['total'];
            $dette = $this->serviceDateTools->minutesTo($results['general']['effortTotal']);
            $detteMinute=$results['general']['effortTotal'];
            $detteReliability = $this->serviceDateTools->minutesTo($results['BUG']['effortTotal']);
            $detteReliabilityMinute=$results['BUG']['effortTotal'];
            $detteVulnerability = $this->serviceDateTools->minutesTo($results['VULNERABILITY']
            ['effortTotal']);
            $detteVulnerabilityMinute=$results['VULNERABILITY']
            ['effortTotal'];
            $detteCodeSmell = $this->serviceDateTools->minutesTo($results['CODE_SMELL']['effortTotal']);
            $detteCodeSmellMinute=$results['CODE_SMELL']['effortTotal'];

            /* On initialise les indicateurs de sévérité, de type et de répartition */
            $severities = ['BLOCKER' => 0, 'CRITICAL' => 0, 'MAJOR' => 0, 'INFO' => 0, 'MINOR' => 0];
            $types = ['BUG' => 0, 'VULNERABILITY' => 0, 'CODE_SMELL' => 0];
            $modules = ['frontend' => 0, 'backend' => 0, 'autre' => 0];

            /** On récupère les informations */
            foreach ($results['general']['facets'] as $facet) {
                switch ($facet['property']) {
                    case 'severities':
                        foreach ($facet['values'] as $severity) {
                            $severities[$severity['val']] = $severity['count'];
                        }
                        break;
                    case 'types':
                        foreach ($facet['values'] as $type) {
                            $types[$type['val']] = $type['count'];
                        }
                        break;
                    case 'directories':
                        foreach ($facet['values'] as $directory) {
                            $file = str_replace($mavenKey . ':', "", $directory['val']);
                            $module = explode('/', $file)[0];
                            $count = $directory['count'];
                            if (in_array($module, ['du-presentation', 'rs-presentation', "$app-presentation", "$app-presentation-commun", "$app-presentation-ear", "$app-webapp"])) {
                                $modules['frontend'] += ($module === "$app-presentation" || $module === "$app-presentation-commun" || $module === "$app-presentation-ear" || $module === "$app-webapp") ? 1 : $count;
                            } elseif (in_array($module, ['rs-metier', "$app-metier", "$app-common", "$app-api", "$app-dao", "$app-metier-ear", "$app-service", "$app-serviceweb", "$app-middleoffice", "$app-metier-rest", "$app-entite", "$app-serviceweb-client"])) {
                                $modules['backend'] += $count;
                            } elseif (in_array($module, ["$app-batch", "$app-batchs", "$app-batch-envoi-dem-aval", "$app-batch-import-billets", "$app-rdd"])) {
                                $modules['autre'] += $count;
                            }
                        }
                        break;
                    default: break;
                }
            }
        }

        /** Enregistrement dans la table Anomalie. */
        $map = [
            'maven_key'=>$mavenKey,
            'project_name'=>$app,
            'anomalie_total'=>$anomalieTotal ?? 0,
            'dette'=>$dette ?? 0,
            'dette_minute'=>$detteMinute ?? 0,
            'dette_reliability'=>$detteReliability ?? 0,
            'dette_reliability_minute'=>$detteReliabilityMinute ?? 0, 'dette_vulnerability'=>$detteVulnerability ?? 0, 'dette_vulnerability_minute'=>$detteVulnerabilityMinute ?? 0,
            'dette_code_smell'=>$detteCodeSmell ?? 0,
            'dette_code_smell_minute'=>$detteCodeSmellMinute ?? 0,
            'frontend'=>$modules['frontend'] ?? 0,
            'backend'=>$modules['backend'] ?? 0,
            'autre'=>$modules['autre'] ?? 0,
            'blocker'=>$severities['BLOCKER'] ?? 0,
            'critical'=>$severities['CRITICAL'] ?? 0,
            'major'=>$severities['MAJOR'] ?? 0,
            'info'=>$severities['INFO'] ?? 0,
            'minor'=>$severities['MINOR'] ?? 0,
            'bug'=>$types['BUG'] ?? 0,
            'vulnerability'=>$types['VULNERABILITY'] ?? 0,
            'code_smell'=>$types['CODE_SMELL'] ?? 0,
            'mode_collecte'=>$modeCollecte,
            'utilisateur_collecte'=>$utilisateurCollecte,
            'date_enregistrement'=>$date];

            $insert = $anomalieRepository->insertAnomalie($map);
            if ($insert['code'] !== 200) {
                return [
                    'code' => $insert['code'],
                    'error'=>[$insert['erreur'],
                    static::$request => 'insertAnomalie']
                ];
            }

        /** On prépare les données pour l'historique */
        $data=[
            'nombre_defaut' => $anomalieTotal,
            'dette' => $detteMinute,
            'nombre_bug' => $types['BUG'] ?? 0,
            'nombre_vulnerability' => $types['VULNERABILITY'] ?? 0,
            'nombre_code_smell' => $types['CODE_SMELL'] ?? 0,
            'frontend' => $modules['frontend'] ?? 0,
            'backend' => $modules['backend'] ?? 0,
            'autre' => $modules['autre'] ?? 0,
            'nombre_anomalie_bloquant' => $severities['BLOCKER'] ?? 0,
            'nombre_anomalie_critique' =>$severities['CRITICAL'] ?? 0,
            'nombre_anomalie_info' =>$severities['INFO'] ?? 0,
            'nombre_anomalie_majeur' => $severities['MAJOR'] ?? 0,
            'nombre_anomalie_mineur'=>$severities['MINOR'] ?? 0
        ];


        return ['code' => 200, 'message' => $map, 'data' => $data ];
    }
}
