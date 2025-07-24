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
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\InformationProjet;
use App\Service\Client;
use App\Service\IsValideMavenKey;
use App\Service\UrlBuilderService;

/**
 * [Description BatchCollecteInformationProjetController]
 */
class BatchCollecteInformationProjetController extends AbstractController
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
        private EntityManagerInterface $em,
        private IsValideMavenKey $isValidMavenKey,
        private Client $client,
        private UrlBuilderService $urlBuilder,

    ) {
    }

    /**
     * [Description for controlVersionProjet]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 04/06/2024 11:51:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function controlVersionProjet($mavenKey): array
    {
        $maven_key = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

         /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/project_analyses/search',
            [ 'project' => $maven_key ]
        );

        /** Appelle le client HTTP */
        $result = $this->client->httpSonarQube($url);

        /** On vérifie si le projet existe en locale dans la table information ou historique*/
        $requestInformation = $this->isValidMavenKey->isValideInformation($mavenKey);
        $requestHistorique = $this->isValidMavenKey->isValideHistorique($mavenKey);

        /* On vérifie le code erreur */
        /* SonarQube, si on récupère un json, alors le projet existe sur le serveur */
        $isFound = isset($result['json']) ? true : false;
        /* Le projet est présent dans les deux tables */
        $inBase = ($requestInformation['code'] == 200 && $requestHistorique['code'] == 200) ? true : false;
        /* Le projet n'a pas été trouvé dans le base de données */
        $isNotInBase = ($requestInformation['code'] == 404 || $requestHistorique['code'] == 404) ? true : false;

        /** Analyse du retour de la réponse du serveur SonarQube */
        $isNotFound = false;
        $isNotAuthorize = false;
        $isNotAvailable = false;
        if ( $isFound === false){
            $isNotAuthorize = ($result['code'] == 401) ? true : false;
            $isNotFound = ($result['code'] == 404) ? true : false;
            $isNotAvailable = ($result['code'] == 503) ? true : false;
        }

        /**
         * Le projet est présent en base (dans la table informationProjet et historique)
         * et sur le serveur.
         */
        if ($isFound && $inBase){
            return [
                'code' => 200,
                'message' => 'Le projet est présent en base et sur le serveur',
                'data-sonarqube' => $result,
                'data-baseInformation' => $requestInformation['request'],
                'data-baseHistorique' => $requestHistorique['request']
            ];
        }

        /** Le projet n'est pas présent en base mais existe sur le serveur */
        if ($isFound && $isNotInBase){
            return [
                'code' => 202,
                'message' => "Le projet est présent en base mais pas sur le serveur.",
                'data-sonarqube' => $result,
                'data-baseInformation'=>[],
                'data-baseHistorique' => []];
        }

        /** L'utilisateur n'a pas les droits SonarQube nécessaires. */
        if ($isNotAuthorize){
            return [
                'code' => 401,
                'message' => "Le serveur SonarQube n'autorise pas l'utilisateur à se connecter à cette API (Erreur 401)."];
        }

        /** Le projet n'est pas disponible sur SonarQube */
        if ($isNotFound){
            return [
                'code' => 404,
                'message' => "Le projet n'existe pas sur le serveur SonarQube (Erreur 404)."];
        }

        /** Le projet n'est pas disponible sur SonarQube */
        if ($isNotAvailable){
            return [
                'code' => 503,
                'message' => "La résolution DNS n'a pas permis d'accéder au serveur SonarQube (Erreur 503)."];
        }

        return ['code' => 500, 'message' => 'Une erreur inattendue est survenue (Erreur500).'];
    }

    /**
     * [Description for calculRepartitionProjet]
     *
     * @param mixed $analyses
     *
     * @return array
     *
     * Created at: 06/02/2025 13:28:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function calculRepartitionProjet($analyses): array
    {
        /* si $analyses est  un tableau ? */
        $total = $release = $snapshot = $autre = 0;
        if (is_array($analyses)){
            foreach($analyses as $analyse){
                if (array_key_exists('projectVersion', $analyse)){
                    $total++;
                    if (strpos(strtolower($analyse['projectVersion']), 'release') !== false) {
                        $release++;
                    } elseif (strpos(strtolower($analyse['projectVersion']), 'snapshot') !== false) {
                        $snapshot++;
                    }
                }
            }
            $autre = $total - ($release+$snapshot);
        }
        return [
                'total' => $total,
                'release' => $release,
                'snapshot' => $snapshot,
                'autre' => $autre
            ];
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
    public function batchInformationVersion(string $maven_key): array
    {
        /** On instancie l'entityRepository */
        $informationProjetRepos = $this->em->getRepository(InformationProjet::class);

        $map = ['maven_key' => $maven_key ];
        $version = $informationProjetRepos->selectInformationProjetVersion($map);
        if ($version['code'] != 200) {
            return [
                'code' => $version['code'],
                'erreur' => $version['erreur']
            ];
        }

        return [
                'analyse_key' => $version['info'][0]['analyse_key'] ?? 'inconnu',
                'release' => $version['info'][0]['version_release_sonar'] ?? 0,
                'snapshot' => $version['info'][0]['version_snapshot_sonar'] ?? 0,
                'autre' => $version['info'][0]['version_autre_sonar'] ?? 0,
                'projet' => $version['info'][0]['projet'] ?? 'inconnu',
                'date' => $version['info'][0]['date'] ?? '1971-07-04 00:00:00',
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
        $informationProjetRepos = $this->em->getRepository(InformationProjet::class);

        /** On récupère les informations du projet */
        $isValide = $this->controlVersionProjet($mavenKey);
        if (in_array($isValide['code'], [401, 404, 503, 500])) {
            return ['code' => $isValide['code'], 'message' => $isValide['message'] ];
        }

        /** On vérifie si on doit mettre à jour la version ou pas */
        /** 01 - Version SonarQube */
        $result = $isValide['data-sonarqube']['json']['analyses'][0];
        /** On compte le nombre de version sur le serveur */
        $versionSonarQube = $result['projectVersion'];
        $dateAnalyseSonarQube = $result['date'];
        $keyAnalyseSonarQube = $result['key'];

        /** 02 - Version  Locale, on prend la version historique par défaut */
        if ($isValide['code'] != 202 && $modeCollecte != 'COLLECTE'){
            $local = $isValide['data-baseHistorique'] ?? $isValide['data-baseInformation'];
            $versionLocale = $local['version'] ?? $local['projectVersion'] ?? 'VIDE';
            $dateAnalyseLocale = $local['date_version'] ?? $local['date'] ?? 'VIDE';
            $keyAnalyseLocale = $local['analyse_key'] ?? $local['analyse_key'] ?? 'VIDE';
            $nameAnalyseLocale = $local['name'] ?? $local['maven_key'] ?? 'VIDE';

            $versionMap = [
                'SonarQube' => [
                        'key-analyse' => $keyAnalyseSonarQube,
                        'version' => $versionSonarQube,
                        'date-analyse'=> $dateAnalyseSonarQube],
                'Locale' => [
                        'name' => $nameAnalyseLocale,
                        'key-analyse' => $keyAnalyseLocale,
                        'version' => $versionLocale,
                        'date-analyse' => $dateAnalyseLocale
                        ]
            ];
            /** Si le projet locale est à jour, pas la peine de lancer la collecte */
            if ($keyAnalyseSonarQube === $keyAnalyseLocale) {
                return [
                    'code' => 100,
                    'message' => 'Le projet est à jour',
                    'data' => $versionMap
                ];
            }
        }

        /** On calcule la répartition des versions du projet présent sur SonarQube */
        $analyses = $isValide['data-sonarqube']['json']['analyses'];
        $repartition = self::calculRepartitionProjet($analyses);

        /** On supprime les informations pour la maven_key. */
        $map = [ 'maven_key' => $mavenKey ];
        $delete = $informationProjetRepos->deleteInformationProjetMavenKey($map);
        if ($delete['code'] != 200) {
            return [
                'code' => $delete['code'],
                'erreur' => $delete['erreur']
            ];
        }

        /** On ajoute les informations du projet dans la table information_projet. */
        /**
         *  La version du projet doit être xxx-release, xxx-snapshot ou xxx
         *  Dans ce cas, le tableau renvoi toujours [0] pour la version et
         *  [1] pour le type de version (release, snapshot ou null)
         */
        $explode = explode('-', $result['projectVersion']);
        if (!isset($explode[1]) || empty($explode[1])) {
            $explode[1] = 'N.C';
        }
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        $map = [
                'maven_key' => $mavenKey,
                'analyse_key' => $result['key'],
                'date' => $result['date'],
                'project_version' => $result['projectVersion'],
                'type' => strtoupper($explode[1]),
                'version_sonar' => $repartition['total'],
                'version_release_sonar' => $repartition['release'],
                'version_snapshot_sonar' => $repartition['snapshot'],
                'version_autre_sonar' => $repartition['autre'],
                'mode_collecte' => $modeCollecte,
                'utilisateur_collecte' => $utilisateurCollecte,
                'date_enregistrement' => $date
            ];

        $insert = $informationProjetRepos->insertInformationProjet($map);
        if ($insert['code'] != 200) {
            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        /** On appel la méthode de traitement des données */
        $version = $this->batchInformationVersion($mavenKey);

        /** On prépare les données pour l'historique */
        $data = [
                'analyse_key' => $version['analyse_key'],
                'version_release' => $version['release'],
                'version_snapshot' => $version['snapshot'],
                'version_autre' => $version['autre'],
                'version_sonar' => $repartition['total'],
                'version_release_sonar' => $repartition['release'],
                'version_snapshot_sonar' => $repartition['snapshot'],
                'version_autre_sonar' => $repartition['autre'],
                'version' => $version['projet'],
                'date_version' => $version['date']
            ];

        /** on injecte les résultats de SonarQube */
        $version['version_sonar'] = $repartition['total'];
        $version['version_release_sonar'] = $repartition['release'];
        $version['version_snapshot_sonar'] = $repartition['snapshot'];
        $version['version_autre_sonar'] = $repartition['autre'];

        return [
            'code' => 200,
            'message' => $version,
            'data'=>$data ];
    }

}
