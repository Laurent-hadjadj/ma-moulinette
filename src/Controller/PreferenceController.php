<?php

/*
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2026.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common  CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller;

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\UserAgent\UserAgentTrackingFacade;

/**
 * [Description PreferenceController]
 */
class PreferenceController extends AbstractController
{
    use AppUserAware;

    /** Définition des constantes */
    private static string $erreur400 = "La requête est incorrecte (Erreur 400).";
    private static string $erreur500 = "Une erreur est survenue lors de la mise à jour des préférences (Erreur 500).";
    private static string $noData = 'Pas de données';

    /** Catégories autorisées pour la lecture/écriture des préférences */
    private const CATEGORIES_AUTORISEES = ['suivi_projet', 'favori_projet', 'favori_version'];

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:06:26 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        ParameterBagInterface $params,
        private UserAgentTrackingFacade $tracking
    ){
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');
    }

    /**
     * [Description for genericRender]
     *
     * @return array<int|string, mixed>
     *
     * Created at: 21/12/2024 21:31:52 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        return [
            'type_footer' => null,
            'logo_entreprise' => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long' => $this->marqueEntrepriseLong,
            'env' => $this->environnement,
            'version' => $this->version,
            'date_copyright' => $this->dateCopyright
        ];
    }

    /**
     * [Description for buildPreferenceJson]
     * Construit le JSON complet des préférences (mêmes clés que celles lues
     * partout ailleurs dans l'application : statut + suivi_projet/favori_projet/favori_version).
     *
     * @param array<string, mixed> $statut
     * @param array<int, mixed> $suiviProjet
     * @param array<int, mixed> $favoriProjet
     * @param array<int, mixed> $favoriVersion
     *
     * @return string|false
     *
     * Created at: 21/07/2026 07:56:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildPreferenceJson(array $statut, array $suiviProjet, array $favoriProjet, array $favoriVersion): string|false
    {
        return json_encode([
            'statut' => $statut,
            'suivi_projet' => $suiviProjet,
            'favori_projet' => $favoriProjet,
            'favori_version' => $favoriVersion,
        ]);
    }

    /**
     * [Description for updatePreference]
     * Met à jour la colonne 'preference' de l'utilisateur via une requête paramétrée.
     * Évite l'injection SQL en utilisant bindValue.
     *
     * @param string $jarray
     * @param string $courriel
     *
     * @return bool true si succès, false sinon
     *
     * Created at: 21/07/2026 07:56:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function updatePreference(string $jarray, string $courriel): bool
    {
        try {
            $stmt = $this->em->getConnection()->prepare(
                "UPDATE utilisateur SET preference = :preference WHERE courriel = :courriel"
            );
            $stmt->bindValue('preference', $jarray);
            $stmt->bindValue('courriel', $courriel);
            $stmt->executeStatement();
            return true;
        } catch (\Throwable $e) {
            $this->logger->critical('[Preference] 🔴 Échec de la mise à jour des préférences.', [
                'courriel' => $courriel,
                'exception' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * [Description for apiPreferenceStatut]
     * On met à jour le statut pour la catégorie
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 09/06/2023, 15:43:33 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/preference/statut', name: 'api_preference_statut', methods: 'POST')]
    public function apiPreferenceStatut(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** Validation de la requête */
        if (
            $data === null
            || !property_exists($data, 'statut')
            || !property_exists($data, 'category') || !is_string($data->category)
        ) {
            $this->logger->error("[Preference-Statut] ❌ Requête invalide : clé 'statut' ou 'category' manquante ou JSON mal formé.", [
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        $etat = $data->statut;
        $category = $data->category;

        /** Whitelist des catégories autorisées (évite l'écriture d'une clé arbitraire dans 'statut'). */
        if (!in_array($category, self::CATEGORIES_AUTORISEES, true)) {
            $this->logger->error("[Preference-Statut] ❌ Catégorie inconnue ou non autorisée.", [
                'category' => $category
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->appUser()->getPreference();
        $courriel = $this->appUser()->getCourriel();

        /** On récupère les préférences */
        $statut = $preference['statut'];
        $suiviProjet = $preference['suivi_projet'];
        $favoriProjet = $preference['favori_projet'];
        $favoriVersion = $preference['favori_version'];

        /** On change le statut pour la catégorie. */
        $statut[$category] = $etat;

        /** On met à jour l'objet. */
        $jarray = $this->buildPreferenceJson($statut, $suiviProjet, $favoriProjet, $favoriVersion);

        /** On met à jour les préférences via une requête paramétrée. */
        if ($jarray === false || !$this->updatePreference($jarray, (string) $courriel)) {
            return new JsonResponse([
                'code' => 500,
                'type' => 'error',
                'message' => self::$erreur500
            ], Response::HTTP_OK);
        }

        return new JsonResponse([
            'code' => 200,
            'statut' => $statut,
            'category' => $category
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiPreferenceFavoriDelete]
     * On supprime un favori de la liste
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 12/06/2023, 14:34:11 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/preference/favori/delete', name: 'api_preference_favori_delete', methods: 'POST')]
    public function apiPreferenceFavoriDelete(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** Validation de la requête */
        if ($data === null || !property_exists($data, 'mavenKey') || !is_string($data->mavenKey)) {
            $this->logger->error("[Preference-FavoriDelete] ❌ Requête invalide : clé 'mavenKey' manquante ou JSON mal formé.", [
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        $mavenKey = $data->mavenKey;

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->appUser()->getPreference();
        $courriel = $this->appUser()->getCourriel();

        /** On récupère les préférences */
        $statut = $preference['statut'];
        $suiviProjet = $preference['suivi_projet'];
        $favoriVersion = $preference['favori_version'];

        /** On supprime le projet de la liste des favoris */
        $nouvelleListeFavori = array_values(array_diff($preference['favori_projet'], [$mavenKey]));

        /** On met à jour l'objet. */
        $jarray = $this->buildPreferenceJson($statut, $suiviProjet, $nouvelleListeFavori, $favoriVersion);

        /** On met à jour les préférences via une requête paramétrée. */
        if ($jarray === false || !$this->updatePreference($jarray, (string) $courriel)) {
            return new JsonResponse([
                'code' => 500,
                'type' => 'error',
                'message' => self::$erreur500
            ], Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

    /**
     * [Description for apiPreferenceVersionDelete]
     * On supprime la version de la liste des versions
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 12/06/2023, 14:35:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/preference/version/delete', name: 'api_preference_version_delete', methods: 'POST')]
    public function apiPreferenceVersionDelete(Request $request): JsonResponse
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** Validation de la requête */
        if (
            $data === null
            || !property_exists($data, 'index')
            || !property_exists($data, 'mavenKey') || !is_string($data->mavenKey)
            || !property_exists($data, 'version') || !is_string($data->version)
        ) {
            $this->logger->error("[Preference-VersionDelete] ❌ Requête invalide : clé 'index', 'mavenKey' ou 'version' manquante ou JSON mal formé.", [
                'payload' => $data ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        $index = $data->index;
        $mavenKey = $data->mavenKey;
        $version = $data->version;

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->appUser()->getPreference();
        $courriel = $this->appUser()->getCourriel();

        /** On récupère les préférences */
        $statut = $preference['statut'];
        $suiviProjet = $preference['suivi_projet'];
        $favoriProjet = $preference['favori_projet'];

        /**
         * Le modèle :
         * "favori_version":[ {"mavenKey":["version","version"]}, {"mavenKey":["version"]} ]
         */
        if (!isset($preference['favori_version'][$index][$mavenKey]) || !is_array($preference['favori_version'][$index][$mavenKey])) {
            $this->logger->warning("[Preference-VersionDelete] ⚠️ Index ou mavenKey introuvable dans les préférences.", [
                'courriel' => $courriel,
                'index' => $index,
                'mavenKey' => $mavenKey
            ]);
            return new JsonResponse([
                'code' => 404,
                'type' => 'warning',
                'message' => "La version demandée n'existe pas dans les préférences (Erreur 404)."
            ], Response::HTTP_OK);
        }

        /** On construit la nouvelle liste */
        $nouvelleListeVersion = array_values(array_diff($preference['favori_version'][$index][$mavenKey], [$version]));
        $nouvelleVersion = [$mavenKey => $nouvelleListeVersion];

        /** On reconstruit la liste des versions */
        $object = [];
        foreach ($preference['favori_version'] as $key => $value) {
            if ($key === $index) {
                $object[] = $nouvelleVersion;
            } else {
                $object[] = $value;
            }
        }

        /** On met à jour l'objet. */
        $jarray = $this->buildPreferenceJson($statut, $suiviProjet, $favoriProjet, $object);

        /** On met à jour les préférences via une requête paramétrée. */
        if ($jarray === false || !$this->updatePreference($jarray, (string) $courriel)) {
            return new JsonResponse([
                'code' => 500,
                'type' => 'error',
                'message' => self::$erreur500
            ], Response::HTTP_OK);
        }

        return new JsonResponse(['code' => 200], Response::HTTP_OK);
    }

    /**
     * [Description for apiPreferenceCategory]
     * Renvoi le statut et les préférences d'une catégorie
     *
     * @param Request $request
     *
     * @return JsonResponse
     *
     * Created at: 15/05/2023, 14:06:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/secure/preference/category', name: 'api_preference_category', methods: 'GET')]
    public function apiPreferenceCategory(Request $request): JsonResponse
    {
        /** On bind les arguments passés depuis l'URL */
        $category = $request->query->get('category');

        /** Whitelist des catégories autorisées (évite l'accès arbitraire à des clés du tableau). */
        if ($category === null || !in_array($category, self::CATEGORIES_AUTORISEES, true)) {
            $this->logger->error("[Preference-Category] ❌ Catégorie inconnue ou non autorisée.", [
                'category' => $category ?? self::$noData
            ]);
            return new JsonResponse([
                'code' => 400,
                'type' => 'error',
                'message' => self::$erreur400
            ], Response::HTTP_OK);
        }

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->appUser()->getPreference();

        return new JsonResponse([
            'code' => 200,
            'statut' => $preference['statut'],
            $category => $preference[$category]
        ], Response::HTTP_OK);
    }

    /**
     * [Description for index]
     *  Affiche la page des préférences
     *
     *
     * @return Response
     *
     * Created at: 16/05/2023, 21:11:05 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/preferences', name: 'preferences', methods: 'GET')]
    public function index(): Response
    {
        $this->tracking->track('PREFERENCE');
        /** On bind les informations utilisateur */
        $prenom = $this->appUser()->getPrenom();
        $nom = $this->appUser()->getNom();
        $avatar = $this->appUser()->getAvatar();
        $courriel = $this->appUser()->getCourriel();
        $roles = $this->appUser()->getRoles();
        $groupeUtilisateur = $this->appUser()->getGroupeUtilisateur();
        if (empty($groupeUtilisateur)) {
            $groupeUtilisateur = "Aucun";
        }
        $groupesFonctionnel = $this->appUser()->getListeGroupeFonctionnel();
        if (empty($groupesFonctionnel)) {
            $groupesFonctionnel[0] = "null";
        }

        $preferences = $this->appUser()->getPreference();
        /** Valeur par défaut */
        $descriptionSuiviProjet = "Liste des projets à suivre.";
        $descriptionFavoriProjet = "Liste des projets favoris.";
        $descriptionFavoriVersion = "Liste des versions favorites.";

        $mesPreferences = [
            "suivi_projet" => ["option" => "Projet", "description" => $descriptionSuiviProjet, "statut" => $preferences['statut']['suivi_projet']],
            "favori_projet" => [
                "option" => "Favori",
                "description" => $descriptionFavoriProjet,
                "statut" => $preferences['statut']['favori_projet']
            ],
            "favori_version" => [
                "option" => "Version",
                "description" => $descriptionFavoriVersion,
                "statut" => $preferences['statut']['favori_version']
            ],
        ];

        /** On charge le template du render */
        $render = $this->genericRender();
        $render['prenom'] = $prenom;
        $render['nom'] = $nom;
        $render['avatar'] = $avatar;
        $render['courriel'] = $courriel;
        $render['roles'] = $roles;
        $render['groupeUtilisateur'] = $groupeUtilisateur;
        $render['groupesFonctionnel'] = $groupesFonctionnel;
        /* Ne pas réaffecter $render['version'] : genericRender() y place la version applicative,
           utilisée par le bandeau (header.html.twig). */
        $render['preferences'] = $mesPreferences;
        $render['peut_voir_actuator'] = $this->isGranted('ROLE_ACTUATOR');
        return $this->render('preference/index.html.twig', $render);
    }
}
