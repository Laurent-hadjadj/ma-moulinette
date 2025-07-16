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

namespace App\Controller\Projet;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Utilisateur;
use App\Entity\ListeProjet;

/**
 * [Description ApiProjetController]
 */
class ApiProjetController extends AbstractController
{
    /** Définition des constantes */
    public static $reference = "<strong>[Projet]</strong> ";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";
    public static $erreur404 = "Vous devez être rattaché à une équipe (Erreur 404).";
    public static $erreur406 = "Je n'ai pas trouvé de projets pour ton équipe. ".
    "Vérifie le nom du tag utilisé dans SonarQube (Erreur 406).";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger
    ) {
    }

    /**
     * [Description for favori]
     * Change le statut du favori pour un projet
     * http://{url}/api/favori?{key}
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:27:08 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/favori', name: 'favori', methods: ['POST'])]
    public function favori(Security $security, Request $request): JsonResponse
    {
        /** On instancie l'entityRepository */
        $utilisateurRepository = $this->em->getRepository(Utilisateur::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'maven_key') ) {
            return new JsonResponse(
                ['data' => $data,'code' => 400, 'type' => 'alert',
                'message'=> static::$reference . static::$erreur400], Response::HTTP_OK);
        }

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();
        $courriel = $security->getUser()->getCourriel();

        $map = [ 'maven_key' => $data->maven_key, 'courriel' => $courriel];
        $request = $utilisateurRepository->updateUtilisateurFavoriProjet($preference, $map);
        if ($request['code'] != 200) {
            return new JsonResponse([
                'code' => $request['code'], 'type' => 'alert',
                'message'=> static::$reference . $request['erreur']], Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200, 'statut' => $request['statut']], Response::HTTP_OK);
    }

    /**
     * [Description for favoriCheck]
     * Récupère le statut d'un favori. Le favori est TRUE ou FALSE ou null
     * http://{url}/api/favori/check={key}
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:28:07 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/favori/check', name: 'favori_check', methods: ['POST'])]
    public function favoriCheck(Security $security, Request $request): JsonResponse
    {
        $user = $security->getUser();

        if (!$user) {
            $this->logger->error('[Favori Check] Aucun utilisateur connecté.');
            return new JsonResponse([
                'code' => 401,
                'type' => 'alert',
                'message' => static::$reference . 'Utilisateur non authentifié (Erreur 401).'
            ], Response::HTTP_OK);
        }

        $username = $user->getUserIdentifier();
        $rawContent = $request->getContent();
        $data = json_decode($rawContent);

        if ($data === null || !property_exists($data, 'maven_key') || !is_string($data->maven_key)) {
            $this->logger->warning('[Favori Check] Requête JSON invalide ou clé maven_key absente.', [
                'utilisateur' => $username,
                'payload' => $rawContent
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'alert',
                'message'=> static::$reference . static::$erreur400,
            ], Response::HTTP_OK);
        }

        $preference = $user->getPreference();
        if (!isset($preference['favori_projet']) || !is_array($preference['favori_projet'])) {
            $this->logger->error('[Favori Check] Clé "favori_projet" manquante ou invalide dans les préférences.', [
                'utilisateur' => $username,
                'preferences' => $preference
            ]);
            return new JsonResponse([
                'code' => 500,
                'type' => 'alert',
                'message' => static::$reference . "Impossible d'accéder aux favoris de l'utilisateur (Erreur 500)."
            ], Response::HTTP_OK);
        }

        $favori = in_array($data->maven_key, $preference['favori_projet']);

        $this->logger->info('[Favori Check] Favori vérifié avec succès.', [
            'utilisateur' => $username,
            'maven_key' => $data->maven_key,
            'est_favori' => $favori
        ]);

        return new JsonResponse([
            'code' => 200,
            'favori' => $favori
        ], Response::HTTP_OK);
    }

    /**
     * [Description for projet_liste]
     * Récupère la liste des projets nom + clé pour une équipe
     * http://{url}}/api/projet/liste
     *
     * @param Security $security
     *
     * @return JsonResponse
     *
     * Created at: 15/12/2022, 21:28:51 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/projet/liste', name: 'projet_liste', methods: ['POST'])]
    public function liste_projet(Security $security): JsonResponse
    {
        $user = $security->getUser();
        $username = $user ? $user->getUserIdentifier() : 'inconnu';

        $listeProjetRepos = $this->em->getRepository(ListeProjet::class);

        $groupes = $user->getEquipe();

        if (empty($groupes)) {
            $this->logger->warning("[Projet Liste] Aucun groupe associé à l'utilisateur.", [
                'utilisateur' => $username
            ]);

            return new JsonResponse([
                'code' => 404,
                'type' => 'alert',
                'message' => static::$reference . static::$erreur404
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Projet Liste] Groupes récupérés.', [
            'utilisateur' => $username,
            'groupes' => $groupes
        ]);

        $in = '';
        foreach ($groupes as $groupe) {
            if ($groupe !== 'null') {
                $minus = trim(strtolower($groupe));
                $in .= " tag LIKE '" . preg_replace('/\s+/', '-', $minus) . "%' OR ";
            }
        }

        $inTrim = rtrim($in, " OR ");
        $map = ['clause_where' => $inTrim];

        $this->logger->debug('[Projet Liste] Clause WHERE construite.', [
            'clause' => $inTrim
        ]);

        $requestListe = $listeProjetRepos->selectListeProjetByEquipe($map);

        if ($requestListe['code'] != 200) {
            $this->logger->error("[Projet Liste] Erreur lors de la récupération des projets.", [
                'utilisateur' => $username,
                'code' => $requestListe['code'],
                'erreur' => $requestListe['erreur'] ?? 'Erreur inconnue'
            ]);

            return new JsonResponse([
                'code' => $requestListe['code'],
                'type' => 'alert',
                'message' => static::$reference . "Une erreur s'est produite lors de la construction de la liste des projets (Erreur 500).",
                'trace' => $requestListe['erreur'] ?? null
            ], Response::HTTP_OK);
        }

        $projets = $requestListe['liste'];

        if (empty($projets)) {
            $this->logger->notice('[Projet Liste] Aucun projet trouvé pour les groupes spécifiés.', [
                'utilisateur' => $username,
                'groupes' => $groupes
            ]);

            return new JsonResponse([
                'code' => 406,
                'type' => 'warning',
                'message' => static::$reference . static::$erreur406
            ], Response::HTTP_OK);
        }

        $this->logger->info('[Projet Liste] Projets récupérés avec succès.', [
            'utilisateur' => $username,
            'nombre_projets' => count($projets)
        ]);

        return new JsonResponse([
            'code' => 200,
            'projet' => $projets
        ], Response::HTTP_OK);
    }
}
