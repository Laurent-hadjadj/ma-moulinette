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
use Symfony\Component\Routing\Annotation\Route;

/** Gestion du temps */
use DateTime;
use DateTimeZone;

/** Accès aux tables SLQLite */
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Main\Profiles;
use App\Entity\Main\ListeProjet;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

class ApiHomeController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $dateFormatShort = "Y-m-d";
    public static $dateFormat = "Y-m-d H:i:s";
    public static $europeParis = "Europe/Paris";
    public static $regex = "/\s+/u";

    /**
     * [Description for __construct]
     *
     * @param mixed
     *
     * Created at: 15/12/2022, 21:12:55 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
    ) {
        $this->logger = $logger;
        $this->em = $em;
    }

    /**
     * [Description for sonarStatus]
     * Vérifie si le serveur sonarqube est UP
     * http://{url}}/api/system/status
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:13:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/status', name: 'sonar_status', methods: ['GET'])]
    public function sonarStatus(Client $client): response
    {
        $url = $this->getParameter(static::$sonarUrl) . "/api/system/status";

        /** On appel le client http */
        $result = $client->http($url);

        return new JsonResponse($result, Response::HTTP_OK);
    }

    /**
     * [Description for sonarHealth]
     * Vérifie l'état du serveur
     * http://{url}}/api/system/health
     * Encore une fois, c'est null, il faut être admin pour récupérrer le résultat.
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:14:20 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/health', name: 'sonar_health', methods: ['GET'])]
    public function sonarHealth(Client $client): response
    {
        $url = $this->getParameter(static::$sonarUrl) . "/api/system/health";

        /** On appel le client http */
        $result = $client->http($url);
        return new JsonResponse($result, Response::HTTP_OK);
    }

    /**
     * [Description for informationSysteme]
     * On récupère les informations système du serveur
     * http://{url}}/api/system/info
     *
     * Attention, il faut avoir le role sonar administrateur
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:14:39 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/system/info', name: 'information_systeme', methods: ['GET'])]
    public function informationSysteme(Client $client): response
    {
        $url = $this->getParameter(static::$sonarUrl) . "/api/system/info";

        /** On appel le client http */
        $result = $client->http($url);
        return new JsonResponse($result, Response::HTTP_OK);
    }

    /**
     * [Description for projetListe]
     * Récupération de la liste des projets.
     * http://{url}}/api/components/search_projects?ps=500
     *
     * @param Request $request
     * @param Client $client
     * @return response
     *
     * Created at: 15/12/2022, 21:15:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/liste', name: 'projet_liste', methods: ['GET'])]
    public function projetListe(Request $request, Client $client): response
    {
        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On vérifie si on a activé le mode test */
        if (is_null($request->get('mode'))) {
            $mode = "null";
        } else {
            $mode = $request->get('mode');
        }

        /** On vérifie si l'utilisateur à un rôle Gestionnaire ? */
        if (!$this->isGranted('ROLE_GESTIONNAIRE')) {
            return $response->setData([
                "mode" => $mode ,
                "type" => 'alert',
                "reference" => "<strong>[Accueil-004]</strong>",
                "message" => "Vous devez disposer du rôle GESTIONNAIRE pour effectuée cette action",
                Response::HTTP_OK]);
        }

        $url = $this->getParameter(static::$sonarUrl)."/api/components/search_projects?ps=500";

        /** On appel le client http */
        $result = $client->http($url);
        /** On, initialiser les variables  */
        $public = $private = $emptyTags = $nombre = 0;

        /** On créé un objet DateTime */
        $date = new DateTime();
        $timezone = new DateTimeZone(static::$europeParis);
        $date->setTimezone($timezone);

        /** On vérifie que sonarqube a au moins 1 projet */
        if (!$result) {
            $reference = "<strong>[Accueil-003]</strong>";
            $type = "alert";
            $message = "Je n'ai pas trouvé de projet sur le serveur sonarqube.";
            return $response->setData(
                ["reference" => $reference, "type" => $type,
                    "message" => $message, Response::HTTP_OK]);
        }
        /** On supprime les données de la table avant d'importer les données. */
        $sql = "DELETE FROM liste_projet";
        $delete = $this->em->getConnection()->prepare($sql);
        if ($mode != 'TEST') {
            $delete->executeQuery();
        }
        /**
         * Si la table est vide on insert les résultats et
         * on revoie les résultats.
         */
        foreach ($result["components"] as $projet) {
            /**
             *  On exclue les projets archivés avec la particule "-SVN".
             *  "project": "fr.domaine:mon-application-SVN"
             */
            $mystring = $projet["key"];
            $findme = '-SVN';
            if (!strpos($mystring, $findme)) {
                $listeProjet = new ListeProjet();
                $listeProjet->setMavenKey($projet["key"]);
                $listeProjet->setName($projet["name"]);
                $listeProjet->setTags($projet["tags"]);
                $listeProjet->setVisibility($projet["visibility"]);
                $listeProjet->setDateEnregistrement($date);
                $this->em->persist($listeProjet);
                if ($mode != 'TEST') {
                    $this->em->flush();
                }
                $nombre++;
                /** On calcul le nombre de projet public et privé */
                if ($projet["visibility"] == 'public') {
                    $public++;
                } else {
                    $private++;
                }
                /** On calcul le nombre de projet sans tags */
                if (empty($projet["tags"])) {
                    $emptyTags++;
                }
            }
        }

        /** On met à jour la table proprietes */
        $dateModificationProjet = $date->format(static::$dateFormatShort);

        $sql = "UPDATE properties
                SET projet_bd = $nombre,
                    projet_sonar = $nombre,
                    date_modification_projet = '$dateModificationProjet'
                WHERE type = 'properties'";
        if ($mode != 'TEST') {
            $this->em->getConnection()
                    ->prepare(trim(preg_replace(static::$regex, " ", $sql)))
                    ->executeQuery();
        }

        /** on renvoie les résultats */
        $reference = "<strong>[Accueil-002]</strong>";
        $type = "success";
        $message = "Mise à jour de la liste des projets effectuée.";

        return $response->setData(
            [
            "mode" => $mode,
            "reference" => $reference,
            "type" => $type,
            "message" => $message,
            "nombre" => $nombre,
            "public" => $public,
            "private" => $private,
            "empty_tags" => $emptyTags,
            Response::HTTP_OK
      ]
        );
    }

    /**
     * [Description for listeQualityProfiles]
     * liste_quality_profiles
     * Renvoie la liste des profils qualités
     * http://{url}/api/qualityprofiles/search?qualityProfile={name}
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:15:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/quality/profiles', name: 'liste_quality_profiles', methods: ['GET'])]
    public function listeQualityProfiles(Request $request, Client $client): response
    {
        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On vérifie si on a activé le mode test */
        $mode = $request->get('mode');

        $url1 = $this->getParameter(static::$sonarUrl)
              . "/api/qualityprofiles/search?qualityProfile="
              . $this->getParameter('sonar.profiles');

        /** On appel le client http */
        $result = $client->http($url1);

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $nombre = 0;

        /** On supprime les données de la table avant d'importer les données;*/
        $sql = "DELETE FROM profiles";
        $delete = $this->em->getConnection()->prepare($sql);
        if ($mode != "TEST") {
            $delete->executeQuery();
        }

        /** On insert les profiles dans la table profiles. */
        foreach ($result["profiles"] as $profil) {
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
            if ($mode != "TEST") {
                $this->em->flush();
            }
        }

        /** On récupère la liste des profils; */
        $sql = "SELECT name as profil, language_name as langage,
            active_rule_count as regle, rules_update_at as date,
            is_default as actif FROM profiles";
        $select = $this->em->getConnection()->prepare(trim(preg_replace(static::$regex, " ", $sql)))->executeQuery();
        $liste = $select->fetchAllAssociative();

        /** On met à jour la table proprietes */
        $dateModificationProfil = $date->format(static::$dateFormatShort);

        $sql = "UPDATE properties
    SET profil_bd = $nombre,
        profil_sonar = $nombre,
        date_modification_profil = '$dateModificationProfil'
    WHERE type = 'properties'";

        if ($mode != "TEST") {
            $this->em->getConnection()
                  ->prepare(trim(preg_replace(static::$regex, " ", $sql)))
                  ->executeQuery();
        }
        return $response->setData(["mode" => $mode, "listeProfil" => $liste, Response::HTTP_OK]);
    }

    /**
     * [Description for visibility]
     * Renvoi le nombre de projet private ou public
     * Il faut avoir un droit Administrateur !!!
     * http://{url}/api/projects/search?qualifiers=TRK&ps=500
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:17:03 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/visibility', name: 'visibility', methods: ['GET'])]
    public function visibility(Client $client): response
    {
        $url = $this->getParameter(static::$sonarUrl) .
              "/api/projects/search?qualifiers=TRK&ps=500";

        /** On appel le client http */
        $components = $client->http($url);

        $private = 0;
        $public = 0;

        foreach ($components["components"] as $component) {
            if ($component == "private") {
                $private++;
            }
            if ($component == "public") {
                $public++;
            }
        }

        $response = new JsonResponse();
        return $response->setData(
            ["private" => $private, "public" => $public, Response::HTTP_OK]
        );
    }

}
