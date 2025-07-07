<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2024-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;

/**
 * [Description DefaultController]
 */
class DefaultController extends AbstractController
{
    public function chromeDevtools(): Response
    {
        return new Response('{}', Response::HTTP_OK, ['Content-Type' => 'application/json']);
    }
}
