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
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;


use App\Entity\ListeProjet;
use App\Entity\Properties;
use App\Service\ClientService;
use App\Service\UrlBuilderService;

/**
 * [Description ApiAccueilController]
 */
class ApiAccueilController extends AbstractController
{
    /** Définition des constantes */
    private static $sonarUrl = "sonar.url";
    private static $europeParis = "Europe/Paris";
    private static $erreur404 = "Je n'ai pas trouvé de projets sur le serveur SonarQube (Erreur 404).";

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
        private ClientService $client,
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
    public function apiSonarStatus(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/status");

        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/system/status'
        );

        /** On appel le client http */
        $result = $this->client->httpSonarQube($url);

        if ($result['code'] !== 200) {
            $this->logger->critical('[Accueil] 🔴 SonarQube indisponible', [
                'code' => $result['code'],
                'url' => $url,
                'erreur' => $result['erreur'] ?? null
            ]);

            return new JsonResponse([
                'code' => $result['code'],
                'type' => 'critical',
                'message' => "Le serveur SonarQube n'est pas disponible (Erreur {$result['code']}).",
                'trace' => $result['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->debug('[Accueil] 🛠️ SonarQube est UP', $result);
        return new JsonResponse([
            'code' => $result['code'],
            'result' => $result
        ], Response::HTTP_OK);
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
    #[Route('/api/accueil/projet', name: 'accueil_projet_liste', methods: ['POST'], defaults: ['role' => 'ROLE_COLLECTE'])]
    #[IsGranted('ROLE_COLLECTE')]
    public function accueilProjetListe(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/accueil/projet");

        /** On instancie l'EntityRepository */
        $listeProjetRepos = $this->em->getRepository(ListeProjet::class);
        $propertiesRepos = $this->em->getRepository(Properties::class);

        /** Sécurisation de l'URL */
        $url = $this->urlBuilder->build(
            $this->getParameter(static::$sonarUrl),
            '/api/components/search_projects',
            ['ps' => 500]
        );

        /** On appel le client http */
        $this->logger->info('[Accueil-projet] ℹ️ Récupération des projets SonarQube', ['url' => $url]);
        $result = $this->client->httpSonarQube($url);

        if ($result['code'] != 200) {
            $this->logger->error("[Accueil-projet] ❌ Erreur de l'appel à SonarQube.", [
                'code' => $result['code'],
                'url' => $url,
                'erreur' => $result['erreur'] ?? null
            ]);

            return new JsonResponse([
                'code' => $result['code'],
                'type' => 'alert',
                'message' => "Une erreur est survenue lors de la recherche des projets depuis le serveur SonarQube (Erreur {$result['code']}).",
                'trace' => $result['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        /** On créé un objet DateTimeImmutable */
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        /** On, initialiser les variables  */
        $public = $private = $emptyTags = $nombre = 0;

        /** On vérifie que SonarQube a au moins 1 projet */
        if (array_key_exists('json', $result) && empty($result['json']['components'])){
            $this->logger->warning('[Accueil-projet] ⚠️ Aucun projet Sonar trouvé.');

            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => "Aucun projet sonar trouvé (Erreur 404).",
                'trace' => static::$erreur404
            ], Response::HTTP_OK);
        }

        /** On supprime les données de la table avant d'importer les données. */
        $delete = $listeProjetRepos->deleteListeProjet();
        if ($delete['code'] !== 200) {
            $this->logger->error('[Accueil-projet] ❌ Échec de la requête deleteListeProjet.', [
                'code' => $delete['code'],
                'erreur' => $delete['erreur'] ?? null
            ]);

            return new JsonResponse([
                'code' => $delete['code'],
                'type' => 'alert',
                'message' => "La suppression de la liste des projets a échouée ({$delete['code']}).",
                'trace' => $delete['erreur'] ?? null
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
                $this->logger->debug('🛠️ [Accueil-projet] Projet ignoré (SVN)', ['key' => $projet['key']]);
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

        $r = $propertiesRepos->updatePropertiesProjet($map);

        if ($r['code'] != 200) {
            $this->logger->error('[Accueil-projet] ❌ Échec de la requête updatePropertiesProjet.', [
                'code' => $r['code'],
                'map' => $map ?? null,
                'erreur' => $r['erreur'] ?? null
            ]);

            return new JsonResponse([
                'code' => $r['code'],
                'type' => 'alert',
                'message' => "La mise à jour des méta-données du projet a échouée ({$r['code']}).",
                'trace' => $r['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Accueil-projet] ℹ️ Mise à jour des projets terminée', [
            'nombre' => $nombre,
            'public' => $public,
            'private' => $private,
            'sans_tags' => $emptyTags
        ]);

        return new JsonResponse([
                'code' => 200,
                'type' => 'success',
                'message' => "Mise à jour de la liste des projets effectuée.",
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
    #[Route('/api/accueil/tags', name: 'accueil_projet_tags', methods: ['POST'], defaults: ['role' => 'ROLE_COLLECTE'])]
    #[IsGranted('ROLE_COLLECTE')]
    public function accueilProjetTags(Request $request): JsonResponse
    {
        $this->logger->info("[API] 📥 Requête reçue sur /api/accueil/tags");

        /** On instancie l'EntityRepository */
        $listeProjetRepos = $this->em->getRepository(ListeProjet::class);

        $this->logger->info('[Accueil] ℹ️ Comptage des tags des projets.');
        $tag = $listeProjetRepos->countListeProjetTags();

        if ($tag['code'] != 200) {
            $this->logger->error('[Accueil] ❌ Échec de la requête countListeProjetTags.', [
                'code' => $tag['code'],
                'erreur' => $tag['erreur'] ?? null
            ]);

            return new JsonResponse([
                'code' => $tag['code'],
                'type' => 'alert',
                'message' =>"Le dénombrement des tags de projets n'a pas abouti ({$tag['code']}).",
                'trace' => $tag['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $this->logger->debug('[Accueil] 🛠️ Nombre de tags comptés', ['tags' => $tag['nombre'][0]['tag'] ?? 0]);
        return new JsonResponse([
            'code' => 200,
            'nombre_tag' => $tag['nombre'][0]['tag'] ?? 0
        ], Response::HTTP_OK);
    }

}
