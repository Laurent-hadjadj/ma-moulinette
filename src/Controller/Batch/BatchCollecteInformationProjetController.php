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
     * [Description for controleVersionProjet]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 04/06/2024 11:51:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function controleVersionProjet($mavenKey):array
    {
        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);
        $mavenKey = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

        /** Construit l'URL en utilisant http_build_query pour les paramètres de la requête */
        $queryParams = [ 'project' => $mavenKey ];
        $queryString = http_build_query($queryParams);

        /** Appelle le client HTTP */
        $result = $this->client->http("$tempoUrl/api/project_analyses/search?$queryString");
        /** On vérifie si le projet exsite en locale */
        $request=$this->isValidMavenKey->isValide($mavenKey);

        /* On vérifie le code erreur */
        $isFound=isset($result['code']) ? false : true;
        $inBase = ($request['code'] == 200) ? true : false;
        $isNotInBase = ($request['code'] == 404) ? true : false;

        if (isset($result['code'])){
            $isNotAuthorize=($result['code'] == 401) ? true : false;
            $isNotFound=($result['code'] == 404) ? true : false;
        }

        /** Le projet est présent en base et sur le serveur */
        if ($isFound && $inBase){
            return ['code'=>200, 'message'=>"Le projet est présent en base et sur le serveur", 'data-sonarqube'=>$result, 'data-base'=>$request['request']];
        }

        /** Le projet n'est pas présent en base et mais existe sur le serveur */
        if ($isFound && $isNotInBase){
            return ['code'=>202, 'message'=>"Le projet est présent en base et sur le serveur", 'data'=>$result];
        }

        /** Le projet n'est pas disponible sur Sonarqube */
        if ($isNotFound){
            return ['code'=>404, 'message'=>"Le projet n'existe pas sur le serveur SonarQube"];
        }

        /** L'utilisateur n'a pas les droits SonaQube nécessaires. */
        if ($isNotAuthorize){
            return ['code'=>401, 'message'=>"Le serveur SonarQube n'autorise pas l'utilisateur à se connecter à cette API."];
        }

        return ['code'=>500, 'message'=>"Une erreur inatendue est survenue !"];
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
    private function batchInformationVersion(string $mavenKey): array
    {
        /** On instancie l'entityRepository */
        $informationProjetRepository = $this->em->getRepository(InformationProjet::class);

        /** On compte toutes les version de type (RELEASE, SNAPSHOT, AUTRE) */
        $map=['maven_key' => $mavenKey];
        $toutesLesVersions=$informationProjetRepository->countInformationProjetAllType($map);
        if ($toutesLesVersions['code']!=200) {
            return ['code' => $toutesLesVersions['code'], static::$request=>'touteLesVersions'];
        }

        /** On compte les releases */
        $map=['maven_key'=>$mavenKey, 'type'=>'RELEASE'];
        $release=$informationProjetRepository->countInformationProjetType($map);
        if ($release['code']!=200) {
            return ['code' => $release['code'], static::$request=>'releases'];
        }

        /** On compte les snapshots */
        $map=['maven_key'=>$mavenKey, 'type'=>'SNAPSHOT'];
        $snapshot=$informationProjetRepository->countInformationProjetType($map);
        if ($snapshot['code']!=200) {
            return ['code' => $snapshot['code'], static::$request=>'snapshot'];
        }

        /** On récupère la dernière version et sa date de publication */
        $map=['maven_key'=>$mavenKey];
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
                'release' => $release, 'snapshot' => $snapshot,
                'autre' => $lesAutres,
                'projet' => $infoRelease['version'][0]['projet'],
                'date' => $infoRelease['version'][0]['date'],
        ];
    }

    /**
     * [Description for batchInformation]
     * Collecte des données pour la table information_projet
     *
     * @param string $mavenKey
     * @param string $modeCollecte
     * @param string $UtilisateurCollecte
     *
     * @return array
     *
     * Created at: 09/12/2022, 16:42:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchCollecteInformation(string $mavenKey, string $modeCollecte, string $utilisateurCollecte): array
    {
        /** On instancie l'EntityRepository */
        $informationProjetRepository = $this->em->getRepository(InformationProjet::class);

        /** On récupre les informations du projet */
        $isValide=$this->controleVersionProjet($mavenKey);
        if ($isValide['code']===404) {
            return ['code'=>$isValide['code'], 'message'=>$isValide['message']];
        }

        /** On vérifie si on doit mettre à jour la version ou pas */
        /** 01 - Version Sonarqube */
        $result=$isValide['data-sonarqube']['analyses'][0];
        $versionSonarQube=$result['projectVersion'];
        $keyAnalyseSonarQube=$result['key'];
        $dateAnalyseSonarQube=$result['date'];

        /** 02 - Version  Locale */
        $request=$isValide['data-base'];
        $versionLocale=$request['project_version'];
        $dateAnalyseLocale=$request['date'];
        $keyAnalyseLocale=$request['analyse_key'];

        $versionMap=['sonarqube'=>['version'=>$versionSonarQube,
            'key-analyse' => $keyAnalyseSonarQube, 'date-analyse'=>$dateAnalyseSonarQube],
            'locale'=>['version'=>$versionLocale, 'key-analyse'=>$keyAnalyseLocale, 'date'=>$dateAnalyseLocale]
        ];

        /** Si le projet locale est à jour, pas la peine de lancer la collecte */
        if ($keyAnalyseLocale===$keyAnalyseSonarQube) {
            return ['code'=>100, 'message'=>"Le projet est à jour", 'data'=>$versionMap];
        }

        /** On supprime les informations pour la maven_key. */
        $map=['maven_key'=>$mavenKey];
        $delete=$informationProjetRepository->deleteInformationProjetMavenKey($map);
        if ($delete['code']!=200) {
            return ['code' => $delete['code'],
                    'error'=>[$delete['erreur'], static::$request=>'deleteInformationProjetMavenKey']
                ];
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
                    'mode_collecte' => $modeCollecte,
                    'utilisateur_collecte' => $utilisateurCollecte,
                    'date_enregistrement' => $date
            ];

            $insert=$informationProjetRepository->insertInformationProjet($map);
            if ($insert['code']!=200) {
                return ['code' => $insert['code'],
                'error'=>[$insert['erreur']],
                static::$request=>'insertInformationProjetMavenKey'];
            }
        }

        /** On appel la méthode de traitement des données */
        $version = $this->batchInformationVersion($mavenKey);

        /** On prépare les données pour l'historique */
        $data=['version_release' => $version['release'],
                'version_snapshot' => $version['snapshot'],
                'version_autre' => $version['autre'],
                'version' => $version['projet'],
                'date_version' =>$version['date']];
        return [ 'code'=>200, 'message' => $version, 'data'=>$data ];
    }

}
