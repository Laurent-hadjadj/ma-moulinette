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

use App\Controller\Traits\{AppUserAware, ProjetPerimetreGuard};
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\HttpFoundation\{Request, Response};
use Psr\Log\LoggerInterface;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\CleanCode;
use App\Service\UserAgent\UserAgentTrackingFacade;

/* MODIF 2026-05-17 : contrôleur de la page
 * /clean-code — mini tableau de bord des indicateurs SonarQube 10+
 * (taxonomy clean code, impact qualités/sévérités, conformité OWASP/SANS/CWE).
 * Accès par token (même pattern que OwaspController). */

/**
 * [Description CleanCodeController]
 */
class CleanCodeController extends AbstractController
{
    use AppUserAware;
    use ProjetPerimetreGuard;

    private static string $page = 'clean-code/index.html.twig';
    private static string $erreur400 = '❌ La requête est incorrecte (Erreur 400).';
    private static string $erreur404 = '⚠️ Aucune donnée clean code disponible pour ce projet. Lancez une collecte depuis la page projet.';

    private string $logoEntreprise;
    private string $marqueEntrepriseShort;
    private string $marqueEntrepriseLong;
    private string $environnement;
    private string $version;
    private string $dateCopyright;

    public function __construct(
        ParameterBagInterface $params,
        private EntityManagerInterface $em,
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
     * @return array
     *
     * Created at: 17/05/2026 12:33:50 (Europe/Paris)
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
            'date_copyright' => $this->dateCopyright,
        ];
    }

    /**
     * [Description for decodeToken]
     *
     * @param string $token
     *
     * @return string|null
     *
     * Created at: 17/05/2026 12:33:59 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function decodeToken(string $token): ?string
    {
        $string = str_rot13($token);
        $decoded = base64_decode($string, true);
        if ($decoded === false) {
            return null;
        }
        $parts = preg_split("/[|]+/", $decoded);
        if (count($parts) !== 2) {
            return null;
        }
        return $parts[1];
    }

    /**
     * [Description for index]
     * Page mini-dashboard des indicateurs SonarQube 10+ clean code taxonomy.
     *
     * @param Request $request
     *
     * @return Response
     *
     * Created at: 17/05/2026 12:34:09 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    #[Route('/clean-code', name: 'clean_code')]
    public function index(Request $request): Response
    {
        $this->tracking->track('CLEAN_CODE');

        $render = $this->genericRender();

        /* MODIF 2026-05-15 : data toujours présent dans le contexte Twig. */
        $render['data'] = null;

        $token = $request->query->get('token');
        if (empty($token)) {
            $this->logger->warning('[Clean Code] ⚠️ Token manquant.');
            $this->addFlash('notice', ['type' => 'error', 'message' => self::$erreur400, 'trace' => 'token']);
            return $this->render(self::$page, $render);
        }

        $maven_key = $this->decodeToken($token);
        if (null === $maven_key) {
            $this->logger->error('[Clean Code] ❌ Décodage du token échoué.');
            $this->addFlash('notice', ['type' => 'error', 'message' => self::$erreur400, 'trace' => 'Décodage token.']);
            return $this->render(self::$page, $render);
        }

        /* MODIF 2026-07-19 : ajout du filtrage par groupe fonctionnel — jusqu'ici
         * seul le pare-feu (ROLE_UTILISATEUR) protégeait cette page, sans
         * vérification de périmètre (alignement sur OwaspController::index()). */
        $perimetre = $this->verifierPerimetreProjet($maven_key, '[Clean Code]');
        if ($perimetre['code'] !== 200) {
            $message = $perimetre['message']
                ?? "Une erreur est survenue lors de la vérification du périmètre (Erreur {$perimetre['code']}).";
            $this->addFlash('notice', ['type' => 'warning', 'message' => $message, 'trace' => null]);
            return $this->render(self::$page, array_merge($render, ['maven_key' => $maven_key, 'data' => null]));
        }

        $cleanCodeRepos = $this->em->getRepository(CleanCode::class);
        $result = $cleanCodeRepos->selectCleanCode(['maven_key' => $maven_key]);

        if ($result['code'] !== 200 || empty($result['liste'])) {
            $this->logger->warning('[Clean Code] ⚠️ Aucune donnée pour ' . $maven_key);
            $this->addFlash('notice', ['type' => 'warning', 'message' => self::$erreur404, 'trace' => null]);
            return $this->render(self::$page, array_merge($render, ['maven_key' => $maven_key, 'data' => null]));
        }

        $d = $result['liste'][0];

        /* ── Indicateurs dérivés ────────────────────────────────────── */
        $issueTotal = (int) ($d['issue_total'] ?? 0);

        /* Score de risque pondéré (par issue) */
        $rawRisk = ($d['impact_blocker'] * 4)
                 + ($d['impact_high'] * 3)
                 + ($d['impact_medium'] * 2)
                 + ($d['impact_low'] * 1)
                 + ($d['impact_info'] * 0.5);
        $riskScore = $issueTotal > 0 ? round($rawRisk / $issueTotal, 2) : 0;

        /* Niveau de risque */
        $riskLevel = match(true) {
            $riskScore >= 3.0 => 'critical',
            $riskScore >= 2.0 => 'high',
            $riskScore >= 1.0 => 'medium',
            default           => 'low',
        };

        /* % RESPONSIBLE (gouvernance) */
        $ccTotal = ($d['cc_consistent'] + $d['cc_intentional'] + $d['cc_adaptable'] + $d['cc_responsible']);
        $responsiblePct = $ccTotal > 0 ? round(($d['cc_responsible'] / $ccTotal) * 100, 1) : 0;

        /* % exposition sécurité */
        $qualityTotal = ($d['quality_maintainability'] + $d['quality_reliability'] + $d['quality_security']);
        $securityPct = $qualityTotal > 0 ? round(($d['quality_security'] / $qualityTotal) * 100, 1) : 0;

        /* Données pour les graphiques JS */
        $chartData = [
            'categories' => [
                'labels'  => ['CONSISTENT', 'INTENTIONAL', 'ADAPTABLE', 'RESPONSIBLE'],
                'values'  => [
                    (int) $d['cc_consistent'],
                    (int) $d['cc_intentional'],
                    (int) $d['cc_adaptable'],
                    (int) $d['cc_responsible'],
                ],
            ],
            'severities' => [
                'labels' => ['BLOCKER', 'HIGH', 'MEDIUM', 'LOW', 'INFO'],
                'values' => [
                    (int) $d['impact_blocker'],
                    (int) $d['impact_high'],
                    (int) $d['impact_medium'],
                    (int) $d['impact_low'],
                    (int) $d['impact_info'],
                ],
            ],
        ];

        /* MODIF 2026-05-19 : delta vs version précédente + graphique évolution
         * violations = total issues dans historique (équivalent de issue_total dans clean_code).
         * TO.DO : A déplacer dans le repository dans la prochaine version*/
        $historyRows = $this->em->getConnection()->fetchAllAssociative(
            'SELECT version, date_enregistrement, violations,
                    cc_consistent, cc_intentional, cc_adaptable, cc_responsible,
                    quality_maintainability, quality_reliability, quality_security,
                    impact_blocker, impact_high, impact_medium, impact_low, impact_info
                FROM ma_moulinette.historique
                WHERE maven_key = :mk AND cc_responsible IS NOT NULL
                ORDER BY date_enregistrement DESC
                LIMIT 10',
                ['mk' => $maven_key]
            );

        $deltas = null;
        if (!empty($historyRows)) {
            $p = $historyRows[0];
            $pRawRisk   = ((int)$p['impact_blocker'] * 4) + ((int)$p['impact_high'] * 3)
                        + ((int)$p['impact_medium'] * 2) + ((int)$p['impact_low'] * 1)
                        + ((int)$p['impact_info'] * 0.5);
            $pViolations = (int)$p['violations'];
            $pRiskScore  = $pViolations > 0 ? round($pRawRisk / $pViolations, 2) : 0;

            $pCcTotal = (int)$p['cc_consistent'] + (int)$p['cc_intentional']
                    + (int)$p['cc_adaptable'] + (int)$p['cc_responsible'];
            $pRespPct = $pCcTotal > 0 ? round(((int)$p['cc_responsible'] / $pCcTotal) * 100, 1) : 0;

            $pQualTotal = (int)$p['quality_maintainability'] + (int)$p['quality_reliability']
                        + (int)$p['quality_security'];
            $pSecPct    = $pQualTotal > 0 ? round(((int)$p['quality_security'] / $pQualTotal) * 100, 1) : 0;

            $deltas = [
                'risk_score'      => round($riskScore - $pRiskScore, 2),
                'responsible_pct' => round($responsiblePct - $pRespPct, 1),
                'security_pct'    => round($securityPct - $pSecPct, 1),
                'version'         => $p['version'],
            ];
        }

        if (\count($historyRows) >= 2) {
            $evoLabels = $evoRisk = $evoResp = $evoSec = [];
            foreach (array_reverse($historyRows) as $row) {
                $rawR        = ((int)$row['impact_blocker'] * 4) + ((int)$row['impact_high'] * 3)
                             + ((int)$row['impact_medium'] * 2) + ((int)$row['impact_low'] * 1)
                             + ((int)$row['impact_info'] * 0.5);
                $violations  = (int)$row['violations'];
                $rs = $violations > 0 ? round($rawR / $violations, 2) : 0;

                $cct = (int)$row['cc_consistent'] + (int)$row['cc_intentional']
                    + (int)$row['cc_adaptable'] + (int)$row['cc_responsible'];
                $rp  = $cct > 0 ? round(((int)$row['cc_responsible'] / $cct) * 100, 1) : 0;

                $qt = (int)$row['quality_maintainability'] + (int)$row['quality_reliability']
                    + (int)$row['quality_security'];
                $sp = $qt > 0 ? round(((int)$row['quality_security'] / $qt) * 100, 1) : 0;

                $evoLabels[] = $row['version'];
                $evoRisk[]   = $rs;
                $evoResp[]   = $rp;
                $evoSec[]    = $sp;
            }
            $chartData['evolution'] = [
                'labels'   => $evoLabels,
                'risk'     => $evoRisk,
                'resp'     => $evoResp,
                'security' => $evoSec,
            ];
        }

        $projectVersion = $this->em->getConnection()->fetchOne(
            'SELECT project_version FROM ma_moulinette.information_projet WHERE maven_key = :mk ORDER BY date_enregistrement DESC LIMIT 1',
            ['mk' => $maven_key]
        ) ?: 'N/A';

        $render = array_merge($render, [
            'maven_key'       => $maven_key,
            'project_name'    => $d['project_name'],
            'project_version' => $projectVersion,
            'date_collecte'   => $d['date_enregistrement'],
            'data'            => $d,
            'issue_total'     => $issueTotal,
            'risk_score'      => $riskScore,
            'risk_level'      => $riskLevel,
            'responsible_pct' => $responsiblePct,
            'security_pct'    => $securityPct,
            'deltas'          => $deltas,
            'chart_data'      => json_encode($chartData),
        ]);

        return $this->render(self::$page, $render);
    }
}
