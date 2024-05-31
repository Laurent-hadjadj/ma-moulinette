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

/** Client HTTP */
use App\Service\Client;
use App\Service\IsValideMavenKey;

/**
 * [Description BatchCollecteInformationProjetController]
 */
class BatchCollecteInformationProjetController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $request = "requête :";

    /**
     * [Description for __construct]
     * On ajoute un constructeur pour éviter à chaque fois d'injecter la même class
     *
     * Created at: 04/12/2022, 08:53:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function __construct(
        private EntityManagerInterface $em,
        private IsValideMavenKey $isValidMavenKey,
        private Client $client,

    ) {
        $this->em = $em;
        $this->isValidMavenKey = $isValidMavenKey;
        $this->client = $client;
    }

    /**
     * [Description for batchInformationVersion]
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 09/12/2022, 17:13:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    ##[Route('/api/batch/information/version', name: 'batch_information_version', methods: ['POST'])]
    private function batchInformationVersion($maven_key): array
    {
        /** On instancie l'entityRepository */
        $informationProjetRepository = $this->em->getRepository(InformationProjet::class);

        $isValide=$this->isValidMavenKey->isValide($maven_key);
        if ($isValide===404) {
            return ['code'=>404];
        }

        /** On compte toutes les version de type (RELEASE, SNAPSHOT, AUTRE) */
        $map=['maven_key' => $maven_key];
        $toutesLesVersions=$informationProjetRepository->countInformationProjetAllType($map);
        if ($toutesLesVersions['code']!=200) {
            return ['code' => $toutesLesVersions['code'], static::$request=>'touteLesVersions'];
        }

        /** On compte les releases */
        $map=['maven_key'=>$maven_key, 'type'=>'RELEASE'];
        $release=$informationProjetRepository->countInformationProjetType($map);
        if ($release['code']!=200) {
            return ['code' => $release['code'], static::$request=>'releases'];
        }

        /** On compte les snapshots */
        $map=['maven_key'=>$maven_key, 'type'=>'SNAPSHOT'];
        $snapshot=$informationProjetRepository->countInformationProjetType($map);
        if ($snapshot['code']!=200) {
            return ['code' => $snapshot['code'], static::$request=>'snapshot'];
        }

        /** On récupère la dernière version et sa date de publication */
        $map=['maven_key'=>$maven_key];
        $infoRelease=$informationProjetRepository->selectInformationProjetVersionLast($map);
        if ($infoRelease['code']!=200) {
            return ['code' => $infoRelease['code'], static::$request=>'infoRelease'];
        }

        $toutesLesVersions = isset($toutesLesVersions['nombre'][0]['total']) ? $toutesLesVersions['nombre'][0]['total'] : 0;
        $release = isset($release['nombre'][0]['total']) ? $release['nombre'][0]['total'] : 0;
        $snapshot = isset($snapshot['nombre'][0]['total']) ? $snapshot['nombre'][0]['total'] : 0;

        /** On calcul la valeur pour les autres types de version */
        $lesAutres = $toutesLesVersions - $release - $snapshot;

        return [
                'release' => $release, 'snapshot' => $snapshot, 'autre' => $lesAutres,
                'projet' => $infoRelease['version'][0]['projet'],
                'date' => $infoRelease['version'][0]['date'],
        ];
    }

    /**
     * [Description for batchInformation]
     * Collecte des données pour la table information_projet
     *
     * @param string $mavenKey
     *
     * @return array
     *
     * Created at: 09/12/2022, 16:42:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchCollecteInformation(string $mavenKey): array
    {
        /** On instancie l'EntityRepository */
        $informationProjetRepository = $this->em->getRepository(InformationProjet::class);

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /** Construit l'URL en utilisant http_build_query pour les paramètres de la requête */
        $queryParams = [ 'project' => $mavenKey ];
        $queryString = http_build_query($queryParams);

        /** Appelle le client HTTP */
        $result = $this->client->http("$tempoUrl/api/project_analyses/search?$queryString");
        /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (isset($result['code']) && in_array($result['code'], [401, 404])) {
            return ['code' => $result['code']];
        }
        /** On supprime les informations pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $delete=$informationProjetRepository->deleteInformationProjetMavenKey($map);
        if ($delete['code']!=200) {
            return ['code' => $delete['code'], static::$request=>'deleteInformationProjetMavenKey'];
        }

        /** On ajoute les informations du projet dans la table information_projet. */
        foreach ($result['analyses'] as $information) {
            /**
             *  La version du projet doit être xxx-release, xxx-snapshot ou xxx
             *  Dans ce cas, le tableau renvoi toujours [0] pour la version et
             *  [1] pour le type de version (release, snapshot ou null)
             */
            $explode = explode('-', $information['projectVersion']);
            if (!isset($explode[1]) || empty($explode[1])) {
                $explode[1] = 'N.C';
            }
            $date = new \DateTimeImmutable();
            $date->setTimezone(new \DateTimeZone(static::$europeParis));

            $map=['maven_key' => $mavenKey,
                    'analyse_key' => $information['key'],
                    'date' => $information['date'],
                    'project_version' => $information['projectVersion'],
                    'type' => strtoupper($explode[1]),
                    'date_enregistrement' => $date
            ];

            $insert=$informationProjetRepository->insertInformationProjet($map);
            if ($insert['code']!=200) {
                return ['code' => $insert['code'], static::$request=>'insertInformationProjetMavenKey'];
            }
        }

        /** On appel la méthode de traitement des données */
        $versions = $this->batchInformationVersion($mavenKey);
        return [ 'code'=>200, 'message' => $versions ];
    }

}
