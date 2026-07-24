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
use Symfony\Component\Form\FormError;
use Symfony\Component\HttpFoundation\{Request, Response};
use Psr\Log\LoggerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Actuator, ActuatorInfo};
use App\Form\ActuatorFormType;
use App\Service\ActuatorCredentialCipher;
use App\Service\ClientService;
use App\Service\UserAgent\UserAgentTrackingFacade;

/**
 * [Description ActuatorController]
 */
class ActuatorController extends AbstractController
{
    private static string $index = 'actuator/index.html.twig';
    private static string $erreur403 = "⚠️ Vous devez avoir le rôle 'ACTUATOR' pour accéder à cette page (Erreur 403).";
    private static string $page = 'actuator/ajouter.html.twig';
    private static string $urlActuatorInfo = '/actuator/info';

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
        private UserAgentTrackingFacade $tracking,
        private ActuatorCredentialCipher $cipher,
        private ClientService $client
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

    /**
     * [Description for actuator]
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 23/07/2026 16:05:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/actuator', name: 'actuator', methods: 'GET')]
    public function actuator(Request $request): Response
    {
        $this->tracking->track('ACTUATOR');

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

    /**
     * [Description for actuatorInfo]
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 23/07/2026 16:04:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/actuator/info', name: 'actuator_info', methods: ['GET', 'POST'])]
    public function actuatorInfo(Request $request): Response
    {
        $this->tracking->track('ACTUATOR_INFO');

        // Initialisation des informations pour la bulle d'information
        $render = $this->genericRender();

        // Vérifier si l'utilisateur a le rôle 'ROLE_ACTUATOR'.
        if (!$this->isGranted('ROLE_ACTUATOR')) {
            $this->logger->warning("[Actuator-Info] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_ACTUATOR).");
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur403
            ]);
            return $this->render(self::$page, $render);
        }

        $actuatorEntity = new Actuator();
        $actuatorInfoEntity = new ActuatorInfo();
        $actuatorEntity->addActuatorInfo($actuatorInfoEntity);
        $form = $this->createForm(ActuatorFormType::class, $actuatorEntity);

        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $actuatorEntity->setUrl($this->completerUrlActuator($actuatorEntity->getUrl()));

            if (!$this->urlActuatorEstJoignable($actuatorEntity->getUrl())) {
                $form->get('url')->addError(new FormError(
                    "⚠️ Ce point d'accès Actuator est injoignable. Vérifiez l'URL avant d'enregistrer."
                ));
                $render['form'] = $form->createView();
                return $this->render(self::$page, $render);
            }

            $actuatorEntity->setActuatorPassword($this->cipher->encrypt($actuatorEntity->getActuatorPassword()));
            $this->em->persist($actuatorEntity);
            $this->em->flush();

            $this->addFlash('notice', [
                'type' => 'success',
                'message' => "✅ L'application a été ajoutée à l'inventaire Actuator."
            ]);
            return $this->redirectToRoute('actuator');
        }

        $render['form'] = $form->createView();

        return $this->render(self::$page, $render);
    }

    /**
     * [Description for actuatorModifier]
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 23/07/2026 16:04:36 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/actuator/{id}/modifier', name: 'actuator_modifier', methods: ['GET', 'POST'], requirements: ['id' => '\d+'])]
    public function actuatorModifier(int $id, Request $request): Response
    {
        $this->tracking->track('ACTUATOR_MODIFIER');

        $render = $this->genericRender();

        if (!$this->isGranted('ROLE_ACTUATOR')) {
            $this->logger->warning("[Actuator-Modifier] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_ACTUATOR).");
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur403
            ]);
            return $this->redirectToRoute('actuator');
        }

        $actuatorEntity = $this->em->getRepository(Actuator::class)->find($id);
        if ($actuatorEntity === null) {
            $this->logger->warning("[Actuator-Modifier] ⚠️ Fiche introuvable.", ['id' => $id]);
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => "⚠️ Cette fiche n'existe pas ou plus (Erreur 404)."
            ]);
            return $this->redirectToRoute('actuator');
        }

        /** On ne pré-remplit jamais le mot de passe chiffré dans le formulaire */
        $motDePasseActuel = $actuatorEntity->getActuatorPassword();
        $actuatorEntity->setActuatorPassword(null);

        $form = $this->createForm(ActuatorFormType::class, $actuatorEntity, ['mode' => 'modifier']);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $actuatorEntity->setUrl($this->completerUrlActuator($actuatorEntity->getUrl()));

            if (!$this->urlActuatorEstJoignable($actuatorEntity->getUrl())) {
                $form->get('url')->addError(new FormError(
                    "⚠️ Ce point d'accès Actuator est injoignable. Vérifiez l'URL avant d'enregistrer."
                ));
                $render['form'] = $form->createView();
                $render['mode'] = 'modifier';
                return $this->render(self::$page, $render);
            }

            $nouveauMotDePasse = $actuatorEntity->getActuatorPassword();
            $actuatorEntity->setActuatorPassword(
                $nouveauMotDePasse !== null && $nouveauMotDePasse !== ''
                    ? $this->cipher->encrypt($nouveauMotDePasse)
                    : $motDePasseActuel
            );
            $this->em->flush();

            $this->addFlash('notice', [
                'type' => 'success',
                'message' => "✅ L'application a été modifiée."
            ]);
            return $this->redirectToRoute('actuator');
        }

        $render['form'] = $form->createView();
        $render['mode'] = 'modifier';

        return $this->render(self::$page, $render);
    }

    /**
     * [Description for completerUrlActuator]
     * Complète l'URL saisie par l'utilisateur avec le suffixe /actuator/info
     * si elle ne s'y termine pas déjà — le besoin autorise une URL complète
     * ou partielle (ex. http://localhost/monapplication).
     *
     * @param string $url
     *
     * @return string
     *
     * Created at: 24/07/2026 19:34:06 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function completerUrlActuator(string $url): string
    {
        $url = rtrim($url, '/');
        if (!str_ends_with($url, self::$urlActuatorInfo)) {
            $url .= self::$urlActuatorInfo;
        }

        return $url;
    }

    /**
     * [Description for urlActuatorEstJoignable]
     * Ping simple (sans authentification) du point d'accès avant enregistrement.
     * Réutilise ClientService::httpActuator (timeout court déjà dédié) : toute
     * réponse HTTP réelle (même 401/403/404) signifie que l'hôte est joignable,
     * seul un échec de transport (504/503, DNS, connexion refusée) est bloquant.
     *
     * @param string $url
     *
     * @return bool
     *
     * Created at: 24/07/2026 19:33:49 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function urlActuatorEstJoignable(string $url): bool
    {
        /* MODIF 2026-07-23 : filet de sécurité — ClientService::httpActuator ne
         * catch que les exceptions HTTP (timeout, transport, 4xx/5xx). Une URL
         * structurellement invalide (schéma manquant, etc.) lève une exception
         * différente AVANT même l'envoi de la requête, non catchée jusqu'ici
         * (crash 500 réel constaté en test manuel). Le formulaire valide
         * désormais le format (Assert\Url sur ActuatorFormType), mais ce ping
         * ne doit de toute façon jamais pouvoir faire planter la requête. */
        try {
            $resultat = $this->client->httpActuator($url, '', '');
        } catch (\Throwable $e) {
            $this->logger->warning("[Actuator] ⚠️ URL invalide ou ping échoué sur {$url}", [
                'exception' => $e->getMessage(),
            ]);
            return false;
        }

        if (in_array($resultat['code'] ?? null, [503, 504], true)) {
            $this->logger->warning("[Actuator] ⚠️ Ping échoué sur {$url}", [
                'code' => $resultat['code'],
                'erreur' => $resultat['erreur'] ?? null,
            ]);
            return false;
        }

        return true;
    }

    /**
     * [Description for actuatorSupprimer]
     *
     * @param int $id
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 23/07/2026 16:06:07 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/actuator/{id}/supprimer', name: 'actuator_supprimer', methods: ['POST'], requirements: ['id' => '\d+'])]
    public function actuatorSupprimer(int $id, Request $request): Response
    {
        $this->tracking->track('ACTUATOR_SUPPRIMER');

        if (!$this->isGranted('ROLE_ACTUATOR')) {
            $this->logger->warning("[Actuator-Supprimer] 🚫 Accès refusé pour l'utilisateur (pas le rôle ROLE_ACTUATOR).");
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur403
            ]);
            return $this->redirectToRoute('actuator');
        }

        if (!$this->isCsrfTokenValid('actuator_supprimer' . $id, (string) $request->request->get('_token'))) {
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => "❌ La requête est incorrecte (Erreur 400)."
            ]);
            return $this->redirectToRoute('actuator');
        }

        $actuatorEntity = $this->em->getRepository(Actuator::class)->find($id);
        if ($actuatorEntity === null) {
            $this->logger->warning("[Actuator-Supprimer] ⚠️ Fiche introuvable.", ['id' => $id]);
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => "⚠️ Cette fiche n'existe pas ou plus (Erreur 404)."
            ]);
            return $this->redirectToRoute('actuator');
        }

        $this->em->remove($actuatorEntity);
        $this->em->flush();

        $this->addFlash('notice', [
            'type' => 'success',
            'message' => "✅ La fiche a été supprimée de l'inventaire Actuator."
        ]);
        return $this->redirectToRoute('actuator');
    }
}
