<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Controller\Statistique;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

use App\Service\UserAgentTrackingFacade;
use App\Service\UserAgentReportingService;

/**
 * [Description StatistiqueUtilisateurController]
 */
class StatistiqueUtilisateurController extends AbstractController
{
    private static $erreur403 = "Vous devez avoir le rôle <strong>GESTIONNAIRE</strong> pour accéder à cette page (Erreur 403).";
    private static $message = "Les données n'ont pas été correctement récupérées ";
    private static $index='statistique/utilisateur.html.twig';

    private $logoEntreprise;
    private $marqueEntrepriseShort;
    private $marqueEntrepriseLong;
    private $environnement;
    private $version;
    private $dateCopyright;

    public function __construct(
        private ParameterBagInterface $params,
        private UserAgentReportingService $reporting,
        private UserAgentTrackingFacade $tracking,
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
            'date_copyright' => $this->dateCopyright
        ];
    }

    /**
     * [Description for genericAddFlashMessage]
     *
     * @param string $type
     * @param string $message
     * @param string $code
     * @param string|null|null $error
     *
     * @return void
     *
     * Created at: 23/12/2025 09:48:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericAddFlashMessage(string $type, string $message, int $code, string|null $error = null):void
    {
        $this->addFlash('notice', ['type' => $type, 'message' => "{$message} ({$code})", 'trace' => $error]);
    }

    /**
     * [Description for statistique]
     *
     * @return Response
     *
     * Created at: 04/09/2024 14:57:26 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/statistiques/utilisateur', name: 'statistiques_utilisateur', methods: ['GET'])]
    #[IsGranted('ROLE_GESTIONNAIRE', message: 'Vous ne disposez pas des droits suffisants pour accéder à cette page.', statusCode: 403)]
    public function statistiques(Request $request): Response
    {
        $this->tracking->track('STATISTIQUES_UTILISATEUR');

        $render = static::genericRender();

        /** Initialisation des variables */
        $render['generated_on'] = new \DateTimeImmutable();
        $render['period'] = 'day';

        $render['nombre_utilisateur_disponible'] = 0;
        $render['nombre_utilisateur_actif'] = 0;

        $render['os']['data']['items'] = [];
        $render['browser']['data']['items'] = [];
        $render['device']['data']['items'] = [];

        $render['page_vue_unique']['pages']['items'] = [];
        $render['session_duration']['items'] = [];
        $render['avg_session_duration'] = [];
        $render['nb_session_unique'] = [];
        $render['session_category'] = [];
        $render['session_unique_category'] = [];

        /** On vérifie que l'utilisateur a bien le ROLE_GESTIONNAIRE */
        if (!$this->isGranted('ROLE_GESTIONNAIRE')) {
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => static::$erreur403,
                'trace' => '']);
            return $this->render(static::$index, $render);
        }

        /******************************************/
        /**               USER_AGENT              */
        /******************************************/
        // Période demandée ou valeur par défaut
        $period = $request->query->get('period', 'day');
        $week   = $request->query->get('week');
        $month  = $request->query->get('month');

        if (!in_array($period, ['day', 'week', 'month'], true)) {
            $period = 'day';
        }
        ['start' => $start, 'end' => $end, 'label' => $label] =
        $this->reporting->getPeriodBounds($period, $week, $month);

        /** On met à jour les informations pour le date-picker */
        $render['period'] = $period;
        $render['selected_week'] = $week;
        $render['selected_month']= $month;

        /* Utilisateur disponible */
        $countUtilisateurDisponible = $this->reporting->getUtilisateurDisponible();

        if ($countUtilisateurDisponible['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $countUtilisateurDisponible['code'], $countUtilisateurDisponible['erreur']);
            return $this->render(static::$index, $render);
        }

        /* Utilisateur actif */
        $countUtilisateurActif = $this->reporting->getUtilisateurActif();
        if ($countUtilisateurActif['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $countUtilisateurActif['code'],  $countUtilisateurActif['erreur']);
            return $this->render(static::$index, $render);
        }

        /* Type d'OS */
        $osStats = $this->reporting->getOsStats($start, $end, $label);
        if ($osStats['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $osStats['code'],  $osStats['erreur']);
            return $this->render(static::$index, $render);
        }

        /* Type de navigateur */
        $browserStats = $this->reporting->getBrowserStats($start, $end, $label);
        if ($browserStats['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $browserStats['code'],  $browserStats['erreur']);
            return $this->render(static::$index, $render);
        }

        /* Type de device */
        $deviceStats = $this->reporting->getDeviceStats($start, $end, $label);
        if ($deviceStats['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $deviceStats['code'],  $deviceStats['erreur']);
            return $this->render(static::$index, $render);
        }

        $sessionPagesStats = $this->reporting->getSessionPagesReport($start, $end, $label);
        if ($sessionPagesStats['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $sessionPagesStats['code'],  $sessionPagesStats['erreur']);
            return $this->render(static::$index, $render);
        }

        /* Durée de la session selon la période */
        $sessionDurationByPeriodStats = $this->reporting->getSessionDurationByPeriodStats($start, $end, $label);

        if ($sessionDurationByPeriodStats['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $sessionDurationByPeriodStats['code'], $sessionDurationByPeriodStats['erreur']);
            return $this->render(static::$index, $render);
        }

        /* Moyenne de la durée d'une session */
        $avgSessionDurationStats = $this->reporting->getAvgSessionDurationReport();

        if ($avgSessionDurationStats['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $avgSessionDurationStats['code'], $avgSessionDurationStats['erreur']);
            return $this->render(static::$index, $render);
        }

        /* Session unique */
        $uniqueSessionStats = $this->reporting->getUniqueSessionReport();
        if ($uniqueSessionStats['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $uniqueSessionStats['code'], $uniqueSessionStats['erreur']);
            return $this->render(static::$index, $render);
        }

        /* Session de type courte, moyenne, longue*/
        $categoryByUniqueSessionStats = $this->reporting->getCategoryByUniqueSessionReport();

        if ($categoryByUniqueSessionStats['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $categoryByUniqueSessionStats['code'], $categoryByUniqueSessionStats['erreur']);
            return $this->render(static::$index, $render);
        }

        /* Session unique typé par une catégorie */
        $uniqueSessionCategoryStats = $this->reporting->getUniqueSessionByCategoryReport();

        if ($uniqueSessionCategoryStats['code'] !== 200) {
            self::genericAddFlashMessage('error', static::$message, $uniqueSessionCategoryStats['code'], $uniqueSessionCategoryStats['erreur']);
            return $this->render(static::$index, $render);
        }

        /** Rendu final */
        $render['nombre_utilisateur_disponible'] = $countUtilisateurDisponible['data']['nombre_utilisateur_disponible'] ?? 0;
        $render['nombre_utilisateur_actif'] = $countUtilisateurActif['data']['nombre_utilisateur_actif'] ?? 0;

        /* ==== Par period ==== */
        $render['os'] = $osStats;
        $render['browser'] = $browserStats;
        $render['device'] = $deviceStats;
        $render['page_vue_unique'] = $sessionPagesStats;
        $render['session_duration'] = $sessionDurationByPeriodStats;

        /* ==== Sur le journée ==== */
        $render['avg_session_duration'] = $avgSessionDurationStats['data'];
        $render['nb_session_unique'] = $uniqueSessionStats['data'];
        $render['session_category'] = $categoryByUniqueSessionStats;
        $render['session_unique_category'] = $uniqueSessionCategoryStats;

        return $this->render(static::$index, $render);
    }

}
