<?php

/**
*  Ma-Moulinette
*  --------------
*  Copyright (c) 2021-2025.
*  Laurent HADJADJ <laurent_h@me.com>.
*  Licensed Creative Common CC-BY-NC-SA 4.0.
*  ---
*  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
*  http://creativecommons.org/licenses/by-nc-sa/4.0/
*/

namespace App\Controller\Actuator;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\Actuator;
use App\Entity\ActuatorInfo;
use App\Form\ActuatorFormType;
use App\service\ClientService;

/**
 * [Description ActuatorController]
 */
class ActuatorController extends AbstractController
{
    private static $index = 'actuator/index.html.twig';
    private static $europeParis = "Europe/Paris";
    private static $erreur403 = "⚠️ Vous devez avoir le rôle 'ACTUATOR' pour accéder à cette page (Erreur 403).";

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    public function __construct(
        private EntityManagerInterface $em,
        private ClientService $client,
        private PaginatorInterface $paginator,
        private ParameterBagInterface $params
    ) {
        $this->paginator = $paginator;
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
     * Created at: 21/12/2024 20:50:18 (Europe/Paris)
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

    #[Route('/actuator', name: 'actuator', methods:'GET')]
    public function actuator(Request $request): Response
    {
      /** On instancie l'EntityRepository */
        $actuatorRepository = $this->em->getRepository(actuator::class);

        $sortColumn = $request->query->get('sort') ?? 'date_modification';
        $sortDirection = $request->query->get('direction') ?? 'DESC';

        // Initialisation des informations
        $render=static::genericRender();
        $render['pagination'] = null;

        /** Vérifier si l'utilisateur a le rôle 'ROLE_ACTUATOR'. */
        if (!$this->isGranted('ROLE_ACTUATOR')) {
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => static::$erreur403
            ]);
            return $this->render(static::$index, $render);
        }

        if ($sortColumn === 'date_enregistrement' || $sortColumn==='date_modification' ) {
            $paginatorQuery=$actuatorRepository->findActuatorOrderByDate($sortDirection);
        } else {
            $paginatorQuery = $actuatorRepository->findActuatorOrderBy($sortColumn, $sortDirection);
        }
        if ($paginatorQuery['code'] != 200) {
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => '⚠️' . $paginatorQuery['erreur']
            ]);
            return $this->render(static::$index, $render);
        }

        $pagination = $this->paginator->paginate(
            $paginatorQuery['paginator_query'], /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            9                                   /*limit par page*/
        );

        $render['pagination'] = $pagination;
        return $this->render(static::$index, $render);
    }

    #[Route('/actuator/info', name: 'actuator_info', methods:'GET')]
    public function actuatorInfo(Request $request): Response
    {
        /** On instancie l'EntityRepository */
        //$actuatorRepository = $this->em->getRepository(BatchTraitement::class);

        // Initialisation des informations pour la bulle d'information
        $render=static::genericRender();

        // Vérifier si l'utilisateur a le rôle 'ROLE_ACTUATOR'.
        if (!$this->isGranted('ROLE_ACTUATOR')) {
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => static::$erreur403
            ]);
            return $this->render('actuator/ajouter.html.twig', $render);
        }

        // Créer un objet date avec le fuseau horaire Europe/Paris
        $date = new \DateTimeImmutable('now', new \DateTimeZone(static::$europeParis));

        $actuatorInfo = [
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

        $render['form'] = $form->createView();

        return $this->render('actuator/ajouter.html.twig', $render);
    }

}
