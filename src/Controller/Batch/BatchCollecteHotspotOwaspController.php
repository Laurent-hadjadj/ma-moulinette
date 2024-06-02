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
use App\Entity\HotspotOwasp;
use App\Entity\InformationProjet;

/** Client HTTP */
use App\Service\Client;


/**
 * [Description BatchCollecteHotspotOwaspController]
 */
class BatchCollecteHotspotOwaspController extends AbstractController
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
    public function batchCollecteHotspotOwasp(string $mavenKey, string $menace): array
    {
        /** On instancie l'EntityRepository */
        $hotspotOwaspRepository = $this->em->getRepository(HotspotOwasp::class);
        $informationProjet = $this->em->getRepository(InformationProjet::class);

        /** On contrôle la variable mavenKey */
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /** On supprime les hotspots pour la maven_key. */
        if ($menace === 'a0') {
            $map=['maven_key'=>$mavenKey];
            $delete=$hotspotOwaspRepository->deleteHotspotOwaspMavenKey($map);
            if ($delete['code']!=200) {
                return ['code' => $delete['code'], static::$request=>'deleteHotspotOwaspMavenKey'];
            }
            $message='A0 : Effacement des données de la table hotspotOwasp pour le projet.';
            return ['code' => 200, 'message' => $message];
        }

        /** On récupère dans la table information_projet la version et la date du projet la plus récente. */
        $map=['maven_key'=>$mavenKey];
        $information=$informationProjet->selectInformationProjetProjectVersion($map);
        if ($information['code']!=200) {
            return ['code' => $information['code'], 'message'=>$information['erreur']];
        }

        if (!$information['info']) {
            return ['code' => 404, 'message' => static::$erreur404];
        }

        /** /** On reconstruit les dates au format dateTimeImmutable */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));
        $dateVersion = new \DateTimeImmutable($information['info'][0]['date']);
        $dateVersion->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** Tableau des paramètres pour la requête HTTP */
        $queryParamsList = [
            'owasp2017'=>['projectKey' => $mavenKey, 'owaspTop10' => $menace,'p' => 1, 'ps' => 500 ],
            'owasp2021'=>['projectKey' => $mavenKey, 'owaspTop10-2021' => $menace,'p' => 1, 'ps' => 500 ]
        ];

        /** On appelle les requêtes HTTP pour chaque référentiel */
        $owasp2017 = $this->client->http("$tempoUrl/api/hotspots/search?".http_build_query($queryParamsList['owasp2017']));
        if (isset($owasp2017['code']) && in_array($owasp2017['code'], [401, 404])) {
            return ['error' => $owasp2017['code']];
        }
        /** On execute si la version de SonarQube est >= 9 */
        //Todo ajouter le tests dans la prochaine version.
        $owasp2021 = $this->client->http("$tempoUrl/api/hotspots/search?".http_build_query($queryParamsList['owasp2021']));
        if (isset($owasp2021['code']) && in_array($owasp2021['code'], [401, 404])) {
            return ['error' => $owasp2021['code']];
        }

        /** On prépare les données */
        $prepareHotspotData = function($data, $ref) use ($mavenKey, $information, $dateVersion, $date, $menace) {
            return [
                'referentiel_owasp' => $ref,
                'maven_key' => $mavenKey,
                'version' => $information['info'][0]['project_version'],
                'date_version' => $dateVersion,
                'menace' => $menace,
                'security_category' => $data['securityCategory'] ?? 'NC',
                'rule_key' => $data['ruleKey'] ?? 'NC',
                'probability' => $data['vulnerabilityProbability'] ?? 'NC',
                'status' => $data['status'] ?? 'NC',
                'resolution' => $data['resolution'] ?? '',
                'niveau' => $this->vulnerabilityProbability($data['vulnerabilityProbability'] ?? -1),
                'date_enregistrement' => $date
            ];
        };

        /** Pour chaque menace owasp on ajoute la menace dans la table */
        $hotspotDataList = [];
        if (array_key_exists('hotspots', $owasp2017)) {
            foreach ($owasp2017['hotspots'] as $item) {
                $hotspotDataList[] = $prepareHotspotData($item, 2017);
            }
        }
        if (array_key_exists('hotspots', $owasp2021)) {
            foreach ($owasp2021['hotspots'] as $item) {
                $hotspotDataList[] = $prepareHotspotData($item, 2021);
            }
        }
        if ($hotspotDataList){
            $insert = $hotspotOwaspRepository->insertHotspotOwasp($hotspotDataList);
            if ($insert['code'] !== 200) {
                return ['code' => $insert['code'],
                        static::$request => 'insertHotspotOwasp'
                    ];
            }
        }
        return ['code' => 200, 'message' => $hotspotDataList];
    }
}
