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

namespace App\Controller\Projet;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Historique;
use App\Service\MesProjets;

/**
 * [Description ProjetController]
 */
class ProjetController extends AbstractController
{
    public static $page= "projet/mes-projets.html.twig";
    public static $reference = "<strong>[Mes-Projets]</strong>";
    public static $erreur404 = "Tu dois être rattaché à une équipe (Erreur 404).";
    public static $erreur406 = "Je n'ai pas trouvé de projets pour ton équipe. Vérifie le nom du tag utilisé dans SonarQube (Erreur 406).";

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    public function __construct(
        private MesProjets $mesProjets,
        private Security $security,
        private EntityManagerInterface $em,
        private ParameterBagInterface $params,
        private LoggerInterface $logger,
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
            'date_copyright' => $this->dateCopyright];
    }

    /**
     * [Description for index]
     * Affiche la page projet
     *
     * @param Security $security
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:16:04 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/projet', name: 'projet', methods: 'GET')]
    public function index(Security $security): Response
    {
        $user = $security->getUser();
        if (!$user) {
            $this->logger->warning('🚫 [Projet] Accès refusé : utilisateur non connecté.');
            throw $this->createAccessDeniedException("Utilisateur non authentifié (Erreur 403).");
        }

        $this->logger->info('ℹ️ [Projet] Chargement des préférences utilisateur.', [
            'user' => $user->getUserIdentifier()
        ]);

        $preference = $user->getPreference();
        $bookmark = ['null'];

        if ($preference['statut']['bookmark'] ?? false) {
            $bookmark = $preference['bookmark'];
            $this->logger->debug('🛠️ [Projet] Préférence de bookmark activée.', [
                'bookmark' => $bookmark
            ]);
        } else {
            $this->logger->debug('🛠️ [Projet] Aucun bookmark activé dans les préférences.');
        }

        $historiqueRepo = $this->em->getRepository(Historique::class);

        /** Vérifie si le bookmark est valide */
        $map = ['maven_key' => $bookmark[0]];
        $countProjet = $historiqueRepo->countHistoriqueProjet($map);

        if ($countProjet === 0) {
            $this->logger->info('ℹ️ [Projet] Le projet en bookmark est introuvable dans le catalogue.', [
                'bookmark_maven_key' => $bookmark[0]
            ]);
            $bookmark = ['null'];
        } else {
            $this->logger->info('ℹ️ [Projet] Projet trouvé en base.', [
                'bookmark_maven_key' => $bookmark[0],
                'projet_count' => $countProjet
            ]);
        }

        $render = static::genericRender();
        $render['bookmark'] = $bookmark;

        $this->logger->info('ℹ️ [Projet] Affichage de la page projet.');
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
        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);

        $render = static::genericRender();
        $teams = $this->security->getUser()->getEquipe();
        $debug = '';

        /** Si l'utilisateur n'est pas rattaché à une équipe on ne charge rien */
        if (empty($teams)) {
            /** On envoi un message à l'utilisateur */
            $render['liste_projet'] = [];
            $this->addFlash('notice', ['type' => 'warning', 'titre' => static::$reference, 'message' => static::$erreur404, 'debug'=> $debug] );
            return $this->render(static::$page, $render);
        }

        $mes_projets = $this->mesProjets->liste($teams);
        if ($mes_projets['code'] === 406 || !isset($mes_projets['projets'])) {
            $render['liste_projet'] = [];
            $this->addFlash('notice', ['type' => 'warning', 'titre' => static::$reference, 'message' => $mes_projets['message'], 'debug' => $mes_projets['erreur']] );
            return $this->render(static::$page, $render);
        }

        /** On construit la clause where  */
        $c = '';
        foreach ($mes_projets['projets'] as $projet) {
                $c = $c."'".$projet['id']."', ";
            }

        /** On supprime la dernière virgule */
        $rtrim = rtrim($c, " ,");
        $liste = $historiqueRepository->selectHistoriqueIndicateurs($rtrim);
        if ($liste['code'] != 200) {
            $render['liste_projet'] = [];
            $this->addFlash('notice', ['type' => 'erreur', 'titre' => static::$reference, 'message' => static::$erreur404, 'debug'=>$liste['erreur']] );
            return $this->render(static::$page, $render);
        }

        $render['liste_projet'] = $liste['indicateur'];
        return $this->render(static::$page, $render);
    }
}
