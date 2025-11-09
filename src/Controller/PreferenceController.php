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

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\EntityManagerInterface;
use App\Service\ClientService;

class PreferenceController extends AbstractController
{
    public static $returnLine = "/\s+/u";

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:06:26 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        private ParameterBagInterface $params,
        private Security $security,
        private ClientService $client)
    {
        $this->params = $params;
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
     * @return array
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
    #[Route('/api/preference/statut', name: 'api_preference_statut', methods:'POST')]
    public function apiPreferenceStatut(Request $request): JsonResponse
    {
        /** On récupère le filtre de recherche */
        $data = json_decode($request->getContent());
        $etat = $data->statut;
        $categorie = $data->categorie;

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->security->getUser()->getPreference();
        $courriel = $this->security->getUser()->getCourriel();

        /** On récupères les préférences */
        $statut = $preference['statut'];
        $projet = $preference['projet'];
        $favori = $preference['favori'];
        $version = $preference['version'];
        $bookmark = $preference['bookmark'];

        /** On change le statut pour la catégorie. */
        $statut[$categorie] = $etat;

        /** On met à jour l'objet. */
        $jarray = json_encode([
            'statut' => $statut,
            'projet' => $projet,
            'favori' => $favori,
            'version' => $version,
            'bookmark' => $bookmark
        ]);

        /** On met à jour les préférences. */
        $sql = "UPDATE utilisateur
                SET preference = '$jarray'
                WHERE courriel='$courriel';";
        $trim = trim(preg_replace(static::$returnLine, " ", $sql));
        $exec = $this->em->getConnection()->prepare($trim)->executeQuery();

        $exec->fetchAllAssociative();


        $data = [
            'statut' => $statut,
            'categorie' => $categorie
        ];
        return new JsonResponse($data,Response::HTTP_OK);
    }

    /**
     * [Description for apiPreferenceFavoriDelete]
     * On supprime un favori de la liste
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 12/06/2023, 14:34:11 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/preference/favori/delete', name: 'api_preference_favori_delete', methods:'POST')]
    public function apiPreferenceFavoriDelete(Request $request): JsonResponse
    {
        /** On bind les arguments passés depuis l'URL */
        $data = json_decode($request->getContent());
        $mavenKey = $data->mavenKey;

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->security->getUser()->getPreference();
        $courriel = $this->security->getUser()->getCourriel();

        /** On récupères les préférences */
        $statut = $preference['statut'];
        $projet = $preference['projet'];
        $version = $preference['version'];
        $bookmark = $preference['bookmark'];

        /** On supprime le projet de la liste */
        $nouvelleListeFavori = array_diff($preference['favori'], [$mavenKey]);

        /** On met à jour l'objet. */
        $jarray = json_encode([
            'statut' => $statut,
            'projet' => $projet,
            'favori' => $nouvelleListeFavori,
            'version' => $version,
            'bookmark' => $bookmark
        ]);

        /** On met à jour les préférences. */
        $sql = "UPDATE utilisateur
                SET preference = '$jarray'
                WHERE courriel='$courriel';";
        $trim = trim(preg_replace(static::$returnLine, " ", $sql));
        $exec = $this->em->getConnection()->prepare($trim)->executeQuery();
        $exec->fetchAllAssociative();

        return new JsonResponse(['200'], Response::HTTP_OK);
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
    #[Route('/api/preference/version/delete', name: 'api_preference_version_delete', methods:'POST')]
    public function apiPreferenceVersionDelete(Request $request): JsonResponse
    {
        /** On bind les arguments passés depuis l'URL */
        $data = json_decode($request->getContent());
        $index = $data->index;
        $mavenKey = $data->mavenKey;
        $version = $data->version;

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->security->getUser()->getPreference();
        $courriel = $this->security->getUser()->getCourriel();

        /** On récupères les préférences */
        $statut = $preference['statut'];
        $projet = $preference['projet'];
        $favori = $preference['favori'];
        $bookmark = $preference['bookmark'];

        /** Le modèle
         * "version":[ {"mavenKey":["version","version"]}, {"mavenKey":["version"]} ],
        */

        /** On construit la nouvelle liste */
        $nouvelleListeVersion = array_diff($preference['version'][$index][$mavenKey], [$version]);
        $nouvelleVersion = [ $mavenKey => $nouvelleListeVersion ];
        /** On reconstruit la liste des versions */
        $object = [];
        foreach ($preference['version'] as $key => $value) {
            if ($key === $index) {
                array_push($object, $nouvelleVersion);
            } else {
                array_push($object, $value);
            }
        }

        /** On met à jour l'objet et on vire les \. */
        $jarray = stripslashes(
            json_encode([
            'statut' => $statut,
            'projet' => $projet,
            'favori' => $favori,
            'version' => $object,
            'bookmark' => $bookmark
        ])
        );

        /** On met à jour les préférences. */
        $sql = "UPDATE utilisateur
                SET preference = '$jarray'
                WHERE courriel='$courriel';";
        $trim = trim(preg_replace(static::$returnLine, " ", $sql));
        $this->em->getConnection()->prepare($trim)->executeStatement();

        /** On crée un objet de response JSON 'o'=>$object,'n'=>$nouvelleListeVersion,'t'=>$trim */
        return new JsonResponse([
            'code' => 200
        ], Response::HTTP_OK);
    }

    /**
     * [Description for apiPreferenceCategorie]
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
    #[Route('/api/preference/categorie', name: 'api_preference_categorie', methods:'GET')]
    public function apiPreferenceCategorie(Request $request): JsonResponse
    {
        /** On bind les arguments passés depuis l'URL */
        $categorie = $request->get('categorie');

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $this->security->getUser()->getPreference();

        $data = [
            'code' => 200,
            'statut' => $preference['statut'],
            $categorie => $preference[$categorie]
        ];
        return new JsonResponse($data, Response::HTTP_OK);
    }

    /**
     * [Description for index]
     *  Affiche la page des préférences
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 16/05/2023, 21:11:05 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/preferences', name: 'preferences', methods:'GET')]
    public function index(): Response
    {
        /** On bind les informations utilisateur */
        $prenom = $this->security->getUser()->getPrenom();
        $nom = $this->security->getUser()->getNom();
        $avatar = $this->security->getUser()->getAvatar();
        $courriel = $this->security->getUser()->getCourriel();
        $roles = $this->security->getUser()->getRoles();
        $groupes = $this->security->getUser()->getGroupe();
        if (empty($groupes)) {
            $groupes[0] = "null";
        }

        $preferences = $this->security->getUser()->getPreference();
        /** Valeur par défaut */
        $descriptionSuiviProjet = "Liste des projets à suivre.";
        $descriptionFavoriProjet = "Liste des projets favoris.";
        $descriptionFavoriVersion = "Liste des versions favorites.";
        $descriptionBookmark = "Afficher le dernier projet.";

        $mesPreferences = [
            "suivi_projet" => ["option" => "Projet", "description" => $descriptionSuiviProjet, "statut" => $preferences['statut']['suivi_projet']],
            "favori_projet" => ["option" => "Favori", "description" => $descriptionFavoriProjet,
            "statut" => $preferences['statut']['favori_projet']],
            "favori_version" => ["option" => "Version","description" => $descriptionFavoriVersion,
            "statut" => $preferences['statut']['favori_version']],
            "bookmark" => ["option" => "Bookmark", "description" => $descriptionBookmark, "statut" => $preferences['statut']['bookmark']]
        ];

         /** On charge le template du render */
        $render = static::genericRender();
        $render['prenom'] = $prenom;
        $render['nom'] = $nom;
        $render['avatar'] = $avatar;
        $render['courriel'] = $courriel;
        $render['roles'] = $roles;
        $render['groupes'] = $groupes;
        $render['preferences'] = $mesPreferences;
        $render['version'] = $mesPreferences;
        return $this->render('preference/index.html.twig', $render);
    }

}
