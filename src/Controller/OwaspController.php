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

class OwaspController extends AbstractController
{

  /**
   * index
   *
   * @return void
   */
  #[Route('/owasp', name: 'owasp')]
  public function index()
  {
    return $this->render('owasp/index.html.twig',
       [
          "serveur" => $this->getParameter("sonar.url"),
          "version" => $this->getParameter("version"), "dateCopyright" => \date("Y")
       ]
    );
  }
}
