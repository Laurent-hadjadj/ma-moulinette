<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Twig\Environment;
use App\Service\UserAgent\UserAgentTrackingFacade;

/**
 * [Description ErrorController]
 */
class ErrorController extends AbstractController
{
    public function __construct(
        private UserAgentTrackingFacade $tracking
    ) {}


    /**
     * [Description for show]
     *
     * @param FlattenException $exception
     * @param Environment $env
     *
     * @return Response
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
     */
    #[Route('/error', name: 'erreur', methods: ['GET'])]
    public function show(FlattenException $exception, Environment $env): Response
    {
        $this->tracking->track(sprintf('ERROR_%d', $exception->getStatusCode()));
        /** On affiche la page correspondant au code HTTP */
        $view = "bundles/TwigBundle/Exception/error{$exception->getStatusCode()}.html.twig";
        if (!$env->getLoader()->exists($view)) {
            $view = "bundles/TwigBundle/Exception/error.html.twig";
        }

        /** On affiche la page d'erreur */
        return $this->render($view);
    }
}
