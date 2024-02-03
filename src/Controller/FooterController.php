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
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

/**
 * [Description SecurityController]
 */
class FooterController extends AbstractController
{
    /**
     * [Description for planDuSite]
     *
     * @return Response
     *
     * Created at: 03/02/2024 23:14:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/plan-du-site', name: 'plan_du_site')]
    public function planDuSite(): Response
    {
        return $this->render('footer/plan-du-site.html.twig', [
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')
        ]);
    }

    /**
     * [Description for mentionLegale]
     *
     * @return Response
     *
     * Created at: 03/02/2024 23:14:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/mentions-legales', name: 'mention_legale')]
    public function mentionLegale(): Response
    {
        return $this->render('footer/mentions-legales.html.twig', [
            'editeur'=>$this->getParameter('cgu.editeur'),
            'adresse'=>$this->getParameter('cgu.adresse'),
            'siret'=>$this->getParameter('cgu.siret'),
            'siren'=>$this->getParameter('cgu.siren'),
            'numSiret'=>$this->getParameter('cgu.numero.siret'),
            'numSiren'=>$this->getParameter('cgu.numero.siren'),
            'directeurPublication'=>$this->getParameter('cgu.directeur.publication'),
            'sourceURL'=>$this->getParameter('cgu.source.url'),
            'sourceSCM'=>$this->getParameter('cgu.source.scm'),
            'hebergement'=>$this->getParameter('cgu.hebergement'),
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')
        ]);
    }

}
