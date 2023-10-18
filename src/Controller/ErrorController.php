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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/** Exception */
use Symfony\Component\ErrorHandler\Exception\FlattenException;

/** TWIG */
use Twig\Environment;

class ErrorController extends AbstractController
{

  #[Route('/error', name: 'error', methods: ['GET'])]
  public function show(FlattenException $exception, Environment $env): Response
  {
    /** On affiche la page correspondant au code HTTP */
    $view = "bundles/TwigBundle/Exception/error{$exception->getStatusCode()}.html.twig";
    if (!$env->getLoader()->exists($view)) {
      $view = "bundles/TwigBundle/Exception/error.html.twig";
    }

    /** On affiche la page d'erreur */
    return $this->render($view);
  }
}
