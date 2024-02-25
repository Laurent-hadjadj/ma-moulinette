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

namespace App\Controller;

/** Core */

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Securité */
use Symfony\Bundle\SecurityBundle\Security;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\JsonResponse;

/** Accès aux tables SLQLite */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Main\Utilisateur;
use App\Entity\Main\Historique;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

use function PHPUnit\Framework\isEmpty;

/**
 * [Description SuiviController]
 */
class SuiviController extends AbstractController
{
    /** Définition des constantes */
    public static $dateFormat = "Y-m-d H:i:s";
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $regex = "/\s+/u";
    public static $erreurMavenKey = "La clé maven est vide !";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:34:06 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
        $this->em = $em;
        $this->logger = $logger;
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
    public function suivi(Request $request): response
    {
        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On récupère la clé du projet */
        $mavenKey = $request->get('mavenKey');
        $mode = $request->get('mode');

        /** On prépare une réponse par défaut */
        $render = [
            'mode' => $mode,
            'suivi' => [], 'severite' => [], 'details' => [],
            'nom' => 'N.C', 'mavenKey' => '',
            'data1' =>0, 'data2' => 0,
            'data3' => 0, 'labels' => 0,
            'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y'),
            Response::HTTP_OK
        ];

        /**
         * On teste si la clé et/ou le mode est valide :
         *  la clé ou le mode peuvent $etre vide
         *  la clé ou le mode peuvent $etre null
         * */
        if (isEmpty($mavenKey)===true || is_null($mavenKey)===true || isEmpty($mode)===true || is_null($mode)===true) {
            /** On prepare un message flash */
            $this->addFlash('alert', sprintf(
                '%s : %s', "[Erreur 001]","La clé maven est incorrecte."
            ));

            return $this->render('suivi/index.html.twig', $render);
        }

        /**On vérifie que le projet est bien dans l'historique */
        $map=['maven_key'=>$mavenKey];
        $historique = $this->em->getRepository(Historique::class);
        $request=$historique->countHistoriqueProjet($mode, $map);
        if ($request['code']!=200 || $request['nombre']===0) {
            /** On prepare un message flash */
            $this->addFlash('alert', sprintf(
                '%s : %s', "[Erreur 002]","Le projet n'a pas été sauvegardé dans l'historique."
            ));
            return $this->render('suivi/index.html.twig', $render);
        }

        /** On vérifie que le projet est disponible pour l'utilisateur */
        // TODO

        /** on construit le tableau des données pour les requêtes */
        $map=['mode'=>$mode, 'maven_key'=>$mavenKey, 'limit'=>$this->getParameter('nombre.favori')];
        $historique = $this->em->getRepository(Historique::class);

        /** Tableau de suivi principal */
        $suivi=$historique-> selectUnionHistoriqueProjet($mode, $map);
        if ($request['code']!=200) {
            /** On prepare un message flash */
            $code="[Erreur ".$request['code'];
            $message="Une erreur s'est produite (".$request['erreur'].").";
            $this->addFlash('alert', sprintf('%s : %s', $code,$message));
        }

        /** On récupère les anomalies par sévérité */
        $severite=$historique-> selectUnionHistoriqueAnomalie($mode, $map);
        if ($request['code']!=200) {
            /** On prepare un message flash */
            $code="[Erreur ".$request['code'];
            $message="Une erreur s'est produite (".$request['erreur'].").";
            $this->addFlash('alert', sprintf('%s : %s', $code,$message));
        }

        /** On récupère les anomalies par type et sévérité. */
        $details=$historique-> selectUnionHistoriqueDetails($mode, $map);
        if ($request['code']!=200) {
            /** On prepare un message flash */
            $code="[Erreur ".$request['code'];
            $message="Une erreur s'est produite (".$request['erreur'].").";
            $this->addFlash('alert', sprintf('%s : %s', $code,$message));
        }

        /** Graphique */
        $graph=$historique->selectHistoriqueAnomalieGraphique($mode, $map);
        if ($request['code']!=200) {
            /** On prepare un message flash */
            $code="[Erreur ".$request['code'];
            $message="Une erreur s'est produite (".$request['erreur'].").";
            $this->addFlash('alert', sprintf('%s : %s', $code,$message));
        }

        /** On compte le nombre de résultat */
        $nl = count((array)$graph['request']);
        for ($i = 0; $i < $nl; $i++) {
            $bug[$i] = $graph['request'][$i]["bug"];
            $secu[$i] = $graph['request'][$i]["secu"];
            $codeSmell[$i] = $graph['request'][$i]["code_smell"];
            $date[$i] = $graph['request'][$i]["date"];
        }

        /** On ajoute une valeur null a la fin de chaque série. */
        $bug[$nl + 1] = 0;
        $secu[$nl + 1] = 0;
        $codeSmell[$nl + 1] = 0;
        $dd = new DateTime($graph['request'][$nl - 1]["date"]);
        $dd->modify('+1 day');
        $ddd = $dd->format('Y-m-d');
        $date[$nl + 1] = $ddd;

        $render = [
            'suivi' => $suivi['request'], 'severite' => $severite['request'], 'details' => $details['request'],
            'nom' => $suivi['request'][0]["nom"], 'mavenKey' => $mavenKey,
            'data1' => json_encode($bug), 'data2' => json_encode($secu),
            'data3' => json_encode($codeSmell), 'labels' => json_encode($date),
            'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y'),
            Response::HTTP_OK
        ];

        if ($mode === "TEST") {
            return $response->setData($render);
        }

        $this->addFlash('sucsess', sprintf(
            '%s : %s', "[Info 001]","Les données ont été récupérées correctement."
        ));

        return $this->render('suivi/index.html.twig', $render);
    }

    /**
     * [Description for listeVersion]
     * On récupère la liste des projets nom + clé
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
    #[Route('/api/liste/version', name: 'liste_version', methods: ['GET'])]
    public function listeVersion(Request $request): Response
    {
        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On vérifie si on a activé le mode test */
        if (is_null($request->get('mode'))) {
            $mode = "null";
        } else {
            $mode = $request->get('mode');
        }

        /** On récupère la clé du projet */
        $mavenKey = $request->get('mavenKey');
        $exception = $request->get('exception');

        /** On teste si la clé est valide */
        if (is_null($mavenKey) && $mode === "TEST") {
            return $response->setData(
                ["mode" => $mode, "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
        }

        $versions = [];
        /** On récupère les versions et la date pour la clé du projet */
        $sql = "SELECT maven_key, project_version as version, date
            FROM information_projet
            WHERE maven_key='$mavenKey'";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();

        if (is_null($exception)) {
            $versions = $select->fetchAllAssociative();
        }

        if (!$versions) {
            $response->setData([Response::HTTP_NOT_FOUND]);
            throw $this->createNotFoundException('Oops - Il y a un problème.');
        }

        $liste = [];
        $id = 0;
        /** objet = { id: clé, text: "blablabla" }; */
        foreach ($versions as $version) {
            $ts = new DateTime($version['date'], new DateTimeZone(static::$europeParis));
            $cc = $ts->format("d-m-Y H:i:sO");
            $objet = [
                'id' => $id,
                'text' => $version['version'] . " (" . $cc . ")"];
            array_push($liste, $objet);
            $id++;
        }

        if ($mode === "TEST") {
            $httpResponse = $response->setData(
                ['mode' => 'TEST','versions' => $versions, 'liste' => $liste, Response::HTTP_OK]);
        } else {
            $httpResponse = $response->setData(["liste" => $liste, Response::HTTP_OK]);
        }

        return $httpResponse;
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
    #[Route('/api/get/version', name: 'get_version', methods: ['GET','POST'])]
    public function getVersion(Client $client, Request $request): Response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());
        $mavenKey = $data->mavenKey;
        $mode = $data->mode;

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** Réponse HTTP */
        $message = 200;

        /** On teste si la clé est valide */
        if ($mavenKey === "TEST" && $mode === "TEST") {
            return $response->setData(["message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
        }

        /**  On modifie la date de 11-02-2022 16:02:06 à 2022-02-11 16:02:06 */
        if ($mode === 'TEST') {
            $d = new Datetime("11-02-2022 16:02:06");
        } else {
            $d = new Datetime($data->date);
        }

        $dd = $d->format('Y-m-d\TH:i:sO');
        $urlencodeDate = urlencode($dd);
        $urlStatic = $this->getParameter(static::$sonarUrl);

        $url = "$urlStatic/api/measures/search_history?component=
            $mavenKey&metrics=reliability_rating,
            security_rating,sqale_rating,bugs,
            vulnerabilities,code_smells,security_hotspots,
            security_review_rating,lines,ncloc,coverage,
            tests,sqale_index,duplicated_lines_density
            &from=$urlencodeDate&to=$urlencodeDate";

        /** On appel le client http */
        if ($mode != "TEST") {
            $result = $client->http(trim(preg_replace(static::$regex, " ", $url)));
        }

        if ($mode === "TEST") {
            $result = ["measures" => [
                ["metric" => "lines", "history" => [["date" => "2022-04-10T00:00:01+0200", "value" => 20984]]],
                ["metric" => "duplicated_lines_density", "history" => [
                    ["date" => "2022-04-10T00:00:01+0200","value" => 2.6]]],
                ["metric" => "vulnerabilities", "history" => [["date" => "2022-04-10T00:00:02+0200","value" => 0]]],
                ["metric" => "sqale_index","history" => [["date" => "2022-04-10T00:00:03+0200","value" => 15596]]],
                ["metric" => "reliability_rating","history" => [["date" => "2022-04-10T00:00:04+0200","value" => 3.0]]] ,
                ["metric" => "code_smells","history" => [["date" => "2022-04-10T00:00:05+0200","value" => 3080]]],
                ["metric" => "bugs","history" => [["date" => "2022-04-10T00:00:06+0200", "value" => 43]]],
                ["metric" => "ncloc", "history" => [[ "date" => "2022-04-10T00:07:00+0200", "value" => 17312]]],
                ["metric" => "security_hotspots", "history" => [["date" => "2022-04-10T00:00:08+0200", "value" => 2]]],
                ["metric" => "sqale_rating", "history" => [["date" => "2022-04-10T00:00:09+0200","value" => 3.0]]],
                ["metric" => "security_rating","history" => [["date" => "2022-04-10T00:00:10+0200","value" => 1.0]]],
                ["metric" => "tests", "history" => [["date" => "2022-04-10T00:00:11+0200", "value" => 134]]],
                ["metric" => "coverage", "history" => [["date" => "2022-04-10T00:00:12+0200","value" => 50]]],
                ["metric" => "security_review_rating", "history" => [
                ["date" => "2022-04-10T00:00:13+0200","value" => 5.0]]],
            ]
            ];
        }

        /** Si on récupère un message alors on a un problème. */
        if (array_key_exists("message", $result)) {
            return $response->setData(['message' => 404]);
        }

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

            /** Sur les versions plus anciennes de sonarqube, il n'y avait pas de hostpots. */
            /** La valeur 6 corsespond à pas de note  (Z) */
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

            /**  Sur les versions plus anciennes de sonarqube, il n'y avait pas de hostpots */
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
            'message' => $message,
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
        /** On décode le body */
        $data = json_decode($request->getContent());
        $dateEnregistrement = new Datetime();
        $dateEnregistrement->setTimezone(new DateTimeZone(static::$europeParis));
        $dateVersion = new Datetime($data->date);
        $mode = $data->mode;

        /** On créé un nouvel objet Json. */
        $response = new JsonResponse();

        /** On bind chaque valeur. */
        $tempoDateVersion = $dateVersion->format(static::$dateFormat);
        $tempoDateEnregistrement = $dateEnregistrement->format(static::$dateFormat);
        $tempoMavenKey = $data->mavenKey;
        $tempoVersion = $data->version;
        $tempoNom = $data->nom;
        $tempoLines = $data->lines;
        $tempoNcloc = $data->ncloc;
        $tempoCoverage = $data->coverage;
        $tempoDuplication = $data->duplication;
        $tempoTests = $data->tests;
        $tempoDefauts = $data->defauts;
        $tempoDette = $data->dette;
        $tempoBug = $data->bug;
        $tempoVulnerabilities = $data->vulnerabilities;
        $tempoCodeSmell = $data->codeSmell;
        $tempoNoteReliability = $data->noteReliability;
        $tempoNoteSecurity = $data->noteSecurity;
        $tempoNoteSqale = $data->noteSqale;
        $tempoNoteHotspotsReview = $data->noteHotspotsReview;
        $tempoHotspotsReview = $data->hotspotsReview;
        $tempoInitial = $data->initial;

        $sql = "INSERT OR IGNORE INTO historique
        (maven_key,version,date_version,
        nom_projet,version_release,version_snapshot,
        suppress_warning,no_sonar,nombre_ligne,
        nombre_ligne_code,couverture,
        duplication,tests_unitaires,nombre_defaut,dette,
        nombre_bug,nombre_vulnerability,nombre_code_smell,
        bug_blocker, bug_critical, bug_major, bug_minor, bug_info,
        vulnerability_blocker, vulnerability_critical, vulnerability_major,
        vulnerability_minor, vulnerability_info,
        code_smell_blocker, code_smell_critical, code_smell_major,
        code_smell_minor, code_smell_info,
        frontend,backend,autre,
        nombre_anomalie_bloquant,nombre_anomalie_critique,
        nombre_anomalie_majeur,
        nombre_anomalie_mineur,nombre_anomalie_info,
        note_reliability,note_security,
        note_sqale,note_hotspot,hotspot_total,
        hotspot_high,hotspot_medium,hotspot_low,
        initial,date_enregistrement)
        VALUES
        ('$tempoMavenKey','$tempoVersion',
        '$tempoDateVersion','$tempoNom',-1,-1,-1,-1,
        $tempoLines,$tempoNcloc,
        $tempoCoverage,$tempoDuplication,$tempoTests,
        $tempoDefauts,$tempoDette,$tempoBug,
        $tempoVulnerabilities,$tempoCodeSmell,
        -1,-1,-1,-1,-1,
        -1,-1,-1,-1,-1,
        -1,-1,-1,-1,-1,
        -1,-1,-1,
        -1,-1,-1,-1,-1,
        '$tempoNoteReliability','$tempoNoteSecurity','$tempoNoteSqale',
        '$tempoNoteHotspotsReview',$tempoHotspotsReview,
        -1,-1,-1, $tempoInitial,
        '$tempoDateEnregistrement')";

        // On exécute la requête
        $con = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)));
        try {
            $con->executeQuery();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
        }
        return $response->setData(["mode" => $mode, "code" => 200, Response::HTTP_OK]);
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
        /** On décode le body */
        $data = json_decode($request->getContent());
        $mavenKey = $data->mavenKey;
        $mode = $data->mode;

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($mavenKey === "null" && $mode === "TEST") {
            return $response->setData([
                "mode" => $mode, "mavenKey" => $mavenKey,
                "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
        }

        /**  On créé un nouvel objet Json. */
        /**  On récupère les versions et la date pour la clé du projet. */
        $sql = "SELECT maven_key, version, date_version as date, initial
            FROM historique
            WHERE maven_key='$mavenKey'
            ORDER BY date_version DESC";

        /** On exécute la requête. */
        $con = $this->em->getConnection()->prepare($sql);
        try {
            $select = $con->executeQuery();
            $version = $select->fetchAllAssociative();
        } catch (\Doctrine\DBAL\Exception $e) {
            return $response->setData(["code" => $e->getCode(), Response::HTTP_OK]);
        }
        return $response->setData(["code" => "OK", "versions" => $version, Response::HTTP_OK]);
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
        /** on décode le body */
        $data = json_decode($request->getContent());

        /** On créé un nouvel objet Json */
        $response = new JsonResponse();

        /** On met à jour le favori et la version favorite */
        $preference = $this->getUser()->getPreference();
        $courriel = $this->getUser()->getCourriel();

        $map=['favori'=>$data->favori, 'courriel'=> $courriel, 'maven_key'=>$data->maven_key, 'version'=>$data->version, 'date_version'=>$data->date_version];
        $utilisateur = $this->em->getRepository(Utilisateur::class);
        /** si le favori a été supprimé favori=0 */
        if ($data->favori===0) {
            $request=$utilisateur->deleteUtilisateurPreferenceFavori($data->mode, $preference, $map);
            return $response->setData(['mode' => $data->mode, 'code' => 201, Response::HTTP_OK]);
        }

        $request=$utilisateur->insertUtilisateurPreferenceFavori($data->mode, $preference, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'maven_key' => $data->maven_key,
                'code'=>$request['code'], 'erreur' => $request['erreur'],
                Response::HTTP_OK]);
        }

        /** Tout c'est bien passé */
        return $response->setData(['mode' => $data->mode, 'code' => 200, Response::HTTP_OK]);
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
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On créé un nouvel objet Json */
        $response = new JsonResponse();

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$security->isGranted('ROLE_GESTIONNAIRE')){
            return $response->setData(["mode" => $data->mode, "code" => 403, Response::HTTP_OK]);
        }

        /** On teste si la clé est valide */
        if ($data->maven_key === 'null' || $data->mode === 'TEST') {
            return $response->setData([
                'mode' => $data->mode, 'mavenKey' => $data->maven_key,
                'code'=>400, Response::HTTP_BAD_REQUEST]);
        }

        /** On créé la map pour la requête de mise à jour */
        $map=[ 'initial'=>$data->initial, 'maven_key'=>$data->maven_key, 'version'=>$data->version, 'date_version'=>$data->date_version];
        $historique = $this->em->getRepository(Historique::class);
        $request=$historique->updateHistoriqueReference($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'mavenKey' => $data->maven_key,
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
        /** on décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$security->isGranted('ROLE_GESTIONNAIRE')){
            return $response->setData(['mode' => $data->mode, 'code' => 403, Response::HTTP_OK]);
        }

        /** On teste si la clé est valide */
        if ($data->maven_key === 'null' || $data->mode === 'TEST') {
            return $response->setData([
                'mode' => $data->mode, 'mavenKey' => $data->maven_key,
                'code'=>400, Response::HTTP_BAD_REQUEST]);
        }

        /** On supprime la version du projet */
        $map=['maven_key'=>$data->maven_key, 'version'=>$data->version, 'date_version'=>$data->date_version];
        $historique = $this->em->getRepository(Historique::class);
        $request=$historique->deleteHistoriqueProjet($data->mode, $map);
        if ($request['code']!=200) {
            return $response->setData([
                'mode' => $data->mode, 'mavenKey' => $data->maven_key,
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

            $utilisateur = $this->em->getRepository(Utilisateur::class);
            $request=$utilisateur->deleteUtilisateurPreferenceFavori($data->mode, $preference, $map);
            $message='Le projet a été également supprimé de vos préférences.';
        }

        /** Tout c'est bien passé */
        return $response->setData(['code' => 200, 'message'=>$message, 'mode' => $data->mode, Response::HTTP_OK]);
    }
}
