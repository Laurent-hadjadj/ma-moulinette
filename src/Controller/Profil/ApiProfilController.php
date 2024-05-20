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

namespace App\Controller\Profil;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/** Securité */
use Symfony\Bundle\SecurityBundle\Security;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Accès aux tables SLQLite */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Properties;
use App\Entity\Profiles;
use App\Entity\ProfilesHistorique;

/** Client HTTP */
use App\Service\Client;

class ApiProfilController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $dateFormat = "Y-m-d H:i:s";
    public static $dateFormatShort = "Y-m-d";

    /**
     * [Description for __construct]
     *  EntityManagerInterface = em
     *
     * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em
    ) {
        $this->em = $em;
    }

    /**
     * [Description for listeQualityProfiles]
     * Renvoie la liste des profils qualité
     * http://{url}/api/qualityprofiles/search?qualityProfile={name}
     * RÔLE-GESTIONNAIRE
     * @return response
     *
     * Created at: 07/05/2023, 21:12:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/quality/profiles', name: 'liste_quality_profiles', methods: ['POST'])]
    public function listeQualityProfiles(Request $request, Security $security, Client $client): response
    {
        /** On instancie l'entityRepository */
        $profilesEntity = $this->em->getRepository(Profiles::class);
        $propertiesEntity = $this->em->getRepository(Properties::class);

        /** On crée un objet response */
        $response = new JsonResponse();

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$security->isGranted('ROLE_GESTIONNAIRE')){
            return $response->setData(['code' => 403, Response::HTTP_OK]);
        }

        /** On définit l'URL et on ajoute le nom des profils sonarqube*/
        $url = $this->getParameter(static::$sonarUrl)
            . "/api/qualityprofiles/search";

        /** On appel le client http */
        $r = $client->http($url);

        /** On Vérifie qu'il existe au moins un profil */
        if (empty($r['profiles'])) {
            return $response->setData(['code' => 202, Response::HTTP_OK]);
        }

        /*** Super on a récupéré la liste des profils par langage */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone('Europe/Paris'));
        $nombre = 0;

        /** On supprime les données de la table avant d'importer les données;*/
        $rq1=$profilesEntity->deleteProfiles();
        if ($rq1['code']===500) {
            return $response->setData(['code' => 500, 'erreur'=>$rq1['erreur'], Response::HTTP_OK]);
        }
        /** On insert les profils dans la table profiles. */
        foreach ($rq1['profiles'] as $profil) {
            $nombre = $nombre + 1;
            $profils = new Profiles();
            $profils->setKey($profil['key']);
            $profils->setName($profil['name']);
            $profils->setLanguageName($profil['languageName']);
            $profils->setReferentielDefault($profil['isDefault']);
            $profils->setActiveRuleCount($profil['activeRuleCount']);
            $rulesDate = new DateTime($profil['rulesUpdatedAt']);
            $profils->setRulesUpdateAt($rulesDate);
            $profils->setDateEnregistrement($date);

            $this->em->persist($profils);
            $this->em->flush();
        }
        /** On récupère la nouvelle liste des profils; */
        $rq2=$profilesEntity->selectProfiles();

        /** On met à jour la table proprietes */
        $dateModificationProfil = $date->format("Y-m-d H:i:s");
        $map=['profil_bd'=>$nombre, 'profil_sonar'=>$nombre, 'date_modification_profil'=>$dateModificationProfil];
        $propertiesEntity->updatePropertiesProfiles($map);

        return $response->setData([
            'code' => 200, "listeProfil" => $rq2['liste'], Response::HTTP_OK]);
    }

    /**
     * [Description for listeQualityLangage]
     * Revoie le tableau des labels et des dataset
     * Permet de tracer un joli dessin sur la répartition des langages de programmation.
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:24:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/quality/langage', name: 'liste_quality_langage', methods: ['POST'])]
    public function listeQualityLangage(Request $request): response
    {
        /** On instancie la classe */
        $profilesEntity = $this->em->getRepository(Profiles::class);

        /** On crée un objet response */
        $response = new JsonResponse();

        $listeLabel = [];
        $listeDataset = [];

        /** On récupère la liste des langage */
        $selectProfilesLanguage=$profilesEntity->selectProfilesLanguage();
        /** On créé la liste des libellés et des données */
        foreach ($selectProfilesLanguage['labels'] as $label) {
            array_push($listeLabel, $label['profile']);
        }
        /** On récupère le nombre de règle de chaque profil */
        $selectProfilesRuleCount=$profilesEntity->selectProfilesRuleCount();
        foreach ($selectProfilesRuleCount['data-set'] as $dataSet) {
            array_push($listeDataset, $dataSet['total']);
        }

        return $response->setData(
            ['label' => $listeLabel, 'dataset' => $listeDataset, Response::HTTP_OK]);
    }

    /**
     * [Description for profilDetails]
     * Affichage des règles par profils avec les changement.
     *
     * @param Request $request
     * @param Client $client
     *
     * @return response
     *
     * Created at: 11/03/2023, 23:08:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/profil/details', name: 'profil_details', methods: ['GET'])]
    public function profilDetails(Request $request, Client $client)
    {
        /** On instancie la classe */
        $profilesHistoriqueEntity = $this->em->getRepository(ProfilesHistorique::class);

        $token = $request->get('token');
        if (empty($token)){
            return;
        }
        //b64=bnVsbHxDU1N8RnJhbmNlQWdyaU1lciB2Mi4wLjAgKDIwMjEp
        //rot13=oaIfoUkQH1A8EaWuozAyDJqlnH1ypvO2Zv4jYwNtXQVjZwRc
        $string=str_rot13($token);
        $decode=base64_decode($string);
        $exxplode=preg_split("/[|]+/",$decode);
        $mode=$exxplode[0];

        /** on initialise une réponse par défaut */
        $render = [
            "profil" => 'NC', "langage" => 'aucun',
            "opened" =>0, "closed" => 0, "updated" => 0, "totalRegle" => null,
            "first" => null, "last"=> null, "dateGroupe" => null, "nbGroupe" => null, "liste" => null, "badge" => null,
            "version" => $this->getParameter("version"),
            "dateCopyright" => \date("Y"), Response::HTTP_OK];
        /** si le mode n'est pas défini ou si le tableau ne contient pas trois clé alors */
        if (count($exxplode) !=3) {
             /** On prepare un message flash */
            $this->addFlash('alert', sprintf(
                '%s : %s', "[Erreur 001]","La clé est incorrecte."
            ));
            return $this->render('profil/details.html.twig', $render);
        }

        $language = strtolower($exxplode[1]);
        $profil = $exxplode[2];


        /** On renome les langages pour sonarqube */
        switch ($language) {
            case "java properties": $language = "jproperties";
                break;
            case "javascript": $language = "js";
                break;
            case "html": $language = "web";
                break;
            case "typescript": $language = "ts";
                break;
            case "python": $language = "py";
                break;
            default: $language = 'aucun';
        }

        /* On récupère que les 500 premiers */
        $baseURL = $this->getParameter(static::$sonarUrl);
        $url = "$baseURL/api/qualityprofiles/changelog?language=$language&qualityProfile=$profil&ps=500&p=1";

        /** On appel le client http */
        $r = $client->http($url);
        $events = $r['events'];
        $total = $r['total'];

        /* On créé une date */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $dateEnregistrement = $date->format(static::$dateFormat);

        /** On met à jkour la table contenant l'historique des changements. */
        foreach($events as $event) {
            /* On bind les données avant de les enregsitrer */
            $dc = new DateTime($event["date"]);
            $dc->setTimezone(new DateTimeZone(static::$europeParis));
            $dateCourte = $dc->format(static::$dateFormatShort);
            $dateModification = $event["date"];
            $action = $event["action"];
            $auteur = $event["authorName"];
            $regle = $event["ruleKey"];
            $description = $event["ruleName"];
            $detail = json_encode($event["params"]);

            /** On prépare les données pour la requête */
            $map=['date_courte'=>$dateCourte, 'langage'=>$language, 'date'=>$dateModification, 'action'=>$action, 'auteur'=>$auteur, 'regle'=>$regle, 'description'=>$description, 'detail'=>$detail, 'date_enregistrement'=>$dateEnregistrement];
            /** on lance la requête */
            $profilesHistoriqueEntity->insertProfilesHistorique($map);
        }

        /** Nombre de règles activé **/
        $map = ['langage'=>$language, 'action'=>'ACTIVATED'];
        $activated=$profilesHistoriqueEntity->selectProfilesHistoriqueAction($map);

        /** Nombre de règles désactivé --> DEACTIVATED **/
        $map = ['langage'=>$language, 'action'=>'DEACTIVATE'];
        $desactivited = $profilesHistoriqueEntity->selectProfilesHistoriqueAction($map);

        /** Nombre de règles mise à jour **/
        $map = ['langage'=>$language, 'action'=>'UPDATED'];
        $updated = $profilesHistoriqueEntity->selectProfilesHistoriqueAction($map);

        /** Date de la première modification **/
        $map2 = ['langage'=>$language, 'tri'=>'ASC', 'limit'=>1];
        $first = $profilesHistoriqueEntity->selectProfilesHistoriqueDateTri($map2);

        /** Date de la dernière modification **/
        $map3 = ['langage'=>$language, 'tri'=>'DESC', 'limit'=>1];
        $last = $profilesHistoriqueEntity->selectProfilesHistoriqueDateTri($map3);

        /** Calcul le  nombre de groupe de modification **/
        $map = ['langage'=>$language];
        $groupes = $profilesHistoriqueEntity->selectProfilesHistoriqueDateCourteGroupeBy($map);

        /** Pour chaque groupe on récupère dans un tableau les modifications */
        $i = 0;
        $liste = $tempoDateGroupe = $badge = [];
        foreach ($groupes['request'] as $groupe) {
            $dateGroupe = $groupe['date_courte'];
            $badgeA = $badgeU = $badgeD = 0;
            $tempo = [];
            $map=['langage'=>$language, 'date_courte'=>$dateGroupe];
            $modif = $profilesHistoriqueEntity->selectProfilesHistoriqueLangageDateCourte($map);
            /* On ajoute la date du groupe */
            array_push($tempoDateGroupe, $dateGroupe);

            foreach ($modif['request'] as $m) {
                $g = [  "groupe" => $i, "date" => $m["date"], "action" => $m["action"],
                        "auteur" => $m["auteur"], "regle" => $m["regle"],
                        "description" => $m["description"], "detail" => $m["detail"]];
                array_push($tempo, $g);
                if ($m["action"] === "UPDATED") {
                    $badgeU += 1;
                }
                if ($m["action"] === "DEACTIVATED") {
                    $badgeD += 1;
                }
                if ($m["action"] === "ACTIVATED") {
                    $badgeA += 1;
                }
            }

            $tempoBadge = ['badgeU' => $badgeU, 'badgeD' => $badgeD, 'badgeA' => $badgeA];
            array_push($badge, $tempoBadge);
            array_push($liste, $tempo);
            $i += 1;
        }

        $render = [
            "profil" => $profil, "langage" => $language,
            "opened" => $activated['request'][0]['nombre'], "closed" => $desactivited['request'][0]['nombre'], "updated" => $updated['request'][0]['nombre'],
            "totalRegle" => $total,
            "first" => $first['request'][0], "last" => $last['request'][0],
            "dateGroupe" => $tempoDateGroupe, "nbGroupe" => $i, "liste" => $liste, "badge" => $badge,
            "version" => $this->getParameter("version"),
            "dateCopyright" => \date("Y"), Response::HTTP_OK];

        return $this->render('profil/details.html.twig', $render);
    }

    /**
     * [Description for listeQualityOff]
     * Renvoie la lit ede profil qui ne ont pa acitf pour un certain langage donnée
     *
     * @param Request $request
     *
     * @return response
     *
     */
    #[Route('/api/quality/off', name: 'liste_quality_off', methods: ['POST'])]
    public function listeQualityOff(Request $request): response
    {
        /** On instancie la classe */
        $profilesEntity = $this->em->getRepository(Profiles::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On instancie une nouvelle response */
        $response = new JsonResponse();

      /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'langage')) {
            return $response->setData(
                ['data'=>$data,'code'=>400, Response::HTTP_BAD_REQUEST]);
        }

        /** On récupère le language */
        $langage = $data->langage;

        /** On récupère la liste des profils pour un language non actif */
        $referentielDefault = "false";
        $request=$profilesEntity->selectProfiles($referentielDefault,$langage);
        $compte=$profilesEntity->countProfiles($referentielDefault,$langage);
        return $response->setData([
            'code' => 200, 'listeProfil' => $request['liste'], 'countProfil' =>$compte, Response::HTTP_OK]);
    }
}
