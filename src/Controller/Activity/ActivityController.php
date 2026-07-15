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

namespace App\Controller\Activity;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Activity, ActivityHistorique};
use App\Service\ClientService;
use App\Service\UserAgent\UserAgentTrackingFacade;

/**
 * [Description ActivityController]
 */
class ActivityController extends AbstractController
{

    private static string $sonarUrl = "sonar.url";
    private static string $page = "activity/index.html.twig";
    private const NO_DATA = 'Pas de données disponibles pour le moment.';

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    /**
     * [Description for __construct]
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        private EntityManagerInterface $em,
        ParameterBagInterface $params,
        private ClientService $client,
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
     * Created at: 21/12/2024 20:50:02 (Europe/Paris)
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
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 14/06/2024, 16:00:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/activity', name: 'activity', methods: 'GET')]
    #[IsGranted('ROLE_ACTIVITY')]
    public function index(): Response
    {
        $this->tracking->track('ACTIVITY');

        /** On instancie l'EntityRepository */
        $activityRepos = $this->em->getRepository(Activity::class);
        $activityHistoriqueRepos = $this->em->getRepository(ActivityHistorique::class);

        // On récupère l'année actuelle
        $actualDate = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        $actualYear = $actualDate->format('Y');

        // Initialise les valeurs par défaut
        $data = [
            'year' => '---',
            'day' => '---',
            'analyse' => '---',
            'success' => '---',
            'failed' => '---',
            'max_time' => '---',
            'analyse_average' => '---',
            'success_rate' => '---',
            'date_enregistrement' => '---'
        ];

        $render = $this->genericRender();
        $render['data'] = [$data];

        /** On récupère la dernière task (rtrim : éviter un double slash si SONAR_URL finit par « / ») */
        $url = rtrim($this->getParameter(self::$sonarUrl), '/');
        $queryParams = ['ps' => 1];
        $result = $this->client->httpActivity("$url/api/ce/activity?" . http_build_query($queryParams));

        /** On catch les erreurs HTTP */
        if (isset($result['code']) && in_array($result['code'], [400, 401, 403, 404, 407, 414, 418, 422, 429, 500, 502, 503, 504, 505])) {
            $this->logger->error('[Activity-Page] ❌ Échec de l\'appel httpActivity.', [
                'code' => $result['code'],
                'erreur' => $result['erreur'] ?? self::NO_DATA
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($result['erreur'] ?? 'Erreur SonarQube')
            ]);
            return $this->render(self::$page, $render);
        }

        /** On vérifie si pour l'année courante, il y des enregistrement ou non  */
        $selectActivity = $activityRepos->selectActivity($actualYear);
        if (($selectActivity['code'] ?? 0) !== 200) {
            $this->logger->error('[Activity-Page] ❌ Échec de la requête selectActivity.', [
                'code' => $selectActivity['code'] ?? self::NO_DATA,
                'erreur' => $selectActivity['erreur'] ?? self::NO_DATA
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => "❌ Une erreur est survenue lors de la récupération des analyses."
            ]);
            return $this->render(self::$page, $render);
        }

        $listeAnalyse = $selectActivity['liste'];
        if (empty($listeAnalyse)) {
            // Ajoute un message flash pour informer l'utilisateur que la liste des analyses est vide pour l'année courante.
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => "⚠️ Aucune analyse en base pour cette année. La collecte automatique (cron « app:activity:collecte ») alimentera la table."
            ]);
            return $this->render(self::$page, $render);
        }

        /** On prend la date la plus récente en base et la dernière de l'analyse */
        $dernierDate = $activityRepos->dernierDate();
        if (($dernierDate['code'] ?? 0) !== 200 || empty($dernierDate['liste']['date'])) {
            $this->logger->warning('[Activity-Page] ⚠️ Date de dernière analyse indisponible.');
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => "⚠️ La date de dernière analyse est indisponible."
            ]);
            return $this->render(self::$page, $render);
        }
        $dateBase = new \DateTime($dernierDate['liste']['date']);
        $dateSonar = new \DateTime($result['json']['tasks'][0]['executedAt']);

        /**
         * Si l'historique agrégé n'a pas encore été généré (table activity_historique
         * vide), on affiche UN SEUL message d'action — pas une erreur — et on s'arrête.
         * On n'affiche pas la comparaison de fraîcheur ci-dessous, qui prêterait à
         * confusion (« à jour » alors qu'aucun tableau n'existe encore).
         */
        $listeHistorique = $activityHistoriqueRepos->selectActivity();
        if (empty($listeHistorique['liste'])) {
            $this->addFlash('notice', [
                'type' => 'primary',
                'message' => "📊 Aucune statistique n'a encore été générée. Cliquez sur « Recalculer les statistiques » pour construire le tableau."
            ]);
            return $this->render(self::$page, $render);
        }

        /** Si SonarQube contient des nouvelles tâches */
        if ($dateSonar > $dateBase) {
            // Écart en jours TOTAUX (%a) entre la base et SonarQube.
            $interval = $dateBase->diff($dateSonar)->format('%a');
            // La collecte n'est plus déclenchable depuis l'UI : message purement informatif.
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => "⏳ SonarQube a des analyses plus récentes que la base (" . $interval . " jours d'écart). La collecte automatique quotidienne les intégrera."
            ]);
        }

        /** Si SonarQube ne contient pas de nouvelles tâches */
        if ($dateSonar == $dateBase) {
            $this->addFlash('notice', [
                'type' => 'info',
                'message' => "📌 La liste des analyses SonarQube est à jour."
            ]);
        }

        /** Pour chaque durée d'execution on convertit les secondes (int) en h:m:s */
        for ($i = 0; $i < count($listeHistorique['liste']); $i++) {
            $listeHistorique['liste'][$i]['max_time'] = gmdate('H:i:s', (int) $listeHistorique['liste'][$i]['max_time']);
        }

        /** On formate la date d'enregistrement (depuis l'historique BDD, pas $result qui est l'API SonarQube) */
        $rawDateEnregistrement = $listeHistorique['liste'][0]['date_enregistrement'] ?? null;
        $listeHistorique['liste'][0]['date_enregistrement'] = $rawDateEnregistrement
            ? (new \DateTime($rawDateEnregistrement))->format('d-m-Y H:i:s')
            : (new \DateTime('1980-01-01 00:00:00'))->format('d-m-Y H:i:s');

        $render['data'] = $listeHistorique['liste'];
        return $this->render(self::$page, $render);
    }
}
