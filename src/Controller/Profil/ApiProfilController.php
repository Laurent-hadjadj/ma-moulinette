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

namespace App\Controller\Profil;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/** Sécurité */
use Symfony\Bundle\SecurityBundle\Security;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Properties;
use App\Entity\Profiles;
use App\Entity\ProfilesHistorique;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description ApiProfilController]
 */
class ApiProfilController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $oups = "OUPS !!!";
    public static $page = "profil/details.html.twig";
    public static $europeParis = "Europe/Paris";
    public static $dateFormat = "Y-m-d H:i:s";
    public static $dateFormatShort = "Y-m-d";

    private $client;

    /**
     * [Description for __construct]
     *  EntityManagerInterface = em
     *
     * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        Client $client,
    ) {
        $this->em = $em;
        $this->client = $client;
    }

    /**
     * [Description for genericRender]
     *
     * @return array
     *
     * Created at: 30/10/2024 08:21:04 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        return [
            'type_footer' => null,
            'marque_entreprise_short' => $this->getParameter('marque.entreprise.short'),
            'marque_entreprise_long' => $this->getParameter('marque.entreprise.long'),
            'logo_entreprise' => $this->getParameter('logo.entreprise'),
            'env' => $this->getParameter('environnement'),
            "version" => $this->getParameter("version"),
            "date_copyright" => \date("Y")];
        }

    /**
     * [Description for listeQualityProfiles]
     * Renvoie la liste des profils qualité
     * http://{url}/api/qualityprofiles/search?qualityProfile={name}
     * RÔLE-GESTIONNAIRE
     * @return JsonResponse
     *
     * Created at: 07/05/2023, 21:12:09 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/quality/profiles', name: 'liste_quality_profiles', methods: ['POST'])]
    public function listeQualityProfiles(Security $security): JsonResponse
    {
        /** On instancie l'entityRepository */
        $profilesRepository = $this->em->getRepository(Profiles::class);
        $propertiesRepository = $this->em->getRepository(Properties::class);

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$security->isGranted('ROLE_GESTIONNAIRE')){
            return new JsonResponse(['code' => 403], Response::HTTP_OK);
        }

        /** On construit l'URL */
        $baseUrl = $this->getParameter(static::$sonarUrl);
        /** On définit l'URL et on ajoute le nom des profils SonarQube*/
        $queryParams = [];
        $result = $this->client->httpSonarQube("$baseUrl/api/qualityprofiles/search?".http_build_query($queryParams));
        if (in_array($result['code'] ?? -1, [503, 504])) {
            $titre = static::$oups;
            $message = $result['erreur'];
            $this->addFlash('notice', ['type'=>'alert', 'titre'=>$titre, 'message'=>$message]);
            return 0;
        }

        /** On Vérifie qu'il existe au moins un profil */
        if (empty($result['profiles'])) {
            return new JsonResponse(['code' => 404], Response::HTTP_OK);
        }

        /*** Super on a récupéré la liste des profils par langage */
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On supprime les données de la table avant d'importer les données;*/
        $r1=$profilesRepository->deleteProfiles();
        if ($r1['code']===500) {
            return new JsonResponse(['code' => 500, 'erreur'=>$r1['erreur']], Response::HTTP_OK);
        }

        /** On insert les profils dans la table profiles. */
        $map=[ 'profiles' => $result['profiles'], 'date_enregistrement' => $date ];
        $r2=$profilesRepository->insertProfiles($map);
        if ($r2['code']===500) {
            return new JsonResponse(['code' => 500, 'erreur'=>$r2['erreur']], Response::HTTP_OK);
        }

        /** On récupère la nouvelle liste des profils */
        $r3=$profilesRepository->selectProfiles();
        if ($r3['code']===500) {
            return new JsonResponse(['code' => 500, 'erreur'=>$r3['erreur']], Response::HTTP_OK);
        }

        /** On met à jour la table propriétés */
        $map=['profil_bd'=>$r2['nombre'], 'profil_sonar'=>$r2['nombre'], 'date_modification_profil' => $date];
        $r4=$propertiesRepository->updatePropertiesProfiles($map);
        if ($r4['code']===500) {
            return new JsonResponse(['code' => 500, 'erreur'=>$r4['erreur']], Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200, 'liste_profil' => $r3['liste']], Response::HTTP_OK);
    }

    /**
     * [Description for listeQualityLangage]
     * Revoie le tableau des labels et des dataset
     * Permet de tracer un joli dessin sur la répartition des langages de programmation.
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:24:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/quality/langage', name: 'liste_quality_langage', methods: ['POST'])]
    public function listeQualityLangage(): JsonResponse
    {
        /** On instancie la classe */
        $profilesRepository = $this->em->getRepository(Profiles::class);

        $listeLabel = [];
        $listeDataset = [];

        /** On récupère la liste des langages */
        $selectProfilesLanguage=$profilesRepository->selectProfilesLanguage();

        /** On créé la liste des libellés et des données */
        foreach ($selectProfilesLanguage['labels'] as $label) {
            array_push($listeLabel, $label['profile']);
        }

        /** On récupère le nombre de règle de chaque profil */
        $selectProfilesRuleCount=$profilesRepository->selectProfilesRuleCount();
        foreach ($selectProfilesRuleCount['data-set'] as $dataSet) {
            array_push($listeDataset, $dataSet['total']);
        }

        return new JsonResponse(['label' => $listeLabel, 'dataset' => $listeDataset], Response::HTTP_OK);
    }

    /**
     * [Description for profilDetails]
     * Affichage des règles par profils avec les changement.
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 11/03/2023, 23:08:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/profil/details', name: 'profil_details', methods: ['GET'])]
    public function profilDetails(Request $request)
    {
        /** On instancie la classe */
        $profilesHistoriqueRepository = $this->em->getRepository(ProfilesHistorique::class);

        $token = $request->get('token');
        if (empty($token)){
            return;
        }
        //b64=bnVsbHxDU1N8RnJhbmNlQWdyaU1lciB2Mi4wLjAgKDIwMjEp
        //rot13=oaIfoUkQH1A8EaWuozAyDJqlnH1ypvO2Zv4jYwNtXQVjZwRc
        $string=str_rot13($token);
        $decode=base64_decode($string);
        $explode=preg_split("/[|]+/",$decode);

        $render=static::genericRender();
        $render['profil'] = 'NC';
        $render['langage'] = 'aucun';
        $render['opened'] = 0;
        $render['closed'] = 0;
        $render['updated'] = 0;
        $render['total_rule'] = null;
        $render['premier'] = null;
        $render['dernier'] = null;
        $render['date_groupe'] = null;
        $render['nombre_groupe'] = null;
        $render['liste'] = null;
        $render['badge'] = null;

        /** si le mode n'est pas défini ou si le tableau ne contient pas trois clé alors */
        if (count($explode) !=3) {
             /** On prepare un message flash */
            $this->addFlash('notice', ['type'=>'alert', 'titre'=> '[DÉTAILS-001]', 'message'=>"Le token est incorrecte (Erreur 400)."]);
            return $this->render(static::$page, $render);
        }
        /** On récupère le nom du langage et le nom du profil */
        $language = strtolower($explode[1]);
        $profil = $explode[2];
        /** On renomme les langages pour SonarQube */
        $sonarLanguage = ['delphi', 'css', 'jsp', 'py', 'js', 'secrets', 'ruby', 'java', 'web', 'xml', 'php', 'json', 'text', 'grvy', 'ts', 'yaml'];

        if (!in_array($language, $sonarLanguage)){
            $titre="[DÉTAILS-002]";
            $message = "Le langage sélectionné ne fait pas partir des langages supporté par SonarQube (Erreur 404)";
            $this->addFlash('notice', ['type'=>'alert', 'titre'=>$titre, 'message'=>$message]);
            return $this->render(static::$page, $render);
        }

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
            case "groovy": $language = "grvy";
            break;
            default: $language;
        }

        /* On récupère que les 500 premiers */
        /** On construit l'URL */
        $baseUrl = $this->getParameter(static::$sonarUrl);
        /** On définit l'URL et on ajoute le nom des profils SonarQube*/
        $queryParams = ['language' => $language, 'qualityProfile' => $profil, 'ps' => 500, 'p' => 1 ];
        $result = $this->client->httpSonarQube("$baseUrl/api/qualityprofiles/changelog?".http_build_query($queryParams));
        if (in_array($result['code'] ?? -1, [400, 503, 504])) {
            $titre="OUPS !!!";
            $message = $result['erreur'];
            $this->addFlash('notice', ['type'=>'alert', 'titre'=>$titre, 'message'=>$message]);
            return $this->render(static::$page, $render);
        }

        $events = $result['events'];
        $total = $result['total'];

        /* On créé une date */
        $date = new \DateTime();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        /** On met à jour la table contenant l'historique des changements. */
        foreach($events as $event) {
            /* On bind les données avant de les enregistrer */
            $dc = new \DateTime($event['date']);
            $dc->setTimezone(new \DateTimeZone(static::$europeParis));
            $dateCourte = $dc->format(static::$dateFormatShort);
            $dateModification = $event['date'];
            $action = $event['action'];
            $auteur = $event['authorName'];
            $rule = $event['ruleKey'];
            $description = $event['ruleName'];
            $detail = json_encode($event["params"]);

            /** On prépare les données pour la requête */
            $map=['date_courte'=>$dateCourte, 'language'=>$language, 'date'=>$dateModification, 'action'=>$action, 'auteur'=>$auteur, 'rule'=>$rule, 'description'=>$description, 'detail'=>$detail, 'date_enregistrement'=>$date];
            /** on lance la requête */
            $profilesHistoriqueRepository->insertProfilesHistorique($map);
        }

        /** Nombre de règles activé **/
        $map = ['language'=>$language, 'action'=>'ACTIVATED'];
        $activated=$profilesHistoriqueRepository->selectProfilesHistoriqueAction($map);

        /** Nombre de règles désactivé --> DEACTIVATED **/
        $map = ['language'=>$language, 'action'=>'DEACTIVATE'];
        $deactivated = $profilesHistoriqueRepository->selectProfilesHistoriqueAction($map);

        /** Nombre de règles mise à jour **/
        $map = ['language'=>$language, 'action'=>'UPDATED'];
        $updated = $profilesHistoriqueRepository->selectProfilesHistoriqueAction($map);

        /** Date de la première modification **/
        $map2 = ['language'=>$language, 'tri'=>'ASC', 'limit'=>1];
        $first = $profilesHistoriqueRepository->selectProfilesHistoriqueDateTri($map2);

        /** Date de la dernière modification **/
        $map3 = ['language'=>$language, 'tri'=>'DESC', 'limit'=>1];
        $last = $profilesHistoriqueRepository->selectProfilesHistoriqueDateTri($map3);

        /** Calcul le  nombre de groupe de modification **/
        $map = ['language'=>$language];
        $groupes = $profilesHistoriqueRepository->selectProfilesHistoriqueDateCourteGroupeBy($map);

        /** Pour chaque groupe on récupère dans un tableau les modifications */
        $i = 0;
        $liste = $tempoDateGroupe = $badge = [];
        foreach ($groupes['liste'] as $groupe) {
            $dateGroupe = $groupe['date_courte'];
            $badgeA = $badgeU = $badgeD = 0;
            $tempo = [];
            $map=['language'=>$language, 'date_courte'=>$dateGroupe];
            $modif = $profilesHistoriqueRepository->selectProfilesHistoriqueLangageDateCourte($map);
            /* On ajoute la date du groupe */
            array_push($tempoDateGroupe, $dateGroupe);

            foreach ($modif['liste'] as $m) {
                $g = [  'groupe' => $i, 'date' => $m['date'], 'action' => $m['action'],
                        'auteur' => $m['auteur'], 'rule' => $m['rule'],
                        'description' => $m['description'], 'detail' => $m['detail']];
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

        $render['profil'] = $profil;
        $render['langage'] = $language;
        $render['opened'] = $activated['nombre'][0]['nombre'];
        $render['closed'] = $deactivated['nombre'][0]['nombre'];
        $render['updated'] = $updated['nombre'][0]['nombre'];
        $render['total_rule'] = $total;
        $render['premier'] = $first['liste'][0];
        $render['dernier'] = $last['liste'][0];
        $render['date_groupe'] = $tempoDateGroupe;
        $render['nombre_groupe'] = $i;
        $render['liste'] = $liste;
        $render['badge'] = $badge;

        return $this->render(static::$page, $render);
    }

    /**
     * [Description for listeQualityOff]
     * Renvoie la liste des profils qui ne ont pas actif pour un language donné
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     */
    #[Route('/api/quality/off', name: 'liste_quality_off', methods: ['POST'])]
    public function listeQualityOff(Request $request): JsonResponse
    {
        /** On instancie la classe */
        $profilesRepository = $this->em->getRepository(Profiles::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

      /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'langage')) {
            return new JsonResponse(
                ['data'=>$data,'code'=>400], Response::HTTP_BAD_REQUEST);
        }

        /** On récupère le language */
        $langage = $data->langage;

        /** On récupère la liste des profils pour un language non actif */
        $referential_default = 'false';
        $request=$profilesRepository->selectProfiles($referential_default, $langage);
        $compte=$profilesRepository->countProfiles($referential_default, $langage);
        return new JsonResponse([
            'code' => 200, 'listeProfil' => $request['liste'], 'countProfil' =>$compte], Response::HTTP_OK);
    }
}
