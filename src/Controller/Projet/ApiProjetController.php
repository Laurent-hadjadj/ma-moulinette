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

namespace App\Controller\Projet;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Gestion de accès aux API */
use Symfony\Component\HttpFoundation\JsonResponse;

/** Securité */
use Symfony\Bundle\SecurityBundle\Security;

use Doctrine\ORM\EntityManagerInterface;

/**
 * [Description ApiProjetController]
 */
class ApiProjetController extends AbstractController
{
    /** Définition des constantes */
    public static $regex = "/\s+/u";
    public static $erreurMavenKey = "La clé maven est vide!";
    public static $reference = "<strong>[PROJET-002]</strong>";
    public static $message = "Vous devez avoir le rôle COLLECTE pour réaliser cette action.";

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 21:25:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em
    ) {
        $this->em = $em;
    }

    /**
     * [Description for favori]
     * Change le statut du favori pour un projet
     * http://{url}/api/favori?{key}
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:27:08 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/favori', name: 'favori', methods: ['GET'])]
    public function favori(Security $security, Request $request): response
    {
        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();
        $courriel = $security->getUser()->getCourriel();

        /** oN créé un objet réponse */
        $response = new JsonResponse();

        /** On on vérifie si on a activé le mode test */
        if (is_null($request->get('mode'))) {
            $mode = "null";
        } else {
            $mode = $request->get('mode');
        }

        /** On bind les variables */
        $mavenKey = $request->get('mavenKey');

        /** On teste si la clé est valide */
        if ($mavenKey === "null" && $mode === "TEST") {
            return $response->setData([
              "mode" => $mode, "mavenKey" => $mavenKey,
              "message" => static::$erreurMavenKey, Response::HTTP_BAD_REQUEST]);
        }

        /** On regarde si la projet est dans les favoris */
        $isFavori = in_array($mavenKey, $preference['favori']);

        /**
         * On le supprime de la liste des favoris s'il exsite dans les préferences
         * Sinon on l'ajoute
         */

        /** On récupéres les préférences */
        $statut = $preference['statut'];
        $projet = $preference['projet'];
        $favori = $preference['favori'];
        $bookmark = $preference['bookmark'];

        if ($isFavori) {
            /** on supprime le projet de la liste */
            $nouvelleListeFavori = array_diff($favori, [$mavenKey]);

            $statut['favori'] = false;

            /** On met à jour l'objet. */
            $jarray = json_encode([
              'statut' => $statut,
              'projet' => $projet,
              'favori' => $nouvelleListeFavori,
              'bookmark' => $bookmark
            ]);

            /** On met à jour les préférences. */
            $sql = "UPDATE utilisateur
        SET preference = '$jarray'
        WHERE courriel='$courriel';";
            $trim = trim(preg_replace(static::$regex, " ", $sql));
            $exec = $this->em->getConnection()->prepare($trim)->executeQuery();
            if ($mode !== 'TEST') {
                $exec->fetchAllAssociative();
            }
            $statut = 0;
        } else {
            /** On ajoute le projet à la liste */
            array_push($preference['favori'], $mavenKey);
            $statut['favori'] = true;

            /** On met à jour l'objet. */
            $jarray = json_encode([
              'statut' => $statut,
              'projet' => $projet,
              'favori' => $preference['favori'],
              'bookmark' => $bookmark
            ]);

            /** On met à jour les préférences. */
            $sql = "UPDATE utilisateur
        SET preference = '$jarray'
        WHERE courriel='$courriel';";
            $trim = trim(preg_replace(static::$regex, " ", $sql));
            $exec = $this->em->getConnection()->prepare($trim)->executeQuery();
            if ($mode !== 'TEST') {
                $exec->fetchAllAssociative();
            }
            $statut = 1;
        }

        return $response->setData(["mode" => $mode, "statut" => $statut, Response::HTTP_OK]);
    }

    /**
     * [Description for favoriCheck]
     * Récupère le statut d'un favori. Le
     * favori est TRUE ou FALSE ou null
     * http://{url}/api/favori/check={key}
     *
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:28:07 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/favori/check', name: 'favori_check', methods: ['GET'])]
    public function favoriCheck(Security $security, Request $request): response
    {
        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();

        /** oN créé un objet réponse */
        $response = new JsonResponse();
        $mavenKey = $request->get('mavenKey');

        $favori = in_array($mavenKey, $preference['favori']);
        return $response->setData(["favori" => $favori, Response::HTTP_OK]);
    }

    /**
     * [Description for liste_projet]
     * Récupère la liste des projets nom + clé pour une équipe
     * http://{url}}/api/liste/projet
     *
     * @param Security $security
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:28:51 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/liste/projet', name: 'liste_projet', methods: ['GET'])]
    public function liste_projet(Security $security): response
    {
        /** On créé un objet response */
        $response = new JsonResponse();

        /* On bind les informations utilisateur */
        $equipes = $security->getUser()->getEquipe();

        /** Si l'utilisateur n'est pas rattaché à une équipe on ne charge rien */
        if (empty($equipes)) {
            /** On envoi un message à l'utilisateur */
            $reference2 = "<strong>[PROJET-003]</strong>";
            $message2 = "Vous devez être rattaché à une équipe.";
            $type = "alert";
            return $response->setData(["reference" => $reference2, "message" => $message2,
              "type" => $type, Response::HTTP_OK]);
        }

        /** On recherche les projets pour les équipes rattaché à l'utilisateur */
        $in = '';
        foreach ($equipes as $equipe) {
            if ($equipe !== '@TEST' || $equipe !== 'null') {
                /** On met en minuscule */
                $minus = trim(strtolower($equipe));
                /** On construit la clause in et on remplace les espaces par des tirets  */
                $in = $in."json_each.value LIKE '".preg_replace('/\s+/', '-', $minus)."%' OR ";
            }
        }

        /** On supprime le dernier OR */
        $inTrim = rtrim($in, " OR ");

        /** On construit la requête de selection des projets en fonction de(s) (l')équipes */
        $sql = "SELECT DISTINCT liste_projet.maven_key as id, liste_projet.name as text
          FROM liste_projet, json_each(liste_projet.tags)
          WHERE $inTrim";
        $trim = trim(preg_replace(static::$regex, " ", $sql));
        $exec = $this->em->getConnection()->prepare($trim)->executeQuery();
        $projets = $exec->fetchAllAssociative();

        /** j'ai pas trouvé de projet pour cette équipe. */
        if (empty($projets)) {
            $reference3 = "<strong>[PROJET-004]</strong>";
            $message3 = "Je n'ai pas trouvé de projets pour ton équipe.";
            $type = "warning";
            return $response->setData(["reference" => $reference3, "message" => $message3,
              "type" => $type, Response::HTTP_OK]);
        }

        return $response->setData(["projet" => $projets, Response::HTTP_OK]);
    }

}
