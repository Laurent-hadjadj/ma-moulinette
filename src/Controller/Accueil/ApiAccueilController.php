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

namespace App\Controller\Accueil;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/** Accès aux tables */
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\ListeProjet;
use App\Entity\Properties;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

/** Logger */
use Psr\Log\LoggerInterface;

/** Client HTTP */
use App\Service\Client;

/**
 * [Description ApiAccueilController]
 */
class ApiAccueilController extends AbstractController
{
    /** Définition des constantes */
    public static $sonarUrl = "sonar.url";
    public static $dateFormatShort = "Y-m-d";
    public static $dateFormat = "Y-m-d H:i:s";
    public static $europeParis = "Europe/Paris";
    public static $reference = '[Accueil]';
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur403 = "Vous devez avoir le rôle COLLECTE pour réaliser cette action (Erreur 403).";
    public static $erreur404 = "Je n'ai pas trouvé de projets sur le serveur SonarQube (Erreur 404).";

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
        private Client $client,
        private LoggerInterface $logger,
        private EntityManagerInterface $em,
        private ParameterBagInterface $params,
    ) {
        $this->client = $client;
        $this->logger = $logger;
        $this->em = $em;
        $this->params = $params;
    }

    /**
     * [Description for sonarStatus]
     * Vérifie si le serveur SonarQube est UP
     * http://{url}}/api/status
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:13:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/status', name: 'api_sonar_status', methods: ['POST'])]
    public function apiSonarStatus(): JsonResponse
    {
        $url = $this->params->get(static::$sonarUrl) . "/api/system/status";

        /** On appel le client http */
        $result = $this->client->httpSonarQube($url);
        if ($result['code'] != 200) {
            return new JsonResponse(['code' => $result['code'], 'erreur' => $result['erreur']], Response::HTTP_OK);
        } else {
                return new JsonResponse(['code' => $result['code'], 'result' => $result]);
            }
    }

    /**
     * [Description for projetListe]
     * Récupération de la liste des projets.
     * http://{url}/api/components/search_projects?ps=500
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:15:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/accueil/projet', name: 'accueil_projet_liste', methods: ['POST'])]
    public function accueilProjetListe(): JsonResponse
    {
        /** On instancie l'EntityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);
        $propertiesRepository = $this->em->getRepository(Properties::class);

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse([
                'type'=>'warning', 'code' => 403,
                'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        $url = $this->getParameter(static::$sonarUrl)."/api/components/search_projects?ps=500";
        /** On appel le client http */
        $result = $this->client->httpSonarQube($url);
        if ($result['code'] != 200) {
            return new JsonResponse([
                'type' => 'alert',
                'reference' => static::$reference, 'code' => $result['code'],
                'message' => $result['erreur']], Response::HTTP_OK);
        }

        /** On, initialiser les variables  */
        $public = $private = $emptyTags = $nombre = 0;

        /** On créé un objet DateTimeImmutable */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        /** On vérifie que SonarQube a au moins 1 projet */
        if (array_key_exists('json', $result) && empty($result['json']['components'])){
            return new JsonResponse([
                'type' => 'warning',
                'reference' => static::$reference, 'code' => 404,
                'message'=>static::$erreur404], Response::HTTP_OK);
        }

        /** On supprime les données de la table avant d'importer les données. */
        $delete = $listeProjetRepository->deleteListeProjet();
        if ($delete['code'] != 200) {
            return new JsonResponse([
                'type' => 'alert',
                'reference' => static::$reference, 'code' => $delete['code'],
                'message' => $delete['erreur']], Response::HTTP_OK);
        }

        /**
         * Si la table est vide on insert les résultats et
         * On revoie les résultats.
         */
        foreach ($result['json']['components'] as $projet) {
            /**
             *  On exclue les projets archivés avec la particule "-SVN".
             *  "project": "fr.domaine:mon-application-SVN"
             */
            $mystring = $projet['key'];
            $findme = '-SVN';
            if (!strpos($mystring, $findme)) {
                $listeProjet = new ListeProjet();
                    $listeProjet->setMavenKey($projet["key"]);
                    $listeProjet->setName($projet["name"]);
                    $listeProjet->setTags($projet["tags"]);
                    $listeProjet->setVisibility($projet["visibility"]);
                    $listeProjet->setDateEnregistrement($date);
                    $this->em->persist($listeProjet);
                $this->em->flush();
                $nombre++;

                /** On calcul le nombre de projet public et privé */
                if ($projet['visibility'] == 'public') {
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

        /** On met à jour la table propriétés */
        $map = ['projet_bd' => $nombre, 'projet_sonar' => $nombre,
                'date_modification_projet' => $date];
        $r = $propertiesRepository->updatePropertiesProjet($map);
        if ($r['code'] != 200) {
            return new JsonResponse([
                'type' => 'alert',
                'reference' => static::$reference, 'code' => $r['code'],
                'message' => $r['erreur']], Response::HTTP_OK);
        }

        /** on renvoie les résultats */
        $message = "Mise à jour de la liste des projets effectuée.";

        return new JsonResponse(
            ['code' => 200,
            'reference' => static::$reference, 'type' => 'success',
            'message' => $message,'nombre' => $nombre,
            'public' => $public, 'private' => $private,
            'empty_tags' => $emptyTags], Response::HTTP_OK);
    }

    /**
     * [Description for accueilProjetTags]
     *
     * @return JsonResponse
     *
     * Created at: 29/07/2024 20:51:02 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/accueil/tags', name: 'accueil_projet_tags', methods: ['POST'])]
    public function accueilProjetTags(): JsonResponse
    {
        /** On instancie l'EntityRepository */
        $listeProjetRepository = $this->em->getRepository(ListeProjet::class);

        /** On vérifie si l'utilisateur à un rôle Collecte ? */
        if (!$this->isGranted('ROLE_COLLECTE')) {
            return new JsonResponse([
                'type'=>'warning', 'code' => 403,
                'reference' => static::$reference,
                'message' => static::$erreur403], Response::HTTP_OK);
        }

        $tag = $listeProjetRepository->countListeProjetTags();
        if ($tag['code'] != 200) {
            return new JsonResponse([
                'type' => 'alert',
                'reference' => static::$reference, 'code' => $tag['code'],
                'message' => $tag['erreur']], Response::HTTP_OK);
        }

        return new JsonResponse(
            ['code' => 200, 'nombre_tag' => $tag['nombre'][0]['tag'] ?? 0], Response::HTTP_OK);
    }

}
