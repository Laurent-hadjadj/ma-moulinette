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
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

/** Accès aux tables SLQLite */
use Doctrine\Persistence\ManagerRegistry;
use App\Entity\Secondary\Repartition;

/**
 * [Description RepartitionController]
 */
class RepartitionController extends AbstractController
{
    /**
     * [Description for __construct]
     *
     * @param  private
     *
     * Created at: 15/12/2022, 22:32:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct( private ManagerRegistry $doctrine) {
        $this->doctrine = $doctrine;
    }

    /**
     * [Description for projetRepartition]
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:32:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/projet/repartition', name: 'projet_repartition')]
    public function projetRepartition(Request $request): Response
    {
        /** On récupère la clé du projet */
        $mavenKey = $request->get('mavenKey');
        /** On enregistre le nom du projet */
        $app=explode(":", $mavenKey);

        /** On se connecte à la base pour connaitre la version du dernier setup pour le projet. */
        $reponse = $this->doctrine->getManager('secondary')
                            ->getRepository(Repartition::class)
                            ->findBy(['maven_key' => $mavenKey],['setup' => 'DESC'],1);

        if (empty($reponse)) {
            $setup="NaN";
            $statut="NaN";
            } else {
                $setup=$reponse[0]->getSetup();
                $statut="actuel";
            }

        return $this->render('projet/details.html.twig',
        [
            'monApplication' => $app[1],
            'mavenKey' => $mavenKey,
            'setup' =>  $setup,
            'statut'=> $statut,
            'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
        ]);
    }
}
