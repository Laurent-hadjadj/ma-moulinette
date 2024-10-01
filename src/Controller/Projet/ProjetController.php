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

namespace App\Controller\Projet;

/** Core */
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

/** Sécurité */
use Symfony\Bundle\SecurityBundle\Security;

/** API */
use Symfony\Component\HttpFoundation\Response;

/** Services */
use App\Service\MesProjets;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Historique;
use Doctrine\ORM\EntityManager;

/**
 * [Description ProjetController]
 */
class ProjetController extends AbstractController
{
    public static $route= "projet/mes-projets.html.twig";
    public static $reference = "<strong>[MES-PROJETS]</strong>";
    public static $erreur404 = "Tu dois être rattaché à une équipe (erreur 404).";
    public static $erreur406 = "Je n'ai pas trouvé de projets pour ton équipe. ".
    "Vérifiez le nom du tag utilisé dans SonarQube (erreur 406).";


    private $mesProjets;
    private $security;
    private $em;

    public function __construct(
        MesProjets $mesProjets,
        Security $security,
        EntityManagerInterface $em
    ) {
        $this->mesProjets = $mesProjets;
        $this->security = $security;
        $this->em = $em;
    }

    /**
     * [Description for getDefaultRender]
     *  Retourne les paramètres par défaut pour la page.
     *
     * @return array
     *
     * Created at: 24/09/2024 15:15:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function getDefaultRender(): array
    {
        return [
            'marque_entreprise_short' => $this->getParameter('marque.entreprise.short'),
            'marque_entreprise_long' => $this->getParameter('marque.entreprise.long'),
            'logo_entreprise' => $this->getParameter('logo.entreprise'),
            'env' => $this->getParameter('environnement'),
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')];
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
        /** On récupère l'objet User du contexte de sécurité */
        $preference = $security->getUser()->getPreference();

        /** On regarde si le bookmark est actif */
        $bookmark = ['null'];
        if ($preference['statut']['bookmark']) {
            $bookmark = $preference['bookmark'];
        }

        // Mise à jour du rendu
        $defaultRender = $this->getDefaultRender();
        $render = array_merge($defaultRender, ['bookmark' => $bookmark]);
        return $this->render('projet/index.html.twig', $render);
    }


    #[Route('/projet/mes-projets', name: 'mes_projets', methods: 'GET')]
    public function mesProjets(Security $security): Response
    {
        /** On instancie l'entityRepository */
        $historiqueRepository = $this->em->getRepository(Historique::class);

        $defaultRender = $this->getDefaultRender();
        $teams = $this->security->getUser()->getEquipe();
        $debug='';

        /** Si l'utilisateur n'est pas rattaché à une équipe on ne charge rien */
        if (empty($teams)) {
            /** On envoi un message à l'utilisateur */
            $render = array_merge($defaultRender, ['liste_projet' => []]);
            $this->addFlash('notice', ['type' => 'warning', 'reference' => static::$reference, 'message' => static::$erreur404, 'debug'=>$debug] );
            return $this->render(static::$route, $render);
        }

        $mes_projets=$this->mesProjets->liste($teams);
        if ($mes_projets['code'] === 406) {
            $render = array_merge($defaultRender, ['liste_projet' => []]);
            $this->addFlash('notice', ['type' => 'warning', 'reference' => static::$reference, 'message' => $mes_projets['message'], 'debug'=>$mes_projets['erreur']] );
            return $this->render(static::$route, $render);
        }

        /** On construit la clause where  */
        $c = '';
        foreach ($mes_projets[0] as $projet) {
                $c = $c."'".$projet['id']."', ";
            }

        /** On supprime la dernière virgule */
        $rtrim = rtrim($c, " ,");
        $liste = $historiqueRepository->selectHistoriqueIndicateurs($rtrim);
        if ($liste['code']!=200) {
            $render = array_merge($defaultRender, ['liste_projet' => []]);
            $this->addFlash('notice', ['type' => 'error', 'reference' => static::$reference, 'message' => static::$erreur404, 'debug'=>$liste['erreur']] );
            return $this->render(static::$route, $render);
        }

        $render = array_merge($defaultRender, ['liste_projet' => $liste['indicateur']]);
        return $this->render(static::$route, $render);
    }
}
