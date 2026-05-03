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

namespace App\Controller\Profil;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Profiles;
use App\Service\UserAgentTrackingFacade;

/**
 * [Description ProfilController]
 */
class ProfilController extends AbstractController
{
    private static string $page = "profil/index.html.twig";
    private static string $erreur404 = "⚠️ La liste des profils est vide. Vous devez la mettre à jour ! (Erreur 404)";
    private static string $erreur500 = "❌ Une erreur technique est survenue lors de la récupération des profils (Erreur 500).";

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:14:50 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        ParameterBagInterface $params,
        private LoggerInterface $logger,
        // AUDIT 2026-05 : utilisé par $this->tracking->track('PROFIL') dans index() — ne pas retirer même si PhpStan signale "property.onlyWritten"
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
     * [Description for profil]
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:14:55 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/profil', name: 'profil', methods: 'GET')]
    public function index(): Response
    {
        $this->tracking->track('PROFIL');

        $this->logger->info('[Profil] ℹ️ Accès à la page /profil', [
            'env' => $this->environnement
        ]);

        /** On instancie l'EntityRepository */
        $profilesRepos = $this->em->getRepository(Profiles::class);

        $render = $this->genericRender();
        $render['liste'] = [];

        try {
            /** On récupère la liste des profiles; */
            $r = $profilesRepos->selectProfiles();

            if ($r['code'] !== 200) {
                $this->logger->error('[Profil] ❌ Erreur lors de la récupération des profils (selectProfiles)', [
                    'code_retour' => $r['code'],
                    'message' => $r['erreur'] ?? 'Pas de données'
                ]);

                $this->addFlash('notice', [
                    'type' => 'error',
                    'message' => "❌ La liste des profils n'a pas été récupérée ({$r['code']})."
                ]);
                return $this->render(self::$page, $render);
            }

            if (empty($r['liste'])) {
                $this->logger->warning('[Profil] ⚠️ Liste des profils vide.');

                $this->addFlash('notice', [
                    'type' => 'warning',
                    'message' => self::$erreur404
                ]);
                return $this->render(self::$page, $render);
            }

                $this->logger->info('[Profil] ℹ️ Profils récupérés avec succès', [
                    'nb_profils' => count($r['liste']),
                    'code_retour' => $r['code']
                ]);
        } catch (\Throwable $e) {
            $this->logger->critical('[Profil] 🔴 Exception lors de l’appel à selectProfiles()', [
                'exception' => $e->getMessage()
            ]);

            $this->addFlash('notice', [
                'type' => 'critical',
                'message' => self::$erreur500
            ]);
            return $this->render(self::$page, $render);
        }

        // Tout va bien.
        $render['liste'] = $r['liste'];
        return $this->render(self::$page, $render);
    }
}
