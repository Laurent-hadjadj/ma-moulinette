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

use App\Entity\HotspotOwasp;
use App\Entity\InformationProjet;
use App\Service\Client;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteHotspotOwaspController]
 */
class BatchCollecteHotspotOwaspController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
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
     * [Description for BatchCollecteHotspotOwasp]
     *
     * @param string $mavenKey
     * @param string $menace
     *
     * @return array
     *
     * Created at: 30/05/2024 11:59:20 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function batchCollecteHotspotOwasp(string $maven_key, string $mode_collecte, string $utilisateur_collecte, string $menace): array
    {
        $maven_key = htmlspecialchars($maven_key, ENT_QUOTES, 'UTF-8');
        $informationProjetRepos = $this->em->getRepository(InformationProjet::class);
        $hotspotOwaspRepos = $this->em->getRepository(HotspotOwasp::class);
        $sonarVersion = $this->getParameter('sonar.version');

        $this->logger->info('ℹ️ [Batch HotspotOwasp] Début de collecte', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte,
            'menace' => $menace
        ]);

        /** On supprime les hotspots pour la maven_key. */
        if ($menace === 'a0') {
            $map = [ 'maven_key' => $maven_key ];
            $delete = $hotspotOwaspRepos->deleteHotspotOwaspMavenKey($map);
            if ($delete['code'] != 200) {
                $this->logger->error('❌ [HotspotOwasp] Échec suppression A0', $delete);
                return [
                    'code' => $delete['code'],
                    'erreur' => $delete['erreur']
                ];
            }

            $message = 'A0 : Effacement des données de la table hotspotOwasp pour le projet.';
            $this->logger->info('ℹ️ [HotspotOwasp] Suppression A0 effectuée', ['maven_key' => $maven_key]);
            /** si on est en version 8, on envoi pas de tableau owasp2021 */
            return ((int) $sonarVersion == 8) ?
                ['code' => 200, 'info' => 'effacement', 'message' => $message, 'data' => []] : ['code' => 200, 'owasp_2021'=> [], 'data' => [], 'info' => 'effacement', 'message' => $message];
        }

        /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map = [ 'maven_key' => $maven_key ];
        $information = $informationProjetRepos->selectInformationProjetVersion($map);
        if ($information['code'] != 200) {
            $this->logger->error('❌ [HotspotOwasp] Erreur récupération info projet', $information);
            return ['code' => $information['code'], 'erreur' => $information['erreur']];
        }

        if (!$information['info']) {
            $this->logger->warning('⚠️ [HotspotOwasp] Aucune information projet trouvée', ['maven_key' => $maven_key]);
            return [
                'code' => 404,
                'message' => static::$erreur404
            ];
        }

        /** On reconstruit les dates au format dateTimeImmutable */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));
        $dateVersion = new \DateTimeImmutable($information['info'][0]['date'], new \DateTimeZone(static::$europeParis));

        /** Tableau des paramètres pour la requête HTTP */
        $queryParamsList = [
            'owasp2017' => ['projectKey' => $maven_key, 'owaspTop10' => $menace,'p' => 1, 'ps' => 500 ],
            'owasp2021' => ['projectKey' => $maven_key, 'owaspTop10-2021' => $menace,'p' => 1, 'ps' => 500 ]
        ];

        /** On appelle les requêtes HTTP pour chaque référentiel */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/hotspots/search',
            $queryParamsList['owasp2017']
        );

        $this->logger->debug('[HotspotOwasp] Requête OWASP 2017', ['url' => $url]);
        $owasp2017 = $this->client->httpSonarQube($url);
        if (isset($owasp2017['code']) && in_array($owasp2017['code'], [400, 401, 403, 404, 500, 503, 504])) {
            $this->logger->error('❌ [HotspotOwasp] Erreur API OWASP 2021', $owasp2017);
            return [
                'code' => $owasp2017['code'],
                'erreur' => $owasp2017['erreur'],
                'type' => 'owasp2017'
            ];
        }

        /** On execute si la version de SonarQube est >= 9 */
        $owasp2021 = ['NC'];
        if ((int) $sonarVersion > 8){
            $url = $this->urlBuilder->build(
                $this->getParameter(static::$sonarUrl),
                '/api/hotspots/search',
                $queryParamsList['owasp2021']
            );

            $this->logger->debug('[HotspotOwasp] Requête OWASP 2021', ['url' => $url]);
            $owasp2021 = $this->client->httpSonarQube($url);
            if (isset($owasp2021['code']) && in_array($owasp2021['code'], [400, 401, 403, 404, 500, 503, 504])) {
                $this->logger->error('❌ [HotspotOwasp] Erreur API OWASP 2021', $owasp2021);
                return [
                        'code' => $owasp2021['code'],
                        'erreur' => $owasp2021['erreur'],
                        'type' => 'owasp2021'
                    ];
                }
        }

        /** On prépare les données */
        $prepareHotspotData = function($data, $ref) use ($maven_key, $information, $dateVersion, $date, $menace, $mode_collecte, $utilisateur_collecte) {
            return [
                    'referential_owasp' => $ref,
                    'maven_key' => $maven_key,
                    'version' => $information['info'][0]['version'],
                    'date_version' => $dateVersion,
                    'menace' => $menace,
                    'security_category' => $data['securityCategory'] ?? 'NC',
                    'rule_key' => $data['ruleKey'] ?? 'NC',
                    'probability' => $data['vulnerabilityProbability'] ?? 'NC',
                    'status' => $data['status'] ?? 'NC',
                    'resolution' => $data['resolution'] ?? '',
                    'niveau' => $this->vulnerabilityProbability($data['vulnerabilityProbability'] ?? -1),
                    'mode_collecte' => $mode_collecte,
                    'utilisateur_collecte' => $utilisateur_collecte,
                    'date_enregistrement' => $date
            ];
        };

        /** Pour chaque menace owasp on ajoute la menace dans la table */
        $hotspotDataList = [];
        if (array_key_exists('hotspots', $owasp2017['json'])) {
            foreach ($owasp2017['json']['hotspots'] as $item) {
                $hotspotDataList[] = $prepareHotspotData($item, 2017);
            }
        }

        if (array_key_exists('json', $owasp2021) && array_key_exists('hotspots', $owasp2021['json'])) {
            foreach ($owasp2021['json']['hotspots'] as $item) {
                $hotspotDataList[] = $prepareHotspotData($item, 2021);
            }
        }
        if ($hotspotDataList){
            $insert = $hotspotOwaspRepos->insertHotspotOwasp($hotspotDataList);
            if ($insert['code'] !== 200) {
                $this->logger->error('❌ [HotspotOwasp] Échec d’insertion en base', $insert);
                return [
                    'code' => $insert['code'],
                    'erreur' => $insert['erreur']
                ];
            }
        }

        $this->logger->info('ℹ️ [HotspotOwasp] Collecte OWASP terminée avec succès', [
            'maven_key' => $maven_key,
            'menace' => $menace,
            'owasp_2017' => $owasp2017['json']['paging']['total'] ?? 0,
            'owasp_2021' => $owasp2021['json']['paging']['total'] ?? 'NC'
        ]);

        return [
                'code' => 200,
                'info' => 'enregistrement',
                'owasp_2017' => $owasp2017['json']['paging']['total'],
                'owasp_2021' => $owasp2021['json']['paging']['total'] ?? 'NC',
                'message' => '',
                'data' => $hotspotDataList
            ];
    }
}
