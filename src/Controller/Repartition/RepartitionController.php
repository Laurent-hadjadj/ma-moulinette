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

namespace App\Controller\Repartition;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\JsonResponse;

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
     * Created at: 15/12/2022, 22:32:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(private ManagerRegistry $doctrine)
    {
        $this->doctrine = $doctrine;
    }

    /**
     * [Description for extractNameFromMavenKey]
     * Extrait le nom du projet de la clé
     *
     * @param mixed $mavenKey
     *
     * @return string
     *
     * Created at: 13/03/2024 22:11:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractNameFromMavenKey($mavenKey): string
    {
        /**
         * On récupère le nom de l'application depuis la clé mavenKey
         * [fr.ma-petite-entreprise] : [ma-moulinette]
         */
        $app = explode(":", $mavenKey);
        if (count($app)===1) {
            /** La clé maven n'est pas conforme, on ne peut pas déduire le nom de l'application */
            $name=$mavenKey;
        } else {
            $name=$app[1];
        }
        return $name;
    }

    /**
     * [Description for projetRepartition]
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:32:12 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/projet/repartition', name: 'projet_repartition')]
    public function projetRepartition(Request $request): Response
    {
        /** On on vérifie si on a activé le mode test */
        if (is_null($request->get('mode'))) {
            $mode = "null";
        } else {
            $mode = $request->get('mode');
        }

        /** On récupère la clé du projet */
        $mavenKey = $request->get('mavenKey');
        $response = new JsonResponse();

        /** On teste si la clé est valide */
        if (is_null($mavenKey) && $mode === "TEST") {
            return $response->setData(["message" => "la clé maven est vide!", Response::HTTP_BAD_REQUEST]);
        }

        /** On enregistre le nom du projet */
        $app = static::extractNameFromMavenKey($mavenKey);

        /** On se connecte à la base pour connaitre la version du dernier setup pour le projet. */
        $reponse = $this->doctrine->getManager('secondary')
                        ->getRepository(Repartition::class)
                        ->findBy(['mavenKey' => $mavenKey], ['setup' => 'DESC'], 1);

        if (empty($reponse)) {
            $setup = "NaN";
            $statut = "NaN";
        } else {
            $setup = $reponse[0]->getSetup();
            $statut = "actuel";
        }

        if ($mode === "TEST") {
            return $response->setData(
                ['monApplication' => $app, 'mavenKey' => $mavenKey,
                'setup' =>  $setup, 'statut' => $statut, Response::HTTP_OK]
            );
        }

        return $this->render(
            'projet/details.html.twig',
            [
            'monApplication' => $app,
            'mavenKey' => $mavenKey,
            'setup' =>  $setup,
            'statut' => $statut,
            'version' => $this->getParameter('version'), 'dateCopyright' => \date('Y')
            ]
        );
    }
}
