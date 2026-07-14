<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Service\DependencyCheck;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * MODIF 2026-05-09 : service d'analyse pour la vue
 * exécutive prise de décision (page /dependency-check/projet/.../executive).
 *
 * Toutes les méthodes prennent un tableau de findings (issu de
 * DcFindingRepository::listForScan) et retournent des structures pures
 * adaptées au template / aux charts. Pas d'I/O ici : facile à tester unit.
 *
 * MODIF 2026-05-12 : formule JH
 * paramétrable par famille technologique + sévérité max.
 *   JH_dep = (test_effort_by_family[famille] + upgrade_overhead) * safety_margin
 *          + severity_bonus[sev_max]
 *   arrondi au demi-jour.
 * Tous les coefficients sont définis dans config/packages/dc_remediation.yaml
 * et injectés via Autowire (modifiables sans recompilation PHP).
 */
final class DcExecutiveAnalyticsService
{
    /** Rang utilisé pour trier (plus petit = plus grave). */
    public const SEVERITY_RANK = [
        'CRITICAL' => 1,
        'HIGH'     => 2,
        'MEDIUM'   => 3,
        'LOW'      => 4,
        'INFO'     => 5,
    ];

    /**
     * MODIF 2026-05-12 : paramètres
     * de la formule JH injectés depuis config/packages/dc_remediation.yaml.
     * Permet de modifier les coefficients sans toucher au code (juste un
     * cache:clear après edition du YAML).
     *
     * @param float $upgradeOverhead  effort fixe de bump version + recompile + release (j)
     * @param float $safetyMargin     coefficient multiplicateur (typiquement 1.2 = +20%)
     * @param array<string, float> $severityBonus     bonus par sévérité max (CRITICAL/HIGH/MEDIUM/LOW/INFO -> float)
     * @param array<string, float> $testEffortByFamily  effort tests non-regression par famille (cf. guessFamily)
     */
    public function __construct(
        #[Autowire(param: 'dc.remediation.upgrade_overhead')]
        private readonly float $upgradeOverhead = 0.2,
        #[Autowire(param: 'dc.remediation.safety_margin')]
        private readonly float $safetyMargin = 1.2,
        #[Autowire(param: 'dc.remediation.severity_bonus')]
        private readonly array $severityBonus = [
            'CRITICAL' => 0.5, 'HIGH' => 0.3, 'MEDIUM' => 0.1, 'LOW' => 0.0, 'INFO' => 0.0,
        ],
        #[Autowire(param: 'dc.remediation.test_effort_by_family')]
        private readonly array $testEffortByFamily = [
            'Log4j' => 0.5, 'Apache Commons' => 0.5, 'XML / Woodstox' => 0.5,
            'Jackson' => 1.0, 'AMQP' => 1.0, 'Apache (autre)' => 1.0, 'Autres' => 1.0,
            'Apache CXF' => 1.5, 'Spring Framework' => 2.0,
        ],
        /* MODIF 2026-05-14 : paramètres du
         * score sécurité par app (page KPI + comparer). Cf YAML pour
         * la formule complete. */
        #[Autowire(param: 'dc.score_secu.weights')]
        private readonly array $scoreSecuWeights = [
            'CRITICAL' => 10.0, 'HIGH' => 3.0, 'MEDIUM' => 1.0, 'LOW' => 0.5,
        ],
        #[Autowire(param: 'dc.score_secu.caps')]
        private readonly array $scoreSecuCaps = [
            'CRITICAL' => 5, 'HIGH' => 10, 'MEDIUM' => 20, 'LOW' => 20,
        ],
    ) {}

    /**
     * MODIF 2026-05-14 : calcule le score
     * sécurité d'une application (0-100, 100 = saine).
     *
     * Formule :
     *   Score = 100 - sum( weights[sev] * min(nb_cve[sev], caps[sev]) )
     *   Score = max(0, Score)
     *
     * Le plafond par sévérité (cap) evite qu'un nombre énorme de CVE LOW
     * noie le signal des CRITICAL, et borne mathématiquement la pénalité max.
     *
     * @param int $crit Nombre de CVE CRITICAL
     * @param int $high Nombre de CVE HIGH
     * @param int $med  Nombre de CVE MEDIUM
     * @param int $low  Nombre de CVE LOW
     *
     * @return float Score entre 0.0 et 100.0
     *
     * Created at: 14/05/2026 12:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeScoreSecu(int $crit, int $high, int $med, int $low): float
    {
        $penalty = $this->scoreSecuWeights['CRITICAL'] * min($crit, $this->scoreSecuCaps['CRITICAL'])
                 + $this->scoreSecuWeights['HIGH']     * min($high, $this->scoreSecuCaps['HIGH'])
                 + $this->scoreSecuWeights['MEDIUM']   * min($med,  $this->scoreSecuCaps['MEDIUM'])
                 + $this->scoreSecuWeights['LOW']      * min($low,  $this->scoreSecuCaps['LOW']);
        return max(0.0, 100.0 - $penalty);
    }

    /**
     * MODIF 2026-05-14 : retourne la règle
     * de calcul du score sécurité sous forme structurée, pour affichage
     * dans le callout info de la page KPI / modale méthodologie.
     *
     * @return array{
     *   weights: array<string, float>,
     *   caps: array<string, int>,
     *   penalty_max: float
     * }
     *
     * Created at: 14/05/2026 12:00:00 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getScoreSecuRule(): array
    {
        $penaltyMax = 0.0;
        foreach ($this->scoreSecuWeights as $sev => $weight) {
            $penaltyMax += $weight * ($this->scoreSecuCaps[$sev] ?? 0);
        }
        return [
            'weights'     => $this->scoreSecuWeights,
            'caps'        => $this->scoreSecuCaps,
            'penalty_max' => $penaltyMax,
        ];
    }

    /**
     * [Description for computeDepJh]
     * MODIF 2026-05-12 : calcule le JH d'une
     * dépendance a upgrader. Centralisée ici pour partager entre
     * computeRemediationPlan (page executive) et controller dashboard
     * (mutualisations). Arrondi au demi-jour.
     *
     * @param string $family
     * @param string $sevMax
     *
     * @return float
     *
     * Created at: 15/05/2026 10:12:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeDepJh(string $family, string $sevMax): float
    {
        $testEffort = $this->testEffortByFamily[$family]
            ?? $this->testEffortByFamily['Autres']
            ?? 1.0;
        $bonus = $this->severityBonus[strtoupper($sevMax)] ?? 0.0;
        $raw   = ($testEffort + $this->upgradeOverhead) * $this->safetyMargin + $bonus;
        return round($raw * 2) / 2;
    }

    /**
     * [Description for getFamily]
     * MODIF 2026-05-12 : expose guessFamily
     * en méthode publique pour que le controller puisse calculer la famille
     * d'une dépendance avant d'appeler computeDepJh.
     *
     * @param string $vendor
     * @param string $product
     *
     * @return string
     *
     * Created at: 15/05/2026 10:13:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getFamily(string $vendor, string $product): string
    {
        return $this->guessFamily(strtolower($vendor), strtolower($product));
    }

    /**
     * [Description for getSeverityBonus]
     * Accesseurs publics pour exposer les paramètres au template (modale
     * méthodologie + affichage des constantes effectives).
     *
     * @return array<string, float>
     *
     * Created at: 15/05/2026 10:13:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getSeverityBonus(): array { return $this->severityBonus; }

    /**
     * [Description for getTestEffortByFamily]
     *
     * @return array<string, float>
     *
     * Created at: 15/05/2026 10:14:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getTestEffortByFamily(): array { return $this->testEffortByFamily; }

    /**
     * [Description for getUpgradeOverhead]
     *
     * @return float
     *
     * Created at: 15/05/2026 10:15:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getUpgradeOverhead(): float { return $this->upgradeOverhead; }

    /**
     * [Description for getSafetyMargin]
     *
     * @return float
     *
     * Created at: 15/05/2026 10:15:24 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getSafetyMargin(): float { return $this->safetyMargin; }

    /**
     * [Description for computeCriticityCounts]
     * Compteurs par sévérité (pie chart + cards de la vue globale).
     *
     * @param array<int, array<string, mixed>> $findings
     * @return array<string, int>
     *
     *
     * Created at: 10/05/2026 23:46:17 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeCriticityCounts(array $findings): array
    {
        $counts = ['CRITICAL' => 0, 'HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0, 'INFO' => 0];
        foreach ($findings as $f) {
            $s = strtoupper((string) ($f['severity'] ?? ''));
            if (isset($counts[$s])) {
                $counts[$s]++;
            }
        }
        return $counts;
    }

    /**
     * [Description for computeTopCwes]
     * Top N CWE par occurrence (radar chart).
     *
     * @param array<int, array<string, mixed>> $findings
     * @return array<int, array{cwe: string, nb: int}>
     *
     * @return array
     *
     * Created at: 10/05/2026 23:46:58 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeTopCwes(array $findings, int $limit): array
    {
        $counts = [];
        foreach ($findings as $f) {
            foreach ((array) ($f['cwes'] ?? []) as $cwe) {
                if (!is_string($cwe) || $cwe === '') { continue; }
                $counts[$cwe] = ($counts[$cwe] ?? 0) + 1;
            }
        }
        arsort($counts);
        $result = [];
        foreach (array_slice($counts, 0, $limit, true) as $cwe => $nb) {
            $result[] = ['cwe' => $cwe, 'nb' => $nb];
        }
        return $result;
    }

    /**
     * [Description for computeFamilyBreakdown]
     * Répartition des CVE par famille technologique (heuristique vendor/product).
     * Reproduit la section 4 de var/socle-java-audit.md.
     *
     * @param array<int, array<string, mixed>> $findings
     * @return array<int, array{family: string, nb_cves: int}>
     *
     * @return array
     *
     * Created at: 10/05/2026 23:47:39 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeFamilyBreakdown(array $findings): array
    {
        $counts = [];
        foreach ($findings as $f) {
            $family = $this->guessFamily(
                strtolower((string) ($f['vendor'] ?? '')),
                strtolower((string) ($f['product'] ?? ''))
            );
            $counts[$family] = ($counts[$family] ?? 0) + 1;
        }
        arsort($counts);
        $result = [];
        foreach ($counts as $family => $nb) {
            $result[] = ['family' => $family, 'nb_cves' => $nb];
        }
        return $result;
    }

    /**
     * [Description for computeTopDepsBreakdown]
     * Top N dépendances avec détail par sévérité. Tri sev_max ASC, puis nb_cves DESC.
     *
     * @param array<int, array<string, mixed>> $findings
     * @return array<int, array{
     *   product: string, version: ?string, nb_cves: int,
     *   nb_critical: int, nb_high: int, nb_medium: int, nb_low: int,
     *   sev_max: string,
     * }>
     *
     * @return array
     *
     * Created at: 10/05/2026 23:48:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeTopDepsBreakdown(array $findings, int $limit): array
    {
        $stats = [];
        foreach ($findings as $f) {
            $key = trim((string) ($f['product'] ?? '')) ?: trim((string) ($f['file_name'] ?? '')) ?: 'inconnu';
            if (!isset($stats[$key])) {
                $stats[$key] = [
                    'product' => $key,
                    'vendor'  => $f['vendor'] ?? null,
                    'version' => $f['dep_version'] ?? null,
                    'nb_cves' => 0,
                    'nb_critical' => 0, 'nb_high' => 0, 'nb_medium' => 0, 'nb_low' => 0,
                    'sev_max' => 'INFO',
                ];
            }
            $stats[$key]['nb_cves']++;
            $sev = strtoupper((string) ($f['severity'] ?? ''));
            switch ($sev) {
                case 'CRITICAL': $stats[$key]['nb_critical']++; break;
                case 'HIGH':     $stats[$key]['nb_high']++;     break;
                case 'MEDIUM':   $stats[$key]['nb_medium']++;   break;
                case 'LOW':      $stats[$key]['nb_low']++;      break;
                default: break;
            }
            $rankCurrent = self::SEVERITY_RANK[$stats[$key]['sev_max']] ?? 99;
            $rankNew     = self::SEVERITY_RANK[$sev] ?? 99;
            if ($rankNew < $rankCurrent) {
                $stats[$key]['sev_max'] = $sev;
            }
        }
        usort($stats, static function (array $a, array $b): int {
            $rankA = self::SEVERITY_RANK[$a['sev_max']] ?? 99;
            $rankB = self::SEVERITY_RANK[$b['sev_max']] ?? 99;
            if ($rankA !== $rankB) { return $rankA <=> $rankB; }
            return $b['nb_cves'] <=> $a['nb_cves'];
        });
        return array_slice($stats, 0, $limit);
    }

    /**
     * [Description for computeTopPriorityCves]
     * Top N CVE prioritaires (CVSS DESC, sévérité tie-breaker), dédupliquées par cve_id.
     *
     * @param array<int, array<string, mixed>> $findings
     * @return array<int, array{cve_id: string, severity: string, cvss: ?float, product: string, description: ?string}>
     *
     * @return array
     *
     * Created at: 10/05/2026 23:53:22 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeTopPriorityCves(array $findings, int $limit): array
    {
        $rows = [];
        $seen = [];
        foreach ($findings as $f) {
            $key = (string) ($f['cve_id'] ?? '');
            if ($key === '' || isset($seen[$key])) { continue; }
            $seen[$key] = true;
            $rows[] = [
                'cve_id'      => $key,
                'severity'    => strtoupper((string) ($f['severity'] ?? '')),
                'cvss'        => isset($f['cvss']) ? (float) $f['cvss'] : null,
                'product'     => (string) ($f['product'] ?? $f['file_name'] ?? ''),
                'description' => $f['description'] ?? null,
            ];
        }
        usort($rows, static function (array $a, array $b): int {
            $aCvss = $a['cvss'] ?? -1.0;
            $bCvss = $b['cvss'] ?? -1.0;
            if ($aCvss !== $bCvss) { return $bCvss <=> $aCvss; }
            $rankA = self::SEVERITY_RANK[$a['severity']] ?? 99;
            $rankB = self::SEVERITY_RANK[$b['severity']] ?? 99;
            return $rankA <=> $rankB;
        });
        return array_slice($rows, 0, $limit);
    }

    /**
     * [Description for computeCveDecisions]
     * Table CVE → Décision → Justification.
     *
     * MODIF 2026-05-12 : retrait du signal
     * `fixed_version` du pipeline. Il dérivait de versionEndExcluding du JSON DC
     * (= borne supérieure d'un range CPE), pas d'une fix recommandée officielle.
     * Heuristique simplifiée à 2 signaux fiables :
     *   1. Decision de base par severity (CRITICAL/HIGH/MEDIUM=Upgrade,
     *      LOW=Mitigation, autres=Surveillance)
     *   2. Attack vector LOCAL/PHYSICAL minore d'un cran (Upgrade->Mitigation,
     *      Mitigation->Surveillance), SAUF CRITICAL qu'on conserve en Upgrade
     *      (un CRITICAL local reste un CRITICAL : escalade post-compromise).
     *
     * @param array<int, array<string, mixed>> $findings
     * @return array<int, array{
     *   product: string, version: ?string, cve_id: string,
     *   severity: string, attack_vector: ?string,
     *   decision: string, justification: string,
     * }>
     */
    public function computeCveDecisions(array $findings): array
    {
        $rows = [];
        foreach ($findings as $f) {
            $sev    = strtoupper((string) ($f['severity'] ?? ''));
            $vector = strtoupper((string) ($f['cvss_v3_attack_vector'] ?? ''));

            $rows[] = [
                'product'       => (string) ($f['product'] ?? $f['file_name'] ?? ''),
                'version'       => $f['dep_version'] ?? null,
                'cve_id'        => (string) ($f['cve_id'] ?? ''),
                'severity'      => $sev,
                'attack_vector' => $vector !== '' ? $vector : null,
                'decision'      => self::computeDecisionFromHeuristic($sev, $vector),
                'justification' => self::buildDecisionJustification($sev, $vector),
            ];
        }
        /* MODIF 2026-05-12 : tri enrichi avec
         * `product` comme tie-breaker entre `severity` et `cve_id`. Détecte
         * en audit qualité : sur un scan avec 5 CVE meme sévérité sur cxf-core,
         * elles étaient dispersées entre d'autres deps de meme sévérité. Avec
         * `product` en 2e cle, les CVE d'une meme dep se suivent. 1 ligne par
         * CVE préservée (la decision peut différer entre 2 CVE meme product). */
        usort($rows, static function (array $a, array $b): int {
            $rankA = self::SEVERITY_RANK[$a['severity']] ?? 99;
            $rankB = self::SEVERITY_RANK[$b['severity']] ?? 99;
            if ($rankA !== $rankB) { return $rankA <=> $rankB; }
            $productCmp = strcmp($a['product'], $b['product']);
            if ($productCmp !== 0) { return $productCmp; }
            return strcmp($a['cve_id'], $b['cve_id']);
        });
        return $rows;
    }

    /**
     * [Description for computeDecisionFromHeuristic]
     * Pipeline pur de calcul de la decision : severity + vector.
     * Externalisée pour faciliter les TU et clarté du raisonnement.
     *
     * @param string $severity
     * @param string $attackVector
     *
     * @return string
     *
     * Created at: 12/05/2026 14:39:51 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function computeDecisionFromHeuristic(string $severity, string $attackVector): string
    {
        $decision = match ($severity) {
            'CRITICAL', 'HIGH', 'MEDIUM' => 'Upgrade',
            'LOW'                        => 'Mitigation',
            default                      => 'Surveillance',
        };

        // LOCAL/PHYSICAL minore d'un cran (sauf CRITICAL qu'on garde)
        if ($severity !== 'CRITICAL' && in_array($attackVector, ['LOCAL', 'PHYSICAL'], true)) {
            $decision = match ($decision) {
                'Upgrade'    => 'Mitigation',
                'Mitigation' => 'Surveillance',
                default      => $decision,
            };
        }

        return $decision;
    }

    /**
     * [Description for buildDecisionJustification]
     * Justification dynamique résumant les 2 signaux exploités.
     *
     * @param string $severity
     * @param string $attackVector
     *
     * @return string
     *
     * Created at: 12/05/2026 14:40:35 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private static function buildDecisionJustification(string $severity, string $attackVector): string
    {
        $parts = [sprintf('Sévérité %s', $severity !== '' ? $severity : 'inconnue')];
        if ($attackVector !== '') {
            $parts[] = sprintf('vecteur %s', $attackVector);
        }
        return implode(' • ', $parts) . '.';
    }

    /**
     * [Description for computeRemediationPlan]
     * REMEDIATION_BASE_JH / REMEDIATION_BONUS_CRITICAL_JH supprimées au profit
     * de la formule paramétrable injectée depuis dc_remediation.yaml et
     * exposée via computeDepJh($family, $sevMax). Voir constructeur du service.
     *
     * @param array $findings
     *
     * @return array
     *
     * Created at: 15/05/2026 10:17:28 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeRemediationPlan(array $findings): array
    {
        $deps  = $this->computeTopDepsBreakdown($findings, PHP_INT_MAX);
        $par   = [];
        $total = 0.0;
        foreach ($deps as $d) {
            $family = $this->guessFamily(
                strtolower((string) ($d['vendor']  ?? '')),
                strtolower((string) ($d['product'] ?? ''))
            );
            $jh = $this->computeDepJh($family, (string) $d['sev_max']);
            $par[] = [
                'product'     => $d['product'],
                'version'     => $d['version'],
                'family'      => $family,
                'nb_critical' => $d['nb_critical'],
                'nb_high'     => $d['nb_high'],
                'nb_medium'   => $d['nb_medium'],
                'nb_low'      => $d['nb_low'],
                'jh'          => $jh,
                'sev_max'     => $d['sev_max'],
            ];
            $total += $jh;
        }
        return ['par_dependance' => $par, 'total_jh' => round($total * 2) / 2];
    }

    /**
     * [Description for computeTopActions]
     * Top N actions recommandées = top JH parmi celles avec ≥1 CRITICAL ou HIGH.
     *
     * @param array<int, array<string, mixed>> $findings
     * @return array<int, array{product: string, version: ?string, sev_max: string, nb_cves: int, jh: float}>
     *
     * @return array
     *
     * Created at: 10/05/2026 23:57:37 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeTopActions(array $findings, int $limit): array
    {
        $plan    = $this->computeRemediationPlan($findings);
        $actions = array_filter(
            $plan['par_dependance'],
            static fn(array $d): bool => $d['nb_critical'] > 0 || $d['nb_high'] > 0
        );
        usort($actions, static fn(array $a, array $b): int => $b['jh'] <=> $a['jh']);
        $top = array_slice($actions, 0, $limit);
        return array_map(static fn(array $d): array => [
            'product' => $d['product'],
            'version' => $d['version'],
            'sev_max' => $d['sev_max'],
            'nb_cves' => $d['nb_critical'] + $d['nb_high'] + $d['nb_medium'] + $d['nb_low'],
            'jh'      => $d['jh'],
        ], $top);
    }

    /**
     * [Description for isUnchanged]
      * MODIF 2026-05-12 : true si une diff
     * (issue de computeScanDiff) ne contient ni nouvelle CVE ni CVE corrigée.
     * Permet au template de distinguer "diff calculée et vraiment vide = pas
     * de changement depuis le dernier passage" (encart vert rassurant) du
     * cas "pas de scan antérieur du tout" (section masquée).
     *
     * @param array{new: array, fixed: array, unchanged_count: int}|array<string, mixed> $diff
     *
     * @return bool
     *
     * Created at: 12/05/2026 14:38:19 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function isUnchanged(array $diff): bool
    {
        return empty($diff['new'] ?? []) && empty($diff['fixed'] ?? []);
    }

    /**
     * [Description for computeScanDiff]
     *
     * @param array $previousFindings
     * @param array $currentFindings
     *
     * @return array
     *
     * Created at: 15/05/2026 10:18:40 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function computeScanDiff(array $previousFindings, array $currentFindings): array
    {
        /* MODIF 2026-05-12 : clé enrichie
         * `product|dep_version|cve_id` au lieu de `product|cve_id`. Détecte
         * en audit qualité : un projet multi-modules peut contenir plusieurs
         * versions de la meme lib (ex : log4j-core 2.14.0 et 2.13.0 côte à côte),
         * et la meme CVE peut toucher les 2. Avec l'ancienne clé, le
         * `$prevByKey[$key] = $f` écrasait silencieusement la 1re entree,
         * et la diff perdait des findings. Les invariants algébriques
         * (`new + unchanged == count(currents)` et
         * `fixed + unchanged == count(previous)`) ne tombaient pas juste. */
        $key = static fn(array $f): string =>
            (string) ($f['product'] ?? '')
            . '|' . (string) ($f['dep_version'] ?? '')
            . '|' . (string) ($f['cve_id'] ?? '');

        $prevByKey = [];
        foreach ($previousFindings as $f) { $prevByKey[$key($f)] = $f; }
        $currByKey = [];
        foreach ($currentFindings as $f) { $currByKey[$key($f)] = $f; }

        $new = $fixed = [];
        $unchanged = 0;
        foreach ($currByKey as $k => $f) {
            if (!isset($prevByKey[$k])) {
                $new[] = [
                    'cve_id'      => (string) ($f['cve_id'] ?? ''),
                    'product'     => (string) ($f['product'] ?? ''),
                    'dep_version' => $f['dep_version'] ?? null,
                    'severity'    => strtoupper((string) ($f['severity'] ?? '')),
                ];
            } else {
                $unchanged++;
            }
        }
        foreach ($prevByKey as $k => $f) {
            if (!isset($currByKey[$k])) {
                $fixed[] = [
                    'cve_id'      => (string) ($f['cve_id'] ?? ''),
                    'product'     => (string) ($f['product'] ?? ''),
                    'dep_version' => $f['dep_version'] ?? null,
                    'severity'    => strtoupper((string) ($f['severity'] ?? '')),
                ];
            }
        }
        return ['new' => $new, 'fixed' => $fixed, 'unchanged_count' => $unchanged];
    }

    /**
     * [Description for guessFamily]
     * Heuristique de regroupement par famille technologique. Liste extensible.
     *
     * @param string $vendor
     * @param string $product
     *
     * @return string
     *
     * Created at: 10/05/2026 23:58:55 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    private function guessFamily(string $vendor, string $product): string
    {
        if (str_starts_with($product, 'spring-') || str_starts_with($vendor, 'spring')) {
            return 'Spring Framework';
        }
        if (str_contains($product, 'jackson') || str_contains($vendor, 'jackson')) {
            return 'Jackson';
        }
        if (str_contains($product, 'cxf') || str_contains($vendor, 'cxf')) {
            return 'Apache CXF';
        }
        if (str_starts_with($product, 'commons-') || str_contains($vendor, 'commons')) {
            return 'Apache Commons';
        }
        if (str_contains($product, 'log4j') || str_contains($vendor, 'log4j')) {
            return 'Log4j';
        }
        if (str_contains($product, 'amqp') || str_contains($vendor, 'amqp')) {
            return 'AMQP';
        }
        if (str_contains($product, 'woodstox') || str_contains($vendor, 'woodstox')) {
            return 'XML / Woodstox';
        }
        if (str_contains($vendor, 'apache')) {
            return 'Apache (autre)';
        }
        return 'Autres';
    }
}
