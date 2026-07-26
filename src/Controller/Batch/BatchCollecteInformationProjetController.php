<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
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
use App\Service\{IsValideMavenKey, ClientService, UrlBuilderService};

/**
 * [Description BatchCollecteInformationProjetController]
 */
class BatchCollecteInformationProjetController extends AbstractController
{
    /** Définition des constantes */
    private static string $sonarUrl = "sonar.url";
    private static string $europeParis = "Europe/Paris";

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
        private ClientService $client,
        private UrlBuilderService $urlBuilder,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for controlVersionProjet]
     *
     *
     * @return array<int|string, mixed>
     *
     * Created at: 04/06/2024 11:51:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function controlVersionProjet(string $mavenKey): array
    {
        $maven_key = htmlspecialchars($mavenKey, ENT_QUOTES, 'UTF-8');

         /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(self::$sonarUrl),
            '/api/project_analyses/search',
            [ 'project' => $maven_key ]
        );

        $this->logger->debug('[Batch controlVersionProjet] 🛠️ Appel API SonarQube', ['url' => $url]);
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
        $isForbidden = false;
        $isNotAvailable = false;
        if ( $isFound === false){
            $isNotAuthorize = ($result['code'] == 401) ? true : false;
            $isForbidden = ($result['code'] == 403) ? true : false;
            $isNotFound = ($result['code'] == 404) ? true : false;
            $isNotAvailable = ($result['code'] == 503) ? true : false;
        }

        /**
         * Le projet est présent en base (dans la table informationProjet et historique)
         * et sur le serveur.
         */
        if ($isFound && $inBase){
            $this->logger->info('[Batch ControlVersionProjet] ℹ️ Projet présent en base et sur SonarQube', [
                'maven_key' => $maven_key]);
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
            $this->logger->info('[Batch ControlVersionProjet] ℹ️ Projet uniquement sur SonarQube', [
                'maven_key' => $maven_key]);
            return [
                'code' => 202,
                /* MODIF 2026-05-07 : message inversé (cas: trouvé sur serveur, pas en base). */
                'message' => "Le projet est présent sur le serveur mais pas en base.",
                'data-sonarqube' => $result,
                'data-baseInformation'=>[],
                'data-baseHistorique' => []
            ];
        }

        /** L'utilisateur n'a pas les droits SonarQube nécessaires. */
        if ($isNotAuthorize){
            $this->logger->error('[Batch ControlVersionProjet] ❌ Accès refusé à l’API Sonar (401)', [
                'maven_key' => $maven_key]);
            return [
                'code' => 401,
                'message' => "Le serveur SonarQube n'autorise pas l'utilisateur à se connecter à cette API (Erreur 401"
            ];
        }

        /** Le token/utilisateur n'a pas les droits suffisants sur CE projet (ex. permission Browse manquante). */
        if ($isForbidden){
            $this->logger->error('[Batch ControlVersionProjet] ❌ Droits SonarQube insuffisants sur le projet (403)', [
                'maven_key' => $maven_key,
                'erreur' => $result['erreur'] ?? null,
            ]);
            return [
                'code' => 403,
                'message' => "Droits SonarQube insuffisants pour ce projet (Erreur 403) : " . ($result['erreur'] ?? 'Insufficient privileges') . "."
            ];
        }

        /** Le projet n'est pas disponible sur SonarQube */
        if ($isNotFound){
            $this->logger->error('[Batch ControlVersionProjet] ❌ Projet introuvable sur Sonar (404)', [
                'maven_key' => $maven_key]);
            return [
                'code' => 404,
                'message' => "Le projet n'existe pas sur le serveur SonarQube (Erreur 404)."
            ];
        }

        /** Le projet n'est pas disponible sur SonarQube */
        if ($isNotAvailable){
            $this->logger->error('[Batch ControlVersionProjet] ❌ SonarQube indisponible (503)', [
                'maven_key' => $maven_key]);
            return [
                'code' => 503,
                'message' => "La résolution DNS n'a pas permis d'accéder au serveur SonarQube (Erreur 503)."];
        }

        /**
         * Code d'erreur SonarQube non anticipé par les branches ci-dessus (ex. 400, 429...) :
         * on relaie le VRAI code/message plutôt que de renvoyer un 500 opaque qui masquerait
         * la cause réelle (cf. incident tetris:TetrisGame du 2026-07-15 : un 403 tombait ici).
         */
        $codeReel = (int) ($result['code'] ?? 500);
        $messageReel = $result['erreur'] ?? null;
        $this->logger->error('[Batch ControlVersionProjet] ❌ Erreur inattendue de l’API SonarQube', [
            'maven_key' => $maven_key,
            'code' => $codeReel,
            'erreur' => $messageReel,
        ]);
        return [
            'code' => $codeReel,
            'message' => $messageReel
                ? "Erreur inattendue de l'API SonarQube (Erreur {$codeReel}) : {$messageReel}."
                : "Une erreur inattendue est survenue (Erreur {$codeReel})."
        ];
    }

    /**
     * [Description for calculRepartitionProjet]
     *
     * @param array<int, array<string, mixed>> $analyses
     *
     * @return array<int|string, mixed>
     *
     * Created at: 06/02/2025 13:28:46 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function calculRepartitionProjet(array $analyses): array
    {
        /* si $analyses est  un tableau ? */
        $total = $release = $snapshot = $autre = 0;
        foreach ($analyses as $analyse) {
            if (array_key_exists('projectVersion', $analyse)) {
                $total++;
                if (strpos(strtolower($analyse['projectVersion']), 'release') !== false) {
                    $release++;
                } elseif (strpos(strtolower($analyse['projectVersion']), 'snapshot') !== false) {
                    $snapshot++;
                // MODIF 2026-05-26 : else terminal requis par S126 (version autre, comptée via $autre = $total - release - snapshot).
                } else {
                    $this->logger->debug('[BatchInfoProjet] 🔍 Version non release/snapshot, comptée dans "autre".', ['version' => $analyse['projectVersion']]);
                }
            }
        }
        $autre = $total - ($release + $snapshot);

        $this->logger->debug('[Batch CalculRepartitionProjet] 🛠️ Répartition calculée', [
            'total' => $total,
            'release' => $release,
            'snapshot' => $snapshot,
            'autre' => $autre
        ]);

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
     * @param string $maven_key
     *
     * @return array<int|string, mixed>
     *
     * Created at: 09/12/2022, 17:13:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchInformationVersion(string $maven_key): array
    {
        /** On instancie l'entityRepository */
        $informationProjetRepos = $this->em->getRepository(InformationProjet::class);

        $map = [ 'maven_key' => $maven_key ];
        $version = $informationProjetRepos->selectInformationProjetVersion($map);
        if ($version['code'] != 200) {
            $this->logger->error('[Batch InformationVersion] ❌  Erreur accès à la base de données.', [
                'maven_key' => $maven_key,
                'erreur' => $version['erreur']
            ]);

            return [
                'code' => $version['code'],
                'erreur' => $version['erreur']
            ];
        }

        // MODIF 2026-06-10 — fetchAssociative() retourne un tableau plat, pas [0]
        return [
                'analyse_key' => $version['info']['analyse_key'] ?? 'inconnu',
                'release' => $version['info']['version_release_sonar'] ?? 0,
                'snapshot' => $version['info']['version_snapshot_sonar'] ?? 0,
                'autre' => $version['info']['version_autre_sonar'] ?? 0,
                'projet_version' => $version['info']['version'] ?? 'inconnu',
                'date' => $version['info']['date'] ?? '1971-07-04 00:00:00',
                ];
    }

    /**
     * [Description for batchInformation]
     * Collecte des données pour la table information_projet
     *
     * @param string $maven_key
     * @param string $mode_collecte
     *
     * @return array<int|string, mixed>
     *
     * Created at: 09/12/2022, 16:42:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     */
    public function batchCollecteInformation(string $maven_key, string $mode_collecte, string $utilisateur_collecte): array
    {
        $informationProjetRepos = $this->em->getRepository(InformationProjet::class);

        $this->logger->info('[Batch InformationProjet] ℹ️  Début de la collecte.', [
            'maven_key' => $maven_key,
            'mode_collecte' => $mode_collecte,
            'utilisateur' => $utilisateur_collecte
        ]);

        /** On récupère les informations du projet */
        $isValide = $this->controlVersionProjet($maven_key);
        /** Seuls 200 (trouvé) et 202 (trouvé sur SonarQube, pas encore en base) sont des cas valides ;
         *  tout le reste (401/403/404/503, ou tout autre code relayé tel quel) est une erreur. */
        if (!in_array($isValide['code'], [200, 202])) {
            $this->logger->error("[Batch ControlVersionProjet] ❌ {$isValide['message']} (Erreur {$isValide['code']}).") ;
            return [
                'code' => $isValide['code'],
                'message' => $isValide['message']
            ];
        }

        /** On vérifie si on doit mettre à jour la version ou pas */
        /** 01 - Version SonarQube */
        $result = $isValide['data-sonarqube']['json']['analyses'][0];
        /** On compte le nombre de version sur le serveur */
        $versionSonarQube = $result['projectVersion'];
        $dateAnalyseSonarQube = $result['date'];
        $keyAnalyseSonarQube = $result['key'];

        /** 02 - Version  Locale, on prend la version historique par défaut */
        if ($isValide['code'] != 202 && $mode_collecte != 'COLLECTE'){
            $local = $isValide['data-baseHistorique'] ?? $isValide['data-baseInformation'];
            $versionLocale = $local['version'] ?? $local['projectVersion'] ?? 'VIDE';
            $dateAnalyseLocale = $local['date_version'] ?? $local['date'] ?? 'VIDE';
            $keyAnalyseLocale = $local['analyse_key'] ?? $local['analyse_key'] ?? 'VIDE';
            $nameAnalyseLocale = $local['name'] ?? $local['maven_key'] ?? 'VIDE';

            $versionMap = [
                'code' => $isValide['code'],
                'mode_collecte' => $mode_collecte,
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
                    'message' => 'Le projet est à jour, pas la peine de continuer.',
                    'historique' => $versionMap
                ];
            }
        }

        /** On calcule la répartition des versions du projet présent sur SonarQube */
        $analyses = $isValide['data-sonarqube']['json']['analyses'];
        $repartition = self::calculRepartitionProjet($analyses);

        /** On supprime les informations pour la maven_key. */
        $map = [ 'maven_key' => $maven_key ];
        $delete = $informationProjetRepos->deleteInformationProjetMavenKey($map);
        if ($delete['code'] != 200) {
            $this->logger->error('[Batch InformationProjet] ❌ Échec suppression ancienne version', [
                'maven_key' => $maven_key,
                'erreur' => $delete['erreur']
            ]);

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
        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));

        $map = [
                'maven_key' => $maven_key,
                'analyse_key' => $result['key'],
                'date' => $result['date'],
                'project_version' => $result['projectVersion'],
                'type' => strtoupper($explode[1]),
                'version_sonar' => $repartition['total'],
                'version_release_sonar' => $repartition['release'],
                'version_snapshot_sonar' => $repartition['snapshot'],
                'version_autre_sonar' => $repartition['autre'],
                'mode_collecte' => $mode_collecte,
                'utilisateur_collecte' => $utilisateur_collecte,
                'date_enregistrement' => $date
            ];

        $insert = $informationProjetRepos->insertInformationProjet($map);
        if ($insert['code'] != 200) {
            $this->logger->error('[Batch InformationProjet] ❌ Échec d’insertion des donnés dans la base.', [
                'maven_key' => $maven_key,
                'erreur' => $insert['erreur']
            ]);

            return [
                'code' => $insert['code'],
                'erreur' => $insert['erreur']
            ];
        }

        /** On appel la méthode de traitement des données */
        $version = $this->batchInformationVersion($maven_key);

        /** On prépare les données pour l'historique */
        $historique = [
                'analyse_key' => $version['analyse_key'],
                'version_release' => $version['release'],
                'version_snapshot' => $version['snapshot'],
                'version_autre' => $version['autre'],
                'version_sonar' => $repartition['total'],
                'version_release_sonar' => $repartition['release'],
                'version_snapshot_sonar' => $repartition['snapshot'],
                'version_autre_sonar' => $repartition['autre'],
                'version' => $version['projet_version'],
                'date_version' => $version['date']
            ];

        $this->logger->info('[Batch InformationProjet] ℹ️ Collecte pour les informations projet terminée.', [
            'maven_key' => $maven_key,
            'version' => $result['projectVersion'],
            'analyse_key' => $result['key']
            ]);

        return [
            'code' => 200,
            'message' => 'Mise à jour des informations pour le projet terminée.',
            'historique'=> $historique
        ];
    }

}
