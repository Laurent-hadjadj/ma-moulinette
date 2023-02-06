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

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;

class ProfilController extends AbstractController
{

  /**
   * [Description for __construct]
   *
   * @param  private
   *
   * Created at: 15/12/2022, 22:14:50 (Europe/Paris)
   * @author     Laurent HADJADJ <laurent_h@me.com>
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
   * @author     Laurent HADJADJ <laurent_h@me.com>
   * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
   */
  #[Route('/profil', name: 'profil')]
  public function index(): Response
  {
    /** On récupère la liste des profiles; */
    $sql = "SELECT name as profil, language_name as langage,
      active_rule_count as regle, rules_update_at as date, is_default as actif
      FROM profiles";

    $select = $this->em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();
    $liste = $select->fetchAllAssociative();

    return $this->render('profil/index.html.twig',
      [
        "liste" => $liste,
        "version" => $this->getParameter("version"), "dateCopyright" => \date("Y")
      ]
    );
  }
}
