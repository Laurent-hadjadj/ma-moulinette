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

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;

class ProfilController extends AbstractController
{

  /**
   * [Description for __construct]
   *
   * Created at: 15/12/2022, 22:14:50 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  public function __construct(private EntityManagerInterface $em) {
      $this->em = $em;
  }

  /**
   * [Description for index]
   *
   * @return Response
   *
   * Created at: 15/12/2022, 22:14:55 (Europe/Paris)
   * @author    Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/profil', name: 'profil', methods: 'GET')]
  public function index(Request $request): Response
  {
    /** oN créé un objet réponse */
    $response = new JsonResponse();

    /** On vérifie si on a activé le mode test */
    if (is_null($request->get('mode'))) {
      $mode="null";
    } else {
      $mode = $request->get('mode');
    }

    /** On récupère la liste des profiles; */
    $sql = "SELECT name as profil, language_name as langage,
      active_rule_count as regle, rules_update_at as date, is_default as actif
      FROM profiles";

    $select = $this->em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();
    $liste = $select->fetchAllAssociative();

    /** On vérifie que la table n'est pas vide */
    if (empty($liste)){
      /** on met a jour */
    }

    $render =
      [ "mode"=>$mode, "liste" => $liste,
        "version" => $this->getParameter("version"),
        "dateCopyright" => \date("Y"),
        Response::HTTP_OK
      ];

    if ($mode=="TEST") {
      return $response->setData($render);
    } else {
      return $this->render('profil/index.html.twig', $render);
    }
  }
}
