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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\InformationProjet;
use App\Entity\Hotspots;
use App\Entity\HotspotDetails;
use App\service\ClientService;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteHotspotDetailController]
 */
class BatchCollecteHotspotDetailController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = 'sonar.url';
    public static $europeParis = 'Europe/Paris';

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {
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
    public function hotspotDetail(string $maven_key, string $hotspotKey): array
    {

         /** Sécurisation de l'URL */
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/hotspots/show',
            [ 'hotspot' => $hotspotKey ]
        );

        $this->logger->debug("[Batch HotspotDétail] 🛠️ Appel API SonarQube", ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        /** On catch les erreurs HTTP  :) */
        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error("[HotspotDetail] ❌ Erreur API SonarQube", [
                'url' => $url,
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? 'Erreur inconnue.'
            ]);

            return [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? 'Erreur Sonar inconnue.'
            ];
        }

        /** On a pas trouvé de données */
        if (!array_key_exists('json', $result)){
            $this->logger->error("[HotspotDetail] ❌ Résultat API invalide ou vide", [
                'hotspot_key' => $hotspotKey
            ]);

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
        $score_frontend = $score_backend = $score_autre = $score_inconnu = 0;
        if (array_key_exists('json', $result) && array_key_exists('path', $result['json']['component'])) {
            $path = $result['json']['component']['path'];
        } elseif (array_key_exists('json', $result) && array_key_exists('component', $result['json'])) {
            /** "key" => "fr.ma-petite-entreprise:ma-moulinette:assets/js/app-password.js" */
            $path = str_replace($maven_key . ':', '', $result['json']['component']['key']);
        }

        /** on récupère la liste des clés pour chaque module */
        $liste_frontend = $this->getParameter('module.frontend');
        $liste_backend = $this->getParameter('module.backend');
        $liste_autre = $this->getParameter('module.autre');

        $frontend = array_map('strtolower', array_map('trim', explode(',', $liste_frontend)));
        $backend = array_map('strtolower', array_map('trim', explode(',', $liste_backend)));
        $autre = array_map('strtolower', array_map('trim', explode(',', $liste_autre)));

        /** Vérifie si le chemin appartient au front */
        foreach ($frontend as $module) {
            if (preg_match("/\b$module\b/", $path)) {
                $score_frontend = 1;
                break;
            }
        }

        /** Vérifie si le chemin appartient au backend */
        foreach ($backend as $module) {
            if (preg_match("/\b$module\b/", $path)) {
                $score_backend = 1;
                break;
            }
        }

        /** Vérifie si le chemin n'appartient ni au fronted, ni au backend */
        foreach ($autre as $module) {
            if (preg_match("/\b$module\b/", $path)) {
                $score_autre = 1;
                break;
            }
        }
        /* On calcule le score pour les clés non identifiées */
        $score_inconnu = ($score_frontend === 0 && $score_backend === 0 && $score_autre === 0) ? 1 : 0;

       /** On renvoie le tableau à insérer */
        return [
            'security_category' => $result['json']['rule'] ? $result['json']['rule']['securityCategory'] : 'NC',
            'severity' => $severity,
            'niveau' => $niveau,
            'status' => $result['json']['status'],
            'resolution' => $result['json']['resolution'] ?? 'Todo',
            'frontend' => $score_frontend,
            'backend' => $score_backend,
            'autre' => $score_autre,
            'inconnu' => $score_inconnu,
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
     * @param string $maven_key
     * @param string $mode_collecte
     * @param string $utilisateur_collecte
     *
     * @return array
     *
     * Created at: 31/05/2024 14:52:44 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function batchCollecteHotspotDetail(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $mavenKey = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $hotspotsRepos = $this->em->getRepository(Hotspots::class);
        $hotspotDetailsRepos = $this->em->getRepository(HotspotDetails::class);
        $informationProjetRepos = $this->em->getRepository(InformationProjet::class);

        $this->logger->info('[Batch HotspotDétail] ℹ️ Début de collecte du détail des hotspots.', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map = [ 'maven_key'=> $maven_key ];
        $information = $informationProjetRepos->selectInformationProjetVersion($map);
        if ($information['code'] != 200) {
            $this->logger->error('[HotspotDetail] ❌ Erreur récupération projet', [
                'maven_key' => $maven_key,
                'erreur' => $information['erreur']
            ]);

            return [
                'code' => $information['code'],
                'erreur' => $information['erreur']
            ];
        }

        if (empty($information['info'])) {
            $this->logger->warning('[HotspotDetail] ⚠️ Aucune information trouvée sur le projet', [
                'maven_key' => $maven_key
            ]);

            return [
                'code' => 404,
                'message' => "Aucune information n'a été trouvée (Erreur 404)."
            ];
        }

        /** On reconstruit la date de version au format dateTime */
        $dateVersion = new \DateTimeImmutable($information['info'][0]['date'], new \DateTimeZone(static::$europeParis));

        /** Création de la date du jour */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        /** On récupère la liste des hotspots au status TO_REVIEW */
        $map = [ 'maven_key' => $mavenKey ];
        $liste = $hotspotsRepos->selectHotspotsToReview($map);

        if ($liste['code'] != 200) {
            $this->logger->error('[HotspotDetail] ❌ Échec de la requête selectHotspotsToReview.', [
                'maven_key' => $maven_key,
                'erreur' => $liste['erreur']
            ]);

            return [
                'code' => $liste['code'],
                'erreur' => $liste['erreur']
            ];
        }

        /** On supprime les résultats pour la maven_key. */
        $map = [ 'maven_key' => $mavenKey ];
        $delete = $hotspotDetailsRepos->deleteHotspotDetailsMavenKey($map);

        if ($delete['code'] != 200) {
            $this->logger->error('[HotspotDetail] ❌ Échec de la requête deleteHotspotsDetailsMavenKey.', [
                'maven_key' => $maven_key,
                'erreur' => $delete['erreur']
            ]);

            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        /** Si la liste des hotspots est vide on envoi un code http 406 */
        if (empty($liste['liste'])) {
            $this->logger->warning('[HotspotDetail] ⚠️ Liste TO_REVIEW vide', [
                'maven_key' => $maven_key
            ]);
            return [
                'code' => 406,
                'message'=> 'Liste vide !!! (Erreur 406).'
            ];
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
                'mode_collecte' => $mode_collecte,
                'utilisateur_collecte' => $utilisateur_collecte,
                'maven_key' => $mavenKey,
                'version' => $information['info'][0]['version'],
                'date_version' => $dateVersion,
                'date_enregistrement'=> $date,
                'hotspot_key' => $elt['hotspot_key']
            ];
            // Fusion des tableaux après la boucle
            $mapData[] = array_merge($mapDataGeneral, $mapDataDetail);
        }

        /** On enregistre les données */
        $insert = $hotspotDetailsRepos->insertHotspotDetails($mapData);

        if ($insert['code'] !== 200) {
            $this->logger->error('[HotspotDetail] ❌ Échec de la requête insertHotspotDetails.', [
                'maven_key' => $maven_key,
                'erreur' => $insert['erreur']
            ]);

            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        $this->logger->info('[HotspotDetail] ℹ️ Détails des hotspots collectés et enregistrés', [
            'maven_key' => $maven_key,
            'utilisateur' => $utilisateur_collecte,
            'nombre_détails' => count($mapData)
        ]);

        return [
            'code' => 200,
            'nombre' => count($liste),
            'message' => $mapData
        ];
    }
}
