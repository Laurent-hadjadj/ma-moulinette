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

namespace App\Controller\Projet;

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Historique;
use App\Service\{MesProjets};
use App\Service\UserAgent\UserAgentTrackingFacade;

/**
 * [Description ProjetController]
 */
class ProjetController extends AbstractController
{
    use AppUserAware;

    private static string $page = "projet/mes-projets.html.twig";
    private static string $erreur404 = "⚠️ Tu dois être rattaché à un groupe fonctionnel (Erreur 404).";
    private static string $erreur406 = "⚠️ Je n'ai pas trouvé de projets pour ton groupe fonctionnel. Vérifie le nom du tag utilisé dans SonarQube (Erreur 406).";

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;
    private int $sonarVersion;

    public function __construct(
        private MesProjets $mesProjets,
        private EntityManagerInterface $em,
        ParameterBagInterface $params,
        private LoggerInterface $logger,
        private UserAgentTrackingFacade $tracking
    ) {
        $this->logoEntreprise = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong = $params->get('marque.entreprise.long');
        $this->environnement = $params->get('environnement');
        $this->version = $params->get('version');
        $this->dateCopyright = \date('Y');
        $this->sonarVersion = (int) $params->get('sonar.version');
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
     * Affiche la page projet
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:16:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/projet', name: 'projet', methods: 'GET')]
    public function index(): Response
    {
        $this->tracking->track('PROJET');
        $render = $this->genericRender();

        /** On récupère la version du serveur SonarQube */
        /** MODIF 12/07/2026 : getenv ne fonctionne pas toujours, on utilise la mtéhode de récupération des paramètres depuis service.yml */
        $sonar_version = (int)  ($this->sonarVersion ?: 0);
        $render['version_serveur_sonar'] = ($sonar_version != 0) ? $sonar_version : 8;

        $this->logger->info('[Projet] ℹ️ Affichage de la page projet.');
        return $this->render('projet/index.html.twig', $render);
    }

    /**
     * [Description for mesProjets]
     *
     * @return Response
     *
     * Created at: 16/07/2025 09:22:16 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/projet/mes-projets', name: 'mes_projets', methods: 'GET')]
    public function mesProjets(): Response
    {
        $this->tracking->track('MES_PROJETS');

        /** On instancie l'entityRepository */
        $historiqueRepos = $this->em->getRepository(Historique::class);

        $render = $this->genericRender();
        $groupes = $this->appUser()->getListeGroupeFonctionnel();
        $debug = '';

        /** Si l'utilisateur n'est pas rattaché à une équipe on ne charge rien */
        if (empty($groupes)) {
            /** On envoi un message à l'utilisateur */
            $render['liste_projet'] = [];
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur404,
                'debug' => $debug
            ]);
            return $this->render(self::$page, $render);
        }

        $mes_projets = $this->mesProjets->liste($groupes);
        if ($mes_projets['code'] === 406 || !isset($mes_projets['projets'])) {
            $render['liste_projet'] = [];
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur406,
                'debug' => $mes_projets['erreur']
            ]);
            return $this->render(self::$page, $render);
        }

        /** Liste des maven_keys des projets visibles par l'utilisateur */
        $mavenKeys = array_values(array_map(
            static fn(array $projet): string => (string) $projet['id'],
            $mes_projets['projets']
        ));
        $liste = $historiqueRepos->selectHistoriqueIndicateurs($mavenKeys);
        if ($liste['code'] != 200) {
            $render['liste_projet'] = [];
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => self::$erreur404,
                'debug' => $liste['erreur']
            ]);
            return $this->render(self::$page, $render);
        }

        $render['liste_projet'] = $liste['indicateur'];
        return $this->render(self::$page, $render);
    }
}
