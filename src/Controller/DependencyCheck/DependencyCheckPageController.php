<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Controller\DependencyCheck;

use App\Controller\Traits\AppUserAware;
use App\Repository\{DcCveRepository, DcDependencyRepository, DcFindingRepository,DcScanRepository};
use App\Service\DependencyCheck\{DcExecutiveAnalyticsService};
use App\Service\{MesProjets,PdfExportService};
use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Util\MavenVersionComparator;
use App\Util\ScanViewMode;
use Knp\Component\Pager\PaginatorInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Request, Response};
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * MODIF 2026-05-08 : pages web OWASP DependencyCheck.
 *
 * 3 pages :
 *  - /dependency-check                                  -> index : liste projets scannes
 *  - /dependency-check/projet/{group}/{artifact}/{vers} -> detail d'un scan
 *  - /dependency-check/dashboard                        -> vue agrégée cross-projets
 *
 * MODIF 2026-05-10 : ACL ROLE_SECURITY au niveau classe,
 * en complement du filtrage groupe_fonctionnel (cf. isProjectAccessible).
 * Avant : seul le bouton dans templates/owasp/index.html.twig était conditionne
 * par is_granted('ROLE_SECURITY'), mais l'URL /dependency-check/* était
 * accessible directement par tout ROLE_UTILISATEUR (aucun access_control
 * dédié dans security.yaml). Ne touche pas aux endpoints /api/secure/dependency-check/
 * qui restent en PUBLIC_ACCESS pour la CI (token bearer).
 */
#[IsGranted('ROLE_SECURITY')]
class DependencyCheckPageController extends AbstractController
{
    use AppUserAware;

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;
    private static string $page = 'dependency_check/index.html.twig';

    const REGEX = '[A-Za-z0-9._-]+';

    public function __construct(
        ParameterBagInterface $params,
        private readonly DcScanRepository $scanRepository,
        private readonly DcCveRepository $cveRepository,
        private readonly DcDependencyRepository $depRepository,
        private readonly DcFindingRepository $findingRepository,
        private readonly PdfExportService $pdfExportService,
        private readonly PaginatorInterface $paginator,
        private readonly LoggerInterface $logger,
        private readonly DcExecutiveAnalyticsService $dcAnalytics,
        private readonly MesProjets $mesProjets,
        private readonly UserAgentTrackingFacade $tracking
    ) {
        $this->logoEntreprise        = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong  = $params->get('marque.entreprise.long');
        $this->environnement         = $params->get('environnement');
        $this->version               = $params->get('version');
        $this->dateCopyright         = date('Y');
    }

    /**
     * [Description for genericRender]
     *
     * @return array<string, mixed>
    *
     * Created at: 08/05/2026 18:06:13 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        return [
            'type_footer'             => null,
            'logo_entreprise'         => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long'  => $this->marqueEntrepriseLong,
            'env'                     => $this->environnement,
            'version'                 => $this->version,
            'date_copyright'          => $this->dateCopyright,
        ];
    }

    /**
     * [Description for isAnalyticsMode]
     * MODIF 2026-05-11 : determine si l'utilisateur
     * doit voir le dashboard DC en mode "vue globale" (toutes maven_keys) ou
     * en mode "périmètre filtre" (ses groupes_fonctionnels uniquement).
     * Profils org-wide : RSSI, équipe sécu, chef de projet, top management.
     *
     * @return bool
     *
     * Created at: 11/05/2026 10:01:12 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function isAnalyticsMode(): bool
    {
        return $this->isGranted('ROLE_SECURITY_ANALYTICS');
    }

    /**
     * [Description for currentScopeLabel]
     * MODIF 2026-05-11 : libelle court pour le badge
     * "périmètre" affiche en tête de dashboard. En analytics : "Vue globale".
     * Sinon : "Périmètre : groupe1, groupe2" (liste des groupes_fonctionnels).
     *
     * @return string
     *
     * Created at: 11/05/2026 10:01:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function currentScopeLabel(): string
    {
        if ($this->isAnalyticsMode()) {
            return 'Vue globale';
        }
        $groupes = $this->appUser()->getListeGroupeFonctionnel();
        if (empty($groupes)) {
            return 'Aucun perimetre';
        }
        return 'Périmètre : ' . implode(', ', $groupes);
    }

    /**
     * [Description for allowedMavenKeys]
     * Construit l'ensemble des maven_keys "{group}:{artifact}" accessibles
     * a l'utilisateur connecte selon ses groupes_fonctionnels.
     *
     * MODIF 2026-05-11 : factorisation pour mutualiser le
     * lookup entre isProjectAccessible() (ACL strict 403 sur project-scoped)
     * et index() (filtrage de la liste multi-projets).
     *
     * Retour : array associatif ['group:artifact' => true] pour lookup O(1),
     * ou null si l'utilisateur n'a aucun périmètre valide (groupes vides,
     * erreur MesProjets ou liste vide).
     *
     * @return array<string, true>|null
     *
     * Created at: 11/05/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function allowedMavenKeys(): ?array
    {
        $groupes = $this->appUser()->getListeGroupeFonctionnel();
        if (empty($groupes)) {
            return null;
        }
        return $this->allowedMavenKeysForGroupes($groupes);
    }

    /**
     * MODIF 2026-05-13 : variante de
     * allowedMavenKeys() qui prend une liste de groupes en argument au lieu
     * de prendre TOUS les groupes du user. Permet de restreindre le périmètre
     * du dashboard à 1 seul groupe via la combo box "Filtrer par groupe".
     *
     * Pas de vérification ici que les groupes appartiennent au user — la
     * validation se fait côté appelant via $this->appUser()->getListeGroupeFonctionnel().
     *
     * @param array<int, string> $groupes
     * @return array<string, true>|null
     */
    private function allowedMavenKeysForGroupes(array $groupes): ?array
    {
        if (empty($groupes)) {
            return null;
        }
        $mesProjets = $this->mesProjets->liste($groupes);
        if (($mesProjets['code'] ?? 500) !== 200 || !isset($mesProjets['projets'])) {
            return null;
        }
        $set = [];
        foreach ($mesProjets['projets'] as $projet) {
            if (isset($projet['id'])) {
                $set[(string) $projet['id']] = true;
            }
        }
        return $set;
    }

    /**
     * [Description for isProjectAccessible]
     * Vérifie que la maven_key {group}:{artifact} appartient a un des
     * groupes_fonctionnels de l'utilisateur connecte.
     *
     * MODIF 2026-05-10  : ACL stricte. Les routes
     * project-scoped (`projet`, `projetPdf`, `executive`) doivent throw
     * createAccessDeniedException (HTTP 403) si false, pour bloquer toute
     * tentative d'accès direct par URL trafiquée (IDOR / OWASP A01:2021).
     *
     * MODIF 2026-05-11 : délègue au helper allowedMavenKeys()
     * mutualise avec index().
     *
     * @param string $group
     * @param string $artifact
     * @return bool
     *
     * Created at: 10/05/2026 18:30:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function isProjectAccessible(string $group, string $artifact): bool
    {
        /* MODIF 2026-05-16 : en mode analytics
         * (ROLE_SECURITY_ANALYTICS) allowedMavenKeys() retourne null car l'user
         * n'a pas de groupes. null != accès interdit = accès global. */
        if ($this->isAnalyticsMode()) {
            return true;
        }
        $allowed = $this->allowedMavenKeys();
        if ($allowed === null) {
            return false;
        }
        return isset($allowed["{$group}:{$artifact}"]);
    }

    /**
     * [Description for index]
     * Index : liste des projets scannes avec leur dernier scan.
     *
     * @return Response
     *
     * Created at: 08/05/2026 18:06:54 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/dependency-check', name: 'dc_index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        $this->tracking->track('DC_INDEX');

        $render = $this->genericRender();

        /* MODIF 2026-05-14 : lecture du mode
         * de vue depuis le query param ?vue=prod|dev (default = prod).
         * En mode PROD on est permissif : un couple sans release ingérée
         * apparaît quand meme avec un flag has_release=false que le template
         * affichera grise + badge. En mode DEV on n'affiche que les couples
         * ayant un scan marqué is_latest_overall (cas standard). */
        $vue = ScanViewMode::normalize($request->query->get('vue'));
        $render['vue'] = $vue;

        $allowed = $this->allowedMavenKeys();
        if ($allowed === null) {
            $this->addFlash('notice', [
                'type'    => 'info',
                'message' => 'Aucun projet accessible dans votre périmètre groupe_fonctionnel.',
            ]);
            $render['projets'] = [];
            return $this->render(self::$page, $render);
        }

        $allowedKeysList = array_keys($allowed);
        // dc_index : permissif (couples sans release affiches avec badge "Pas de release ingérée")
        $result = $this->scanRepository->listLatestPerCoupleForView($vue, $allowedKeysList, true);
        if ($result['code'] !== 200) {
            $this->logger->error('[DC] ❌ Échec de la requête listLatestPerCoupleForView.', [
                'vue'    => $vue,
                'code'   => $result['code'],
                'erreur' => $result['erreur'] ?? '',
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => sprintf(
                    'La récupération des projets a échoué (Erreur %s). %s',
                    $result['code'],
                    $result['erreur'] ?? ''
                ),
            ]);
            $render['projets'] = [];
            return $this->render(self::$page, $render);
        }

        $render['projets'] = $result['liste'] ?? [];

        return $this->render(self::$page, $render);
    }

    /**
     * [Description for history]
     * Page historique
     * d'une application — vue d'évolution temporelle CVE par sévérité sur tous
     * les scans du couple (group, artifact), toutes versions confondues.
     *
     * @param string $group
     * @param string $artifact
     *
     * @return Response
     *
     * Created at: 12/07/2026 09:03:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/dependency-check/projet/{group}/{artifact}/history', name: 'dc_projet_history', methods: ['GET'],
        requirements: ['group' => self::REGEX, 'artifact' => self::REGEX])]
    public function history(string $group, string $artifact): Response
    {
        /**
         *
         * Affiche :
         *  - line chart Chart.js : 4 courbes (CRITICAL/HIGH/MEDIUM/LOW) × scan_date
         *  - tableau des scans : 1 ligne = 1 scan, avec deltas vs scan précédent
         *    colorés (vert = baisse = bon, rouge = hausse = régression)
         *  - liens cliquables vers la page dc_projet de chaque scan
         *
         * ACL : ROLE_SECURITY (classe) + isProjectAccessible (anti-IDOR).
         *
         * MODIF 2026-05-16 : déplacée AVANT dc_projet.
         * Why: la route `/projet/{g}/{a}/{v}` (REGEX `[A-Za-z0-9._-]+` sur version)
         * matche /projet/x/y/history avec version="history", masquant cette route.
         * Symfony route dans l'ordre de déclaration → on déclare la plus spécifique
         * en premier. Bouton "Historique" du template projet désormais fonctionnel.
         */

        $this->tracking->track('DC_HISTORY');

        $render = $this->genericRender();
        $render['project_group']    = $group;
        $render['project_artifact'] = $artifact;

        if (!$this->isProjectAccessible($group, $artifact)) {
            throw $this->createAccessDeniedException(
                sprintf('Projet %s:%s hors du périmètre du groupe_fonctionnel.', $group, $artifact)
            );
        }

        $history = $this->extractListeOrFlash(
            $this->scanRepository->listHistoryForProject($group, $artifact),
            'listHistoryForProject'
        );

        /* Calcul des deltas vs scan précédent (côté PHP).
         * Pour chaque scan (à partir du 2e), on ajoute delta_critical, delta_high,
         * delta_medium, delta_low. Premier scan : tous null (= "1ère analyse"). */
        $previous = null;
        foreach ($history as &$s) {
            if ($previous === null) {
                $s['delta_critical'] = null;
                $s['delta_high']     = null;
                $s['delta_medium']   = null;
                $s['delta_low']      = null;
            } else {
                $s['delta_critical'] = $s['cve_count_critical'] - $previous['cve_count_critical'];
                $s['delta_high']     = $s['cve_count_high']     - $previous['cve_count_high'];
                $s['delta_medium']   = $s['cve_count_medium']   - $previous['cve_count_medium'];
                $s['delta_low']      = $s['cve_count_low']      - $previous['cve_count_low'];
            }
            $previous = $s;
        }
        unset($s);

        /* Datasets Chart.js (4 lignes par sévérité, axe X = scan_date ISO). */
        $chartData = [
            'labels' => array_map(
                static fn(array $s): string => $s['scan_date']->format('Y-m-d H:i'),
                $history
            ),
            'versions' => array_map(
                static fn(array $s): string => $s['project_version'],
                $history
            ),
            'critical' => array_map(static fn(array $s): int => $s['cve_count_critical'], $history),
            'high'     => array_map(static fn(array $s): int => $s['cve_count_high'],     $history),
            'medium'   => array_map(static fn(array $s): int => $s['cve_count_medium'],   $history),
            'low'      => array_map(static fn(array $s): int => $s['cve_count_low'],      $history),
        ];

        $render['history']    = array_reverse($history); // affichage tableau : récent en haut
        $render['chart_data'] = $chartData;
        $render['nb_scans']   = count($history);

        /* Périmètre temporel + nb versions distinctes (synthèse haut de page). */
        if (!empty($history)) {
            $render['first_scan_date'] = $history[0]['scan_date'];
            $render['last_scan_date']  = end($history)['scan_date'];
            $render['nb_versions']     = count(array_unique(array_column($history, 'project_version')));
        } else {
            $render['first_scan_date'] = null;
            $render['last_scan_date']  = null;
            $render['nb_versions']     = 0;
        }

        return $this->render('dependency_check/projet_history.html.twig', $render);
    }

    /**
     * [Description for projet]
     *  Vue par projet : detail d'un scan.
     *
     * @param string $group
     * @param string $artifact
     * @param string $version
     *
     * @return Response
     *
     * Created at: 08/05/2026 18:07:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/dependency-check/projet/{group}/{artifact}/{version}', name: 'dc_projet', methods: ['GET'],
        requirements: ['group' => self::REGEX, 'artifact' => self::REGEX, 'version' => self::REGEX])]
    public function projet(string $group, string $artifact, string $version): Response
    {
        $this->tracking->track('DC_PROJET');

        $render = $this->genericRender();

        /* MODIF 2026-05-10 : ACL stricte HTTP 403
         * si le projet n'est pas dans le périmètre groupe_fonctionnel de
         * l'utilisateur connecte (faille IDOR sur URL trafiquée). */
        if (!$this->isProjectAccessible($group, $artifact)) {
            throw $this->createAccessDeniedException(
                sprintf('Projet %s:%s hors du périmètre du groupe_fonctionnel.', $group, $artifact)
            );
        }
        $scan = $this->scanRepository->findLatestForProject($group, $artifact, $version);
        if ($scan === null) {
            /* MODIF 2026-05-10 : flash enrichi qui
             * remplace le callout doublon supprime du template. Inclut
             * le rappel de verifier la CI (ancien texte du callout). */
            $this->addFlash('notice', [
                'type'    => 'warning',
                'message' => sprintf(
                    'Aucun scan trouvé pour %s:%s:%s. Vérifier que la CI a bien envoyé un rapport DependencyCheck pour cette version.',
                    $group, $artifact, $version
                ),
            ]);
            $render['scan'] = null;
            $render['findings'] = [];
        } else {
            $render['scan'] = $scan;

            $result = $this->findingRepository->listForScan((int) $scan->getId());
            if ($result['code'] !== 200) {
                $this->logger->error('[DC] ❌ Échec de la requête listForScan.', [
                    'scan_id' => (int) $scan->getId(),
                    'code'    => $result['code'],
                    'erreur'  => $result['erreur'] ?? '',
                ]);
                $this->addFlash('notice', [
                    'type' => 'error',
                    'message' => sprintf(
                        'La récupération des findings a échoué (Erreur %s). %s',
                        $result['code'],
                        $result['erreur'] ?? ''
                    ),
                ]);
                $render['findings'] = [];
            } else {
                $render['findings'] = $result['liste'] ?? [];
            }
        }
        $render['project_group']    = $group;
        $render['project_artifact'] = $artifact;
        $render['project_version']  = $version;

        return $this->render('dependency_check/projet.html.twig', $render);
    }

    /**
     * [Description for projetPdf]
     * Export PDF d'un scan projet.
     * Réutilise le PdfExportService et le template rapport-dc.html.twig.
     *
     * @param string $group
     * @param string $artifact
     * @param string $version
     *
     * @return Response
     *
     * Created at: 08/05/2026 18:15:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/dependency-check/projet/{group}/{artifact}/{version}/pdf', name: 'dc_projet_pdf', methods: ['GET'],
        requirements: ['group'    => self::REGEX, 'artifact' => self::REGEX, 'version'  => self::REGEX,])]
    public function projetPdf(string $group, string $artifact, string $version, Request $request): Response
    {
        /* MODIF 2026-05-10 : ACL stricte HTTP 403
         * même pour l'export PDF (anti-IDOR). */
        if (!$this->isProjectAccessible($group, $artifact)) {
            throw $this->createAccessDeniedException(
                sprintf('Projet %s:%s hors du périmètre du groupe_fonctionnel.', $group, $artifact)
            );
        }
        $scan = $this->scanRepository->findLatestForProject($group, $artifact, $version);

        $findings = [];
        if ($scan !== null) {
            $result = $this->findingRepository->listForScan((int) $scan->getId());
            if ($result['code'] !== 200) {
                $this->logger->error('[DC] ❌ Échec de la requête listForScan (PDF)', [
                    'scan_id' => (int) $scan->getId(),
                    'code'    => $result['code'],
                    'erreur'  => $result['erreur'] ?? '',
                ]);
            } else {
                $findings = $result['liste'] ?? [];
            }
        }

        // Données executive : top 5 deps par nb CVE + top 5 CWE par occurrence
        // computeTopCwes déplacé dans DcExecutiveAnalyticsService (logique identique).
        //computeTopDeps reste local au controller car spécifique au PDF (forme de retour différente).
        $topDeps = $this->computeTopDeps($findings, 5);
        $topCwes = $this->dcAnalytics->computeTopCwes($findings, 5);

        /* alignement avec generateSuiviPdf.
         * ?type=... pilote la mention bas-de-page + texte du filigrane.
         * ?watermark=on|off prime sur RAPPORT_PDF_WATERMARK_MODE (env). */
        $documentType = (string) $request->query->get('type', 'Document confidentiel');
        $wmParam = strtolower((string) $request->query->get('watermark', ''));
        $watermarkOverride = match ($wmParam) {
            'on', '1', 'true', 'yes' => true,
            'off', '0', 'false', 'no' => false,
            default => null,
        };

        $pdf = $this->pdfExportService->generateDcPdf(
            [
                'project_group'    => $group,
                'project_artifact' => $artifact,
                'project_version'  => $version,
                'scan'             => $scan,
                'findings'         => $findings,
                'top_deps'         => $topDeps,
                'top_cwes'         => $topCwes,
            ],
            $documentType,
            $watermarkOverride,
        );

        $filename = sprintf('rapport-dc-%s-%s.pdf', $artifact, $version);

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => sprintf('attachment; filename="%s"', $filename),
        ]);
    }

    /**
     * [Description for computeTopDeps]
     * top N dépendances de ce scan par nb CVE.
     *
     * @param array<int, array<string, mixed>> $findings
     * @return array<int, array{label: string, nb_cves: int, version: ?string}>
     *
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function computeTopDeps(array $findings, int $limit): array
    {
        $stats = []; // label => [nb_cves, version]
        foreach ($findings as $f) {
            $label = (string) ($f['product'] ?: $f['file_name'] ?: 'inconnu');
            if (!isset($stats[$label])) {
                $stats[$label] = ['label' => $label, 'nb_cves' => 0, 'version' => $f['dep_version'] ?? null];
            }
            $stats[$label]['nb_cves']++;
        }
        usort($stats, static fn($a, $b) => $b['nb_cves'] <=> $a['nb_cves']);
        return array_slice($stats, 0, $limit);
    }

    /**
     * [Description for dashboard]
     * Dashboard agrégé cross-projets.
     * Pagination de la table CVE critiques (25 items par page)
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 08/05/2026 18:19:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/dependency-check/dashboard', name: 'dc_dashboard', methods: ['GET'])]
    public function dashboard(Request $request): Response
    {
        $this->tracking->track('DC_DASHBOARD');

        $render = $this->genericRender();

        /* MODIF 2026-05-11 : expose le mode et le
         * libelle de périmètre au template. */
        $render['analytics_mode'] = $this->isAnalyticsMode();
        $render['scope_label']    = $this->currentScopeLabel();

        /* MODIF 2026-05-11 : calcule la liste des
         * maven_keys autorisées. null si analytics_mode (= org-wide), sinon
         * liste plate des clés autorisées pour les 4 requêtes filtrées.
         * Si l'utilisateur n'a aucun périmètre valide (helper retourne null),
         * on force [] pour que les requêtes retournent des listes vides.
         *
         * MODIF 2026-05-13 : filtre optionnel sur 1 groupe
         * fonctionnel via query string ?groupe=<nom>. Restreint encore plus
         * le périmètre déjà filtré par groupes du user. Désactivé en mode
         * analytics (vue globale = pas de notion de groupe). */
        $userGroupes        = $this->appUser()->getListeGroupeFonctionnel();
        $groupeFilter       = trim((string) $request->query->get('groupe', ''));
        $isGroupeFilterValid = $groupeFilter !== ''
            && in_array($groupeFilter, $userGroupes, true)
            && !$this->isAnalyticsMode();

        if ($this->isAnalyticsMode()) {
            $allowedKeysList = null; // org-wide
        } elseif ($isGroupeFilterValid) {
            $allowedKeysMap  = $this->allowedMavenKeysForGroupes([$groupeFilter]);
            $allowedKeysList = $allowedKeysMap === null ? [] : array_keys($allowedKeysMap);
        } else {
            $allowedKeysMap  = $this->allowedMavenKeys();
            $allowedKeysList = $allowedKeysMap === null ? [] : array_keys($allowedKeysMap);
        }
        // Combo box visible uniquement si le user a >=2 groupes (sinon pas
        // d'intérêt à filtrer). En analytics_mode, on cache la combo.
        $render['available_groupes']     = $this->isAnalyticsMode() ? [] : $userGroupes;
        $render['current_groupe_filter'] = $isGroupeFilterValid ? $groupeFilter : null;

        /* MODIF 2026-05-14 : mode de vue
         * prod/dev. Strict ici (pas de fallback permissif comme dc_index) :
         *   - vue=prod : ne montre que les couples avec une release ingérée
         *   - vue=dev  : ne montre que les couples avec un scan latest_overall
         * scans_resume est donc réduit a 1 ligne par couple, ce qui propage
         * automatiquement le filtre aux sections dérivées : cartographie,
         * mes_projets_exposes, synthèse par socle, distribution archetype x
         * socle. Les autres requêtes SQL (cwes_breakdown, top_deps,
         * critical_cves, mutualisables...) agrègent encore sur toutes les
         * versions -> à étendre en step 7b.2. */
        $vue = ScanViewMode::normalize($request->query->get('vue'));
        $render['vue'] = $vue;

        /* MODIF 2026-05-12 : chargement de
         * scans_resume EN PREMIER pour pouvoir en dériver la liste des
         * archétypes disponibles + appliquer le filtre éventuel AVANT les
         * autres requêtes repo (cwes_breakdown, top_deps, critical_cves).
         * Avec un filtre archétype actif, on restreint scans_resume et on
         * recalcule $allowedKeysList sur les maven_keys du sous-ensemble. */
        $scansResume = $this->extractListeOrFlash(
            $this->scanRepository->listLatestPerCoupleForView($vue, $allowedKeysList),
            'listLatestPerCoupleForView'
        );

        /* MODIF 2026-05-12 : nommage UI "socle" au
         * lieu de "archétype" cote affichage. Le `parent_label`/`parent_version`
         * du pom.xml représente le SOCLE (module deps + logique technique/sécurité,
         * ex: springboot-config) — distinct de l'ARCHETYPE qui est le template
         * de generation du projet (= `archetype_version`). Le query param reste
         * `?archetype=...` pour les URLs deja partagées, mais le label UI parle
         * de socle. Cote code SQL/repo, on garde les noms `parent_label`. */
        $availableSocles = [];
        foreach ($scansResume as $s) {
            $label   = $s['parent_label']   ?? null;
            $version = $s['parent_version'] ?? null;
            if ($label !== null && $label !== '' && $version !== null && $version !== '') {
                $availableSocles[$label . '@' . $version] = true;
            }
        }
        $availableSocles = array_keys($availableSocles);
        sort($availableSocles);

        // Lecture du filtre depuis la query string (param historique 'archetype'
        // conserve pour ne pas casser les URLs partagées, alias 'socle' accepte).
        $socleFilter = trim((string) (
            $request->query->get('socle')
            ?? $request->query->get('archetype', '')
        ));
        $isSocleFilterValid = $socleFilter !== ''
            && $socleFilter !== 'all'
            && in_array($socleFilter, $availableSocles, true);

        if ($isSocleFilterValid) {
            $scansResume = array_values(array_filter(
                $scansResume,
                static fn(array $s) =>
                    ($s['parent_label'] ?? '') !== ''
                    && ($s['parent_version'] ?? '') !== ''
                    && (($s['parent_label'] . '@' . $s['parent_version']) === $socleFilter)
            ));
            // Restreindre $allowedKeysList aux maven_keys filtrés ; tableau vide
            // si rien (les repos retournent alors une liste vide).
            $allowedKeysList = array_values(array_unique(array_column($scansResume, 'maven_key')));
        }
        $render['scans_resume']         = $scansResume;
        $render['available_socles']     = $availableSocles;
        $render['current_socle_filter'] = $isSocleFilterValid ? $socleFilter : null;

        /**
         * Le dashboard agrège 4 sources : on log + addFlash chaque échec individuellement, le rendu continue avec les listes vides correspondantes.
         *
         * MODIF 2026-05-14 : passe $allowedScanIds
         * (extrait de scans_resume filtre par vue) a chaque
         * requête d’agrégation pour éviter le double-comptage sur les couples
         * scannes en plusieurs versions. */
        $allowedScanIds = array_map(static fn(array $s): int => (int) $s['id'], $scansResume);

        $render['cwes_breakdown'] = $this->extractListeOrFlash(
            $this->cveRepository->countProjectsByCwe($allowedKeysList, $allowedScanIds),
            'countProjectsByCwe'
        );
        $render['top_deps'] = $this->extractListeOrFlash(
            $this->depRepository->topVulnerableDependencies(20, $allowedKeysList, $allowedScanIds),
            'topVulnerableDependencies'
        );

        $criticalCves = $this->extractListeOrFlash(
            $this->cveRepository->findCriticalCvesAcrossProjects(2, $allowedKeysList, $allowedScanIds),
            'findCriticalCvesAcrossProjects'
        );
        $render['critical_cves'] = $this->paginator->paginate(
            $criticalCves,
            $request->query->getInt('page', 1),
            25
        );

        /* MODIF 2026-05-11 :
         * mes_projets_exposes = top 5 trie par cve_count_critical desc, derive
         * de scans_resume deja charge (pas de requête SQL en plus).
         * mes_critical_cves  = top 10 CVE CRITICAL du périmètre, par CVSS desc. */
        $scansSorted = $render['scans_resume'];
        usort($scansSorted, static fn(array $a, array $b) =>
            ((int) ($b['cve_count_critical'] ?? 0)) <=> ((int) ($a['cve_count_critical'] ?? 0))
        );
        $render['mes_projets_exposes'] = array_slice($scansSorted, 0, 5);

        $render['mes_critical_cves'] = $this->extractListeOrFlash(
            $this->cveRepository->listTopCriticalCves(10, $allowedKeysList, $allowedScanIds),
            'listTopCriticalCves'
        );

        /* MODIF 2026-05-11 :
         * Top 10 dépendances mutualisables (>= 2 projets) classées par "Gain
         * mutualisation" = JH unitaire * (nb_projets - 1). On récupère toutes
         * les deps eligibles (hardLimit 200), on calcule JH par dep, on trie
         * en PHP puis on slice a 10. */
        $mutualisables = $this->extractListeOrFlash(
            $this->depRepository->topMutualisableDependencies(2, 200, $allowedKeysList, $allowedScanIds),
            'topMutualisableDependencies'
        );
        foreach ($mutualisables as &$m) {
            $m['jh_unit'] = $this->computeDepJh($m);
            $m['jh_gain'] = $m['jh_unit'] * max(0, ((int) $m['nb_projets']) - 1);
        }
        unset($m);
        /* MODIF 2026-05-12 : tri enrichi.
         * À gain égal (cas frequent avec la formule paramétrable), tie-breakers :
         *   1. sévérité max (CRITICAL en tête -> rank ASC)
         *   2. nb_projets DESC (impact plus large)
         *   3. product ASC (stabilité)
         * Evite les sequences HIGH 7.5 / MEDIUM 7.5 / HIGH 7.5 vues en audit. */
        usort($mutualisables, function (array $a, array $b): int {
            $gainCmp = $b['jh_gain'] <=> $a['jh_gain'];
            if ($gainCmp !== 0) { return $gainCmp; }
            $rankA = $this->severityRank($a);
            $rankB = $this->severityRank($b);
            if ($rankA !== $rankB) { return $rankB <=> $rankA; }
            $projCmp = ((int) ($b['nb_projets'] ?? 0)) <=> ((int) ($a['nb_projets'] ?? 0));
            if ($projCmp !== 0) { return $projCmp; }
            return strcmp((string) ($a['product'] ?? ''), (string) ($b['product'] ?? ''));
        });
        $render['mes_mutualisables'] = array_slice($mutualisables, 0, 10);

        /* MODIF 2026-05-12 :
         * Vraie diff scan précédent : CVE CRITICAL apparues dans le dernier
         * scan d'un projet et absentes de son scan immédiatement antérieur.
         * Si pas de scan antérieur (premier passage), is_first_scan=true et
         * tout est marqué "1ère analyse" coté template (badge dédié).
         *
         * MODIF 2026-05-14 : passage de
         * la version "1 ligne par scan" a la version "1 ligne par CVE
         * distincte" avec sous-tableau impacted_apps. Evite le panneau
         * sature par les repetitions de la meme CVE sur plusieurs versions
         * du meme socle. Hard limit 25 sur les CVE distinctes (vs 50 lignes
         * scan-par-scan avant). Le detail des apps impactées est dans une
         * modale Foundation 6 dédiée, ouverte par bouton dans chaque ligne. */
        $render['mes_critical_recentes'] = $this->extractListeOrFlash(
            $this->cveRepository->listNewCriticalCvesGroupedByCve(25, $allowedKeysList),
            'listNewCriticalCvesGroupedByCve'
        );

        /* MODIF 2026-05-11 :
         * Cartographie complete : projets du périmètre tries par sévérité max
         * desc (CRITICAL > HIGH > MEDIUM > LOW > NONE) puis cve_count_total desc.
         * Derive de scans_resume, pas de SQL en plus. */
        $cartographie = $render['scans_resume'];
        usort($cartographie, function (array $a, array $b): int {
            $rankA = $this->severityRank($a);
            $rankB = $this->severityRank($b);
            if ($rankA !== $rankB) {
                return $rankB <=> $rankA;
            }
            return ((int) ($b['cve_count_total'] ?? 0)) <=> ((int) ($a['cve_count_total'] ?? 0));
        });
        $render['mes_cartographie'] = $cartographie;

        /*
         * MODIF 2026-05-12 : synthèse par
         * archétype. 1 ligne = 1 (parent_label, parent_version) référencé
         * par >=1 app du périmètre. Compteurs = SOMME des CVE des apps
         * qui en héritent (pas le scan du BOM lui-même, qui sur compterait).
         * Tri sévérité max DESC puis nb apps DESC.
         * Standalone (parent_label NULL) exclues — visibles dans Fragment B.
         *
         * MODIF 2026-05-13 : 1 requête bulk
         * findArchetypeScansBatch au lieu de N appels findArchetypeScan dans
         * la boucle. Sur un parc à 5 socles : 1 SQL au lieu de 5 (-200 ms
         * typiques avec un round-trip réseau de ~40 ms). Sémantique identique :
         * la map est lookup-only, scan absent -> null comme avant. */
        $archetypeKeyPairs = [];
        foreach ($render['scans_resume'] as $s) {
            $label   = $s['parent_label']   ?? null;
            $version = $s['parent_version'] ?? null;
            if ($label !== null && $label !== '' && $version !== null && $version !== '') {
                $archetypeKeyPairs[$label . '@' . $version] = ['label' => $label, 'version' => $version];
            }
        }
        $archetypeScansMap = $this->scanRepository->findArchetypeScansBatch(
            array_values($archetypeKeyPairs)
        );

        $archetypes = [];
        foreach ($render['scans_resume'] as $s) {
            $label   = $s['parent_label']   ?? null;
            $version = $s['parent_version'] ?? null;
            if ($label === null || $label === '' || $version === null || $version === '') {
                continue;
            }
            $key = $label . '@' . $version;
            if (!isset($archetypes[$key])) {
                $archetypes[$key] = [
                    'parent_label'   => $label,
                    'parent_version' => $version,
                    'archetype_scan' => $archetypeScansMap[$key] ?? null,
                    'nb_apps'        => 0,
                    'nb_critical'    => 0,
                    'nb_high'        => 0,
                    'nb_medium'      => 0,
                    'nb_low'         => 0,
                    'cve_total'      => 0,
                ];
            }
            $archetypes[$key]['nb_apps']++;
            $archetypes[$key]['nb_critical'] += (int) ($s['cve_count_critical'] ?? 0);
            $archetypes[$key]['nb_high']     += (int) ($s['cve_count_high']     ?? 0);
            $archetypes[$key]['nb_medium']   += (int) ($s['cve_count_medium']   ?? 0);
            $archetypes[$key]['nb_low']      += (int) ($s['cve_count_low']      ?? 0);
            $archetypes[$key]['cve_total']   += (int) ($s['cve_count_total']    ?? 0);
        }
        $archetypes = array_values($archetypes);
        usort($archetypes, function (array $a, array $b): int {
            $rankA = $this->severityRank([
                'cve_count_critical' => $a['nb_critical'],
                'cve_count_high'     => $a['nb_high'],
                'cve_count_medium'   => $a['nb_medium'],
                'cve_count_low'      => $a['nb_low'],
            ]);
            $rankB = $this->severityRank([
                'cve_count_critical' => $b['nb_critical'],
                'cve_count_high'     => $b['nb_high'],
                'cve_count_medium'   => $b['nb_medium'],
                'cve_count_low'      => $b['nb_low'],
            ]);
            if ($rankA !== $rankB) {
                return $rankB <=> $rankA;
            }
            return $b['nb_apps'] <=> $a['nb_apps'];
        });
        $render['mes_socles_synthese'] = $archetypes;

        /*
         * MODIF 2026-05-13 : table de
         * fragmentation socle × archétype. 1 ligne par triplet
         * (parent_label, parent_version, archetype_version) référencé par
         * >=1 app du périmètre. Objectif métier : repérer les apps qui
         * "traînent" sur un ancien archétype tout en étant alignées sur le
         * dernier socle, ou inversement. Si un même socle (label+version)
         * référence plusieurs archetype_version distincts, le flag
         * is_socle_fragmented signale visuellement la dispersion.
         *
         * Mapping pom.xml (Cf. doc audit DC) :
         *  - apps "archetype" : archetype_version = <springboot-archetype.version>,
         *    socle = parent_label/parent_version (= springboot-exemple-config.version).
         *  - apps legacy : archetype_version = <socleVersion>, socle = parent_label
         *    (= <artifactId>) + parent_version.
         *  - apps standalone (sans parent_label) exclues.
         *
         * Tri : socle_label ASC, socle_version DESC (semver), archetype_version
         * DESC (semver, null en dernier), nb_apps DESC. */
        $distribution = [];
        $socleArchetypeCount = [];
        foreach ($render['scans_resume'] as $s) {
            $label   = $s['parent_label']   ?? null;
            $version = $s['parent_version'] ?? null;
            if ($label === null || $label === '' || $version === null || $version === '') {
                continue;
            }
            $archetype    = $s['archetype_version'] ?? null;
            $archetypeKey = ($archetype === null || $archetype === '') ? '__none__' : $archetype;
            $key          = $label . '@' . $version . '#' . $archetypeKey;
            if (!isset($distribution[$key])) {
                $distribution[$key] = [
                    'socle_label'       => $label,
                    'socle_version'     => $version,
                    'archetype_version' => ($archetype === null || $archetype === '') ? null : $archetype,
                    'nb_apps'           => 0,
                    'apps'              => [],
                ];
            }
            $distribution[$key]['nb_apps']++;
            $distribution[$key]['apps'][] = [
                'artifact' => (string) ($s['project_artifact'] ?? ''),
                'version'  => (string) ($s['project_version']  ?? ''),
            ];
            $socleKey = $label . '@' . $version;
            $socleArchetypeCount[$socleKey][$archetypeKey] = true;
        }
        foreach ($distribution as &$row) {
            $socleKey = $row['socle_label'] . '@' . $row['socle_version'];
            $row['is_socle_fragmented'] = count($socleArchetypeCount[$socleKey] ?? []) > 1;
        }
        unset($row);
        $distribution = array_values($distribution);
        usort($distribution, static function (array $a, array $b): int {
            $cmpLabel = strcasecmp($a['socle_label'], $b['socle_label']);
            if ($cmpLabel !== 0) {
                return $cmpLabel;
            }
            $cmpSocleVer = MavenVersionComparator::compare($b['socle_version'], $a['socle_version']);
            if ($cmpSocleVer !== 0) {
                return $cmpSocleVer;
            }
            $aArch = (string) ($a['archetype_version'] ?? '');
            $bArch = (string) ($b['archetype_version'] ?? '');
            if ($aArch === '' && $bArch !== '') {
                return 1;
            }
            if ($aArch !== '' && $bArch === '') {
                return -1;
            }
            if ($aArch !== '' && $bArch !== '') {
                $cmpArch = MavenVersionComparator::compare($bArch, $aArch);
                if ($cmpArch !== 0) {
                    return $cmpArch;
                }
            }
            return $b['nb_apps'] <=> $a['nb_apps'];
        });
        $render['mes_socle_archetype_distribution'] = $distribution;

        /*
         * MODIF 2026-05-11 : 4 datasets pour les
         * charts JS (line timeseries + scatter projets + stacked bar top 15
         * + donut global). Bundle en une seule var charts_data, sérialisée
         * en JSON dans le template puis consommée par le JS. */
        $timeseries = $this->extractListeOrFlash(
            $this->scanRepository->aggregateSeverityByDay(30, $allowedKeysList),
            'aggregateSeverityByDay'
        );

        $scatter = array_map(static fn(array $s): array => [
            'x'     => (int) ($s['dep_count_total'] ?? 0),
            'y'     => (int) ($s['cve_count_total'] ?? 0),
            'r'     => max(4, min(25, 4 + 3 * (int) ($s['cve_count_critical'] ?? 0))),
            'label' => $s['project_artifact'] . ' ' . $s['project_version'],
        ], $render['scans_resume']);

        // Stacked bar : top 15 projets par cve_count_total desc.
        $stackedSource = $render['scans_resume'];
        usort($stackedSource, static fn(array $a, array $b): int =>
            ((int) ($b['cve_count_total'] ?? 0)) <=> ((int) ($a['cve_count_total'] ?? 0))
        );
        $stackedSource = array_slice($stackedSource, 0, 15);
        $stacked = [
            'labels'   => array_map(static fn(array $s): string => $s['project_artifact'], $stackedSource),
            'critical' => array_map(static fn(array $s): int => (int) ($s['cve_count_critical'] ?? 0), $stackedSource),
            'high'     => array_map(static fn(array $s): int => (int) ($s['cve_count_high'] ?? 0),     $stackedSource),
            'medium'   => array_map(static fn(array $s): int => (int) ($s['cve_count_medium'] ?? 0),   $stackedSource),
            'low'      => array_map(static fn(array $s): int => (int) ($s['cve_count_low'] ?? 0),      $stackedSource),
        ];

        // Donut global : totaux par sévérité sur tout le périmètre.
        $donut = ['critical' => 0, 'high' => 0, 'medium' => 0, 'low' => 0];
        foreach ($render['scans_resume'] as $s) {
            $donut['critical'] += (int) ($s['cve_count_critical'] ?? 0);
            $donut['high']     += (int) ($s['cve_count_high'] ?? 0);
            $donut['medium']   += (int) ($s['cve_count_medium'] ?? 0);
            $donut['low']      += (int) ($s['cve_count_low'] ?? 0);
        }

        $render['charts_data'] = [
            'timeseries' => $timeseries,
            'scatter'    => $scatter,
            'stacked'    => $stacked,
            'donut'      => $donut,
        ];

        /* MODIF 2026-05-12 : paramètres de la formule
         * JH pour la modale "Méthode de calcul JH" partagée avec la page exec. */
        $render['jh_params'] = [
            'upgrade_overhead'      => $this->dcAnalytics->getUpgradeOverhead(),
            'safety_margin'         => $this->dcAnalytics->getSafetyMargin(),
            'severity_bonus'        => $this->dcAnalytics->getSeverityBonus(),
            'test_effort_by_family' => $this->dcAnalytics->getTestEffortByFamily(),
        ];

        return $this->render('dependency_check/dashboard.html.twig', $render);
    }

    /**
     * [Description for kpi]
     * page KPIs / pilotage SLA.
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 12/07/2026 08:56:11 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/dependency-check/kpi', name: 'dc_kpi', methods: ['GET'])]
    public function kpi(Request $request): Response
    {
        /**
         * MODIF 2026-05-13 : page KPIs / pilotage SLA.
         * Cible reporting DSI/RSSI : vision macro de la posture sécurité du parc.
         *
         * Affiche :
         *  - 4 cartes synthèse : score sécu moyen, % apps "zéro CRITICAL",
         *    % apps scannées récemment (<30j), nb apps total.
         *  - Line chart tendance 90j (CVE par sévérité, agrégés par jour).
         *  - Top 5 apps les plus à risque (score sécu DESC).
         *  - Top 5 apps les plus saines (score sécu ASC).
         *
         * Score sécu (0-100) par app, formule simple :
         *   100
         *   - 10 × min(nb_critical, 5)   (max -50 contribution)
         *   - 3  × min(nb_high, 10)      (max -30)
         *   - 1  × min(nb_medium, 20)    (max -20)
         *   - 0.5× min(nb_low, 20)       (max -10)
         *   = 0 si la formule donne un négatif (cap min)
         *
         *  Périmètre identique au dashboard (analytics_mode vs groupes_fonctionnels).
         */

        $this->tracking->track('DC_KPI');

        $render = $this->genericRender();
        $render['analytics_mode'] = $this->isAnalyticsMode();
        $render['scope_label']    = $this->currentScopeLabel();

        /* MODIF 2026-05-14 : mode de vue
         * prod/dev strict. En prod : 1 ligne par app = dernière release.
         * En dev : 1 ligne par app en mouvement.
         * Le score sécu et les top 5 risque/sain sont calcules sur cette
         * cohorte filtrée. La tendance 90j en bas reste sur tous les
         * scans historiques (le filtre vue n'a pas de sens sur une série
         * temporelle). */
        $vue = ScanViewMode::normalize($request->query->get('vue'));
        $render['vue'] = $vue;

        if ($this->isAnalyticsMode()) {
            $allowedKeysList = null;
        } else {
            $allowedKeysMap  = $this->allowedMavenKeys();
            $allowedKeysList = $allowedKeysMap === null ? [] : array_keys($allowedKeysMap);
        }

        $scans = $this->extractListeOrFlash(
            $this->scanRepository->listLatestPerCoupleForView($vue, $allowedKeysList),
            'listLatestPerCoupleForView'
        );

        $now      = new \DateTimeImmutable();
        $now30j   = $now->sub(new \DateInterval('P30D'));
        $nbScans  = count($scans);
        $nbZeroCritical    = 0;
        $nbScansRecent     = 0;
        $sumScoreSecu      = 0.0;
        $scansAvecScore    = [];

        foreach ($scans as $s) {
            $crit = (int) ($s['cve_count_critical'] ?? 0);
            $high = (int) ($s['cve_count_high']     ?? 0);
            $med  = (int) ($s['cve_count_medium']   ?? 0);
            $low  = (int) ($s['cve_count_low']      ?? 0);

            /* MODIF 2026-05-14 : formule
             * déplacée dans DcExecutiveAnalyticsService (paramétrée via
             * config/packages/dc_remediation.yaml). */
            $score = $this->dcAnalytics->computeScoreSecu($crit, $high, $med, $low);

            if ($crit === 0) {
                $nbZeroCritical++;
            }
            /** @var \DateTimeImmutable|null $scanDate */
            $scanDate = $s['scan_date'] ?? null;
            if ($scanDate instanceof \DateTimeImmutable && $scanDate >= $now30j) {
                $nbScansRecent++;
            }

            $sumScoreSecu += $score;
            $scansAvecScore[] = $s + ['score_secu' => $score];
        }

        $render['nb_scans']           = $nbScans;
        $render['nb_zero_critical']   = $nbZeroCritical;
        $render['pct_zero_critical']  = $nbScans > 0 ? round(100 * $nbZeroCritical / $nbScans) : 0;
        $render['nb_scans_recent']    = $nbScansRecent;
        $render['pct_scans_recent']   = $nbScans > 0 ? round(100 * $nbScansRecent / $nbScans) : 0;
        $render['score_secu_moyen']   = $nbScans > 0 ? round($sumScoreSecu / $nbScans, 1) : 0;
        /* MODIF 2026-05-14 : regle de calcul exposée au template pour le callout methodo. */
        $render['score_secu_rule']    = $this->dcAnalytics->getScoreSecuRule();

        /* Top 5 risque : score ASC (les plus mauvais) — tie-breaker cve_count DESC. */
        $byRisk = $scansAvecScore;
        usort($byRisk, static function (array $a, array $b): int {
            $cmp = $a['score_secu'] <=> $b['score_secu'];
            if ($cmp !== 0) {
                return $cmp;
            }
            return ((int) ($b['cve_count_total'] ?? 0)) <=> ((int) ($a['cve_count_total'] ?? 0));
        });
        $render['top_risque'] = array_slice($byRisk, 0, 5);

        /* Top 5 sain : score DESC — tie-breaker cve_count ASC. */
        $bySain = $scansAvecScore;
        usort($bySain, static function (array $a, array $b): int {
            $cmp = $b['score_secu'] <=> $a['score_secu'];
            if ($cmp !== 0) {
                return $cmp;
            }
            return ((int) ($a['cve_count_total'] ?? 0)) <=> ((int) ($b['cve_count_total'] ?? 0));
        });
        $render['top_sain'] = array_slice($bySain, 0, 5);

        /* Tendance 90 jours via aggregateSeverityByDay (déjà existant, juste paramètre 90). */
        $timeseries = $this->extractListeOrFlash(
            $this->scanRepository->aggregateSeverityByDay(90, $allowedKeysList),
            'aggregateSeverityByDay'
        );
        $render['chart_data'] = [
            'labels'   => array_map(static fn(array $r): string => $r['day'], $timeseries),
            'critical' => array_map(static fn(array $r): int => (int) ($r['critical'] ?? 0), $timeseries),
            'high'     => array_map(static fn(array $r): int => (int) ($r['high']     ?? 0), $timeseries),
            'medium'   => array_map(static fn(array $r): int => (int) ($r['medium']   ?? 0), $timeseries),
            'low'      => array_map(static fn(array $r): int => (int) ($r['low']      ?? 0), $timeseries),
        ];

        return $this->render('dependency_check/kpi.html.twig', $render);
    }

    /**
     * [Description for comparer]
     * page de comparaison side-by-side de 2 à 4 applications.
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 12/07/2026 08:58:14 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/dependency-check/comparer', name: 'dc_comparer', methods: ['GET'])]
    public function comparer(Request $request): Response
    {
        /**
         * MODIF 2026-05-13 : page de comparaison
         * side-by-side de 2 à 4 applications. Pensée pour piloter une décision
         * d'alignement (« doit-on migrer ces 3 apps sur le même socle ? ») ou
         * comparer la posture sécu de 2 versions d'une même app.
         *
         * Affiche :
         *  - Formulaire de sélection des apps (jusqu'à 4 selects)
         *  - Tableau synthèse side-by-side (1 colonne par app + 1 colonne libellé)
         *    avec compteurs CVE, sévérité max, score sécu, socle, archétype
         *  - Tableau des CVE communes (présentes dans ≥2 apps sélectionnées),
         *    avec colonne par app (✓ si présente, vide sinon)
         *
         * Query string : ?apps[]=group:artifact:version&apps[]=...
         * Limite : 2 minimum, 4 maximum.
         * ACL : chaque triplet doit passer isProjectAccessible (anti-IDOR).
         */

        $this->tracking->track('DC_COMPARER');

        $render = $this->genericRender();
        $render['analytics_mode'] = $this->isAnalyticsMode();
        $render['scope_label']    = $this->currentScopeLabel();

        /* MODIF 2026-05-14 : mode prod/dev strict.
         * Le sélecteur d'apps est restreint a la cohorte vue : mode prod ->
         * uniquement les releases stables, mode dev -> apps en mouvement.
         * La comparaison side-by-side elle-meme reste libre (l'user choisit
         * explicitement ses triplets, on ne filtre pas ses choix). */
        $vue = ScanViewMode::normalize($request->query->get('vue'));
        $render['vue'] = $vue;

        if ($this->isAnalyticsMode()) {
            $allowedKeysList = null;
        } else {
            $allowedKeysMap  = $this->allowedMavenKeys();
            $allowedKeysList = $allowedKeysMap === null ? [] : array_keys($allowedKeysMap);
        }

        // Liste des scans dispos pour les selects (latest par couple, selon mode vue).
        $availableScans = $this->extractListeOrFlash(
            $this->scanRepository->listLatestPerCoupleForView($vue, $allowedKeysList),
            'listLatestPerCoupleForView'
        );
        $render['available_scans'] = $availableScans;

        // Récupère et valide les triplets demandés via query string ?apps[].
        $appsParam = $request->query->all('apps');
        $triplets = [];
        foreach ($appsParam as $raw) {
            $raw = (string) $raw;
            if ($raw === '') {
                continue;
            }
            $parts = explode(':', $raw, 3);
            if (count($parts) !== 3 || $parts[0] === '' || $parts[1] === '' || $parts[2] === '') {
                continue;
            }
            // ACL stricte : on rejette tout projet hors périmètre (anti-IDOR).
            if (!$this->isProjectAccessible($parts[0], $parts[1])) {
                continue;
            }
            $triplets[] = ['group' => $parts[0], 'artifact' => $parts[1], 'version' => $parts[2]];
        }
        // Limite : max 4 apps en comparaison.
        $triplets = array_slice($triplets, 0, 4);

        $render['nb_apps_selected'] = count($triplets);

        if (count($triplets) < 2) {
            // Page d'accueil : sélecteur seul, pas de comparaison.
            $render['comparison'] = [];
            $render['common_cves'] = [];
            $render['raw_app_keys'] = []; // pour préremplir les selects si user a sélectionné 1 seul
            foreach ($triplets as $t) {
                $render['raw_app_keys'][] = $t['group'] . ':' . $t['artifact'] . ':' . $t['version'];
            }
            return $this->render('dependency_check/comparer.html.twig', $render);
        }

        // 1 SQL bulk pour fetch les 2-4 scans en un coup.
        $scansMap = $this->scanRepository->findScansForTriplets($triplets);

        // Construit le tableau de comparaison dans l'ordre des triplets demandés
        // (pour préserver l'ordre de sélection du user dans l'URL).
        $comparison = [];
        $scanIds    = [];
        foreach ($triplets as $t) {
            $key  = $t['group'] . ':' . $t['artifact'] . ':' . $t['version'];
            $scan = $scansMap[$key] ?? null;
            if ($scan === null) {
                continue; // scan introuvable (la version a peut-être été purgée depuis)
            }
            $scanIds[] = (int) $scan->getId();

            $crit = $scan->getCveCountCritical();
            $high = $scan->getCveCountHigh();
            $med  = $scan->getCveCountMedium();
            $low  = $scan->getCveCountLow();

            /* MODIF 2026-05-14 : formule
             * déplacée dans DcExecutiveAnalyticsService (cf KPI). */
            $score = $this->dcAnalytics->computeScoreSecu($crit, $high, $med, $low);

            $severityMax = match (true) {
                $crit > 0 => 'CRITICAL',
                $high > 0 => 'HIGH',
                $med  > 0 => 'MEDIUM',
                $low  > 0 => 'LOW',
                default   => 'NONE',
            };

            $comparison[] = [
                'scan_id'           => (int) $scan->getId(),
                'group'             => $scan->getProjectGroup(),
                'artifact'          => $scan->getProjectArtifact(),
                'version'           => $scan->getProjectVersion(),
                'scan_date'         => $scan->getScanDate(),
                'parent_label'      => $scan->getParentLabel(),
                'parent_version'    => $scan->getParentVersion(),
                'archetype_version' => $scan->getArchetypeVersion(),
                'dep_count_total'   => $scan->getDepCountTotal(),
                'dep_count_vulnerable' => $scan->getDepCountVulnerable(),
                'cve_count_total'   => $scan->getCveCountTotal(),
                'cve_count_critical'=> $crit,
                'cve_count_high'    => $high,
                'cve_count_medium'  => $med,
                'cve_count_low'     => $low,
                'severity_max'      => $severityMax,
                'score_secu'        => $score,
            ];
        }
        $render['comparison']    = $comparison;
        $render['raw_app_keys']  = array_map(
            static fn(array $c): string => $c['group'] . ':' . $c['artifact'] . ':' . $c['version'],
            $comparison
        );

        // CVE communes ≥2 scans, ordre par nb_scans DESC puis severity puis cvss.
        $render['common_cves'] = $this->extractListeOrFlash(
            $this->cveRepository->findCommonCvesForScans($scanIds),
            'findCommonCvesForScans'
        );

        return $this->render('dependency_check/comparer.html.twig', $render);
    }

    /**
     * [Description for mutualisables]
     * liste complète des dépendances mutualisables
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 12/07/2026 08:59:41 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/dependency-check/mutualisables', name: 'dc_mutualisables', methods: ['GET'])]
    public function mutualisables(Request $request): Response
    {
        /**
         * MODIF 2026-05-13 : liste complète des
         * dépendances mutualisables — version intégrale du Fragment D du
         * dashboard, sans cap top 10. Pensée comme une vue "exploration" pour
         * le lead dev/RSSI qui veut piloter l'effort de mutualisation au-delà
         * des 10 premiers gains.
         *
         * Fonctionnalités :
         *  - Périmètre identique au dashboard (analytics_mode ou groupes_fonctionnels).
         *  - Filtre socle (query string ?socle=parent_label@parent_version) repris du
         *    dashboard, alias ?archetype= conserve pour rétro-compatibilité.
         *  - Filtre "Masquer VIA SOCLE" (query string ?hide_via_socle=1) : enlève
         *    les deps déjà mutualisées par héritage (= toutes apps même socle).
         *  - Tri cliquable sur 6 colonnes (product, severity, nb_projets, nb_cves,
         *    jh_unit, jh_gain). Direction asc/desc via query string ?dir=.
         *  - hardLimit relevé a 5000 (vs 200 sur le dashboard) : on couvre les
         *    gros parcs sans tomber dans le hors-RAM (chaque row pèse ~200 octets).
         */

        $this->tracking->track('DC_MUTUALISABLES');

        $render = $this->genericRender();
        $render['analytics_mode'] = $this->isAnalyticsMode();
        $render['scope_label']    = $this->currentScopeLabel();

        /* MODIF 2026-05-14 : mode prod/dev strict.
         * scans_resume = 1 ligne par couple (release stable en prod, app en
         * mouvement en dev). Les scan_ids extraits sont passes au repo qui
         * restreint l’agrégation des dépendances aux versions latest, évitant
         * le double-comptage (10 versions du meme JAR -> 10x la meme CVE). */
        $vue = ScanViewMode::normalize($request->query->get('vue'));
        $render['vue'] = $vue;

        if ($this->isAnalyticsMode()) {
            $allowedKeysList = null;
        } else {
            $allowedKeysMap  = $this->allowedMavenKeys();
            $allowedKeysList = $allowedKeysMap === null ? [] : array_keys($allowedKeysMap);
        }

        $scansResume = $this->extractListeOrFlash(
            $this->scanRepository->listLatestPerCoupleForView($vue, $allowedKeysList),
            'listLatestPerCoupleForView'
        );

        $availableSocles = [];
        foreach ($scansResume as $s) {
            $label   = $s['parent_label']   ?? null;
            $version = $s['parent_version'] ?? null;
            if ($label !== null && $label !== '' && $version !== null && $version !== '') {
                $availableSocles[$label . '@' . $version] = true;
            }
        }
        $availableSocles = array_keys($availableSocles);
        sort($availableSocles);

        $socleFilter = trim((string) (
            $request->query->get('socle')
            ?? $request->query->get('archetype', '')
        ));
        $isSocleFilterValid = $socleFilter !== ''
            && $socleFilter !== 'all'
            && in_array($socleFilter, $availableSocles, true);

        if ($isSocleFilterValid) {
            $scansResume = array_values(array_filter(
                $scansResume,
                static fn(array $s) =>
                    ($s['parent_label'] ?? '') !== ''
                    && ($s['parent_version'] ?? '') !== ''
                    && (($s['parent_label'] . '@' . $s['parent_version']) === $socleFilter)
            ));
            $allowedKeysList = array_values(array_unique(array_column($scansResume, 'maven_key')));
        }
        $render['available_socles']     = $availableSocles;
        $render['current_socle_filter'] = $isSocleFilterValid ? $socleFilter : null;

        $hideViaSocle = in_array(
            (string) $request->query->get('hide_via_socle', ''),
            ['1', 'true', 'on'],
            true
        );
        $render['hide_via_socle'] = $hideViaSocle;

        /* MODIF 2026-05-14 : extraction des scan_ids
         * latest depuis scans_resume (deja filtre par maven_keys + socle).
         * Passe au repo qui restreint le JOIN dc_scan a ces ids. */
        $allowedScanIds = array_map(static fn(array $s): int => (int) $s['id'], $scansResume);

        $mutualisables = $this->extractListeOrFlash(
            $this->depRepository->topMutualisableDependencies(2, 5000, $allowedKeysList, $allowedScanIds),
            'topMutualisableDependencies'
        );

        foreach ($mutualisables as &$m) {
            $m['jh_unit'] = $this->computeDepJh($m);
            $m['jh_gain'] = $m['jh_unit'] * max(0, ((int) $m['nb_projets']) - 1);
            $m['severity_rank'] = match (true) {
                ((int) ($m['nb_critical'] ?? 0)) > 0 => 4,
                ((int) ($m['nb_high']     ?? 0)) > 0 => 3,
                ((int) ($m['nb_medium']   ?? 0)) > 0 => 2,
                ((int) ($m['nb_low']      ?? 0)) > 0 => 1,
                default                              => 0,
            };
            /* MODIF 2026-05-13 : flag
             * is_accessible sur chaque triplet using_projects pour rendre
             * conditionnellement le badge en lien (vrai) ou en span non
             * cliquable (faux) cote Twig. La page projet impose une ACL
             * stricte (isProjectAccessible -> 403 IDOR), donc un badge
             * cliquable sur un projet hors périmètre serait trompeur :
             * l'user verrait "ma-moulinette" en mode analytics mais se ferait
             * jeter au clic. On evite la frustration en supprimant le lien
             * en amont. */
            if (isset($m['using_projects']) && is_array($m['using_projects'])) {
                foreach ($m['using_projects'] as &$p) {
                    $p['is_accessible'] = $this->isProjectAccessible(
                        (string) ($p['group']    ?? ''),
                        (string) ($p['artifact'] ?? '')
                    );
                }
                unset($p);
            }
        }
        unset($m);

        if ($hideViaSocle) {
            $mutualisables = array_values(array_filter(
                $mutualisables,
                static fn(array $m) => !($m['is_via_archetype'] ?? false)
            ));
        }

        /* MODIF 2026-05-16: tri et pagination délégués à
         * DataTables côté client. Tri par défaut jh_gain DESC pour rendu
         * sans JS ; DataTables ré-ordonnera au chargement. */
        usort($mutualisables, static fn(array $a, array $b): int =>
            ((float) ($b['jh_gain'] ?? 0)) <=> ((float) ($a['jh_gain'] ?? 0))
        );
        $render['total_count']   = count($mutualisables);
        $render['mutualisables'] = $mutualisables;

        /* MODIF 2026-05-13 : paramètres de la formule
         * JH expose a la modale "Méthode de calcul JH" partagée. Idem dashboard
         * + executive. */
        $render['jh_params'] = [
            'upgrade_overhead'      => $this->dcAnalytics->getUpgradeOverhead(),
            'safety_margin'         => $this->dcAnalytics->getSafetyMargin(),
            'severity_bonus'        => $this->dcAnalytics->getSeverityBonus(),
            'test_effort_by_family' => $this->dcAnalytics->getTestEffortByFamily(),
        ];

        return $this->render('dependency_check/mutualisables.html.twig', $render);
    }

    /**
     * [Description for executive]
     * Page exécutive prise de décision pour un scan donné.
     * 11 sections : contexte, vue globale, sévérité, familles, top deps,
     * radar CWE, CVE prioritaires, table CVE→Décision, plan de
     * remédiation chiffré, diff vs scan précédent, top 5 actions.
     *
     * @param string $group
     * @param string $artifact
     * @param string $version
     *
     * @return Response
     *
     * Created at: 10/05/2026 17:01:38 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/dependency-check/projet/{group}/{artifact}/{version}/executive', name: 'dc_projet_executive', methods: ['GET'], requirements: ['group' => self::REGEX, 'artifact' => self::REGEX, 'version' => self::REGEX])]
    public function executive(string $group, string $artifact, string $version): Response
    {
        $this->tracking->track('DC_EXECUTIVE');

        $render = $this->genericRender();
        $render['project_group']    = $group;
        $render['project_artifact'] = $artifact;
        $render['project_version']  = $version;

        /* MODIF 2026-05-10 : ACL stricte HTTP 403 (anti-IDOR). */
        if (!$this->isProjectAccessible($group, $artifact)) {
            throw $this->createAccessDeniedException(
                sprintf('Projet %s:%s hors du périmètre groupe_fonctionnel.', $group, $artifact)
            );
        }
        $scan = $this->scanRepository->findLatestForProject($group, $artifact, $version);
        if ($scan === null) {
            $this->addFlash('notice', [
                'type'    => 'warning',
                'message' => sprintf('Aucun scan trouvé pour %s:%s:%s', $group, $artifact, $version),
            ]);
            $render['scan'] = null;
            $render['findings'] = [];
            return $this->render('dependency_check/executive.html.twig', $render);
        }
        $render['scan'] = $scan;

        $result = $this->findingRepository->listForScan((int) $scan->getId());
        if ($result['code'] !== 200) {
            $this->logger->error('[DC] ❌ Échec de la requête listForScan (executive)', [
                'scan_id' => (int) $scan->getId(),
                'code'    => $result['code'],
                'erreur'  => $result['erreur'] ?? '',
            ]);
            $this->addFlash('notice', [
                'type'    => 'error',
                'message' => sprintf(
                    'La récupération des findings a échoué (Erreur %s). %s',
                    $result['code'],
                    $result['erreur'] ?? ''
                ),
            ]);
            $findings = [];
        } else {
            $findings = $result['liste'] ?? [];
        }
        $render['findings'] = $findings;

        /* MODIF 2026-05-12 : retrouve le scan
         * archetype/parent-POM correspondant pour afficher un lien dans la
         * cellule "Archetype" du bloc Contexte. null si l'app est standalone
         * (pas de parent_label) ou si le scan archetype n'est pas en base
         * (cas SCAN MISSING). */
        $render['archetype_scan'] = $this->scanRepository->findArchetypeScan(
            $scan->getParentLabel(),
            $scan->getParentVersion()
        );

        // Sections analytiques (déléguées à DcExecutiveAnalyticsService)
        $render['severity_counts']   = $this->dcAnalytics->computeCriticityCounts($findings);
        $render['family_breakdown']  = $this->dcAnalytics->computeFamilyBreakdown($findings);
        $render['top_deps_severity'] = $this->dcAnalytics->computeTopDepsBreakdown($findings, 10);
        $render['top_cwes']          = $this->dcAnalytics->computeTopCwes($findings, 8);
        $render['top_priority_cves'] = $this->dcAnalytics->computeTopPriorityCves($findings, 10);
        $render['cve_decisions']     = $this->dcAnalytics->computeCveDecisions($findings);
        $render['plan_remediation']  = $this->dcAnalytics->computeRemediationPlan($findings);
        $render['top_actions']       = $this->dcAnalytics->computeTopActions($findings, 5);

        /* MODIF 2026-05-12 : 2 diffs distinctes.
         *
         * (A) intra-version : re-scan de la même version (group+artifact+version
         *     match en LOWER) → "depuis le dernier scan de cette version, NVD
         *     a-t-il découvert quelque chose ?". Si pas de scan antérieur de
         *     la même version, scan_diff_same_version reste null et le template
         *     affiche "pas de comparaison disponible". Si la diff est vide
         *     (isUnchanged), le template affiche un encart "pas de changement".
         *
         * (B) inter-versions : version différente du même projet → "ce passage
         *     de version améliore-t-il la posture ?". Si pas de scan antérieur
         *     d'une autre version, section masquée. Tri par scan_date strict
         *     (limitation acceptée, cf. doc audit §7.2.2). */
        $render['previous_scan_same_version']  = null;
        $render['scan_diff_same_version']      = null;
        $render['count_prev_same_findings']    = 0;
        $render['previous_scan_any_version']   = null;
        $render['scan_diff_previous_version']  = null;
        $render['count_prev_any_findings']     = 0;

        $prevSame = $this->scanRepository->findPreviousScanSameVersion($scan);
        if ($prevSame !== null) {
            $prevSameFindings = $this->fetchFindingsOrEmpty((int) $prevSame->getId());
            $render['previous_scan_same_version'] = $prevSame;
            $render['scan_diff_same_version']     = $this->dcAnalytics->computeScanDiff($prevSameFindings, $findings);
            $render['count_prev_same_findings']   = count($prevSameFindings);
        }

        $prevAny = $this->scanRepository->findPreviousScanAnyVersion($scan);
        if ($prevAny !== null) {
            $prevAnyFindings = $this->fetchFindingsOrEmpty((int) $prevAny->getId());
            $render['previous_scan_any_version']  = $prevAny;
            $render['scan_diff_previous_version'] = $this->dcAnalytics->computeScanDiff($prevAnyFindings, $findings);
            $render['count_prev_any_findings']    = count($prevAnyFindings);
        }

        /* MODIF 2026-05-12 : paramètres de la formule
         * JH pour la modale "méthode de calcul JH" partagée avec le dashboard. */
        $render['jh_params'] = [
            'upgrade_overhead'      => $this->dcAnalytics->getUpgradeOverhead(),
            'safety_margin'         => $this->dcAnalytics->getSafetyMargin(),
            'severity_bonus'        => $this->dcAnalytics->getSeverityBonus(),
            'test_effort_by_family' => $this->dcAnalytics->getTestEffortByFamily(),
        ];

        return $this->render('dependency_check/executive.html.twig', $render);
    }

    /**
     * [Description for fetchFindingsOrEmpty]
     * MODIF 2026-05-12 : helper privé pour
     * éviter la duplication du pattern listForScan + extract liste / [].
     *
     * @param int $scanId
     *
     * @return array<int, array<string, mixed>>
     *
     * Created at: 12/05/2026 14:27:32 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function fetchFindingsOrEmpty(int $scanId): array
    {
        $result = $this->findingRepository->listForScan($scanId);
        if ($result['code'] !== 200) {
            $this->logger->error('[DC] ❌ Échec listForScan pour diff', [
                'scan_id' => $scanId,
                'code'    => $result['code'],
                'erreur'  => $result['erreur'] ?? '',
            ]);
            return [];
        }
        return $result['liste'] ?? [];
    }

    /**
     * [Description for extractListeOrFlash]
     * helper pour le pattern repository {code/liste/erreur}.
     *
     * @param array<string, mixed> $result
     * @return array<int, mixed>
     *
     * @return array
     *
     * Created at: 10/05/2026 17:02:56 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function extractListeOrFlash(array $result, string $tag): array
    {
        if ($result['code'] !== 200) {
            $this->logger->error("[DC] ❌ Échec {$tag}", [
                'code'   => $result['code'],
                'erreur' => $result['erreur'] ?? '',
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => sprintf(
                    'La récupération %s a échoué (Erreur %s). %s',
                    $tag,
                    $result['code'],
                    $result['erreur'] ?? ''
                ),
            ]);
            return [];
        }
        return $result['liste'] ?? [];
    }

    /**
     * [Description for severityRank]
     * MODIF 2026-05-11 : retourne le rang
     * de sévérité max d'un scan. Mapping décroissant pour usort() :
     * CRITICAL=4, HIGH=3, MEDIUM=2, LOW=1, NONE=0.
     * Utilise par le fragment B du dashboard.
     *
     * @param array<string, mixed> $scan
     *
     * @return int
     *
     * Created at: 12/07/2026 09:08:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function severityRank(array $scan): int
    {
        if ((int) ($scan['cve_count_critical'] ?? 0) > 0) { return 4; }
        if ((int) ($scan['cve_count_high'] ?? 0)     > 0) { return 3; }
        if ((int) ($scan['cve_count_medium'] ?? 0)   > 0) { return 2; }
        if ((int) ($scan['cve_count_low'] ?? 0)      > 0) { return 1; }
        return 0;
    }

    /**
     * [Description for computeDepJh]
     * MODIF 2026-05-12 : JH unitaire d'upgrade
     * pour une dépendance. Délègue à DcExecutiveAnalyticsService pour rester
     * DRY avec la formule de la page Executive (modif unique à un seul endroit :
     * config/packages/dc_remediation.yaml).
     *
     * Formule paramétrable :
     *   JH = (test_effort_by_family[famille] + upgrade_overhead) * safety_margin
     *      + severity_bonus[sev_max]
     *   arrondi au demi-jour.
     *
     * @param array<string, mixed> $dep retourne par topMutualisableDependencies()
     *
     * @return float
     *
     * Created at: 15/05/2026 09:06:05 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function computeDepJh(array $dep): float
    {
        $sevMax = match (true) {
            ((int) ($dep['nb_critical'] ?? 0)) > 0 => 'CRITICAL',
            ((int) ($dep['nb_high']     ?? 0)) > 0 => 'HIGH',
            ((int) ($dep['nb_medium']   ?? 0)) > 0 => 'MEDIUM',
            ((int) ($dep['nb_low']      ?? 0)) > 0 => 'LOW',
            default => 'INFO',
        };
        $family = $this->dcAnalytics->getFamily(
            (string) ($dep['vendor']  ?? ''),
            (string) ($dep['product'] ?? '')
        );
        return $this->dcAnalytics->computeDepJh($family, $sevMax);
    }

}
