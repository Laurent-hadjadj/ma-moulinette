<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Service\UserAgent\UserAgentTrackingFacade;

/**
 * [Description FooterController]
 */
class FooterController extends AbstractController
{

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    public function __construct(
        ParameterBagInterface $params,
        private UserAgentTrackingFacade $tracking
    ) {
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');
    }

    /**
     * [Description for genericRender]
     *
     * @return array<int|string, mixed>
     *
     * Created at: 21/12/2024 23:25:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        return [
            'type_footer' => null,
            'logo_entreprise' => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long' => $this->marqueEntrepriseLong,
            'env' => $this->environnement,
            'version' => $this->version,
            'date_copyright' => $this->dateCopyright
        ];
    }

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
        $this->tracking->track('FOOTER_PLAN_SITE');
        $render = $this->genericRender();
        return $this->render('footer/plan-du-site.html.twig', $render);
    }

    /**
     * [Description for mentionLegal]
     *
     * @return Response
     *
     * Created at: 03/02/2024 23:14:53 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/mention-legale', name: 'mention_legal')]
    public function mentionLegal(): Response
    {
        $this->tracking->track('FOOTER_MENTION_LEGAL');
        $render = $this->genericRender();
        $render['editeur'] = $this->getParameter('cgu.editeur');
        $render['siret'] = $this->getParameter('cgu.siret');
        $render['siren'] = $this->getParameter('cgu.siren');
        $render['numSiret'] = $this->getParameter('cgu.numero.siret');
        $render['numSiren'] = $this->getParameter('cgu.numero.siren');
        $render['urlSite'] = $this->getParameter('cgu.url.site');
        return $this->render('footer/mention-legal.html.twig', $render);
    }

    /**
     * [Description for donneesPersonnelles]
     *
     * @return Response
     *
     * Created at: 21/12/2024 23:32:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/donnees-personnelles', name: 'donnees_personnelles')]
    public function donneesPersonnelles(): Response
    {
        $this->tracking->track('FOOTER_DONNEES_PERSO');
        $render = $this->genericRender();
        $render['urlSite'] = $this->getParameter('cgu.url.site');
        return $this->render('footer/donnees-personnelles.html.twig', $render);
    }
}
