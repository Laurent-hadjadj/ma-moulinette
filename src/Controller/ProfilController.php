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

class ProfilController extends AbstractController
{

  /**
   * index
   *
   * @param  mixed $em
   * @return void
   */
  #[Route('/profil', name: 'profil')]
  public function index(EntityManagerInterface $em)
  {
    // On récupère la liste des profiles;
    $sql = "SELECT name as profil, language_name as langage,
      active_rule_count as regle, rules_update_at as date, is_default as actif
      FROM profiles";

    $select = $em->getConnection()->prepare(trim(preg_replace("/\s+/u", " ", $sql)))->executeQuery();
    $liste = $select->fetchAllAssociative();

    return $this->render('profil/index.html.twig',
       [
          "liste" => $liste,
          "version" => $this->getParameter("version"), "dateCopyright" => \date("Y")
       ]
    );
  }
}
