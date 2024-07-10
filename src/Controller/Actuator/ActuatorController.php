<?php

/**
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2022.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Actuator;

/** Core */
use App\Service\Client;
use App\Entity\Actuator;

use App\Entity\ActuatorInfo;
use App\Form\ActuatorFormType;

use Doctrine\ORM\EntityManagerInterface;

/** Client HTTP */
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class ActuatorController extends AbstractController
{
    public static $europeParis = "Europe/Paris";
    public static $request = "requête : ";
    public static $titre = 'Traitement';
    public static $erreur403 = "Vous devez avoir le rôle 'ACTUATOR' accéder à cette page [Erreur 403].";

    private $em;
    private $client;
    private $paginator;

    public function __construct(
        EntityManagerInterface $em,
        Client $client,
        PaginatorInterface $paginator
    ) {
        $this->em = $em;
        $this->client = $client;
        $this->paginator = $paginator;
    }

    #[Route('/actuator', name: 'actuator', methods:'GET')]
    public function actuator(Request $request): Response
    {
      /** On instancie l'EntityRepository */
        $actuatorRepository = $this->em->getRepository(actuator::class);

        $sortColumn = $request->query->get('sort') ?? 'date_modification';
        $sortDirection = $request->query->get('direction') ?? 'DESC';

        // Initialisation des informations
        $render = [
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y'),
            'pagination' => null,
        ];

        /** Vérifier si l'utilisateur a le rôle 'ROLE_ACTUATOR'. */
        if (!$this->isGranted('ROLE_ACTUATOR')) {
            $this->addFlash('notice', ['type'=>'alert', 'titre'=>static::$titre, 'message'=>static::$erreur403]);
            return $this->render('actuator/index.html.twig', $render);
        }

        if ($sortColumn==='date_enregistrement' || $sortColumn==='date_modification' ) {
            $paginatorQuery=$actuatorRepository->findActuatorOrderByDate($sortDirection);
        } else {
            $paginatorQuery=$actuatorRepository->findActuatorOrderBy($sortColumn, $sortDirection);
        }
        if ($paginatorQuery['code']!=200) {
            $this->addFlash('notice', ['type'=>'warning', 'titre'=>static::$titre, 'message'=>$paginatorQuery['erreur']]);
            return $this->render('actuator/index.html.twig', $render);
        }

        $pagination = $this->paginator->paginate(
            $paginatorQuery['paginator_query'], /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            9                                   /*limit par page*/
        );

        return $this->render('actuator/index.html.twig',
            array_merge($render, [
                'pagination' => $pagination,
            ])
        );
    }
    #[Route('/actuator/info', name: 'actuator_info', methods:'GET')]
    public function actuatorInfo(Request $request): Response
    {
      /** On instancie l'EntityRepository */
        //$actuatorRepository = $this->em->getRepository(BatchTraitement::class);

        // Initialisation des informations pour la bulle d'information
        $render = [
            'version' => $this->getParameter('version'),
            'dateCopyright' => \date('Y')
        ];

        // Vérifier si l'utilisateur a le rôle 'ROLE_ACTUATOR'.
        if (!$this->isGranted('ROLE_ACTUATOR')) {
            $this->addFlash('notice', ['type'=>'alert', 'titre'=>static::$titre, 'message'=>static::$erreur403]);
            return $this->render('actuator/ajouter.html.twig', $render);
        }

        // Créer un objet date avec le fuseau horaire Europe/Paris
        $date = new \DateTimeImmutable();
        $date->setTimezone(new \DateTimeZone(static::$europeParis));

        $actuatorInfo=[
            "nom" => "monapplication-mat-api",
            "description" => "Mon Application Mat",
            "version" => "3.2.1-RC1",
            "exebud" => "Ajouter le code application",
            "socle" => [
                "archetype" => "4.2.1-RC1",
                "config" => "4.1.1-RELEASE",
                "angular" => "14.3.0",
                "sde" => "@sde.version@",
                "java" => "1.8.0_231",
                "encodage" => "UTF-8"
            ],
            "app" => [
                "javamelody" => "1.95.0",
                "services-transverses" => "1.0.6-RELEASE",
                "mgh" => "4.4.0-RELEASE",
                "projet-hub" => "4.9.1-RELEASE",
                "cachetserveur" => "2.0.7-RELEASE",
                "mcc" => "2.9.0-RELEASE",
            ]
            ];

        $actuatorEntity = new Actuator();
        $actuatorInfoEntity = new ActuatorInfo();
        $actuatorEntity->addActuatorInfo($actuatorInfoEntity);
        $form = $this->createForm(ActuatorFormType::class, $actuatorEntity);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($actuatorEntity);
            $entityManager->flush();

            return $this->redirectToRoute('actuator_success');
        }

        return $this->render('actuator/ajouter.html.twig',
            array_merge($render, [
                'form' => $form->createView(),
            ])
        );
    }

}


