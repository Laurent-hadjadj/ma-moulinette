<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller\Owasp;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\OwaspTop10;
use App\Service\UserAgentTrackingFacade;

/**
 * [Description OwaspController]
 */
class OwaspController extends AbstractController
{

    private static string $page = "owasp/index.html.twig";
    private static string $erreur404 = "⚠️ Les informations concernant les référentiels OWASP n'ont pas été trouvés.";
    private static string $noData = 'Pas de données';

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

        /**
     * [Description for __construct]
     *
     * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        ParameterBagInterface $params,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private UserAgentTrackingFacade $tracking)
    {
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
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:14:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp', name: 'owasp')]
    public function index(): Response
    {

        $this->tracking->track('OWASP');

        /** On charge le template du render */
        $render = $this->genericRender();

        /** On instancie l'entityRepos */
        $owaspTop10Repos = $this->em->getRepository(OwaspTop10::class);

        /** On récupère les informations du projet de la table historique */
        $map = ['referential_version' => 2017];
        $owasp_2017 = $owaspTop10Repos->selectOwaspTop10Referential($map);

        if ($owasp_2017['code'] != 200) {
            $this->logger->error('[Owasp] ❌ Échec selectOwaspTop10Referential 2017.', [
                'code' => $owasp_2017['code'],
                'erreur' => $owasp_2017['erreur'] ?? self::$noData
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($owasp_2017['erreur'] ?? self::$noData)
            ]);
            return $this->render(self::$page, $render);
        }

        $map = ['referential_version' => 2021];
        $owasp_2021 = $owaspTop10Repos->selectOwaspTop10Referential($map);

        if ($owasp_2021['code'] != 200) {
            $this->logger->error('[Owasp] ❌ Échec selectOwaspTop10Referential 2021.', [
                'code' => $owasp_2021['code'],
                'erreur' => $owasp_2021['erreur'] ?? self::$noData
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($owasp_2021['erreur'] ?? self::$noData)
            ]);
            return $this->render(self::$page, $render);
        }

        if (count($owasp_2017['liste']) === 0 && count($owasp_2021['liste']) === 0) {
            $this->logger->warning('[Owasp] ⚠️ Référentiels OWASP 2017 et 2021 vides.');
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur404
            ]);
        }

        $render['sonar_version'] = $this->getParameter('sonar.version');
        $render['serveur'] = $this->getParameter('sonar.url');
        $render['owasp_2017'] = $owasp_2017;
        $render['owasp_2021'] = $owasp_2021;
        return $this->render(self::$page, $render);
    }

    /**
     * [Description for details]
     *
     * @param int $id
     *
     * @return Response
     *
     * Created at: 21/11/2024 20:16:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp/detail/{id}', name: 'owasp_detail', condition: "params['id'] < 21", methods: ['GET', 'HEAD'])]
    public function details(int $id): Response
    {
        /** On charge le template du render */
        $render = $this->genericRender();

        /** On instancie l'entityRepository */
        $owaspTop10Repos = $this->em->getRepository(OwaspTop10::class);

        /** On récupère les informations du projet de la table historique */
        $map = ['menace' => $id];
        $liste = $owaspTop10Repos->selectOwaspTop10Details($map);
        if ($liste['code'] != 200) {
            $this->logger->error('[Owasp] ❌ Échec selectOwaspTop10Details.', [
                'code' => $liste['code'],
                'erreur' => $liste['erreur'] ?? self::$noData,
                'menace' => $id
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($liste['erreur'] ?? self::$noData)
            ]);
            return $this->render(self::$page, $render);
        }
        $render['menace'] = $id;
        $render['owasp'] = $liste['details'][0];
        return $this->render('owasp/detail.html.twig', $render);
    }
}
