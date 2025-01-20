<?php

declare(strict_types=1);

namespace App\Controller\Suivi;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use App\Service\Client;
use App\Entity\Historique;
use App\Entity\Utilisateur;
use App\Entity\InformationProjet;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/**
 * [Description ApiSuiviController]
 */
class ApiSuiviController extends AbstractController
{

    public static $europeParis = "Europe/Paris";
    public static $dateFormatTimezone = "Y-m-d\TH:i:sO";
    public static $sonarUrl = "sonar.url";
    public static $reference = "[Suivi]";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur403 = "Vous devez avoir le rôle GESTIONNAIRE pour réaliser cette action (Erreur 403).";
    public static $erreur404 = "Vous devez être rattaché à une équipe (Erreur 404).";
    public static $erreur406 = "Je n'ai pas trouvé de projets pour ton équipe. ".
    "Vérifiez le nom du tag utilisé dans SonarQube (Erreur 406).";
    public static $erreur500 = "L'accès au serveur SonarQube n'est pas possible (Erreur 500).";

    private $em;
    private $client;
    private $security;

    public function __construct(
        EntityManagerInterface $em,
        Client $client,
        Security $security
    ) {
        $this->em = $em;
        $this->client = $client;
        $this->security = $security;
    }

    /**
     * [Description for listeVersion]
     * On récupère la liste des projets nom + clé pour le sélecteur de projet.
     * http://{url}}/api/liste/version
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:35:41 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/liste/v1.0/version', name: 'liste_v1_version', methods: ['POST'])]
    public function listeVersionV1(Request $request): JsonResponse
    {
        /** On instancie l'entityRepository */
        $informationProjetRepository = $this->em->getRepository(InformationProjet::class);

        /** On récupère le job et le type (manuel ou automatique) */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse([
                'code' => 400, 'type' => 'alert',
                'message' => static::$reference . static::$erreur400], Response::HTTP_OK);
        }

        /** On vérifie  */
        $map = ['maven_key' => $data->maven_key];
        $request = $informationProjetRepository->selectInformationProjetVersion($map);
        if ($request['code'] != 200) {
            return new JsonResponse([
                'maven_key' => $data->maven_key, 'code'=>$request['code'],
                'erreur' => $request['erreur']], Response::HTTP_OK);
        }

        $liste = [];
        $id = 0;
        /** objet = { id: clé, text: "blablabla" }; */
        foreach ($request['versions'] as $version) {
            $ts = new \DateTime($version['date'], new \DateTimeZone(static::$europeParis));
            $cc = $ts->format("d-m-Y H:i:s");
            $objet = [
                'id' => $id,
                'text' => $version['version'] . " (" . $cc . ")"];
            array_push($liste, $objet);
            $id++;
        }

        return new JsonResponse(['code' => 200, 'liste' => $liste], Response::HTTP_OK);
    }

    #[Route('/api/liste/v2.0/version', name: 'liste_v1_version', methods: ['POST'])]
    public function listeVersionV2(Request $request): JsonResponse
    {
        /** On récupère le job et le type (manuel ou automatique) */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse([
                'code' => 400, 'type' => 'alert',
                'message' => static::$reference . static::$erreur400], Response::HTTP_OK);
        }

        /** On construit l'URL */
        $tempoUrl = $this->getParameter(static::$sonarUrl);

        /** Appelle le client HTTP */
        $queryParams = ['project' => $data->maven_key, 'p'=>1, 'ps'=>100 ];
        $result = $this->client->httpSonarQube("$tempoUrl/api/project_analyses/search?".http_build_query($queryParams));
        if (in_array($result['code'] ?? -1, [503, 504])) {
            return new JsonResponse([
                'code' => 500, 'type' => 'alert',
                'message' => static::$reference . static::$erreur500], Response::HTTP_OK);
        }
        //analyses/date "date" => "2024-11-15T15:52:03+0100"
        //analyses/projectVersion "projectVersion" => "1.1.0-RELEASE"
        $liste = [];
        $id = 0;
        /** objet = { id: clé, text: "blablabla" }; */
        foreach ($result['json']['analyses'] as $version) {
            $ts = new \DateTime($version['date'], new \DateTimeZone(static::$europeParis));
            $cc = $ts->format('d-m-Y H:i:sO');
            $objet = [
                'id' => $id,
                'text' => $version['projectVersion'] . " (" . $cc . ")"];
            array_push($liste, $objet);
            $id++;
        }

        return new JsonResponse(['code' => 200, 'liste' => $liste], Response::HTTP_OK);
    }

    /**
     * [Description for getVersion]
     * On récupère les données disponibles pour une version données
     * http://{url}}/api/get/version
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:36:17 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/get/version', name: 'get_version', methods: ['POST'])]
    public function getVersion(Request $request): JsonResponse
    {
        /** On récupère la maven_Key */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key') ||
            !property_exists($data, 'date')) {
            return new JsonResponse([
                'code' => 400, 'type' => 'alert',
                'message' => static::$reference . static::$erreur400], Response::HTTP_OK);
        }

        /**  On a pas besoin d'encodé la date */
        //28-06-2024 20:48:20 to 2024-06-28T20:48:20+0200"
        $date_convert=new \Datetime($data->date);
        $date_format = $date_convert->format('Y-m-d\TH:i:sO');
        $url = $this->getParameter(static::$sonarUrl);

        $paramsList = [
            'coverage' => [
                'component' => $data->maven_key,
                'metrics' => 'coverage,tests,skipped_tests,test_errors,test_failures,test_success_density,duplicated_lines_density',
                'from' => $date_format,
                'to' => $date_format
            ],
            'issues' => [
                'component' => $data->maven_key,
                'metrics' => 'violations',
                'from' => $date_format,
                'to' => $date_format ],
            'size' => [
                'component' => $data->maven_key,
                'metrics' => 'classes,comment_lines,comment_lines_density,files,lines,ncloc,ncloc_language_distribution,functions',
                'from' => $date_format,
                'to' => $date_format
            ],
            'maintainability' => [
                'component' => $data->maven_key,
                'metrics' => 'code_smells,sqale_index,sqale_debt_ratio,sqale_rating',
                'from' => $date_format,
                'to' => $date_format
            ],
            'reliability' => [
                'component' => $data->maven_key,
                'metrics' => 'bugs,reliability_rating',
                'from' => $date_format,
                'to' => $date_format
            ],
            'security' => [
                'component' => $data->maven_key,
                'metrics' => 'vulnerabilities,security_rating,security_hotspots,security_review_rating',
                'from' => $date_format,
                'to' => $date_format
            ]
        ];

        $results = [];
        foreach ($paramsList as $key => $params) {
            $request=$this->client->httpSonarQube("$url/api/measures/search_history?".http_build_query($params));
            $results[$key] = $request['json'];
        }
        $keys=['coverage', 'issues', 'size', 'maintainability', 'reliability', 'security'];

        if (array_key_exists('coverage', $results) && array_key_exists('code', $results['coverage'])){
            return new JsonResponse(['code' => $results['coverage']['code'], 'erreur'=>$results['coverage']['erreur']], Response::HTTP_OK);
        }
        foreach ($keys as $key){
            $metricsData[$key] = [];
            foreach ($results[$key]['measures'] as $measure) {
                $metric = $measure['metric'];
                foreach ($measure['history'] as $history) {
                    $value = $history['value'];
                    // Enregistre la valeur de la métrique
                    $metricsData[$key][$metric] = $value;
                }
            }
        }

        $metricTypesCoverage = [
            'tests' => 'intval',
            'test_errors' => 'intval',
            'skipped_tests' => 'intval',
            'test_failures' => 'intval',
            'test_success_density' => 'floatval',
            'coverage' => 'floatval',
            'duplicated_lines_density' => 'floatval'
        ];

        $metricTypesIssues = [
            'violations' => 'intval',
        ];

        $metricTypesSize = [
            'comment_lines_density' => 'floatval',
            'lines' => 'intval',
            'ncloc' => 'intval',
            'ncloc_language_distribution' => 'App\Controller\Suivi\handleNclocLanguageDistribution',
            'classes' => 'intval',
            'files' => 'intval',
            'functions' => 'intval',
            'comment_lines' => 'intval'
        ];

        $metricTypesMaintainability = [
            'code_smells' => 'intval',
            'sqale_index' => 'intval',
            'sqale_debt_ratio' => 'floatval',
            'sqale_rating' => 'floatval'
        ];

        $metricTypesReliability = [
            'bugs' => 'intval',
            'reliability_rating' => 'floatval'
        ];

        $metricTypesSecurity = [
            'vulnerabilities' => 'intval',
            'security_rating' => 'floatval',
            'security_hotspots' => 'intval',
            'security_review_rating' => 'floatval'
        ];

        // Définition de la fonction pour traiter ncloc_language_distribution
        function handleNclocLanguageDistribution($value) {
            return $value;
        }

        // Fonction pour extraire et convertir les métriques
        $data=[];
        function extractMetrics(&$data, $metricsData, $group, $metricTypes) {
            foreach ($metricTypes as $metric => $type) {
                if (isset($metricsData[$group][$metric])) {
                    $data[$metric] = is_callable($type) ? $type($metricsData[$group][$metric]) : $type($metricsData[$group][$metric]);
                } else {
                    $data[$metric] = -1;
                }
            }
        }

        // Extraction et conversion des métriques pour la couverture
        extractMetrics($data, $metricsData, 'coverage', $metricTypesCoverage);
        extractMetrics($data, $metricsData, 'issues', $metricTypesIssues);
        extractMetrics($data, $metricsData, 'size', $metricTypesSize);
        extractMetrics($data, $metricsData, 'maintainability', $metricTypesMaintainability);
        extractMetrics($data, $metricsData, 'reliability', $metricTypesReliability);
        extractMetrics($data, $metricsData, 'security', $metricTypesSecurity);

        return new JsonResponse(['code' => 200, 'data'=>$data], Response::HTTP_OK);
    }

    /**
     * [Description for suiviMiseAJour]
     * Enregistre une version reconstituée dans la table historique
     * http://{url}}/api/suivi/mise-a-jour
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:37:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/suivi/mise-a-jour', name: 'suivi_mise_a_jour', methods: ['PUT'])]
    public function suiviMiseAJour(Request $request): JsonResponse
    {
        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);

        /** On décode le body */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse([
                'code' => 400, 'type' => 'alert', 'reference' => static::$reference,
                'message' => static::$erreur400], Response::HTTP_OK);
        }

        /** On créé objet date */
        $dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        /** On bind chaque valeur dans une map. */
        $info = [
            'maven_key' => $data->maven_key,
            'analyse_key' => '-',
            'version' => $data->version,
            'date_version' => $data->date_version,
            'nom_projet' => $data->nom_projet,
            'initial' => $data->initial
        ];
        $version = [
            'version_release' => -1,
            'version_snapshot'=> -1,
            'version_autre' => -1
        ];
        $nosonar = [
            'suppress_warning' =>-1,
            'no_sonar' => -1
        ];
        $todo = [
            'todo' => -1
        ];
        $logger = [
            'logger_info' => -1,
            'logger_warn' => -1,
            'logger_error' => -1,
            'logger_debug' => -1
        ];
        $repartition=[
            'frontend'=> -1,
            'backend'=> -1,
            'autre'=> -1,
        ];
        $coverage = [
            'coverage' =>$data->coverage,
            'duplicated_lines_density' =>$data->duplicated_lines_density,
            'tests' =>$data->tests,
            'skipped_tests' => $data->skipped_tests,
            'test_errors' => $data->test_errors,
            'test_failures' => $data->test_failures,
        ];
        $issues = [
            'violations'=>$data->violations
        ];
        $size = [
            'classes' => $data->classes,
            'comment_lines' => $data->comment_lines,
            'comment_lines_density' => $data->comment_lines_density,
            'files' => $data->files,
            'nombre_ligne' => $data->lines,
            'nombre_ligne_code' => $data->ncloc,
            'ncloc_language_distribution' =>$data->ncloc_language_distribution,
            'functions' => $data->functions,
        ];
        // dette = squale_index
        $dette=[
            'sqale_debt_ratio'=>$data->sqale_debt_ratio,
            'dette'=>$data->dette,
        ];
        $maintainability = [
            'note_sqale'=>$data->sqale_rating,
            'nombre_code_smell'=>$data->code_smells,
            'sqale_debt_ratio'=>$data->sqale_debt_ratio,
        ];
        $reliability = [
            'nombre_bug' =>$data->bugs,
            'note_reliability' =>$data->reliability_rating
        ];
        $security = [
            'nombre_vulnerability'=>$data->vulnerabilities,
            'note_security'=>$data->security_rating,
            'note_hotspot'=>$data->security_review_rating,
            'nombre_hotspot'=>$data->security_hotspots
        ];
        $severity = [
            'bug_blocker'=> -1,
            'bug_critical'=> -1,
            'bug_major'=> -1,
            'bug_minor'=> -1,
            'bug_info'=> -1,
            'vulnerability_blocker'=>-1,
            'vulnerability_critical'=>-1,
            'vulnerability_major'=> -1,
            'vulnerability_minor'=> -1,
            'vulnerability_info'=> -1,
            'code_smell_blocker'=> -1,
            'code_smell_critical'=> -1,
            'code_smell_major'=> -1,
            'code_smell_minor'=> -1,
            'code_smell_info'=> -1,
            'nombre_anomalie_bloquant' => -1,
            'nombre_anomalie_critique' => -1,
            'nombre_anomalie_majeur' => -1,
            'nombre_anomalie_mineur' => -1,
            'nombre_anomalie_info' =>-1
        ];
        $hotspot=[
                'hotspot_high' => -1,
                'hotspot_medium' => -1,
                'hotspot_low' => -1,
                'date_enregistrement' => $dateEnregistrement
            ];
        $autre=[
            'mode_collecte' =>'COLLECTE',
            'utilisateur_collecte' => $this->security->getUser()->getCourriel() ?? 'null',
            'date_enregistrement' => $dateEnregistrement
        ];

        $map=$info+$version+$nosonar+$todo+$logger+$repartition+$coverage+$issues+$size+$dette+$maintainability+$reliability+$security+$severity+$hotspot+$autre;
        $json='{}';
        /** On enregistre */
        $request=$historiqueRepository->insertHistoriqueAjoutProjet($map,$json);
        if ($request['code']!=200) {
            return new JsonResponse(['code' => $request['code'], 'message'=>$request['erreur']],Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

    /**
     * [Description for suiviVersionListe]
     * récupère la liste des projets nom + clé + favori + reference
     * http://{url}}/api/suivi/liste/version
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:38:29 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/suivi/version/liste', name: 'suivi_version_liste', methods: ['POST'])]
    public function suiviVersionListe(Request $request): JsonResponse
    {
        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);

        /** On décode le body */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key')) {
            return new JsonResponse([
                'code' => 400, 'type' => 'alert', 'reference' => static::$reference,
                'message' => static::$erreur400], Response::HTTP_OK);
        }

        /**  On récupère les versions et la date pour la clé du projet. */
        $map=['maven_key'=>$data->maven_key];
        $request=$historiqueRepository->selectHistoriqueProjetByDate($map);
        if ($request['code']!=200) {
            return new JsonResponse(['code' => $request['code'], 'message'=>$request['erreur']],Response::HTTP_OK);
        }

        /** On récupère les préférences de l'utilisateur */
        $preference = $this->getUser()->getPreference();
        /* ? l'utilisateur a activer la gestion des favoris */
        //$preferenceStatutFavoriVersion=$preference['statut']['favori_version'] ?? false;
        $preferenceFavoriVersion=$preference['favori_version'] ?? false;
        // Récupérer les versions favorites pour la maven_key
        $listeVersions = static::getFavoriVersions($preferenceFavoriVersion, $data->maven_key);

        // Ajouter la clé 'favori' aux versions correspondantes dans la liste des versions
        foreach ($request['version'] as &$version) {
            if ($version['maven_key'] === $data->maven_key && in_array($version['version'], $listeVersions)) {
                $version['favori'] = true;
            } else {
                $version['favori'] = false;
            }
        }
        return new JsonResponse(['code' => 200, 'versions' => $request['version'], 'preference_favori' => $preferenceFavoriVersion], Response::HTTP_OK);
    }

    /**
     * [Description for getFavoriVersions]
     * Fonction pour récupérer les versions d'une application spécifique
     *
     * @param mixed $version
     * @param mixed $appKey
     *
     * @return array
     *
     * Created at: 26/07/2024 11:34:08 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getFavoriVersions($version, $appKey): array
    {
        foreach ($version as $entry) {
            if (isset($entry[$appKey])) {
                return $entry[$appKey];
            }
        }
        return [];
    }

    /**
     * [Description for suiviVersionFavori]
     * On ajoute ou on supprime la version favorite
     * http://{url}}/api/suivi/version/favori
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:39:11 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/suivi/version/favori', name: 'suivi_projet_favori', methods: ['PUT'])]
    public function suiviVersionFavori(Request $request): JsonResponse
    {
        /** On instancie l'entityRepository */
        $utilisateurRepository = $this->em->getRepository(Utilisateur::class);

        /** on décode le body */
        $data = json_decode($request->getContent());

        /** On met à jour le favori et la version favorite */
        $preference = $this->getUser()->getPreference();
        $courriel = $this->getUser()->getCourriel();

        $map=[
            'favori'=>$data->favori,
            'courriel'=> $courriel,
            'maven_key'=>$data->maven_key,
            'version'=>$data->version,
            'date_version'=>$data->date_version
        ];

        $request=$utilisateurRepository->updateUtilisateurFavoriVersion($preference, $map);
        if ($request['code']!=200) {
            return new JsonResponse([
                'code'=>$request['code'], 'erreur' => $request['erreur']], Response::HTTP_OK);
        }

        /** Tout c'est bien passé */
        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

    /**
     * [Description for suiviVersionReference]
     * On ajoute ou on supprime la version de reference
     * http://{url}}/api/suivi/version/reference
     *
     * @param JsonRequest $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 22:40:34 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/suivi/version/reference', name: 'suivi_version_reference', methods: ['PUT'])]
    public function suiviVersionReference(Request $request): JsonResponse
    {
        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);

        /** On décode le body */
        $data = json_decode($request->getContent());
        if ($data === null ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'initial') ||
            !property_exists($data, 'version') ||
            !property_exists($data, 'date_version')) {
            return new JsonResponse([
                'code' => 400, 'type' => 'alert', 'reference' => static::$reference,
                'message' => static::$erreur400], Response::HTTP_OK);
        }

         /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$this->security->isGranted('ROLE_GESTIONNAIRE')){
            return new JsonResponse(['type'=>'warning', 'code' => 403,
                'reference' => static::$reference, 'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On créé la map pour la requête de mise à jour */
        $map=[ 'initial'=>$data->initial, 'maven_key'=>$data->maven_key, 'version'=>$data->version, 'date_version'=>$data->date_version];
        $request=$historiqueRepository->updateHistoriqueReference($map);
        if ($request['code']!=200) {
            return new JsonResponse(['maven-Key' => $data->maven_key,
                'code'=>$request['code'], 'erreur' => $request['erreur']],Response::HTTP_OK);
        }

        /** Tout c'est bien passé */
        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

    /**
     * [Description for suiviVersionPoubelle]
     * On supprime la version de historique
     * On fait PUT pour un DELETE. (i.e on bloque la méthode DELETE)
     * http://{url}}/api/suivi/version/poubelle
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 22:41:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/suivi/version/poubelle', name: 'suivi_version_poubelle', methods: ['PUT'])]
    public function suiviVersionPoubelle(Request $request): JsonResponse
    {
        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);
        $utilisateurRepository = $this->em->getRepository(Utilisateur::class);

        /** on décode le body */
        $data = json_decode($request->getContent());

        /** On regarde si $data est valide */
        if ($data === null ||
            !property_exists($data, 'maven_key') ||
            !property_exists($data, 'version') ||
            !property_exists($data, 'date_version')) {
                return new JsonResponse([
                    'code' => 400, 'type' => 'alert', 'reference' => static::$reference,
                    'message' => static::$erreur400], Response::HTTP_OK);
            }

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$this->security->isGranted('ROLE_GESTIONNAIRE')){
            return new JsonResponse(['type'=>'warning', 'code' => 403,
            'reference' => static::$reference, 'message' => static::$erreur403], Response::HTTP_OK);
        }

        /** On supprime la version du projet */
        $map=['maven_key'=>$data->maven_key, 'version'=>$data->version, 'date_version'=>$data->date_version];
        $request=$historiqueRepository->deleteHistoriqueProjet($map);
        if ($request['code']!=200) {
            return new JsonResponse([
                'maven_key' => $data->maven_key, 'code'=>$request['code'], 'erreur' => $request['erreur']], Response::HTTP_OK);
        }

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->security->getUser()->getPreference();

        /**
         * On regarde si le le projet est un favori ?
         * Si le projet a une version en favori alors il est un projet favori.
         * */
        $message='';
        if  (str_contains(\serialize($preference['favori_version']), $data->maven_key)){
            $courriel = $this->security->getUser()->getCourriel();
            $map=['favori' => 0, 'courriel' => $courriel, 'maven_key' => $data->maven_key, 'version' => $data->version, 'date_version' => $data->date_version];
            $request=$utilisateurRepository->updateUtilisateurFavoriVersion($preference, $map);
            $message='Le projet a également été supprimé de vos préférences.';
        }

        /** Tout c'est bien passé */
        return new JsonResponse(['code' => 200, 'message'=>$message], Response::HTTP_OK);
    }
}
