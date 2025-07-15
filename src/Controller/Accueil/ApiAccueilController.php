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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\ListeProjet;
use App\Entity\Properties;
use App\Service\Client;
use App\Service\UrlBuilderService;

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
    public static $reference = '<strong>[Accueil]</strong> ';
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
        private UrlBuilderService $urlBuilder,
    ) {
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
        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/system/status'
        );

        $this->logger->info('[Accueil] Vérification du statut SonarQube', ['url' => $url]);

        /** On appel le client http */
        $result = $this->client->httpSonarQube($url);

        if ($result['code'] != 200) {
            $this->logger->error('[Accueil] SonarQube indisponible', $result);
            return new JsonResponse(['code' => $result['code'], 'erreur' => $result['erreur']], Response::HTTP_OK);
        }

        $this->logger->debug('[Accueil] SonarQube est UP', $result);
        return new JsonResponse(['code' => $result['code'], 'result' => $result]);
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
                'code' => 403,
                'type' => 'warning',
                'message' => static::$reference.static::$erreur403],
                Response::HTTP_OK);
        }

        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/components/search_projects',
            ['ps' => 500]
        );

        /** On appel le client http */
        $this->logger->info('[Accueil] Récupération des projets SonarQube', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        if ($result['code'] != 200) {
            $this->logger->error('[Accueil] Erreur appel SonarQube', $result);
            return new JsonResponse([
                'code' => $result['code'],
                'type' => 'alert',
                'message' => static::$reference . $result['erreur']],
                Response::HTTP_OK);
        }

        /** On créé un objet DateTimeImmutable */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        /** On, initialiser les variables  */
        $public = $private = $emptyTags = $nombre = 0;

        /** On vérifie que SonarQube a au moins 1 projet */
        if (array_key_exists('json', $result) && empty($result['json']['components'])){
            $this->logger->warning('[Accueil] Aucun projet Sonar trouvé');
            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => static::$reference . static::$erreur404],
                Response::HTTP_OK);
        }

        /** On supprime les données de la table avant d'importer les données. */
        $delete = $listeProjetRepository->deleteListeProjet();
        if ($delete['code'] != 200) {
            $this->logger->error('[Accueil] Échec suppression des anciens projets', $delete);
            return new JsonResponse([
                'code' => $delete['code'],
                'type' => 'alert',
                'message' => static::$reference . "Échec de l’exécution de la requête deleteListeProjet ({$delete['code']}).",
                'trace' => $delete['erreur']
            ], Response::HTTP_OK);
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
            if (strpos($projet['key'], '-SVN') !== false) {
                $this->logger->debug('[Accueil] Projet ignoré (SVN)', ['key' => $projet['key']]);
                continue;
            }
            $listeProjet = new ListeProjet();
            $listeProjet->setMavenKey($projet["key"]);
            $listeProjet->setName($projet["name"]);
            $listeProjet->setTags($projet["tags"]);
            $listeProjet->setVisibility($projet["visibility"]);
            $listeProjet->setDateEnregistrement($date);

            $this->em->persist($listeProjet);
            $nombre++;

            $projet['visibility'] === 'public' ? $public++ : $private++;
            if (empty($projet["tags"])) {
                $emptyTags++;
            }
        }

        /** On flush les enregistrements */
        $this->em->flush();

        /** On met à jour la table propriétés */
        $map = [
            'projet_bd' => $nombre,
            'projet_sonar' => $nombre,
            'date_modification_projet' => $date
        ];

        $r = $propertiesRepository->updatePropertiesProjet($map);

        if ($r['code'] != 200) {
            $this->logger->error('[Accueil] Échec updatePropertiesProjet', $r);
            return new JsonResponse([
                'code' => $r['code'],
                'type' => 'alert',
                'message' => static::$reference. "Échec de l’exécution de la requête updatePropertiesProjet ({$r['code']}).",
                'trace' => $r['erreur']
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Accueil] Mise à jour des projets terminée', [
            'nombre' => $nombre,
            'public' => $public,
            'private' => $private,
            'sans_tags' => $emptyTags
        ]);

        /** on renvoie les résultats */
        $message = "Mise à jour de la liste des projets effectuée.";

        return new JsonResponse(
            [
                'code' => 200,
                'type' => 'success',
                'message' => static::$reference.$message,
                'nombre' => $nombre,
                'public' => $public,
                'private' => $private,
                'empty_tags' => $emptyTags
            ], Response::HTTP_OK);
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
            $this->logger->warning('[Accueil] Accès refusé pour la consultation des tags');
            return new JsonResponse([
                'code' => 403,
                'type' => 'warning',
                'message' => static::$reference.static::$erreur403
                ], Response::HTTP_OK);
        }

        $this->logger->info('[Accueil] Comptage des tags de projets');
        $tag = $listeProjetRepository->countListeProjetTags();

        if ($tag['code'] != 200) {
            $this->logger->error('[Accueil] Échec countListeProjetTags', $tag);
            return new JsonResponse([
                'code' => $tag['code'],
                'type' => 'alert',
                'message' => static::$reference."Échec de l’exécution de la requête countListeProjetTags ({$tag['code']}).",
                'trace' => $tag['erreur']
            ], Response::HTTP_OK);
        }

        $this->logger->debug('[Accueil] Nombre de tags comptés', ['tags' => $tag['nombre'][0]['tag'] ?? 0]);
        return new JsonResponse([
            'code' => 200,
            'nombre_tag' => $tag['nombre'][0]['tag'] ?? 0
            ], Response::HTTP_OK);
    }

}
