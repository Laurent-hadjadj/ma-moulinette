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
use App\Entity\Main\Properties;

/** Client HTTP */
use App\Service\Client;

/** Entity */
use App\Entity\Main\Profiles;

class ApiProfilController extends AbstractController
{
    /** Définition des constantes */
    public static $strContentType = 'application/json';
    public static $sonarUrl = "sonar.url";
    public static $europeParis = "Europe/Paris";
    public static $dateFormat = "Y-m-d H:i:s";
    public static $dateFormatShort = "Y-m-d";
    public static $regex = "/\s+/u";

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
        /** on décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet response */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null) {
        return $response->setData(['mode' => null, 'code'=>400, Response::HTTP_BAD_REQUEST]); }

        /** si on est pas GESTIONNAIRE on ne fait rien. */
        if (!$security->isGranted('ROLE_GESTIONNAIRE')){
            return $response->setData(['mode' => $data->mode, 'code' => 403, Response::HTTP_OK]);
        }

        /** On définit l'URL et on ajoute le nom des profils sonarqube*/
        $url = $this->getParameter(static::$sonarUrl)
            . "/api/qualityprofiles/search?qualityProfile="
            . $this->getParameter('sonar.profiles');

        /** On appel le client http */
        $r = $client->http($url);

        /** On Vérifie qu'il existe au moins un profil */
        if (empty($r['profiles'])) {
            return $response->setData(['mode' => $data->mode, 'code' => 202, Response::HTTP_OK]);
        }

        /*** Super on a récupéré la liste des profils par langage */
        $date = new DateTime();
        $date->setTimezone(new DateTimeZone('Europe/Paris'));
        $nombre = 0;

        /** On supprime les données de la table avant d'importer les données;*/
        $profiles = $this->em->getRepository(Profiles::class);
        $request=$profiles->deleteProfiles($data->mode);
        if ($request['code']===500) {
            return $response->setData(['mode' => $data->mode, 'code' => 500, 'erreur'=>$request['erreur'], Response::HTTP_OK]);
        }

        /** On insert les profils dans la table profiles. */
        foreach ($r["profiles"] as $profil) {
            $nombre = $nombre + 1;

            $profils = new Profiles();
            $profils->setKey($profil["key"]);
            $profils->setName($profil["name"]);
            $profils->setLanguageName($profil["languageName"]);
            $profils->setIsDefault($profil["isDefault"]);
            $profils->setActiveRuleCount($profil["activeRuleCount"]);
            $rulesDate = new DateTime($profil["rulesUpdatedAt"]);
            $profils->setRulesUpdateAt($rulesDate);
            $profils->setDateEnregistrement($date);
            $this->em->persist($profils);
            $this->em->flush();
        }

        /** On récupère la nouvelle liste des profils; */
        $request=$profiles->selectProfiles($data->mode);

        /** On met à jour la table proprietes */
        $dateModificationProfil = $date->format("Y-m-d H:i:s");
        $map=['profil_bd'=>$nombre, 'profil_sonar'=>$nombre, 'date_modification_profil'=>$dateModificationProfil];
        $properties = $this->em->getRepository(Properties::class);
        $properties->updateProfilesProperties($data->mode, $map);

        return $response->setData([
            'mode' => $data->mode, 'code' => 200, "listeProfil" => $request['liste'], Response::HTTP_OK]);
    }

    /**
     * [Description for listeQualityLangage]
     * Revoie le tableau des labels et des dataset
     * Permet de tracer un jolie dessin sur la répartition des langages de programmation.
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:24:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/quality/langage', name: 'liste_quality_langage', methods: ['GET'])]
    public function listeQualityLangage(): response
    {
        $listeLabel = [];
        $listeDataset = [];

        /** On créé la liste des libellés et des données */
        $sql = "SELECT language_name AS profile FROM profiles";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $labels = $select->fetchAllAssociative();
        foreach ($labels as $label) {
            array_push($listeLabel, $label["profile"]);
        }

        $sql = "SELECT active_rule_count AS total FROM profiles";
        $select = $this->em->getConnection()->prepare($sql)->executeQuery();
        $dataSets = $select->fetchAllAssociative();
        foreach ($dataSets as $dataSet) {
            array_push($listeDataset, $dataSet["total"]);
        }

        $response = new JsonResponse();
        return $response->setData(["label" => $listeLabel, "dataset" => $listeDataset, Response::HTTP_OK]);
    }

    /**
     * [Description for profilQualityChangement]
     *
     * @return response
     *
     * Created at: 11/03/2023, 23:08:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/profil/details', name: 'profil_details', methods: ['GET'])]
    public function profilDetails(Request $request, Client $client): response
    {
        /** On récupère les données */
        $profil = $request->get('profil');
        $language = strtolower($request->get('language'));
        $mode = $request->get('mode');

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
            default: $language = "null";
        }

        /** On créé un objet response pour le retour JSON. */
        $response = new JsonResponse();

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

            /** On escape les ' */
            $reEncode = str_replace("'", "''", $description);

            $sql = "INSERT OR IGNORE INTO profiles_historique
              (date_courte, langage, date, action, auteur, regle, description, detail, date_enregistrement)
              VALUES ('$dateCourte', '$language', '$dateModification', '$action', '$auteur',
                      '$regle', '$reEncode', '$detail', '$dateEnregistrement');";
            $trim = trim(preg_replace(static::$regex, " ", $sql));
            $this->em->getConnection()->prepare($trim)->executeQuery();
        }

        /** Nombre de règles activé **/
        $sql1 = "SELECT COUNT() AS 'nombre' FROM profiles_historique WHERE action='ACTIVATED' AND langage='$language'";
        $activited = $this->em->getConnection()->prepare($sql1)->executeQuery()->fetchAllAssociative();

        /** Nombre de règles désactivé --> DEACTIVATED **/
        $sql2 = "SELECT COUNT() AS 'nombre' FROM profiles_historique WHERE action='DEACTIVATED' AND langage='$language'";
        $desactivited = $this->em->getConnection()->prepare($sql2)->executeQuery()->fetchAllAssociative();
        if (empty($desactivited)) {
            $desactivited = 0;
        }

        /** Nombre de règles mise à jour **/
        $sql3 = "SELECT COUNT() AS 'nombre' FROM profiles_historique WHERE action='UPDATED' AND langage='$language'";
        $updated = $this->em->getConnection()->prepare($sql3)->executeQuery()->fetchAllAssociative();
        if (empty($updated)) {
            $updated = 0;
        }

        /** Date de la première modification **/
        $sql4 = "SELECT date FROM profiles_historique WHERE langage='$language' ORDER BY date ASC limit 1";
        $first = $this->em->getConnection()->prepare($sql4)->executeQuery()->fetchAllAssociative();
        /** Date de la dernière modification **/
        $sql5 = "SELECT date FROM profiles_historique WHERE langage='$language' ORDER BY date DESC limit 1";
        $last = $this->em->getConnection()->prepare($sql5)->executeQuery()->fetchAllAssociative();
        /** Calcul le  nombre de groupe de modification **/
        $sql6 = "SELECT date_courte FROM profiles_historique
          WHERE langage='$language' GROUP BY date_courte ORDER BY date_courte DESC";
        $groupes = $this->em->getConnection()->prepare($sql6)->executeQuery()->fetchAllAssociative();

        /** Pour chaque groupe on récupère dans un tableau les modifications */
        $i = 0;
        $liste = [];
        $tempoDateGroupe = [];
        $badge = [];
        foreach ($groupes as $groupe) {
            $dateGroupe = $groupe["date_courte"];
            $badgeA = $badgeU = $badgeD = 0;
            $tempo = [];

            $sql = "SELECT * FROM profiles_historique WHERE langage='$language' AND date_courte='$dateGroupe'";
            $trim = trim(preg_replace(static::$regex, " ", $sql));
            $modif = $this->em->getConnection()->prepare($trim)->executeQuery()->fetchAllAssociative();

            /* On ajoute la date du groupe */
            array_push($tempoDateGroupe, $dateGroupe);

            foreach ($modif as $m) {
                $g = ["groupe" => $i, "date" => $m["date"], "action" => $m["action"],
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
          "mode" => $mode,
          "profil" => $profil, "langage" => $language,
          "opened" => $activited[0]['nombre'], "closed" => $desactivited[0]['nombre'], "updated" => $updated[0]['nombre'],
          "totalRegle" => $total,
          "first" => $first[0], "last" => $last[0],
          "dateGroupe" => $tempoDateGroupe, "nbGroupe" => $i, "liste" => $liste, "badge" => $badge,
          "version" => $this->getParameter("version"),
          "dateCopyright" => \date("Y"),
          Response::HTTP_OK];
        if ($mode == "TEST") {
            return $response->setData($render);
        } else {
            return $this->render('profil/details.html.twig', $render);
        }
    }
}
