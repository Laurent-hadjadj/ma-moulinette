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

namespace App\Controller\Home;

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
use App\Entity\Main\Properties;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;


/**
 * [Description ApiHomeController]
 */
class ApiHomeController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $dateFormatShort = "Y-m-d";
    public static $dateFormat = "Y-m-d H:i:s";
    public static $europeParis = "Europe/Paris";
    public static $removeReturnline = "/\s+/u";
    public static $reference = '<strong>[Accueil]</strong>';
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    public static $erreur404 = "Je n'ai pas trouvé de projets sur le serveur sonarqube (Erreur 404).";

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
     * http://{url}}/api/status
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:13:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/status', name: 'api_sonar_status', methods: ['POST'])]
    public function apiSonarStatus(Request $request, Client $client): response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si le body est correcte */
        if ($data === null || !property_exists($data, 'mode')) {
            return $response->setData(['data'=>$data, 'code'=>400, 'reference'=>static::$reference, 'message'=>static::$erreur400, 'type'=>'alert', 'result'=>'',Response::HTTP_BAD_REQUEST]);
            }

        $url = $this->getParameter(static::$sonarUrl) . "/api/system/status";

        /** On appel le client http */
        $result = $client->http($url);
        return $response->setData([$result, Response::HTTP_OK]);
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
    #[Route('/api/home/projet', name: 'home_projet_liste', methods: ['POST'])]
    public function homlProjetListe(Request $request, Client $client): response
    {
        /** On instancie l'EntityRepository */
        $listeProjetEntity = $this->em->getRepository(ListeProjet::class);
        $propertiesEntity = $this->em->getRepository(Properties::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si le body est correcte */
        if ($data === null || !property_exists($data, 'mode')) {
        return $response->setData(['data'=>$data,'code'=>400, 'reference'=>static::$reference, 'message'=>static::$erreur400, 'type'=>'alert', Response::HTTP_BAD_REQUEST]);
        }

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return $response->setData([
                'mode' => $data->mode,
                'type'=>'warning', 'code' => 403,
                'reference' => static::$reference,
                'message' => static::$erreur403, Response::HTTP_OK]);
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
        if (!$result || $result['paging']['total']<1 || count($result['components'])<1) {
            return $response->setData([
                'type' => 'warning', 'mode' => $data->mode,
                'reference' => static::$reference, 'code' => 404,
                'message'=>static::$erreur404, Response::HTTP_OK]);
        }

        /** On supprime les données de la table avant d'importer les données. */
        $request=$listeProjetEntity->deleteListeProjet($data->mode);
        if ($request['code']!=200) {
            return $response->setData([
                'type' => 'alert', 'mode' => $data->mode,
                'reference' => static::$reference, 'code' => $request['code'],
                'message'=>$request['erreur'], Response::HTTP_OK]);
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
                if ($data->mode != 'TEST') {
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
        $map=[  'projet_bd'=>$nombre, 'projet_sonar'=>$nombre,
                'date_modification_projet'=>$date->format(static::$dateFormatShort),
            ];
        $r=$propertiesEntity->updatePropertiesProjet($data->mode,$map);
        if ($r['code']!=200) {
            return $response->setData([
                'type' => 'alert', 'mode' => $data->mode,
                'reference' => static::$reference, 'code' => $r['code'],
                'message'=>$r['erreur'], Response::HTTP_OK]);
        }

        /** on renvoie les résultats */
        $message = "Mise à jour de la liste des projets effectuée.";

        return $response->setData(
            [ 'mode' => $data->mode, 'code' => 200,
            'reference' => static::$reference, 'type' => 'success',
            'message' => $message,'nombre' => $nombre,
            'public' => $public, 'private' => $private,
            'empty_tags' => $emptyTags, Response::HTTP_OK ]);
    }

    /**
     * [Description for listeQualityProfiles]
     * Retourne la liste des profils qualité
     *
     * Renvoie la liste des profils qualités
     * http://{url}/api/quality/profiles
     *
     * @param Request $request
     * @param Client $client
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:15:43 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/quality/profiles', name: 'liste_quality_profiles', methods: ['POST'])]
    public function listeQualityProfiles(Request $request, Client $client): response
    {
        /** On instancie l'EntityRepository */
        $profilesEntity = $this->em->getRepository(Profiles::class);
        $propertiesEntity = $this->em->getRepository(Properties::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si le body est correcte */
        if ($data === null || !property_exists($data, 'mode')) {
        return $response->setData(['data'=>$data,'code'=>400, 'reference'=>static::$reference, 'message'=>static::$erreur400, 'type'=>'alert', Response::HTTP_BAD_REQUEST]);
        }

        $url1 = $this->getParameter(static::$sonarUrl)
                . "/api/qualityprofiles/search?qualityProfile="
                . $this->getParameter('sonar.profiles');

        /** On appel le client http */
        $result = $client->http($url1);

        $date = new DateTime();
        $date->setTimezone(new DateTimeZone(static::$europeParis));
        $nombre = 0;

        /** On supprime les données de la table avant d'importer les données. */
        $request=$profilesEntity->deleteProfiles($data->mode);
        if ($request['code']!=200) {
            return $response->setData([
                'type' => 'alert', 'mode' => $data->mode,
                'reference' => static::$reference, 'code' => $request['code'],
                'message'=>$request['erreur'], Response::HTTP_OK]);
        }

        /** On insert les profiles dans la table profiles. */
        foreach ($result['profiles'] as $profil) {
            $nombre = $nombre + 1;

            $profils = new Profiles();
            $profils->setKey($profil['key']);
            $profils->setName($profil['name']);
            $profils->setLanguageName($profil['languageName']);
            $profils->setIsDefault($profil['isDefault']);
            $profils->setActiveRuleCount($profil['activeRuleCount']);
            $rulesDate = new DateTime($profil['rulesUpdatedAt']);
            $profils->setRulesUpdateAt($rulesDate);
            $profils->setDateEnregistrement($date);
            $this->em->persist($profils);
            if ($data->mode != 'TEST') {
                $this->em->flush();
            }
        }

        /** On récupère la liste des profils; */
        $liste = $profilesEntity->selectProfile($data->mode);
        if ($liste['code']!=200) {
            return $response->setData([
                'type' => 'alert', 'mode' => $data->mode,
                'reference' => static::$reference, 'code' => $liste['code'],
                'message'=>$liste['erreur'], Response::HTTP_OK]);
        }

        /** On met à jour la table proprietes */
        $map=['profil_bd'=>$nombre, 'profil_sonar'=>$nombre,
        'date_modification_profil'=>$date->format(static::$dateFormatShort),
        ];
        $r=$propertiesEntity->updatePropertiesProfil($data->mode,$map);
        if ($r['code']!=200) {
            return $response->setData([
                'type' => 'alert', 'mode' => $data->mode,
                'reference' => static::$reference, 'code' => $r['code'],
                'message'=>$propertiesEntity['erreur'], Response::HTTP_OK]);
        }

        return $response->setData(['mode' => $data->mode, 'listeProfil' => $liste, Response::HTTP_OK]);
    }

}
