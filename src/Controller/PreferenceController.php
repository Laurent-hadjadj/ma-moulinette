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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Securité */
use Symfony\Component\Security\Core\Security;

/** Accès aux tables SLQLite*/
use Doctrine\ORM\EntityManagerInterface;

/** API */
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

/** Client HTTP */
use App\Service\Client;

class PreferenceController extends AbstractController
{
  public static $regex = "/\s+/u";

  /**
   * [Description for __construct]
   *
   * @param  private
   * @param  private
   *
   * Created at: 15/12/2022, 22:06:26 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct (private EntityManagerInterface $em)
      {
        $this->em = $em;
      }

  /**
   * [Description for apiPreferenceStatut]
   * On met à jur le statut pour la categorie
   *
   * @param Security $security
   * @param Client $client
   * @param Request $request
   *
   * @return Response
   *
   * Created at: 09/06/2023, 15:43:33 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/preference/statut', name: 'api_preference_statut', methods:'POST')]
  public function apiPreferenceStatut(Security $security, Client $client, Request $request): Response
  {
    /** On créé on objet de reponse HTTP */
    $response = new JsonResponse();

    /** On récupère le filtre de recherche */
    $data = json_decode($request->getContent());
    $mode=$data->mode;
    $etat=$data->statut;
    $categorie=$data->categorie;

    /** On récupère l'objet User du contexte de sécurité */
    $userSecurity=$security->getUser();
    $preference=$security->getUser()->getPreference();
    $courriel=$security->getUser()->getCourriel();

    /** On récupéres les préférences */
    $statut=$preference['statut'];
    $projet=$preference['projet'];
    $favori=$preference['favori'];
    $versions=$preference['vesions'];
    $bookmark=$preference['bookmark'];

    /** On change me statut pour la catégorie. */
    $statut[$categorie]=$etat;

    /** On met à jour l'objet. */
    $jarray=json_encode([
      'statut'=>$statut,
      'projet'=>$projet,
      'favori'=>$favori,
      'versions'=>$versions,
      'bookmark'=>$bookmark
      ]);

    /** On met à jour les préférences. */
    $sql = "UPDATE utilisateur
    SET preference = '$jarray'
    WHERE courriel='$courriel';";
    $trim=trim(preg_replace(static::$regex, " ", $sql));
    $exec=$this->em->getConnection()->prepare($trim)->executeQuery();
    if ($mode!=='TEST'){
      $e=$exec->fetchAll();
    }

    $data=['mode'=>$mode,'statut'=>$statut, 'categorie'=>$categorie,Response::HTTP_OK];
    return $response->setData($data);
  }

  /**
   * [Description for apiPreferenceCategorie]
   * Renvoi le statut et les préferences d'une catégorie
   *
   * @param Security $security
   * @param Client $client
   * @param Request $request
   *
   * @return Response
   *
   * Created at: 15/05/2023, 14:06:12 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/api/preference/categorie', name: 'api_preference_categorie', methods:'GET')]
  public function apiPreferenceCategori(Security $security, Client $client, Request $request): Response
  {
    /** On bind les arguments passés depuis l'URL */
    $mode=$request->get('mode');
    $categorie=$request->get('categorie');

    /** On récupère l'objet User du contexte de sécurité */
    $userSecurity=$security->getUser();
    $preference=$security->getUser()->getPreference();

    /** On crée un objet de reponse JSON */
    $response = new JsonResponse();

    $data=['mode'=>$mode,
            'statut'=>$preference['statut'], $categorie=>$preference[$categorie], Response::HTTP_OK];
    return $response->setData($data);
  }

  /**
   * [Description for index]
   *  Affiche la page des préférences
   *
   * @param Security $security
   * @param Client $client
   * @param Request $request
   *
   * @return Response
   *
   * Created at: 16/05/2023, 21:11:05 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/preferences', name: 'preferences', methods:'GET')]
  public function index(Security $security, Client $client, Request $request): Response
  {
    $mode=$request->get('mode');

    $response = new JsonResponse();

    /** On récupère les infos utilisateurs */
    $userSecurity=$security->getUser();
    /* On bind les informations utilisateur */
    $prenom=$security->getUser()->getPrenom();
    $nom=$security->getUser()->getNom();
    $avatar=$security->getUser()->getAvatar();
    $courriel=$security->getUser()->getCourriel();
    $roles=$security->getUser()->getRoles();
    $equipes=$security->getUser()->getEquipe();
    if (empty($equipes)){
      $equipes[0]="null";
    }

    $preferences=$security->getUser()->getPreference();
    /** Valeur par défaut */
    $descriptionProjet="Liste des projets à suivre.";
    $descriptionFavori="Liste des projets favoris.";
    $descriptionVersions="Liste des versions favorites.";
    $descriptionBookmark="Afficher le dernier projet.";

    $mesPreferences=[
      "projet"=>["option"=>"Projet", "description"=>$descriptionProjet, "statut"=>$preferences['statut']['projet']],
      "favori"=>["option"=>"Favori", "description"=>$descriptionFavori,
      "statut"=>$preferences['statut']['favori']],
      "versions"=>["option"=>"Versions","description"=>$descriptionFavori,
      "statut"=>$preferences['statut']['versions']],
      "bookmark"=>["option"=>"Bookmark", "description"=>$descriptionBookmark, "statut"=>$preferences['statut']['bookmark']]
    ];

    $versionAPP=$this->getParameter('version');
    $render=[
      'prenom'=>$prenom, 'nom'=>$nom, 'avatar'=>$avatar, 'courriel'=>$courriel,
      'roles'=>$roles, 'equipes'=>$equipes,
      'preferences'=>$mesPreferences,
      'version' => $versionAPP, 'dateCopyright' => \date('Y'),
      'mode'=>$mode, Response::HTTP_OK];

    if ($mode==="TEST"){
      return $response->setData($render);
    } else {
      return $this->render('preference/index.html.twig', $render);
    }
  }

}
