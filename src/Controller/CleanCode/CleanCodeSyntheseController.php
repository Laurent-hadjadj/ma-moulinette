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

namespace App\Controller\CleanCode;

use App\Controller\Traits\AppUserAware;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\Response;
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\Historique;
use App\Service\{MesProjets, PdfExportService};
use App\Service\UserAgent\UserAgentTrackingFacade;

/* MODIF 2026-05-19 : tableau de bord multi-projets clean code
 * Affiche la dernière collecte par projet du portefeuille utilisateur avec les 14 indicateurs macro (notes A-E, score risque CC, RESPONSIBLE%, compteurs qualité/conformité).
 */

/**
 * [Description CleanCodeSyntheseController]
 */
class CleanCodeSyntheseController extends AbstractController
{
    use AppUserAware;

    private static string $page     = 'clean-code/synthese.html.twig';
    private static string $erreur404 = '⚠️ Vous devez être rattaché à une équipe pour accéder à cette vue (Erreur 404).';
    private static string $erreur406 = '⚠️ Aucun projet trouvé pour votre équipe — vérifiez le tag SonarQube (Erreur 406).';
    private static string $erreur503 = '⚠️ Aucun projet du portefeuille ne dispose encore de données clean code dans l\'historique.';

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    public function __construct(
        ParameterBagInterface $params,
        private MesProjets $mesProjets,
        private EntityManagerInterface $em,
        private LoggerInterface $logger,
        private PdfExportService $pdfExportService,
        private UserAgentTrackingFacade $tracking
    ) {
        $this->logoEntreprise        = $params->get('logo.entreprise');
        $this->marqueEntrepriseShort = $params->get('marque.entreprise.short');
        $this->marqueEntrepriseLong  = $params->get('marque.entreprise.long');
        $this->environnement         = $params->get('environnement');
        $this->version               = $params->get('version');
        $this->dateCopyright         = \date('Y');
    }

    /**
     * [Description for genericRender]
     *
     * @return array{
     *     type_footer: null,
     *     logo_entreprise: string,
     *     marque_entreprise_short: string,
     *     marque_entreprise_long: string,
     *     env: string,
     *     version: string,
     *     date_copyright: string,
     * }
     *
     * Created at: 19/05/2026 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function genericRender(): array
    {
        return [
            'type_footer'          => null,
            'logo_entreprise'      => $this->logoEntreprise,
            'marque_entreprise_short' => $this->marqueEntrepriseShort,
            'marque_entreprise_long'  => $this->marqueEntrepriseLong,
            'env'                  => $this->environnement,
            'version'              => $this->version,
            'date_copyright'       => $this->dateCopyright,
        ];
    }

    /**
     * [Description for index]
     * Vue synthèse multi-projets clean code — une ligne par projet, dernière collecte.
     *
     * @return Response
     *
     * Created at: 19/05/2026 09:23:48 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/clean-code/synthese', name: 'clean_code_synthese')]
    public function index(): Response
    {
        $this->tracking->track('CLEAN_CODE_SYNTHESE');

        $render = $this->genericRender();
        $data   = $this->buildSyntheseData();

        $render['scope_label'] = $data['scope_label'];
        $render['projets']     = $data['projets'];

        if ($data['code'] === 404) {
            $this->logger->warning('[CleanCode Synthèse] ⚠️ Utilisateur sans équipe rattachée.');
            $this->addFlash('notice', ['type' => 'warning', 'message' => self::$erreur404, 'trace' => null]);
        } elseif ($data['code'] === 406) {
            $this->logger->warning('[CleanCode Synthèse] ⚠️ Aucun projet dans le portefeuille.');
            $this->addFlash('notice', ['type' => 'warning', 'message' => self::$erreur406, 'trace' => null]);
        } elseif ($data['code'] === 503) {
            $this->logger->warning('[CleanCode Synthèse] ⚠️ Aucune donnée clean code dans l\'historique.');
            $this->addFlash('notice', ['type' => 'warning', 'message' => self::$erreur503, 'trace' => null]);
        // MODIF 2026-05-26 : else terminal requis par S126 (code 200 = succès, aucune action requise).
        } else {
            $this->logger->debug('[CleanCode Synthèse] ✅ Données synthèse chargées avec succès.');
        }

        return $this->render(self::$page, $render);
    }

    /**
     * [Description for buildSyntheseData]
     * MODIF 2026-05-19 : factorise le calcul
     * des projets pour éviter la duplication entre index() et pdf().
     *
     * @return array{code: int, scope_label: string, projets: array<int, array<string, mixed>>}
     *
     * Created at: 19/05/2026 09:23:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function buildSyntheseData(): array
    {
        $groupes = $this->appUser()->getListeGroupeFonctionnel();
        $scopeLabel = empty($groupes)
            ? 'Aucun périmètre'
            : 'Périmètre : ' . implode(', ', $groupes);

        if (empty($groupes)) {
            return ['code' => 404, 'scope_label' => $scopeLabel, 'projets' => []];
        }

        $mesProjets = $this->mesProjets->liste($groupes);
        if ($mesProjets['code'] !== 200 || empty($mesProjets['projets'])) {
            return ['code' => 406, 'scope_label' => $scopeLabel, 'projets' => []];
        }

        $mavenKeys = array_values(array_map(
            static fn(array $projet): string => (string) $projet['id'],
            $mesProjets['projets']
        ));

        $result = $this->em->getRepository(Historique::class)
            ->selectHistoriqueCleanCodeSynthese($mavenKeys);

        if ($result['code'] !== 200 || empty($result['liste'])) {
            return ['code' => 503, 'scope_label' => $scopeLabel, 'projets' => []];
        }

        $ratingMap = ['1' => 'A', '2' => 'B', '3' => 'C', '4' => 'D', '5' => 'E'];
        $normalizeNote = static fn(?string $v): string =>
            ($v === null || $v === '') ? '—' : ($ratingMap[$v] ?? strtoupper($v));

        $levelOrder = ['critical' => 0, 'high' => 1, 'medium' => 2, 'low' => 3];
        $projets = [];

        foreach ($result['liste'] as $row) {
            $violations = (int)($row['violations'] ?? 0);
            $rawRisk    = ((int)($row['impact_blocker'] ?? 0) * 4)
                        + ((int)($row['impact_high']    ?? 0) * 3)
                        + ((int)($row['impact_medium']  ?? 0) * 2)
                        + ((int)($row['impact_low']     ?? 0) * 1)
                        + ((int)($row['impact_info']    ?? 0) * 0.5);
            $riskScore  = $violations > 0 ? round($rawRisk / $violations, 2) : 0;
            $riskLevel  = match(true) {
                $riskScore >= 3.0 => 'critical',
                $riskScore >= 2.0 => 'high',
                $riskScore >= 1.0 => 'medium',
                default           => 'low',
            };

            $ccTotal        = (int)($row['cc_consistent'] ?? 0) + (int)($row['cc_intentional'] ?? 0)
                            + (int)($row['cc_adaptable']  ?? 0) + (int)($row['cc_responsible'] ?? 0);
            $responsiblePct = $ccTotal > 0
                ? round(((int)($row['cc_responsible'] ?? 0) / $ccTotal) * 100, 1)
                : 0;

            $projets[] = [
                'maven_key'               => $row['maven_key'],
                'project_name'            => $row['project_name'],
                'version'                 => $row['version'],
                'date_collecte'           => $row['date_enregistrement'],
                'alert_status'            => $row['alert_status'] ?? null,
                'note_mnt'                => $normalizeNote($row['note_mnt']     ?? null),
                'note_fia'                => $normalizeNote($row['note_fia']     ?? null),
                'note_sec'                => $normalizeNote($row['note_sec']     ?? null),
                'note_hsp'                => $normalizeNote($row['note_hsp']     ?? null),
                'note_cpx'                => $normalizeNote($row['note_cpx']     ?? null),
                'note_cpx_cog'            => $normalizeNote($row['note_cpx_cog'] ?? null),
                'risk_score'              => $riskScore,
                'risk_level'              => $riskLevel,
                'responsible_pct'         => $responsiblePct,
                'bugs'                    => (int)($row['bugs']            ?? 0),
                'vulnerabilities'         => (int)($row['vulnerabilities'] ?? 0),
                'coverage'                => $row['coverage'] !== null
                    ? round((float)$row['coverage'], 1) : null,
                'duplicated_lines_density' => $row['duplicated_lines_density'] !== null
                    ? round((float)$row['duplicated_lines_density'], 1) : null,
                'owasp_top10'             => (int)($row['owasp_top10'] ?? 0),
            ];
        }

        usort($projets, static function (array $a, array $b) use ($levelOrder): int {
            $diff = $levelOrder[$a['risk_level']] <=> $levelOrder[$b['risk_level']];
            return $diff !== 0 ? $diff : strcmp($a['project_name'], $b['project_name']);
        });

        return ['code' => 200, 'scope_label' => $scopeLabel, 'projets' => $projets];
    }

    /**
     * [Description for pdf]
     * Export PDF paysage A4 — 9 colonnes.
     *
     * @return Response
     *
     * Created at: 19/05/2026 09:24:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/clean-code/synthese/pdf', name: 'clean_code_synthese_pdf', methods: ['GET'])]
    public function pdf(): Response
    {
        $data = $this->buildSyntheseData();
        if ($data['code'] !== 200) {
            $this->addFlash('notice', ['type' => 'warning', 'message' => self::$erreur503, 'trace' => null]);
            return $this->redirectToRoute('clean_code_synthese');
        }

        $pdf = $this->pdfExportService->generateCleanCodeSynthesePdf([
            'projets'     => $data['projets'],
            'scope_label' => $data['scope_label'],
        ]);

        return new Response($pdf, 200, [
            'Content-Type'        => 'application/pdf',
            'Content-Disposition' => 'inline; filename="synthese-clean-code.pdf"',
        ]);
    }
}
