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

namespace App\Controller\Profil;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

// Accès aux tables SLQLite
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Profiles;

class ProfilController extends AbstractController
{
    /**
     * [Description for __construct]
     *
     * Created at: 15/12/2022, 22:14:50 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(private EntityManagerInterface $em)
    {
        $this->em = $em;
    }

    /**
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:14:55 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/profil', name: 'profil', methods: 'GET')]
    public function index(Request $request): Response
    {
        /** On instancie l'EntityRepository */
        $profiles = $this->em->getRepository(Profiles::class);

        /** oN créé un objet réponse */
        $response = new JsonResponse();

        /** On récupère la liste des profiles; */
        $request=$profiles->selectProfiles();
        switch ($request['code']) {
            case 202:
                $this->addFlash('warning', sprintf('%s : %s', "[PROFIL-099]","Le mode TEST a été activé."));
                break;
            case 500:
                $this->addFlash('alert',
                    sprintf('%s : %s', "[PROFIL-002]","La liste des profils n'a pas été récupurée."));
                break;
            default:
                if (!$request['liste']){
                    $this->addFlash('warning',
                    sprintf('%s : %s', "[PROFIL-003]","La liste des profils est vide. Vous devez la mettre à jour !"));
                } else {
                $this->addFlash('info',
                        sprintf('%s : %s', "[PROFIL-001]","La liste des profils a été récupurée."));
                }
            break;
        }

        $liste = isset($request['liste']) ? $request['liste'] : []; // Fournit un tableau vide par défaut

        $render =
        [   "liste" => $request['liste'],
            "version" => $this->getParameter("version"),
            "dateCopyright" => \date("Y"),
            Response::HTTP_OK
        ];
        return $this->render('profil/index.html.twig', $render);
    }
}
