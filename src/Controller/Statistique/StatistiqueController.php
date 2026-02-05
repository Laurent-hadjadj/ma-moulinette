<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2025..
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Controller\Statistique;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Service\UserAgentTrackingFacade;
use App\Service\UserAgentAnalysisService;

/**
 * [Description StatistiqueController]
 */
class StatistiqueController extends AbstractController
{

    private static $erreur403 = "Vous devez avoir le rôle <strong>GESTIONNAIRE</strong> pour accéder à cette page (Erreur 403).";
    private static $index='statistique/index.html.twig';

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    public function __construct(
        private ParameterBagInterface $params,
        private UserAgentTrackingFacade $tracking,
        private UserAgentAnalysisService $analysis
    ) {
        $this->params = $params;
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
     * @return array
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
     * [Description for statistique]
     *
     * @return Response
     *
     * Created at: 04/09/2024 14:57:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/statistiques', name: 'statistiques', methods: ['GET'])]
    #[IsGranted('ROLE_GESTIONNAIRE', message: 'Vous ne disposez pas des droits suffisants pour accéder à cette page.', statusCode: 403)]
    public function statistique(): Response
    {
        $this->tracking->track('STATISTIQUES');

        $render=static::genericRender();
        return $this->render(static::$index, $render);
    }

    #[Route('/statistique/run_batch', name: 'runBatchAnalysis', methods: ['GET'])]
    #[IsGranted('ROLE_INTERNAL', message: 'Vous ne disposez pas des droits suffisants pour accéder à cette page.', statusCode: 403)]
    public function runBatchAnalysis(): Response
    {
        $render = static::genericRender();

        /** On vérifie que l'utilisateur a bien le ROLE_GESTIONNAIRE */
        if (!$this->isGranted('ROLE_INTERNAL')) {
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => static::$erreur403,
                'trace' => '']);
            return $this->render(static::$index, $render);
        }

        $exec = $this->analysis->runBatch(50);
        if ($exec['code'] !== 200) {
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => "Une erreur s'est produite pendant l'analyse des données UserAgent ({$exec['code']})",
                'trace' => count($exec['errors'])
            ]);
            return $this->render(static::$index, $render);
        }

        $error = count($exec['errors']) ?? 0;
        $this->addFlash('notice', [
            'type' => 'info',
            'message' => "Collecte terminée : {$exec['processed']} collecté, {$error} erreurs.",
            'trace' => null
        ]);
        return $this->render(static::$index, $render);
    }

}
