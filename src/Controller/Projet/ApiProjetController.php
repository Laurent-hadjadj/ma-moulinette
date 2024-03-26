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
use App\Entity\Main\Utilisateur;
use App\Entity\Main\ListeProjet;

/**
 * [Description ApiProjetController]
 */
class ApiProjetController extends AbstractController
{
    /** Définition des constantes */
    public static $removeReturnline = "/\s+/u";
    public static $reference = "<strong>[PROJET-002]</strong>";
    public static $message = "Vous devez avoir le rôle COLLECTE pour réaliser cette action.";
    public static $erreur400 = "La requête est incorrecte (Erreur 400).";

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
    #[Route('/api/favori', name: 'favori', methods: ['POST'])]
    public function favori(Security $security, Request $request): response
    {
        /** On instancie l'entityRepository */
        $utilisateurEntity = $this->em->getRepository(Utilisateur::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'mode') || !property_exists($data, 'maven_key') ) {
            return $response->setData(['data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                                        'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();
        $courriel = $security->getUser()->getCourriel();

        $map=['maven_key'=>$data->maven_key, 'courriel'=>$courriel];
        $request = $utilisateurEntity->updateUtilisateurPreferenceFavori($data->mode, $preference, $map);
        if ($request['code']!=200) {
            return $response->setData(['code' => $request['code'], Response::HTTP_OK]);
        }

        return $response->setData(['mode' => $data->mode, 'code'=>200, 'statut' => $request['statut'], Response::HTTP_OK]);
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
    #[Route('/api/favori/check', name: 'favori_check', methods: ['POST'])]
    public function favoriCheck(Security $security, Request $request): response
    {
        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'mode') || !property_exists($data, 'maven_key') ) {
            return $response->setData(['data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                                        'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();

        $favori = in_array($data->mavenKey, $preference['favori']);
        return $response->setData(['favori' => $favori, Response::HTTP_OK]);
    }

    /**
     * [Description for liste_projet]
     * Récupère la liste des projets nom + clé pour une équipe
     * http://{url}}/api/liste/projet
     *
     * @param Security $security
     * @param Request $request
     *
     * @return response
     *
     * Created at: 15/12/2022, 21:28:51 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/api/liste/projet', name: 'liste_projet', methods: ['POST'])]
    public function liste_projet(Security $security, Request $request): response
    {
        /** On instancie l'entityRepository */
        $listeProjetEntity = $this->em->getRepository(ListeProjet::class);

        /** On décode le body */
        $data = json_decode($request->getContent());

        /** On crée un objet de reponse JSON */
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if ($data === null || !property_exists($data, 'mode') || !property_exists($data, 'maven_key') ) {
            return $response->setData(['data'=>$data,'code'=>400, 'type'=>'alert','reference'=> static::$reference,
                                        'message'=> static::$erreur400, Response::HTTP_BAD_REQUEST]);
        }

        /* On bind les informations utilisateur */
        $equipes = $security->getUser()->getEquipe();

        /** Si l'utilisateur n'est pas rattaché à une équipe on ne charge rien */
        if (empty($equipes)) {
            /** On envoi un message à l'utilisateur */
            $reference2 = '<strong>[PROJET]</strong>';
            $message2 = "Vous devez être rattaché à une équipe (erreur 406)";
            $type = 'alert';
            return $response->setData(['mode'=>$data->mdoe, 'code'=>406, 'reference' => $reference2,
                    'message' => $message2, 'type' => $type, Response::HTTP_OK]);
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
        $map=['clause_where'=>$inTrim];
        $request = $listeProjetEntity->selectListeProjetByEquipe($data->mode,$map);
        if ($request['code']!=200) {
            return $response->setData(['code' => $request['code'], Response::HTTP_OK]);
        }

        $projets = $request['liste'];

        /** j'ai pas trouvé de projet pour cette équipe. */
        if (empty($projets)) {
            $reference3 = '<strong>[PROJET]</strong>';
            $message3 = "Je n'ai pas trouvé de projets pour ton équipe (erreur 406).";
            $type = 'warning';
            return $response->setData(['mode'=>$data->mode, 'code'=>406, 'reference' => $reference3, 'message' => $message3,
                'type' => $type, Response::HTTP_OK]);
        }

        return $response->setData(['mode'=>$data->mode, 'code'=>200, 'projet' => $projets, Response::HTTP_OK]);
    }

}
