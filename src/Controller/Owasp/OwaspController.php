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

namespace App\Controller\Owasp;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\{Request, Response, ResponseHeaderBag};
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;

use App\Entity\{OwaspTop10, Owasp, HotspotOwasp, HotspotDetails, Historique};
use App\Service\{PdfExportService, UserAgentTrackingFacade};

/**
 * [Description OwaspController]
 */
class OwaspController extends AbstractController
{

    private static string $page = "owasp/index.html.twig";
    private static string $erreur404 = "⚠️ Les informations concernant les référentiels OWASP n'ont pas été trouvés.";
    private static string $noData = 'Pas de données';

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

        /**
     * [Description for __construct]
     *
     * Created at: 13/02/2023, 08:57:23 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function __construct(
        ParameterBagInterface $params,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private UserAgentTrackingFacade $tracking,
        private PdfExportService $pdfExportService)
    {
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
     * [Description for index]
     *
     * @return Response
     *
     * Created at: 15/12/2022, 22:14:00 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp', name: 'owasp')]
    public function index(): Response
    {

        $this->tracking->track('OWASP');

        /** On charge le template du render */
        $render = $this->genericRender();

        /** On instancie l'entityRepos */
        $owaspTop10Repos = $this->em->getRepository(OwaspTop10::class);

        /** On récupère les informations du projet de la table historique */
        $map = ['referential_version' => 2017];
        $owasp_2017 = $owaspTop10Repos->selectOwaspTop10Referential($map);

        if ($owasp_2017['code'] != 200) {
            $this->logger->error('[Owasp] ❌ Échec selectOwaspTop10Referential 2017.', [
                'code' => $owasp_2017['code'],
                'erreur' => $owasp_2017['erreur'] ?? self::$noData
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($owasp_2017['erreur'] ?? self::$noData)
            ]);
            return $this->render(self::$page, $render);
        }

        $map = ['referential_version' => 2021];
        $owasp_2021 = $owaspTop10Repos->selectOwaspTop10Referential($map);

        if ($owasp_2021['code'] != 200) {
            $this->logger->error('[Owasp] ❌ Échec selectOwaspTop10Referential 2021.', [
                'code' => $owasp_2021['code'],
                'erreur' => $owasp_2021['erreur'] ?? self::$noData
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($owasp_2021['erreur'] ?? self::$noData)
            ]);
            return $this->render(self::$page, $render);
        }

        if (count($owasp_2017['liste']) === 0 && count($owasp_2021['liste']) === 0) {
            $this->logger->warning('[Owasp] ⚠️ Référentiels OWASP 2017 et 2021 vides.');
            $this->addFlash('notice', [
                'type' => 'warning',
                'message' => self::$erreur404
            ]);
        }

        $render['sonar_version'] = $this->getParameter('sonar.version');
        $render['serveur'] = $this->getParameter('sonar.url');
        $render['owasp_2017'] = $owasp_2017;
        $render['owasp_2021'] = $owasp_2021;
        return $this->render(self::$page, $render);
    }

    /**
     * [Description for details]
     *
     * @param int $id
     *
     * @return Response
     *
     * Created at: 21/11/2024 20:16:31 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp/detail/{id}', name: 'owasp_detail', condition: "params['id'] < 21", methods: ['GET', 'HEAD'])]
    public function details(int $id): Response
    {
        /** On charge le template du render */
        $render = $this->genericRender();

        /** On instancie l'entityRepository */
        $owaspTop10Repos = $this->em->getRepository(OwaspTop10::class);

        /** On récupère les informations du projet de la table historique */
        $map = ['menace' => $id];
        $liste = $owaspTop10Repos->selectOwaspTop10Details($map);
        if ($liste['code'] != 200) {
            $this->logger->error('[Owasp] ❌ Échec selectOwaspTop10Details.', [
                'code' => $liste['code'],
                'erreur' => $liste['erreur'] ?? self::$noData,
                'menace' => $id
            ]);
            $this->addFlash('notice', [
                'type' => 'error',
                'message' => '❌' . ($liste['erreur'] ?? self::$noData)
            ]);
            return $this->render(self::$page, $render);
        }
        $render['menace'] = $id;
        $render['owasp'] = $liste['details'][0];
        return $this->render('owasp/detail.html.twig', $render);
    }

    /**
     * [Description for owaspLabels]
     * Libellés des catégories OWASP par référentiel.
     *
     * @return array<string, array<int, string>
     *
     * Created at: 03/05/2026 16:57:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function owaspLabels(): array
    {
        return [
            '2017' => [
                1 => "Attaques d'injection",
                2 => "Authentification défaillante",
                3 => "Fuites de données sensibles",
                4 => "Entités externes XML (XXE)",
                5 => "Contrôle d'accès défaillant",
                6 => "Configurations défaillantes",
                7 => "Attaques cross-site scripting (XSS)",
                8 => "Désérialisation sans validation",
                9 => "Composants tiers vulnérables",
                10 => "Journalisation et surveillance insuffisantes",
            ],
            '2021' => [
                1 => "Contrôle d'accès défaillant",
                2 => "Défauts cryptographiques",
                3 => "Injections",
                4 => "Conception non sécurisée",
                5 => "Mauvaise configuration de sécurité",
                6 => "Composants vulnérables et obsolètes",
                7 => "Identification et authentification défaillantes",
                8 => "Manque d'intégrité des données et du logiciel",
                9 => "Carences des systèmes de contrôle et de journalisation",
                10 => "Falsification de requête côté serveur (SSRF)",
            ],
        ];
    }

    /**
     * [Description for rapportPdf]
     * Export PDF du rapport OWASP : information projet, synthèse vulnérabilités
     * + hotspots, liste détaillée des menaces, graphique de répartition par
     * catégorie A1..A10. Agrège côté serveur via les repos Owasp / HotspotOwasp
     * / HotspotDetails (mêmes que les endpoints API utilisés par la page web)
     * puis délègue le rendu PDF à PdfExportService::generateOwaspPdf.
     *
     * @param Request $request
     * @param bool $download Force l'attachement (true) ou rendu inline (false)
     *
     * Created at: 2026-05-03 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/owasp/rapport/pdf', name: 'owasp_rapport_pdf', methods: ['GET'])]
    public function rapportPdf(Request $request): Response
    {
        $mavenKey = (string) $request->query->get('maven_key', '');
        /** Cast int : la colonne `referential_owasp` est INTEGER côté DB.
         *  Postgres accepte le cast string→int silencieusement mais on est
         *  explicite pour éviter toute ambiguïté sur la comparaison du WHERE. */
        $referentialOwasp = (int) ($request->query->get('referential_owasp', 2021));
        if (!in_array($referentialOwasp, [2017, 2021], true)) {
            $referentialOwasp = 2021;
        }
        $download = (bool) $request->query->get('download', false);

        if ($mavenKey === '') {
            throw $this->createNotFoundException('Clé Maven manquante.');
        }

        /** Repos */
        $owaspRepo = $this->em->getRepository(Owasp::class);
        $hotspotOwaspRepo = $this->em->getRepository(HotspotOwasp::class);
        $hotspotDetailsRepo = $this->em->getRepository(HotspotDetails::class);

        /** Vulnérabilités OWASP par catégorie + sévérité (mêmes données que peintureOwaspListe) */
        $owaspResult = $owaspRepo->selectOwaspOrderByDateEnregistrement([
            'maven_key' => $mavenKey,
            'referential_owasp' => $referentialOwasp,
        ]);

        $vuln = ['total' => 0, 'blocker' => 0, 'critical' => 0, 'major' => 0, 'minor' => 0];
        $version = null;
        $dateVersion = null;
        $categoriesFaille = array_fill(1, 10, 0);
        /** Severity breakdown par catégorie pour calculer le rating A-E à la
         *  manière de la page (cf. JS remplissageOwaspInfo) :
         *   blocker > 1 → E ; critical > 1 → D ; major > 1 → C ;
         *   minor > 1 → B ; sinon (tout 0) → A. */
        $sevBreakdown = array_fill(1, 10, ['blocker' => 0, 'critical' => 0, 'major' => 0, 'minor' => 0]);

        if ($owaspResult['code'] === 200 && !empty($owaspResult['liste'])) {
            $ligne = $owaspResult['liste'][0];
            $version = $ligne['version'] ?? null;
            $dateVersion = $ligne['date_version'] ?? null;
            for ($i = 1; $i <= 10; $i++) {
                $categoriesFaille[$i] = (int) ($ligne["a{$i}"] ?? 0);
                $sevBreakdown[$i] = [
                    'blocker'  => (int) ($ligne["a{$i}_blocker"] ?? 0),
                    'critical' => (int) ($ligne["a{$i}_critical"] ?? 0),
                    'major'    => (int) ($ligne["a{$i}_major"] ?? 0),
                    'minor'    => (int) ($ligne["a{$i}_minor"] ?? 0),
                ];
                $vuln['total']    += $categoriesFaille[$i];
                $vuln['blocker']  += $sevBreakdown[$i]['blocker'];
                $vuln['critical'] += $sevBreakdown[$i]['critical'];
                $vuln['major']    += $sevBreakdown[$i]['major'];
                $vuln['minor']    += $sevBreakdown[$i]['minor'];
            }
        }

        /** Fallback : si Owasp n'a pas de ligne pour le couple (maven_key,
         *  referential_owasp), on récupère version + date_version depuis la
         *  dernière entrée Historique. Couvre le cas d'un projet qui n'a que
         *  des données 2017 mais dont le PDF est demandé en 2021 (ou inverse). */
        if ($version === null || $dateVersion === null) {
            $historiqueRepo = $this->em->getRepository(Historique::class);
            $last = $historiqueRepo->selectHistoriqueProjetLast(['maven_key' => $mavenKey]);
            if (($last['code'] ?? null) === 200 && !empty($last['infos'])) {
                $version ??= $last['infos'][0]['version'] ?? null;
                $dateVersion ??= $last['infos'][0]['date_version'] ?? null;
            }
        }

        /** Hotspots : reviewed/to_review/probability + per-catégorie */
        $reviewed = $hotspotOwaspRepo->countHotspotOwaspStatus(['maven_key' => $mavenKey, 'status' => 'REVIEWED']);
        $toReview = $hotspotOwaspRepo->countHotspotOwaspStatus(['maven_key' => $mavenKey, 'status' => 'TO_REVIEW']);
        $probability = $hotspotOwaspRepo->countHotspotOwaspProbability(['maven_key' => $mavenKey]);
        $menaces = $hotspotOwaspRepo->countHotspotOwaspMenaces(['maven_key' => $mavenKey]);

        $hotspots = [
            'reviewed'  => (int) ($reviewed['request'][0]['nombre'] ?? 0),
            'to_review' => (int) ($toReview['request'][0]['nombre'] ?? 0),
            'high'      => 0,
            'medium'    => 0,
            'low'       => 0,
            'note'      => '--',
            'taux'      => 0.0,
        ];
        $hotspots['total'] = $hotspots['reviewed'] + $hotspots['to_review'];

        if (($probability['code'] ?? null) === 200) {
            foreach ($probability['nombre'] ?? [] as $row) {
                $level = strtolower((string) ($row['probability'] ?? ''));
                if (isset($hotspots[$level])) {
                    $hotspots[$level] = (int) ($row['total'] ?? 0);
                }
            }
        }

        /** Note hotspot global = note A-E selon le taux de review.
         *  Reproduit la logique JS remplissageHotspotInfo + calculNoteHotspot.
         *  taux = 1 - (toReview / total) = pourcentage examiné.
         *  Cas spéciaux affichés en `--` côté UI (pas de hotspot ou aucun review). */
        if ($hotspots['total'] > 0) {
            $hotspots['taux'] = 1 - ($hotspots['to_review'] / $hotspots['total']);
            $hotspots['note'] = $hotspots['taux'] > 0
                ? self::computeHotspotNoteFromTaux($hotspots['taux'])
                : '--';
        }

        $categoriesHotspot = array_fill(1, 10, 0);
        if (($menaces['code'] ?? null) === 200) {
            foreach ($menaces['menaces'] ?? [] as $row) {
                $idx = (int) substr((string) ($row['menace'] ?? 'A0'), 1);
                if ($idx >= 1 && $idx <= 10) {
                    $categoriesHotspot[$idx] = (int) ($row['total'] ?? 0);
                }
            }
        }

        /** Liste détaillée des menaces + répartition module calculée à partir des flags.
         *  Colonnes hotspot_details : rule_key (clé technique SonarQube),
         *  rule_name (libellé), file_path (chemin complet du fichier), line. */
        $detailsResult = $hotspotDetailsRepo->selectHotspotDetailsByStatus(['maven_key' => $mavenKey]);
        $repartition = ['frontend' => 0, 'backend' => 0, 'autre' => 0, 'inconnu' => 0];
        $menacesListe = [];
        if (($detailsResult['code'] ?? null) === 200) {
            foreach ($detailsResult['menaces'] ?? [] as $row) {
                $repartition['frontend'] += (int) ($row['frontend'] ?? 0);
                $repartition['backend']  += (int) ($row['backend']  ?? 0);
                $repartition['autre']    += (int) ($row['autre']    ?? 0);
                $repartition['inconnu']  += (int) ($row['inconnu']  ?? 0);
                $menacesListe[] = [
                    'rule'      => $row['rule_key'] ?? $row['rule_name'] ?? '—',
                    'severity'  => $row['severity'] ?? '',
                    'component' => $row['file_path'] ?? $row['file_name'] ?? '—',
                    'ligne'     => $row['line'] ?? '—',
                    'message'   => $row['message'] ?? '—',
                    'status'    => $row['status'] ?? '—',
                ];
            }
        }

        /** Construction des catégories A1..A10 avec libellés selon le référentiel
         *  + ratings A-E vulnérabilité (severity breakdown) et hotspot (taux
         *  = 1 - share_de_la_categorie). Reproduit la logique JS de la page
         *  pour cohérence d'affichage. */
        $labels = self::owaspLabels()[$referentialOwasp] ?? self::owaspLabels()['2021'];
        $totalHotspots = array_sum($categoriesHotspot);
        $categories = [];
        for ($i = 1; $i <= 10; $i++) {
            $categories[] = [
                'id'             => $i,
                'label'          => $labels[$i] ?? "A{$i}",
                'faille'         => $categoriesFaille[$i],
                'faille_rating'  => self::computeFailleRating($sevBreakdown[$i]),
                'hotspot'        => $categoriesHotspot[$i],
                'hotspot_rating' => self::computeHotspotRating($categoriesHotspot[$i], $totalHotspots),
                'hotspot_taux'   => $totalHotspots > 0 ? (1 - ($categoriesHotspot[$i] / $totalHotspots)) : 1,
            ];
        }

        /** Assemblage final pour le service */
        $rapport = [
            'maven_key'         => $mavenKey,
            'project_name'      => self::extractProjectName($mavenKey),
            'version'           => $version,
            'date_version'      => $dateVersion,
            'referential_owasp' => $referentialOwasp,
            'vulnerabilities'   => $vuln,
            'hotspots'          => $hotspots,
            'repartition'       => $repartition,
            'categories'        => $categories,
            'menaces'           => $menacesListe,
        ];

        $pdfContent = $this->pdfExportService->generateOwaspPdf($rapport, 'Document Interne');

        $disposition = $download ? ResponseHeaderBag::DISPOSITION_ATTACHMENT : ResponseHeaderBag::DISPOSITION_INLINE;
        $filename = sprintf('rapport_owasp_%s_%s.pdf',
            preg_replace('/[^a-z0-9_-]/i', '_', $mavenKey),
            date('Y-m-d_H-i')
        );

        return new Response($pdfContent, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => $disposition . '; filename=' . $filename,
        ]);
    }

    /**
     * Dernier segment de la maven_key (ex: "fr.acme:paddpm" → "paddpm").
     */
    private static function extractProjectName(string $mavenKey): string
    {
        $parts = explode(':', $mavenKey);
        return end($parts) ?: $mavenKey;
    }

    /**
     * Rating A-E d'une catégorie OWASP basé sur la severity breakdown.
     * Reproduit la logique du JS remplissageOwaspInfo (seuil > 1 par sévérité).
     *
     * @param array{blocker:int, critical:int, major:int, minor:int} $sev
     */
    private static function computeFailleRating(array $sev): string
    {
        if ($sev['blocker'] > 1) {
            return 'E';
        }
        if ($sev['critical'] > 1) {
            return 'D';
        }
        if ($sev['major'] > 1) {
            return 'C';
        }
        if ($sev['minor'] > 1) {
            return 'B';
        }
        return 'A';
    }

    /**
     * Rating A-E hotspot d'une catégorie : taux = 1 - (count_categorie / total).
     * Plus le taux est proche de 1, mieux c'est (cf. JS calculNoteHotspot).
     */
    private static function computeHotspotRating(int $count, int $total): string
    {
        if ($total === 0) {
            return 'A';
        }
        $taux = 1 - ($count / $total);
        return self::computeHotspotNoteFromTaux($taux);
    }

    /**
     * Conversion taux → lettre A-E (mêmes seuils que JS calculNoteHotspot).
     */
    private static function computeHotspotNoteFromTaux(float $taux): string
    {
        if ($taux > 0.79) {
            return 'A';
        }
        if ($taux > 0.71) {
            return 'B';
        }
        if ($taux > 0.51) {
            return 'C';
        }
        if ($taux > 0.31) {
            return 'D';
        }
        return 'E';
    }
}
