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

declare(strict_types=1);

namespace App\Controller;

use App\EventSubscriber\DemoModeSubscriber;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Page « Action indisponible dans l'instance de démonstration ».
 *
 * Les pages refusées par le mode démo ({@see DemoModeSubscriber}) y sont redirigées : elle s'affiche avec le gabarit de
 * l'application (en-tête, pied de page, logo). La route est publique (`security.yaml`) et autorisée en mode démo
 * (`ModeDemo`) ; hors mode démo, elle reste accessible et sans effet.
 */
class DemoController extends AbstractController
{
    public function __construct(private readonly ParameterBagInterface $params)
    {
    }

    /**
     * Affiche la page d'information.
     *
     * @return Response
     *
     * Created at: 15/11/2026 22:40:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 or later (AGPL-3.0-or-later).
     */
    #[Route('/demo/indisponible', name: DemoModeSubscriber::ROUTE_INDISPONIBLE, methods: ['GET'])]
    public function indisponible(): Response
    {
        return $this->render('demo/indisponible.html.twig', [
            'type_footer' => null,
            'logo_entreprise' => $this->params->get('logo.entreprise'),
            'marque_entreprise_short' => $this->params->get('marque.entreprise.short'),
            'marque_entreprise_long' => $this->params->get('marque.entreprise.long'),
            'env' => $this->params->get('environnement'),
            'version' => $this->params->get('version'),
            'date_copyright' => \date('Y'),
            'message' => DemoModeSubscriber::MESSAGE,
        ]);
    }
}
