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
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\{Request, Response};
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Actuator, ActuatorInfo};
use App\Form\ActuatorFormType;
use App\Service\UserAgentTrackingFacade;
use Psr\Log\LoggerInterface;

/**
 * [Description ActuatorController]
 */
class ActuatorController extends AbstractController
{
    private static string $index = 'actuator/index.html.twig';
    private static string $europeParis = "Europe/Paris";
    private static string $erreur403 = "⚠️ Vous devez avoir le rôle 'ACTUATOR' pour accéder à cette page (Erreur 403).";

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    public function __construct(
        private EntityManagerInterface $em,
        private PaginatorInterface $paginator,
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
    }

    /**
     * [Description for genericRender]
     *
     * @return array<int|string, mixed>
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

    #[Route('/actuator', name: 'actuator', methods: 'GET')]
    public function actuator(Request $request): Response
    {
        $this->tracking->track('PROMPT_SIMPLE');

      /** On instancie l'EntityRepository */
        $actuatorRepository = $this->em->getRepository(Actuator::class);

        /** Whitelist des colonnes triables (valeurs venant de l URL) */
        $allowedSortColumns = ['nom_application', 'url', 'personne', 'date_modification', 'date_enregistrement'];
        $sortColumn = $request->query->get('sort') ?? 'date_modification';
        if (!in_array($sortColumn, $allowedSortColumns, true)) {
            $sortColumn = 'date_modification';
        }

        $sortDirection = strtoupper((string) ($request->query->get('direction') ?? 'DESC'));
        if ($sortDirection !== 'ASC' && $sortDirection !== 'DESC') {
            $sortDirection = 'DESC';
        }

        // Initialisation des informations
        $render = $this->genericRender();
        $render['pagination'] = null;

        /** Vérifier si l'utilisateur a le rôle 'ROLE_ACTUATOR'. */
        if (!$this->isGranted('ROLE_ACTUATOR')) {
            $this->logger->warning("[Actuator] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_ACTUATOR).");
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur403
            ]);
            return $this->render(self::$index, $render);
        }

        if ($sortColumn === 'date_enregistrement' || $sortColumn === 'date_modification') {
            $paginatorQuery = $actuatorRepository->findActuatorOrderByDate($sortDirection);
        } else {
            $paginatorQuery = $actuatorRepository->findActuatorOrderBy($sortColumn, $sortDirection);
        }
        if ($paginatorQuery['code'] != 200) {
            $this->logger->error('[Actuator] ❌ Échec de la requête de pagination.', [
                'code' => $paginatorQuery['code'],
                'erreur' => $paginatorQuery['erreur'] ?? 'Pas de données'
            ]);
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => '⚠️' . ($paginatorQuery['erreur'] ?? 'Erreur inconnue.')
            ]);
            return $this->render(self::$index, $render);
        }

        $pagination = $this->paginator->paginate(
            $paginatorQuery['paginator_query'], /* query NOT result */
            $request->query->getInt('page', 1), /*page number*/
            9                                   /*limit par page*/
        );

        $render['pagination'] = $pagination;
        return $this->render(self::$index, $render);
    }

    #[Route('/actuator/info', name: 'actuator_info', methods: 'GET')]
    public function actuatorInfo(Request $request): Response
    {
        /** On instancie l'EntityRepository */
        //$actuatorRepository = $this->em->getRepository(BatchTraitement::class);

        // Initialisation des informations pour la bulle d'information
        $render = $this->genericRender();

        // Vérifier si l'utilisateur a le rôle 'ROLE_ACTUATOR'.
        if (!$this->isGranted('ROLE_ACTUATOR')) {
            $this->logger->warning("[Actuator-Info] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_ACTUATOR).");
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur403
            ]);
            return $this->render('actuator/ajouter.html.twig', $render);
        }

        // Créer un objet date avec le fuseau horaire Europe/Paris
        $date = new \DateTimeImmutable('now', new \DateTimeZone(self::$europeParis));

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
            $this->em->persist($actuatorEntity);
            $this->em->flush();

            return $this->redirectToRoute('actuator_success');
        }

        $render['form'] = $form->createView();

        return $this->render('actuator/ajouter.html.twig', $render);
    }
}
