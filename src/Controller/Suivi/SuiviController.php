<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller\Suivi;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\SecurityBundle\Security;

use Doctrine\ORM\EntityManagerInterface;
use App\Service\Client;
use App\Entity\Historique;
use App\Entity\ListeProjet;
use App\Entity\Utilisateur;
use App\Entity\InformationProjet;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Exception\FetchDataException;

/**
 * [Description SuiviController]
 */
class SuiviController extends AbstractController
{
    /** Définition des constantes */
    public static $dateFormat = "Y-m-d H:i:s";
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $route= "suivi/index.html.twig";
    public static $reference = "SUIVI";
    public static $erreur = "Une erreur s'est produite (erreur ";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur404 = "Vous devez être rattaché à une équipe (erreur 404).";
    public static $erreur406 = "Je n'ai pas trouvé de projets pour ton équipe. ".
    "Vérifiez le nom du tag utilisé dans SonarQube (erreur 406).";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:34:06 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private $client;
    private $em;

    public function __construct(
        EntityManagerInterface $em,
        Client $client
    ) {
        $this->em = $em;
        $this->client = $client;
    }

    /**
     * [Description for listeProjet]
     *
     * @param $mavenKey array
     * @param $teams array
     *
     * @return array
     *
     * Created at: 16/07/2024 20:05:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function listeProjet($mavenKey, $teams): array
    {
        /** On instancie l'entityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);

        /** On recherche les projets pour les équipes rattaché à l'utilisateur */
        $in = '';
        foreach ($teams as $team) {
            if ($team !== 'null') {
                /** On met en minuscule */
                $minus = trim(strtolower($team));
                /** On construit la clause in et on remplace les espaces par des tirets  */
                $in = $in." tag LIKE '".preg_replace('/\s+/', '-', $minus)."%' OR ";
            }
        }

        /** On supprime le dernier OR */
        $inTrim = rtrim($in, " OR ");

        /** On construit la requête de selection des projets en fonction de(s) (l')équipes */
        $map=['clause_where'=>$inTrim];
        $requestListe = $listeProjetRepository->selectListeProjetByEquipe($map);
        if ($requestListe['code']!=200) {
            return ['code' => $requestListe['code']];
        }

        $projets = $requestListe['liste'];

        /** j'ai pas trouvé de projet pour cette équipe. */
        if (empty($projets)) {
            return ['code'=>406, 'message' => static::$erreur406];
        }

        $searchId = $mavenKey;
        $idFound = false;

        foreach ($projets as $item) {
            if (isset($item['id']) && $item['id'] === $searchId) {
                $idFound = true;
                break;
            }
        }
        if ($idFound===false) {
            return ['code'=>406, 'message' => "Le projet n'est pas présent dans la liste de projets de l'utilisateur."];
        }
        return ['code'=>200];
    }

    #[Route('/suivi/set', name: 'suivi_set', methods: ['GET'])]
    public function setSession(Request $request)
    {
        $mavenKey = $request->get('mavenKey');
        // Stocker des données dans la session via l'objet Request
        $session = $request->getSession();
        $session->set('mavenKey', $mavenKey);
        // Rediriger vers la route sans les paramètres dans l'URL
        return $this->redirectToRoute('suivi');
    }

    /**
     * [Description for suivi]
     * On remonte les 10 dernières version + la version initiale
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 22:34:25 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/suivi', name: 'suivi', methods: ['GET'])]
    public function suivi(Request $request, Security $security): Response
    {
        $session = $request->getSession();

        // Instanciation des repositories
        $historiqueRepository = $this->em->getRepository(Historique::class);

        // Initialisation des variables
        $mavenKey = $session->get('mavenKey');
        $teams = $security->getUser()->getEquipe();
        $render = $this->getDefaultRender($mavenKey);
        $debug='';

        // Vérifications initiales
        if (empty($mavenKey)) {
            return $this->addFlashAndRender('alert', static::$erreur400, $debug, $render);
        }

        if (empty($teams)) {
            return $this->addFlashAndRender('warning', static::$erreur404, $debug, $render);
        }

        // Vérification du projet
        $listeProjet = self::listeProjet($mavenKey, $teams);
        if ($listeProjet['code'] === 406) {
            return $this->addFlashAndRender('warning', $listeProjet['message'], $debug, $render);
        }

        // Vérification dans l'historique
        $map = ['maven_key' => $mavenKey];
        $liste = $historiqueRepository->countHistoriqueProjet($map);
        if ($liste['code'] != 200 || $liste['nombre'] === 0) {
            return $this->addFlashAndRender('warning', "Le projet n'a pas été sauvegardé dans l'historique.", $debug, $render);
        }

        // Construction du tableau des données pour les requêtes
        $map['limit'] = $this->getParameter('nombre.favori');

        try {
            // Récupération des données
            $suivi = $this->fetchData($historiqueRepository, 'selectUnionHistoriqueProjet', $map);
            $severity = $this->fetchData($historiqueRepository, 'selectUnionHistoriqueAnomalie', $map);
            $details = $this->fetchData($historiqueRepository, 'selectUnionHistoriqueDetails', $map);
            $graph = $this->fetchData($historiqueRepository, 'selectHistoriqueAnomalieGraphique', $map);

            // Traitement des données graphiques
            $graphData = $this->processGraphData($graph);

            // Mise à jour du rendu
            $render = array_merge($render, [
                'suivi' => $suivi['request'],
                'severity' => $severity['request'],
                'details' => $details['request'],
                'nom' => $suivi['request'][0]["nom"],
                'data1' => json_encode($graphData['bug']),
                'data2' => json_encode($graphData['sec']),
                'data3' => json_encode($graphData['codeSmell']),
                'labels' => json_encode($graphData['date'])
            ]);

            $this->addFlash('notice', ['type' => 'success', 'reference' => static::$reference, 'message' => "Les données ont été correctement récupérées."]);
            return $this->render(static::$route, $render);
        } catch (FetchDataException $e) {
            return $this->addFlashAndRender('alert', $e->getMessage(), $e->getDebug(), $e->getRender());
        }
    }

    /**
     * [Description for getDefaultRender]
     *
     * @param mixed $mavenKey
     *
     * @return array
     *
     * Created at: 17/07/2024 08:58:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getDefaultRender($mavenKey): array
    {
        return [
            'suivi' => [], 'severity' => [], 'details' => [],
            'nom' => 'N.C', 'mavenKey' => $mavenKey ?? '',
            'data1' => 0, 'data2' => 0, 'data3' => 0, 'labels' => 0,
            'version' => $this->getParameter('version'), 'dateCopyright' => date('Y'),
            Response::HTTP_OK
        ];
    }

    /**
     * [Description for addFlashAndRender]
     *
     * @param string $type
     * @param string $message
     * @param array $render
     *
     * @return Response
     *
     * Created at: 17/07/2024 08:58:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function addFlashAndRender(string $type, string $message, string $debug, array $render): Response
    {
        $this->addFlash('notice', ['type' => $type, 'reference' => static::$reference, 'message' => $message, 'debug'=>$debug] );
        return $this->render(static::$route, $render);
    }

    /**
     * [Description for fetchData]
     *
     * @param mixed $repository
     * @param string $method
     * @param array $map
     * @param array $render
     *
     * @return [type]
     *
     * Created at: 17/07/2024 08:58:29 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function fetchData($repository, string $method, array $map)
    {
        $data = $repository->$method($map);
        if ($data['code'] != 200) {
            $message = static::$erreur . $data['code'] . ').';
            $debug = $data['erreur'];
            throw new FetchDataException($message, $debug, $this->getDefaultRender($map['maven_key']));
        }
        return $data;
    }

    /**
     * [Description for processGraphData]
     *
     * @param array $graphRequest
     *
     * @return array
     *
     * Created at: 17/07/2024 08:58:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function processGraphData(array $graphRequest): array
    {
        $nl = count($graphRequest);
        $bug = $sec = $codeSmell = $date = [];

        for ($i = 0; $i < $nl; $i++) {
            $bug[$i] = $graphRequest[$i]["bug"];
            $sec[$i] = $graphRequest[$i]["sec"];
            $codeSmell[$i] = $graphRequest[$i]["code_smell"];
            $date[$i] = $graphRequest[$i]["date"];
        }

        // Ajout d'une valeur null à la fin de chaque série
        $bug[$nl] = $sec[$nl] = $codeSmell[$nl] = 0;
        $dd = new \DateTime($graphRequest[$nl - 1]["date"]);
        $dd->modify('+1 day');
        $date[$nl] = $dd->format('Y-m-d');

        return ['bug' => $bug, 'sec' => $sec, 'codeSmell' => $codeSmell, 'date' => $date];
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

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de response JSON */
        $response = new JsonResponse();

        // on regarde si $Data est null
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /** On vérifie  */
        $map=['maven_key'=>$data->maven_key];
        $request=$informationProjet->selectInformationProjetVersion($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
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
     * @param Client $client
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:36:17 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/get/version', name: 'get_version', methods: ['POST'])]
    public function getVersion(Client $client, Request $request): Response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de response JSON */
        $response = new JsonResponse();

        /** On teste si $data est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'maven_key')) {
            return $response->setData(['maven_key' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /**  On modifie la date de 11-02-2022 16:02:06 à 2022-02-11 16:02:06 */
        $d = new \Datetime($data->date);

        $dd = $d->format('Y-m-d\TH:i:sO');
        $urlencodeDate = urlencode($dd);
        $url = $this->getParameter(static::$sonarUrl);
        /** Appelle le client HTTP */
        $coverage='coverage,tests,skipped_tests,test_errors,test_failures,test_success_density,duplicated_lines_density';
        $size='classes,comment_lines,comment_lines_density,files,lines,ncloc,ncloc_language_distribution,functions';
        $issues='issues';
        $maintainability='code_smells,sqale_index,sqale_debt_ratio,sqale_rating';
        $reliability='bugs,reliability_rating';
        $security='vulnerabilities,security_rating,security_hotspots,security_review_rating';

        $queryParams = [
            'componentKeys'=>$data->maven_key,
            'metrics'=> $coverage.$issues.$maintainability.$reliability.$security.$size,
            'from'=>$urlencodeDate,
            'to'=>$urlencodeDate];
        $result = $this->client->http("$url/api/measures/search_history?".http_build_query($queryParams));
        /** On catch les erreurs HTTP 401 et 404, si possible :) */
        if (isset($result['code']) && in_array($result['code'], [401, 404])) {
            return $response->setData(['code' => $result['code'], 'error'=>[$result['erreur']]], response::HTTP_OK);
        }
        dd(http_build_query($queryParams), $result);
        $data = $result["measures"];
        for ($i = 0; $i < 14; $i++) {
            if ($data[$i]["metric"] === "reliability_rating") {
                $noteReliability = intval($data[$i]["history"][0]["value"], 10);
            }
            if ($data[$i]["metric"] === "security_rating") {
                $noteSecurity = intval($data[$i]["history"][0]["value"], 10);
            }
            if ($data[$i]["metric"] === "sqale_rating") {
                $noteSqale = intval($data[$i]["history"][0]["value"], 10);
            }

            /** Sur les versions plus anciennes de SonarQube, il n'y avait pas de hotspots. */
            /** La valeur 6 correspond à pas de note  (Z) */
            if ($data[$i]["metric"] === "security_review_rating" &&
                array_key_exists("value", $data[$i]["history"][0])) {
                $noteHotspotsReview = intval($data[$i]["history"][0]["value"], 10);
            }

            if ($data[$i]["metric"] === "security_review_rating" &&
                array_key_exists("value", $data[$i]["history"][0]) === false) {
                $noteHotspotsReview = 6;
            }

            if ($data[$i]["metric"] === "bugs") {
                $bug = intval($data[$i]["history"][0]["value"], 10);
            }
            if ($data[$i]["metric"] === "vulnerabilities") {
                $vulnerabilities = intval($data[$i]["history"][0]["value"], 10);
            }
            if ($data[$i]["metric"] === "code_smells") {
                $codeSmell = intval($data[$i]["history"][0]["value"], 10);
            }

            /**  Sur les versions plus anciennes de SonarQube, il n'y avait pas de hotspots */
            if ($data[$i]["metric"] === "security_hotspots" &&
                array_key_exists("value", $data[$i]["history"][0])) {
                $hotspotsReview = intval($data[$i]["history"][0]["value"], 10);
            }
            if ($data[$i]["metric"] === "security_hotspots" &&
                array_key_exists("value", $data[$i]["history"][0]) === false) {
                $hotspotsReview = -1;
            }

            if ($data[$i]["metric"] === "lines") {
                $lines = intval($data[$i]["history"][0]["value"], 10);
            }
            if ($data[$i]["metric"] === "ncloc") {
                $ncloc = intval($data[$i]["history"][0]["value"], 10);
            }
            if ($data[$i]["metric"] === "duplicated_lines_density") {
                $duplication = $data[$i]["history"][0]["value"];
            }

            /**  Sur certains projets il n'y a pas de la couverture fonctionnelle */
            if ($data[$i]["metric"] === "coverage" &&
                array_key_exists("value", $data[$i]["history"][0])) {
                $coverage = $data[$i]["history"][0]["value"];
            }

            if ($data[$i]["metric"] === "coverage" &&
                array_key_exists("value", $data[$i]["history"][0]) === false) {
                $coverage = 0;
            }

            /**  Sur certains projets il n'y a pas de tests fonctionnels */
            if ($data[$i]["metric"] === "tests" &&
            array_key_exists("value", $data[$i]["history"][0])) {
                $tests = intval($data[$i]["history"][0]["value"], 10);
            }

            if ($data[$i]["metric"] === "tests" &&
            array_key_exists("value", $data[$i]["history"][0]) === false) {
                $tests = 0;
            }

            if ($data[$i]["metric"] === "sqale_index") {
                $dette = intval($data[$i]["history"][0]["value"], 10);
            }
        }

        return $response->setData([
            'code' => 200,
            'noteReliability' => $noteReliability, 'noteSecurity' => $noteSecurity,
            'noteSqale' => $noteSqale, 'noteHotspotsReview' => $noteHotspotsReview,
            'bug' => $bug, 'vulnerabilities' => $vulnerabilities,
            'codeSmell' => $codeSmell, 'hotspotsReview' => $hotspotsReview,
            'lines' => $lines, 'ncloc' => $ncloc,
            'duplication' => $duplication, 'coverage' => $coverage, 'tests' => $tests,
            'dette' => $dette, Response::HTTP_OK
            ]);
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

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si $data est valide */
        if ($data === null) {
            return $response->setData(['data' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }
        if (!property_exists($data, 'mode')) {
            return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /** On créé objet date */
        $dateEnregistrement = new Datetime();
        $dateEnregistrement->setTimezone(new DateTimeZone(static::$europeParis));
        $dateVersion = new Datetime($data->date_version);

        /** On bind chaque valeur dans une map. */
        $map=[
            'maven_key' => $data->maven_key, 'version' => $data->version,
            'date_version' => $dateVersion->format(static::$dateFormat),
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
            'date_enregistrement' => $dateEnregistrement->format(static::$dateFormat)
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

        /** On crée un objet de reponse JSON */
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
     * On fait PUT pour un DELETE. (i.e on bloque la methode DELETE)
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

        /** On crée un objet de reponse JSON */
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
