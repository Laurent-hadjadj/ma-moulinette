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

namespace App\Controller\Profiling;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Response};
use Symfony\Component\Routing\Attribute\Route;
use App\Service\UserAgent\UserAgentTrackingFacade;

/**
 * [Description ProfilingController]
 */
class ProfilingController extends AbstractController
{
    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    private static string $erreur403 = "⚠️ Vous devez avoir le rôle 'BATCH' pour accéder à cette page (Erreur 403).";

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
     * Created at: 30/10/2024 08:21:04 (Europe/Paris)
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
     * [Description for profiling]
     * Page principale du dashboard Profiling.
     * Le JS côté client interroge les endpoints /api/profiling/* pour charger les données.
     *
     * @return Response
     *
     * Created at: 12/07/2026 10:08:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/traitement/profiling', name: 'profiling_dashboard', methods: ['GET'])]
    public function profiling(): Response
    {
        $this->tracking->track('PROFILING');

        $render = $this->genericRender();

        /** On vérifie que l'utilisateur a le ROLE_BATCH pour voir cette page. */
        if (!$this->isGranted('ROLE_BATCH')) {
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur403
            ]);
            return $this->render('profiling/index.html.twig', $render);
        }

        return $this->render('profiling/index.html.twig', $render);
    }
}
