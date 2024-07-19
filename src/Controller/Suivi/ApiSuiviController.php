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
    public static $reference = "SUIVI";
    public static $erreur400 = "La requête est incorrecte (erreur 400).";
    public static $erreur404 = "Vous devez être rattaché à une équipe (erreur 404).";
    public static $erreur406 = "Je n'ai pas trouvé de projets pour ton équipe. ".
    "Vérifiez le nom du tag utilisé dans SonarQube (erreur 406).";

    private $em;
    private $client;

    public function __construct(
        EntityManagerInterface $em,
        Client $client
    ) {
        $this->em = $em;
        $this->client = $client;
    }

    /**
     * [Description for listeVersion]
     * On récupère la liste des projets nom + clé pour le sélecteur de projet.
     * http://{url}}/api/liste/version
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:35:41 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/liste/version', name: 'liste_version', methods: ['POST'])]
    public function listeVersion(Request $request): Response
    {
        /** On instancie l'entityRepository */
        $informationProjet = $this->em->getRepository(InformationProjet::class);

        /** On crée un objet de response JSON */
        $response = new JsonResponse();

        /** On récupère le job et le type (manuel ou automatique) */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key')) {
            return $response->setData([
                'code' => 400, 'type' => 'alert', 'reference' => static::$reference,
                'message' => static::$erreur400], Response::HTTP_BAD_REQUEST);
        }

        /** On vérifie  */
        $map=['maven_key'=>$data->maven_key];
        $request=$informationProjet->selectInformationProjetVersion($map);
        if ($request['code']!=200) {
            return $response->setData([
                'maven_key' => $data->maven_key,
                'code'=>$request['code'], 'erreur' => $request['erreur'],
                Response::HTTP_OK]);
        }

        $liste = [];
        $id = 0;
        /** objet = { id: clé, text: "blablabla" }; */
        foreach ($request['versions'] as $version) {
            $ts = new \DateTime($version['date'], new \DateTimeZone(static::$europeParis));
            $cc = $ts->format("d-m-Y H:i:sO");
            $objet = [
                'id' => $id,
                'text' => $version['version'] . " (" . $cc . ")"];
            array_push($liste, $objet);
            $id++;
        }

        return $response->setData(["liste" => $liste, Response::HTTP_OK]);
    }

    /**
     * [Description for getVersion]
     * On récupère les données disponibles pour une version données
     * http://{url}}/api/get/version
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:36:17 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/get/version', name: 'get_version', methods: ['POST'])]
    public function getVersion(Request $request): Response
    {
        /** On crée un objet de response JSON */
        $response = new JsonResponse();


        /** On récupère la maven_Key */
        $data = json_decode($request->getContent());
        if ($data === null || !property_exists($data, 'maven_key') ||
            !property_exists($data, 'date')) {
            return $response->setData([
                'code' => 400, 'type' => 'alert', 'reference' => static::$reference,
                'message' => static::$erreur400], Response::HTTP_BAD_REQUEST);
        }

        /**  On modifie la date de 11-02-2022 16:02:06 à 2022-02-11  */
        $dateConvert = new \Datetime($data->date);
        $url = $this->getParameter(static::$sonarUrl);

        $paramsList = [
            'coverage' => [
                'component' => $data->maven_key,
                'metrics' => 'coverage,tests,skipped_tests,test_errors,test_failures,test_success_density,duplicated_lines_density',
                'from' => $dateConvert->format('Y-m-d'),
                'to' => $dateConvert->format('Y-m-d')
            ],
            'issues' => [
                'component' => $data->maven_key,
                'metrics' => 'violations',
                'from' => $dateConvert->format('Y-m-d'),
                'to' => $dateConvert->format('Y-m-d') ],
            'size' => [
                'component' => $data->maven_key,
                'metrics' => 'classes,comment_lines,comment_lines_density,files,lines,ncloc,ncloc_language_distribution,functions',
                'from' => $dateConvert->format('Y-m-d'),
                'to' => $dateConvert->format('Y-m-d')
            ],
            'maintainability' => [
                'component' => $data->maven_key,
                'metrics' => 'code_smells,sqale_index,sqale_debt_ratio,sqale_rating',
                'from' => $dateConvert->format(static::$dateFormatTimezone),
                'to' => $dateConvert->format(static::$dateFormatTimezone)
            ],
            'reliability' => [
                'component' => $data->maven_key,
                'metrics' => 'bugs,reliability_rating',
                'from' => $dateConvert->format(static::$dateFormatTimezone),
                'to' => $dateConvert->format(static::$dateFormatTimezone)
            ],
            'security' => [
                'component' => $data->maven_key,
                'metrics' => 'vulnerabilities,security_rating,security_hotspots,security_review_rating',
                'from' => $dateConvert->format(static::$dateFormatTimezone),
                'to' => $dateConvert->format(static::$dateFormatTimezone)
            ]
        ];

        $results = [];
        foreach ($paramsList as $key => $params) {
            $request=$this->client->http("$url/api/measures/search_history?".http_build_query($params));
            $results[$key] = $request;
        }

        $keys=['coverage', 'issues', 'size', 'maintainability', 'reliability', 'security'];
        foreach ($keys as $key){
            $metricsData[$key] = [];
            foreach ($results[$key]['measures'] as $measure) {
                $metric = $measure['metric'];
                foreach ($measure['history'] as $history) {
                    $value = $history['value'];
                    // Enregistrer la valeur de la métrique
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

        return $response->setData(['code' => 200, 'data'=>$data], Response::HTTP_OK);
    }

    /**
     * [Description for suiviMiseAJour]
     * Enregistre une version reconstituée dans la table historique
     * http://{url}}/api/suivi/mise-a-jour
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:37:32 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/suivi/mise-a-jour', name: 'suivi_mise_a_jour', methods: ['PUT'])]
    public function suiviMiseAJour(Request $request): Response
    {
        /** On instancie l'entityRepository */
        $historique = $this->em->getRepository(Historique::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de response JSON */
        $response = new JsonResponse();

        /** On teste si $data est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /** On créé objet date */
        $dateEnregistrement = new \DateTimeImmutable();
        $dateEnregistrement->setTimezone(new \DateTimeZone(static::$europeParis));
        $dateVersion = new \DateTimeImmutable($data->date_version);

        /** On bind chaque valeur dans une map. */
        $map=[
            'maven_key' => $data->maven_key, 'version' => $data->version,
            'date_version' => $dateVersion->format(static::$dateFormatTimezone),
            'nom_projet' => $data->nom_projet, 'version_release' => -1, 'version_snapshot' => -1,
            'suppress_warning' => -1,'no_sonar' => -1, 'nombre_ligne' => $data->nombre_ligne,
            'nombre_ligne_code' => $data->nombre_ligne_code, 'couverture' => $data->couverture,
            'duplication' => $data->duplication, 'tests_unitaires' => $data->tests_unitaires,
            'nombre_defaut' => $data->nombre_defaut, 'dette' => $data->dette,
            'nombre_bug' => $data->nombre_bug, 'nombre_vulnerability' => $data->nombre_vulnerability,
            'nombre_code_smell' => $data->nombre_code_smell, 'bug_blocker'=> -1,
            'bug_critical'=> -1, 'bug_major'=> -1, 'bug_minor'=> -1, 'bug_info'=> -1,
            'vulnerability_blocker'=>-1, 'vulnerability_critical'=>-1,
            'vulnerability_major'=> -1, 'vulnerability_minor'=> -1, 'vulnerability_info'=> -1,
            'code_smell_blocker'=> -1, 'code_smell_critical'=> -1,
            'code_smell_major'=> -1, 'code_smell_minor'=> -1,
            'code_smell_info'=> -1, 'frontend' => -1,
            'backend' => -1, 'autre' => -1, 'nombre_anomalie_bloquant' => -1,
            'nombre_anomalie_critique' => -1, 'nombre_anomalie_majeur' => -1,
            'nombre_anomalie_mineur' => -1, 'nombre_anomalie_info' =>-1,
            'note_reliability' => $data->note_reliability, 'note_security' => $data->note_security,
            'note_sqale' => $data->note_sqale, 'note_hotspot' => $data->note_hotspot,
            'hotspot_total' => $data->hotspot_total, 'hotspot_high' => -1,
            'hotspot_medium' => -1, 'hotspot_low' => -1, 'initial' => $data->initial,
            'date_enregistrement' => $dateEnregistrement
        ];

        /** On enregistre */
        $request=$historique->countHistoriqueProjet($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData(["mode" => $data->mode, "code" => $request['code'], 'message'=>$request['erreur'],Response::HTTP_OK]);
        }

        return $response->setData(["mode" => $data->mode, "code" => 200, Response::HTTP_OK]);
    }

    /**
     * [Description for suiviVersionListe]
     * récupère la liste des projets nom + clé
     * http://{url}}/api/suivi/liste/version
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:38:29 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/suivi/version/liste', name: 'suivi_version_liste', methods: ['POST'])]
    public function suiviVersionListe(Request $request): Response
    {
        /** On instancie l'entityRepository */
        $historique = $this->em->getRepository(Historique::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de response JSON */
        $response = new JsonResponse();

        // on regarde si $Data est null
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /**  On récupère les versions et la date pour la clé du projet. */
        $map=['maven_key'=>$data->maven_key];
        $request=$historique->selectHistoriqueProjetByDate($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$request['code'], 'erreur' => $request['erreur'],
                Response::HTTP_OK]);
        }

        return $response->setData(["code" => 200, "versions" => $request['version'], Response::HTTP_OK]);
    }

    /**
     * [Description for suiviVersionFavori]
     * On ajoute ou on supprime la version favorite
     * http://{url}}/api/suivi/version/favori
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 22:39:11 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/suivi/version/favori', name: 'suivi_version_favori', methods: ['PUT'])]
    public function suiviVersionFavori(Request $request): response
    {
        /** On instancie l'entityRepository */
        $utilisateur = $this->em->getRepository(Utilisateur::class);

        /** on décode le body */
        $data = json_decode($request->getContent());

        /** On créé un nouvel objet Json */
        $response = new JsonResponse();

        /** On met à jour le favori et la version favorite */
        $preference = $this->getUser()->getPreference();
        $courriel = $this->getUser()->getCourriel();

        $map=['favori'=>$data->favori, 'courriel'=> $courriel, 'maven_key'=>$data->maven_key, 'version'=>$data->version, 'date_version'=>$data->date_version];
        /** si le favori a été supprimé favori=0 */
        if ($data->favori===0) {
            $request=$utilisateur->deleteUtilisateurPreferenceFavori($preference, $map);
            return $response->setData(['code' => 201, Response::HTTP_OK]);
        }

        $request=$utilisateur->insertUtilisateurPreferenceFavori($preference, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'maven_key' => $data->maven_key,
                'code'=>$request['code'], 'erreur' => $request['erreur'],
                Response::HTTP_OK]);
        }

        /** Tout c'est bien passé */
        return $response->setData(['code' => 200, Response::HTTP_OK]);
    }

    /**
     * [Description for suiviVersionReference]
     * On ajoute ou on supprime la version de reference
     * http://{url}}/api/suivi/version/reference
     *
     * @param Request $request
     * @param Security $security
     *
     * @return response
     *
     * Created at: 15/12/2022, 22:40:34 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/suivi/version/reference', name: 'suivi_version_reference', methods: ['PUT'])]
    public function suiviVersionReference(Request $request, Security $security): response
    {
        /** On instancie l'entityRepository */
        $historique = $this->em->getRepository(Historique::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On créé un nouvel objet Json */
        $response = new JsonResponse();

        /** On regarde si $data est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'version')) {
            return $response->setData(['version' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$security->isGranted('ROLE_GESTIONNAIRE')){
            return $response->setData(["mode" => $data->mode, "code" => 403, Response::HTTP_OK]);
        }

        /** On créé la map pour la requête de mise à jour */
        $map=[ 'initial'=>$data->initial, 'maven_key'=>$data->maven_key, 'version'=>$data->version, 'date_version'=>$data->date_version];
        $request=$historique->updateHistoriqueReference($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven-Key' => $data->maven_key,
                'code'=>$request['code'], 'erreur' => $request['erreur'],
                Response::HTTP_OK]);
        }

        /** Tout c'est bien passé */
        return $response->setData(['code' => 200, 'mode' => $data->mode, Response::HTTP_OK]);
    }
    /**
     * [Description for suiviVersionPoubelle]
     * On supprime la version de historique
     * On fait PUT pour un DELETE. (i.e on bloque la méthode DELETE)
     * http://{url}}/api/suivi/version/poubelle
     *
     * @param Request $request
     * @param Security $security
     *
     * @return [type]
     *
     * Created at: 15/12/2022, 22:41:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/suivi/version/poubelle', name: 'suivi_version_poubelle', methods: ['PUT'])]
    public function suiviVersionPoubelle(Request $request, Security $security): response
    {
        /** On instancie l'entityRepository */
        $historique = $this->em->getRepository(Historique::class);
        $utilisateur = $this->em->getRepository(Utilisateur::class);

        /** on décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de response JSON */
        $response = new JsonResponse();

        /** On regarde si $data est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'version')) {
            return $response->setData(['version' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'version')) {
            return $response->setData(['date_version' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$security->isGranted('ROLE_GESTIONNAIRE')){
            return $response->setData(['mode' => $data->mode, 'code' => 403, Response::HTTP_OK]);
        }

        /** On supprime la version du projet */
        $map=['maven_key'=>$data->maven_key, 'version'=>$data->version, 'date_version'=>$data->date_version];
        $request=$historique->deleteHistoriqueProjet($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$request['code'], 'erreur' => $request['erreur'],
                Response::HTTP_OK]);
        }

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();

        /**
         * On regarde si le le projet est un favori ?
         * Si le projet a une version en favori alors il est un projet favori.
         * */
        $message='';
        if  (str_contains(\serialize($preference['version']), $data->maven_key)){
            $courriel = $security->getUser()->getCourriel();
            $map=['courriel'=>$courriel, 'maven_key'=>$data->maven_key, 'version'=>$data->version, 'date_version'=>$data->date_version];
            $request=$utilisateur->deleteUtilisateurPreferenceFavori($preference, $map);
            $message='Le projet a été également supprimé de vos préférences.';
        }

        /** Tout c'est bien passé */
        return $response->setData(['code' => 200, 'message'=>$message, 'mode' => $data->mode, Response::HTTP_OK]);
    }
}
