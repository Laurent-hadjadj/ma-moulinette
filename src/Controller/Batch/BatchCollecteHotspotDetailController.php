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
use App\Entity\InformationProjet;
use App\Entity\Hotspots;
use App\Entity\HotspotDetails;

/** Client HTTP */
use App\Service\Client;
/** Import des services */
use App\Service\ExtractName;

/**
 * [Description BatchCollecteHotspotDetailController]
 */
class BatchCollecteHotspotDetailController extends AbstractController
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
        private ExtractName $serviceExtractName,
        private EntityManagerInterface $em,
        private Client $client,
    ) {
        $this->serviceExtractName = $serviceExtractName;
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
     * [Description for hotspotDetail]
     *
     * @return array
     *
     * Created at: 31/05/2024 15:02:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function hotspotDetail(string $mavenKey, string $hotspotKey): array
    {
        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** Construit l'URL en utilisant http_build_query pour les paramètres de la requête */
        $queryParams = [ 'hotspot' => $hotspotKey ];

        /** Appelle le client HTTP */
        $result = $this->client->httpSonarQube("$tempoUrl/api/hotspots/show?".http_build_query($queryParams));
        /** On catch les erreurs HTTP  :) */
        if (isset($result['code']) && in_array($result['code'], [401, 403, 404, 500])) {
            return ['code' => $result['code'], 'erreur' => $result['erreur']];
        }
        /** On a pas trouvé de données */
        if (!array_key_exists('json', $result)){
            return [
                    'security_category' =>  'NC',
                    'severity' => 'NC',
                    'niveau' => 'NC',
                    'status' => 'NC',
                    'resolution' => 'NC',
                    'frontend' => 'NC',
                    'backend' => 'NC',
                    'autre' => 'NC',
                    'file_name' => 'NC',
                    'file_path' => 'NC',
                    'line' => 'NC',
                    'rule_key' => 'NC',
                    'rule_name' => 'NC',
                    'message' => 'Aucune données trouvée pour ce projet.',
                    'hotspot_key' => 'NC',
                ];
        }
        /****************** préparation des données globales */
        /** Si un hotspot est trouvé mais n'a pas été évalué alors on le qualifie de MEDIUM */
        $severity = empty($result['json']['rule']['vulnerabilityProbability']) ? 'MEDIUM' : $result['json']['rule']['vulnerabilityProbability'];
        $niveau = $this->vulnerabilityProbability($severity);

        /**************** Components ***************/
        /** On calcule la répartition pour les application java et ?Php */
        $frontend = $backend = $autre = 0;
        if (array_key_exists('json', $result) && array_key_exists('path', $result['json']['component'])) {
            $path = $result['json']['component']['path'];
        } elseif (array_key_exists('json', $result) && array_key_exists('component', $result['json'])) {
            /** "key" => "fr.ma-petite-entreprise:ma-moulinette:assets/js/app-password.js" */
            $path = str_replace($mavenKey . ":", "", $result['json']['component']['key']);
        }

        $frontModules = ["assets", "css", "js", "templates", "front", "presentation", "webapp"];
        $backModules= ["controller", "service", "back", "metier", "common", "api", "dao", "serviceweb", "middleoffice", "rest", "soap", "entite", "entity", "repository", "serviceweb-client"];
        $autreModules= ["batch", "rdd"];

        /** Pour l'application frontend */
        foreach ($frontModules as $module) {
            if (substr_count($path, $module) === 1) {
                $frontend++;
            }
        }

        /** Pour l'application backend */
        foreach ($backModules as $module) {
            if (substr_count($path, $module) === 1) {
                $backend++;
            }
        }

        foreach ($autreModules as $module) {
            if (substr_count($path, $module) === 1) {
                $autre++;
            }
        }

       /** On renvoie le tableau à insérer */
        return [
            'security_category' => $result['json']['rule'] ? $result['json']['rule']['securityCategory'] : 'NC',
            'severity' => $severity,
            'niveau' => $niveau,
            'status' => $result['json']['status'],
            'resolution' => $result['json']['resolution'] ?? "Todo",
            'frontend' => $frontend,
            'backend' => $backend,
            'autre' => $autre,
            'file_name' => $result['json']['component'] ? $result['json']['component']['name'] : 'NC',
            'file_path' => $path,
            'line' => empty($result['json']['line']) ? 0 : $result['json']['line'],
            'rule_key' => $result['json']['rule'] ? $result['json']['rule']['key'] : 'NC',
            'rule_name' => $result['json']['rule'] ? $result['json']['rule']['name'] : 'NC',
            'message' => $result['json']['message'],
            'hotspot_key' => $result['json']['key'],
        ];
    }

    /**
     * [Description for batchCollecteHotspotDetails]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 31/05/2024 14:52:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function batchCollecteHotspotDetail(string $mavenKey, string $modeCollecte, string $utilisateurCollecte): array
    {
        /** On instancie l'EntityRepository */
        $hotspotsRepository = $this->em->getRepository(Hotspots::class);
        $hotspotDetailsRepository = $this->em->getRepository(HotspotDetails::class);
        $informationProjet = $this->em->getRepository(InformationProjet::class);

        /** On contrôle la variable mavenKey */
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map = ['maven_key'=>$mavenKey];
        $information = $informationProjet->selectInformationProjetProjectVersion($map);
        if ($information['code'] != 200) {
            return ['code' => $information['code'], 'erreur' => $information['erreur']];
        }


        if (array_key_exists('json', $information) && !$information['json']['info']) {
            return ['code' => 404, 'message' => "Aucune information n'a été trouvée."];
        }

        /** On reconstruit la date de version au format dateTime */
        $dateVersion = new \DateTimeImmutable($information['info'][0]['date'], new \DateTimeZone(static::$europeParis));

        /** Création de la date du jour */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        /** On récupère la liste des hotspots au status TO_REVIEW */
        $map = ['maven_key' => $mavenKey];
        $liste = $hotspotsRepository->selectHotspotsToReview($map);
        if ($liste['code'] != 200) {
            return ['code' => $liste['code'], 'erreur' => $liste['erreur']];
        }

        /** On supprime les résultats pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $delete=$hotspotDetailsRepository->deleteHotspotDetailsMavenKey($map);
        if ($delete['code'] != 200) {
            return ['code' => $delete['code'], 'erreur' => $delete['erreur']];
        }

        /** Si la liste des hotspots est vide on envoi un code http 406 */
        if (empty($liste['liste'])) {
            return ['code' => 406, 'message'=> 'Liste vide !!!'];
        }

        /**
         * On boucle sur les clés pour récupérer le détails du hotspot.
         * On envoie la clé du projet et la clé du hotspot.
         */
        $ligne = 0;
        /** Initialisation des tableaux */
        $mapDataGeneral = [];
        $mapDataDetail = [];
        $mapData = [];

        foreach ($liste['liste'] as $elt) {
            $ligne++;
            $mapDataDetail = $this->hotspotDetail($mavenKey, $elt['hotspot_key']);
            $mapDataGeneral = [
                'mode_collecte' => $modeCollecte,
                'utilisateur_collecte' => $utilisateurCollecte,
                'maven_key' => $mavenKey,
                'version' => $information['info'][0]['project_version'],
                'date_version' => $dateVersion,
                'date_enregistrement'=> $date,
                'hotspot_key' => $elt['hotspot_key']
            ];
            // Fusion des tableaux après la boucle
            $mapData[] = array_merge($mapDataGeneral, $mapDataDetail);
        }

        /** On enregistre les données */
        $insert = $hotspotDetailsRepository->insertHotspotDetails($mapData);
        if ($insert['code'] !== 200) {
            return ['code' => $insert['code'], 'erreur' => $insert['erreur']];
        }
        return ['code' => 200, 'message' => $mapData];
    }
}
