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
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Psr\Log\LoggerInterface;

use Doctrine\ORM\EntityManagerInterface;
use App\Entity\{Activity, ActivityHistorique};
use App\Repository\ActivityRepository;
use App\Service\ClientService;
use App\Service\PdfExportService;
use App\Service\UserAgent\UserAgentTrackingFacade;

/**
 * [Description ActivityController]
 */
class ActivityController extends AbstractController
{

    private static string $sonarUrl = "sonar.url";
    private static string $page = "activity/index.html.twig";
    private static string $timezone = 'Europe/Paris';
    private static string $formatDate = 'd/m/Y';
    private static string $mondayThisWeek = 'monday this week';

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
        private PdfExportService $pdfExportService,
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
        $actualDate = new \DateTime('now', new \DateTimeZone(self::$timezone));
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

        /** MODIF 2026-10-16 : KPI + tableaux semaine/mois/top10, calculés dès
         *  maintenant (lecture 100% locale, table `activity`) pour qu'ils
         *  s'affichent même si l'appel SonarQube ci-dessous échoue — ce
         *  contenu n'a aucune dépendance au serveur SonarQube, contrairement
         *  au reste de cette méthode. */
        $render += $this->buildTableauxLocaux($activityRepos);

        /** On récupère la dernière task (rtrim : éviter un double slash si SONAR_URL finit par « / ») */
        $sonarUrl = $this->getParameter(self::$sonarUrl);
        $url = rtrim(is_string($sonarUrl) ? $sonarUrl : '', '/');
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

    /**
     * [Description for buildTableauxLocaux]
     * Construit les blocs KPI/semaine/mois/top10 affichés sur la page
     * Activité — même logique d'agrégation que rapportPdf(), mais pour le
     * rendu web direct (pas de PDF). Lecture 100% locale (table `activity`).
     *
     * @param \App\Repository\ActivityRepository $activityRepos
     *
     * @return array<string, mixed>
     *
     * Created at: 16/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildTableauxLocaux(ActivityRepository $activityRepos): array
    {
        $maintenant = new \DateTimeImmutable('now', new \DateTimeZone(self::$timezone));

        /** KPI sur les 90 derniers jours (même période que le rapport PDF). */
        $debutKpi = $maintenant->modify('-90 days');
        $periodeKpi = $activityRepos->listeAnalyseProjetPeriode($debutKpi);
        $joursKpi = ($periodeKpi['code'] ?? 0) === 200 ? $periodeKpi['request'] : [];

        $totalAnalyses = 0;
        $joursAvecActivite = 0;
        $jourPic = ['date' => '—', 'analyse' => 0];
        foreach ($joursKpi as $j) {
            $analyse = (int) $j['analyse'];
            $totalAnalyses += $analyse;
            if ($analyse > 0) {
                $joursAvecActivite++;
            }
            if ($analyse > $jourPic['analyse']) {
                $jourPic = ['date' => (new \DateTimeImmutable($j['day']))->format(self::$formatDate), 'analyse' => $analyse];
            }
        }
        $countProjetsKpi = $activityRepos->countProjetsDistinctsPeriode($debutKpi);
        $totalProjetsKpi = ($countProjetsKpi['code'] ?? 0) === 200 ? (int) ($countProjetsKpi['request']['total'] ?? 0) : 0;
        $moyenneJourKpi = $joursAvecActivite > 0 ? round($totalAnalyses / $joursAvecActivite, 1) : 0;

        /** Semaine en cours (lundi -> aujourd'hui). */
        $lundiSemaine = $maintenant->modify(self::$mondayThisWeek);
        $periodeSemaine = $activityRepos->listeAnalyseProjetPeriode($lundiSemaine);
        $semaineRows = ($periodeSemaine['code'] ?? 0) === 200 ? $periodeSemaine['request'] : [];
        $joursFr = [1 => 'Lundi', 2 => 'Mardi', 3 => 'Mercredi', 4 => 'Jeudi', 5 => 'Vendredi', 6 => 'Samedi', 7 => 'Dimanche'];
        $semaineTable = array_map(static function (array $j) use ($joursFr) {
            $date = new \DateTimeImmutable($j['day']);
            $analyse = (int) $j['analyse'];
            $projet = (int) $j['projet'];
            return [
                'jour' => $joursFr[(int) $date->format('N')] . ' ' . $date->format('d/m'),
                'analyse' => $analyse,
                'projet' => $projet,
                'percent' => $analyse > 0 ? round(($projet / $analyse) * 100, 1) : 0,
            ];
        }, $semaineRows);

        /** Mois en cours, regroupé par semaine ISO (1er du mois -> aujourd'hui). */
        $debutMois = $maintenant->modify('first day of this month')->setTime(0, 0);
        $periodeMois = $activityRepos->listeAnalyseProjetPeriode($debutMois);
        $moisRows = ($periodeMois['code'] ?? 0) === 200 ? $periodeMois['request'] : [];
        $moisParSemaine = [];
        foreach ($moisRows as $j) {
            $date = new \DateTimeImmutable($j['day']);
            $cle = $date->format('W');
            if (!isset($moisParSemaine[$cle])) {
                $moisParSemaine[$cle] = [
                    'semaine' => 'Semaine ' . $date->format('W') . ' (' . $date->modify(self::$mondayThisWeek)->format('d/m') . '-' . $date->modify('sunday this week')->format('d/m') . ')',
                    'analyse' => 0,
                    'projet_max' => 0,
                ];
            }
            $moisParSemaine[$cle]['analyse'] += (int) $j['analyse'];
            $moisParSemaine[$cle]['projet_max'] = max($moisParSemaine[$cle]['projet_max'], (int) $j['projet']);
        }
        foreach ($moisParSemaine as &$semaine) {
            $semaine['percent'] = $semaine['analyse'] > 0 ? round(($semaine['projet_max'] / $semaine['analyse']) * 100, 1) : 0;
        }
        unset($semaine);
        $moisTable = array_values($moisParSemaine);

        /** Top 10 pics d'analyse (toutes périodes confondues). */
        $top10Pics = $activityRepos->top10PicsAnalyse(10);
        $top10PicsTable = (($top10Pics['code'] ?? 0) === 200) ? array_map(static fn(array $j) => [
            'date' => (new \DateTimeImmutable($j['day']))->format(self::$formatDate),
            'analyse' => (int) $j['analyse'],
            'projet' => (int) $j['projet'],
        ], $top10Pics['request']) : [];

        /** Top 10 projets — semaine / mois en cours. */
        $topSemaine = $activityRepos->topProjetsPeriode($lundiSemaine, 10);
        $topSemaineTable = (($topSemaine['code'] ?? 0) === 200) ? $topSemaine['request'] : [];

        $topMois = $activityRepos->topProjetsPeriode($debutMois, 10);
        $topMoisTable = (($topMois['code'] ?? 0) === 200) ? $topMois['request'] : [];

        return [
            'kpi_total_analyses' => $totalAnalyses,
            'kpi_total_projets' => $totalProjetsKpi,
            'kpi_moyenne_jour' => $moyenneJourKpi,
            'kpi_jour_pic' => $jourPic,
            'semaine_table' => $semaineTable,
            'mois_table' => $moisTable,
            'top10_pics' => $top10PicsTable,
            'top10_projets_semaine' => $topSemaineTable,
            'top10_projets_mois' => $topMoisTable,
        ];
    }

    /**
     * [Description for rapportPdf]
     * Export PDF du rapport Activité : synthèse, histogramme hebdomadaire,
     * journées de pic détectées (écart significatif à la moyenne) et top
     * projets, sur une période glissante (90 jours par défaut). Lecture
     * 100% locale (table `activity`), aucun appel SonarQube — contrairement
     * à index(), qui vérifie en plus la disponibilité du serveur.
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 16/10/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/activity/rapport/pdf', name: 'activity_rapport_pdf', methods: ['GET'])]
    #[IsGranted('ROLE_ACTIVITY')]
    public function rapportPdf(Request $request): Response
    {
        $days = max(7, (int) $request->query->get('days', 90));
        $download = (bool) $request->query->get('download', false);

        $fin = new \DateTimeImmutable('today', new \DateTimeZone(self::$timezone));
        $debut = $fin->modify("-{$days} days");

        $activityRepos = $this->em->getRepository(Activity::class);
        $periode = $activityRepos->listeAnalyseProjetPeriode($debut);
        $jours = ($periode['code'] ?? 0) === 200 ? $periode['request'] : [];

        $totalAnalyses = 0;
        $joursAvecActivite = 0;
        $jourPic = ['date' => '—', 'analyse' => 0];
        $sommeAnalyse = 0;
        foreach ($jours as $j) {
            $analyse = (int) $j['analyse'];
            $totalAnalyses += $analyse;
            if ($analyse > 0) {
                $joursAvecActivite++;
                $sommeAnalyse += $analyse;
            }
            if ($analyse > $jourPic['analyse']) {
                $jourPic = ['date' => (new \DateTimeImmutable($j['day']))->format(self::$formatDate), 'analyse' => $analyse];
            }
        }
        $moyenneJour = $joursAvecActivite > 0 ? round($sommeAnalyse / $joursAvecActivite, 1) : 0;

        /** Détection des journées de pic : écart significatif à la moyenne
         *  (seuil = 2x la moyenne, avec un plancher absolu pour éviter de
         *  signaler du bruit sur un faible volume). Classification
         *  large/concentré selon le ratio projets distincts / analyses. */
        $seuilPic = max(10, $moyenneJour * 2);
        $pics = [];
        foreach ($jours as $j) {
            $analyse = (int) $j['analyse'];
            $projet = (int) $j['projet'];
            if ($analyse >= $seuilPic) {
                $pics[] = [
                    'date' => (new \DateTimeImmutable($j['day']))->format(self::$formatDate),
                    'dateIso' => $j['day'],
                    'analyse' => $analyse,
                    'projet' => $projet,
                    'type' => ($analyse > 0 && ($projet / $analyse) < 0.3) ? 'concentre' : 'large',
                ];
            }
        }

        /** Agrégation par semaine ISO pour l'histogramme (lisible sur 90 jours,
         *  contrairement à une barre par jour). */
        $semaines = [];
        foreach ($jours as $j) {
            $date = new \DateTimeImmutable($j['day']);
            $cle = $date->format('o-W');
            if (!isset($semaines[$cle])) {
                $lundi = $date->modify(self::$mondayThisWeek);
                $dimanche = $date->modify('sunday this week');
                $semaines[$cle] = [
                    'label' => $lundi->format('d/m') . '-' . $dimanche->format('d/m'),
                    'total' => 0,
                    'projet_max' => 0,
                    'pic' => null,
                ];
            }
            $semaines[$cle]['total'] += (int) $j['analyse'];
            $semaines[$cle]['projet_max'] = max($semaines[$cle]['projet_max'], (int) $j['projet']);
        }
        foreach ($pics as $pic) {
            $date = new \DateTimeImmutable($pic['dateIso']);
            $cle = $date->format('o-W');
            if (isset($semaines[$cle])) {
                $semaines[$cle]['pic'] = $pic['type'];
            }
        }
        $semaines = array_values($semaines);

        $topProjetsResult = $activityRepos->topProjetsPeriode($debut, 10);
        $topProjets = ($topProjetsResult['code'] ?? 0) === 200 ? $topProjetsResult['request'] : [];

        $countProjetsResult = $activityRepos->countProjetsDistinctsPeriode($debut);
        $totalProjets = ($countProjetsResult['code'] ?? 0) === 200 ? (int) ($countProjetsResult['request']['total'] ?? 0) : 0;

        $rapport = [
            'periode_label' => sprintf('%s — %s (%d jours)', $debut->format(self::$formatDate), $fin->format(self::$formatDate), $days),
            'total_analyses' => $totalAnalyses,
            'total_projets' => $totalProjets,
            'moyenne_jour' => $moyenneJour,
            'jour_pic' => $jourPic,
            'semaines' => $semaines,
            'pics' => $pics,
            'top_projets' => $topProjets,
        ];

        $pdfContent = $this->pdfExportService->generateActivityPdf($rapport, 'Document Interne');

        $disposition = $download ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE;
        $filename = sprintf('rapport_activity_%s.pdf', date('Y-m-d_H-i'));

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename=' . $filename,
        ]);
    }
}
