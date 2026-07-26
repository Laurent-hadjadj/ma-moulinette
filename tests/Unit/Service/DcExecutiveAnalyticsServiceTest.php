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

namespace App\Tests\Unit\Service;

use App\Service\DependencyCheck\DcExecutiveAnalyticsService;
use PHPUnit\Framework\TestCase;

/**
 * Tests unitaires de DcExecutiveAnalyticsService.
 *
 * Le service est pur (0 I/O, final) : instanciation directe avec les
 * valeurs par défaut du constructeur.
 *
 * Couvre :
 *  - computeScoreSecu / getScoreSecuRule
 *  - computeDepJh / getFamily / accesseurs
 *  - computeCriticityCounts
 *  - computeTopCwes
 *  - computeFamilyBreakdown
 *  - computeTopDepsBreakdown
 *  - computeTopPriorityCves
 *  - computeCveDecisions (heuristique sev + attack_vector)
 *  - computeRemediationPlan / computeTopActions
 *  - isUnchanged / computeScanDiff
 */
class DcExecutiveAnalyticsServiceTest extends TestCase
{
    private DcExecutiveAnalyticsService $svc;

    protected function setUp(): void
    {
        parent::setUp();
        $this->svc = new DcExecutiveAnalyticsService();
    }

    /* ========================================================
     *  computeScoreSecu
     * ======================================================== */

    public function testScoreSecuAllZeroIsHundred(): void
    {
        $this->assertSame(100.0, $this->svc->computeScoreSecu(0, 0, 0, 0));
    }

    public function testScoreSecuOneCritical(): void
    {
        // penalty = 10 * min(1, 5) = 10
        $this->assertSame(90.0, $this->svc->computeScoreSecu(1, 0, 0, 0));
    }

    public function testScoreSecuFiveCritical(): void
    {
        // penalty = 10 * 5 = 50
        $this->assertSame(50.0, $this->svc->computeScoreSecu(5, 0, 0, 0));
    }

    public function testScoreSecuCapAtFiveCritical(): void
    {
        // 6 CRITICAL : min(6, 5) = 5 → same as 5
        $this->assertSame(50.0, $this->svc->computeScoreSecu(6, 0, 0, 0));
    }

    public function testScoreSecuMixedSeverities(): void
    {
        // CRIT=5 HIGH=10 : penalty = 50 + 30 = 80 → score = 20
        $this->assertSame(20.0, $this->svc->computeScoreSecu(5, 10, 0, 0));
    }

    public function testScoreSecuFloorAtZero(): void
    {
        // All caps exceeded → penalty > 100 → score clamped to 0
        $this->assertSame(0.0, $this->svc->computeScoreSecu(5, 10, 20, 20));
    }

    /* ========================================================
     *  getScoreSecuRule
     * ======================================================== */

    public function testGetScoreSecuRulePenaltyMax(): void
    {
        // weights: CRIT=10 HIGH=3 MED=1 LOW=0.5 / caps: 5,10,20,20
        // max = 10*5 + 3*10 + 1*20 + 0.5*20 = 50+30+20+10 = 110
        $rule = $this->svc->getScoreSecuRule();
        $this->assertSame(110.0, $rule['penalty_max']);
    }

    /* ========================================================
     *  computeDepJh — formule JH paramétrée
     * ======================================================== */

    public function testDepJhLog4jCritical(): void
    {
        // effort=0.5, overhead=0.2, margin=1.2, bonus=0.5
        // raw=(0.5+0.2)*1.2+0.5 = 0.84+0.5 = 1.34 → round(2.68)/2 = 1.5
        $this->assertSame(1.5, $this->svc->computeDepJh('Log4j', 'CRITICAL'));
    }

    public function testDepJhLog4jHigh(): void
    {
        // raw=(0.5+0.2)*1.2+0.3=1.14 → round(2.28)/2=1.0
        $this->assertSame(1.0, $this->svc->computeDepJh('Log4j', 'HIGH'));
    }

    public function testDepJhSpringCritical(): void
    {
        // effort=2.0, raw=(2.0+0.2)*1.2+0.5=3.14 → round(6.28)/2=3.0
        $this->assertSame(3.0, $this->svc->computeDepJh('Spring Framework', 'CRITICAL'));
    }

    public function testDepJhAutresLow(): void
    {
        // effort=1.0, raw=(1.0+0.2)*1.2+0.0=1.44 → round(2.88)/2=1.5
        $this->assertSame(1.5, $this->svc->computeDepJh('Autres', 'LOW'));
    }

    public function testDepJhUnknownFamilyFallsBackToAutres(): void
    {
        // unknown → 'Autres' effort=1.0, MEDIUM bonus=0.1
        // raw=1.44+0.1=1.54 → round(3.08)/2=1.5
        $this->assertSame(1.5, $this->svc->computeDepJh('FamilleInconnue', 'MEDIUM'));
    }

    /* ========================================================
     *  getFamily (expose guessFamily)
     * ======================================================== */

    public function testFamilySpringByProduct(): void
    {
        $this->assertSame('Spring Framework', $this->svc->getFamily('', 'spring-boot'));
    }

    public function testFamilySpringByVendor(): void
    {
        $this->assertSame('Spring Framework', $this->svc->getFamily('spring', 'boot'));
    }

    public function testFamilyJackson(): void
    {
        $this->assertSame('Jackson', $this->svc->getFamily('', 'jackson-databind'));
    }

    public function testFamilyCxf(): void
    {
        $this->assertSame('Apache CXF', $this->svc->getFamily('', 'cxf-core'));
    }

    public function testFamilyCommonsLang(): void
    {
        $this->assertSame('Apache Commons', $this->svc->getFamily('', 'commons-lang3'));
    }

    public function testFamilyLog4j(): void
    {
        $this->assertSame('Log4j', $this->svc->getFamily('', 'log4j-core'));
    }

    public function testFamilyWoodstox(): void
    {
        $this->assertSame('XML / Woodstox', $this->svc->getFamily('', 'woodstox-core'));
    }

    public function testFamilyApacheOther(): void
    {
        $this->assertSame('Apache (autre)', $this->svc->getFamily('apache', 'httpclient'));
    }

    public function testFamilyAmqp(): void
    {
        $this->assertSame('AMQP', $this->svc->getFamily('', 'amqp-client'));
    }

    public function testFamilyUnknownIsAutres(): void
    {
        $this->assertSame('Autres', $this->svc->getFamily('unknown', 'somelib'));
    }

    /* ========================================================
     *  Accesseurs simples
     * ======================================================== */

    public function testGetSeverityBonusReturnsDefaults(): void
    {
        $bonus = $this->svc->getSeverityBonus();
        $this->assertSame(0.5, $bonus['CRITICAL']);
        $this->assertSame(0.0, $bonus['LOW']);
    }

    public function testGetTestEffortByFamilyReturnsDefaults(): void
    {
        $effort = $this->svc->getTestEffortByFamily();
        $this->assertSame(0.5, $effort['Log4j']);
        $this->assertSame(2.0, $effort['Spring Framework']);
    }

    public function testGetUpgradeOverheadDefault(): void
    {
        $this->assertSame(0.2, $this->svc->getUpgradeOverhead());
    }

    public function testGetSafetyMarginDefault(): void
    {
        $this->assertSame(1.2, $this->svc->getSafetyMargin());
    }

    /* ========================================================
     *  computeCriticityCounts
     * ======================================================== */

    public function testCriticityCountsEmptyFindings(): void
    {
        $counts = $this->svc->computeCriticityCounts([]);
        $this->assertSame(0, $counts['CRITICAL']);
        $this->assertSame(0, $counts['HIGH']);
    }

    public function testCriticityCountsMixed(): void
    {
        $findings = [
            ['severity' => 'CRITICAL'],
            ['severity' => 'CRITICAL'],
            ['severity' => 'HIGH'],
            ['severity' => 'LOW'],
            ['severity' => 'unknown'],
        ];
        $counts = $this->svc->computeCriticityCounts($findings);
        $this->assertSame(2, $counts['CRITICAL']);
        $this->assertSame(1, $counts['HIGH']);
        $this->assertSame(0, $counts['MEDIUM']);
        $this->assertSame(1, $counts['LOW']);
    }

    public function testCriticityCountsUnknownSeverityIgnored(): void
    {
        $counts = $this->svc->computeCriticityCounts([['severity' => 'WHATEVER']]);
        $this->assertSame(0, array_sum($counts));
    }

    public function testCriticityCountsLowercaseNormalized(): void
    {
        $counts = $this->svc->computeCriticityCounts([['severity' => 'critical']]);
        $this->assertSame(1, $counts['CRITICAL']);
    }

    /* ========================================================
     *  computeTopCwes
     * ======================================================== */

    public function testTopCwesEmptyFindings(): void
    {
        $this->assertSame([], $this->svc->computeTopCwes([], 5));
    }

    public function testTopCwesAggregatesAndSorts(): void
    {
        $findings = [
            ['cwes' => ['CWE-79', 'CWE-89']],
            ['cwes' => ['CWE-79']],
            ['cwes' => ['CWE-89']],
            ['cwes' => ['CWE-79']],
        ];
        $top = $this->svc->computeTopCwes($findings, 5);
        $this->assertSame('CWE-79', $top[0]['cwe']);
        $this->assertSame(3, $top[0]['nb']);
        $this->assertSame('CWE-89', $top[1]['cwe']);
        $this->assertSame(2, $top[1]['nb']);
    }

    public function testTopCwesRespectsLimit(): void
    {
        $findings = array_map(
            static fn(int $i): array => ['cwes' => ["CWE-{$i}"]],
            range(1, 10)
        );
        $this->assertCount(3, $this->svc->computeTopCwes($findings, 3));
    }

    public function testTopCwesIgnoresEmptyOrNonString(): void
    {
        $findings = [['cwes' => ['', null, 123, 'CWE-20']]];
        $top = $this->svc->computeTopCwes($findings, 5);
        $this->assertCount(1, $top);
        $this->assertSame('CWE-20', $top[0]['cwe']);
    }

    /* ========================================================
     *  computeFamilyBreakdown
     * ======================================================== */

    public function testFamilyBreakdownEmptyFindings(): void
    {
        $this->assertSame([], $this->svc->computeFamilyBreakdown([]));
    }

    public function testFamilyBreakdownGroupsAndSorts(): void
    {
        $findings = [
            ['vendor' => '', 'product' => 'log4j-core'],
            ['vendor' => '', 'product' => 'log4j-api'],
            ['vendor' => 'spring', 'product' => 'boot'],
        ];
        $breakdown = $this->svc->computeFamilyBreakdown($findings);
        $this->assertSame('Log4j', $breakdown[0]['family']);
        $this->assertSame(2, $breakdown[0]['nb_cves']);
        $this->assertSame('Spring Framework', $breakdown[1]['family']);
        $this->assertSame(1, $breakdown[1]['nb_cves']);
    }

    public function testFamilyBreakdownUnknownFallsToAutres(): void
    {
        $breakdown = $this->svc->computeFamilyBreakdown([
            ['vendor' => 'unknown-corp', 'product' => 'unknown-lib'],
        ]);
        $this->assertSame('Autres', $breakdown[0]['family']);
    }

    /* ========================================================
     *  computeTopDepsBreakdown
     * ======================================================== */

    public function testTopDepsEmptyFindings(): void
    {
        $this->assertSame([], $this->svc->computeTopDepsBreakdown([], 10));
    }

    public function testTopDepsAggregatesMultipleCvesForSameDep(): void
    {
        $findings = [
            ['product' => 'log4j-core', 'dep_version' => '2.14.0', 'severity' => 'CRITICAL'],
            ['product' => 'log4j-core', 'dep_version' => '2.14.0', 'severity' => 'HIGH'],
        ];
        $deps = $this->svc->computeTopDepsBreakdown($findings, 10);
        $this->assertCount(1, $deps);
        $this->assertSame('log4j-core', $deps[0]['product']);
        $this->assertSame(2, $deps[0]['nb_cves']);
        $this->assertSame('CRITICAL', $deps[0]['sev_max']);
        $this->assertSame(1, $deps[0]['nb_critical']);
        $this->assertSame(1, $deps[0]['nb_high']);
    }

    public function testTopDepsSortsBySevMaxFirst(): void
    {
        $findings = [
            ['product' => 'lib-a', 'severity' => 'HIGH'],
            ['product' => 'lib-b', 'severity' => 'CRITICAL'],
        ];
        $deps = $this->svc->computeTopDepsBreakdown($findings, 10);
        $this->assertSame('lib-b', $deps[0]['product']);
    }

    public function testTopDepsRespectsLimit(): void
    {
        $findings = array_map(
            static fn(int $i): array => ['product' => "lib-{$i}", 'severity' => 'LOW'],
            range(1, 10)
        );
        $this->assertCount(3, $this->svc->computeTopDepsBreakdown($findings, 3));
    }

    public function testTopDepsFallsBackToFileName(): void
    {
        $findings = [['product' => null, 'file_name' => 'some-jar.jar', 'severity' => 'MEDIUM']];
        $deps = $this->svc->computeTopDepsBreakdown($findings, 10);
        $this->assertSame('some-jar.jar', $deps[0]['product']);
    }

    /* ========================================================
     *  computeTopPriorityCves
     * ======================================================== */

    public function testTopPriorityCvesEmptyFindings(): void
    {
        $this->assertSame([], $this->svc->computeTopPriorityCves([], 5));
    }

    public function testTopPriorityCvesDedupByCveId(): void
    {
        $findings = [
            ['cve_id' => 'CVE-2021-44228', 'severity' => 'CRITICAL', 'cvss' => 10.0, 'product' => 'log4j-core'],
            ['cve_id' => 'CVE-2021-44228', 'severity' => 'CRITICAL', 'cvss' => 10.0, 'product' => 'log4j-core'],
        ];
        $this->assertCount(1, $this->svc->computeTopPriorityCves($findings, 5));
    }

    public function testTopPriorityCvesSortsByCvssDesc(): void
    {
        $findings = [
            ['cve_id' => 'CVE-001', 'severity' => 'HIGH', 'cvss' => 7.5, 'product' => 'lib-a'],
            ['cve_id' => 'CVE-002', 'severity' => 'CRITICAL', 'cvss' => 9.8, 'product' => 'lib-b'],
        ];
        $top = $this->svc->computeTopPriorityCves($findings, 5);
        $this->assertSame('CVE-002', $top[0]['cve_id']);
    }

    public function testTopPriorityCvesRespectsLimit(): void
    {
        $findings = array_map(
            static fn(int $i): array => ['cve_id' => "CVE-00{$i}", 'severity' => 'LOW', 'cvss' => 3.0, 'product' => 'lib'],
            range(1, 10)
        );
        $this->assertCount(3, $this->svc->computeTopPriorityCves($findings, 3));
    }

    public function testTopPriorityCvesSkipsEmptyCveId(): void
    {
        $findings = [
            ['cve_id' => '', 'severity' => 'HIGH', 'cvss' => 7.5, 'product' => 'lib'],
            ['cve_id' => 'CVE-001', 'severity' => 'LOW', 'cvss' => 3.0, 'product' => 'lib'],
        ];
        $top = $this->svc->computeTopPriorityCves($findings, 5);
        $this->assertCount(1, $top);
        $this->assertSame('CVE-001', $top[0]['cve_id']);
    }

    /* ========================================================
     *  computeCveDecisions — heuristique sev + attack_vector
     * ======================================================== */

    public function testDecisionCriticalNetworkIsUpgrade(): void
    {
        $rows = $this->svc->computeCveDecisions([
            ['cve_id' => 'CVE-1', 'severity' => 'CRITICAL', 'cvss_v3_attack_vector' => 'NETWORK', 'product' => 'lib'],
        ]);
        $this->assertSame('Upgrade', $rows[0]['decision']);
    }

    public function testDecisionCriticalLocalStillUpgrade(): void
    {
        // CRITICAL is never downgraded even with LOCAL vector
        $rows = $this->svc->computeCveDecisions([
            ['cve_id' => 'CVE-1', 'severity' => 'CRITICAL', 'cvss_v3_attack_vector' => 'LOCAL', 'product' => 'lib'],
        ]);
        $this->assertSame('Upgrade', $rows[0]['decision']);
    }

    public function testDecisionHighLocalIsMitigation(): void
    {
        $rows = $this->svc->computeCveDecisions([
            ['cve_id' => 'CVE-1', 'severity' => 'HIGH', 'cvss_v3_attack_vector' => 'LOCAL', 'product' => 'lib'],
        ]);
        $this->assertSame('Mitigation', $rows[0]['decision']);
    }

    public function testDecisionMediumPhysicalIsMitigation(): void
    {
        $rows = $this->svc->computeCveDecisions([
            ['cve_id' => 'CVE-1', 'severity' => 'MEDIUM', 'cvss_v3_attack_vector' => 'PHYSICAL', 'product' => 'lib'],
        ]);
        $this->assertSame('Mitigation', $rows[0]['decision']);
    }

    public function testDecisionLowNetworkIsMitigation(): void
    {
        $rows = $this->svc->computeCveDecisions([
            ['cve_id' => 'CVE-1', 'severity' => 'LOW', 'cvss_v3_attack_vector' => 'NETWORK', 'product' => 'lib'],
        ]);
        $this->assertSame('Mitigation', $rows[0]['decision']);
    }

    public function testDecisionLowLocalIsSurveillance(): void
    {
        $rows = $this->svc->computeCveDecisions([
            ['cve_id' => 'CVE-1', 'severity' => 'LOW', 'cvss_v3_attack_vector' => 'LOCAL', 'product' => 'lib'],
        ]);
        $this->assertSame('Surveillance', $rows[0]['decision']);
    }

    public function testDecisionInfoIsSurveillance(): void
    {
        $rows = $this->svc->computeCveDecisions([
            ['cve_id' => 'CVE-1', 'severity' => 'INFO', 'cvss_v3_attack_vector' => '', 'product' => 'lib'],
        ]);
        $this->assertSame('Surveillance', $rows[0]['decision']);
    }

    public function testDecisionSortsBySeverityRank(): void
    {
        $rows = $this->svc->computeCveDecisions([
            ['cve_id' => 'CVE-LOW',  'severity' => 'LOW',      'cvss_v3_attack_vector' => '', 'product' => 'lib'],
            ['cve_id' => 'CVE-CRIT', 'severity' => 'CRITICAL', 'cvss_v3_attack_vector' => '', 'product' => 'lib'],
        ]);
        $this->assertSame('CVE-CRIT', $rows[0]['cve_id']);
    }

    /* ========================================================
     *  computeRemediationPlan
     * ======================================================== */

    public function testRemediationPlanEmptyFindings(): void
    {
        $plan = $this->svc->computeRemediationPlan([]);
        $this->assertSame([], $plan['par_dependance']);
        $this->assertSame(0.0, $plan['total_jh']);
    }

    public function testRemediationPlanSingleDep(): void
    {
        $findings = [
            ['product' => 'log4j-core', 'vendor' => '', 'dep_version' => '2.14.0', 'severity' => 'CRITICAL'],
        ];
        $plan = $this->svc->computeRemediationPlan($findings);
        $this->assertCount(1, $plan['par_dependance']);
        $this->assertSame('log4j-core', $plan['par_dependance'][0]['product']);
        $this->assertGreaterThan(0.0, $plan['total_jh']);
    }

    public function testRemediationPlanTotalJhIsSumOfParts(): void
    {
        $findings = [
            ['product' => 'lib-a', 'vendor' => '', 'dep_version' => '1.0', 'severity' => 'HIGH'],
            ['product' => 'lib-b', 'vendor' => '', 'dep_version' => '2.0', 'severity' => 'LOW'],
        ];
        $plan = $this->svc->computeRemediationPlan($findings);
        $expectedTotal = array_sum(array_column($plan['par_dependance'], 'jh'));
        $this->assertSame(round($expectedTotal * 2) / 2, $plan['total_jh']);
    }

    /* ========================================================
     *  computeTopActions
     * ======================================================== */

    public function testTopActionsEmptyFindings(): void
    {
        $this->assertSame([], $this->svc->computeTopActions([], 5));
    }

    public function testTopActionsOnlyIncludesCriticalOrHigh(): void
    {
        $findings = [
            ['product' => 'lib-critical', 'vendor' => '', 'dep_version' => '1.0', 'severity' => 'CRITICAL'],
            ['product' => 'lib-low',      'vendor' => '', 'dep_version' => '1.0', 'severity' => 'LOW'],
        ];
        $actions = $this->svc->computeTopActions($findings, 5);
        $this->assertCount(1, $actions);
        $this->assertSame('lib-critical', $actions[0]['product']);
    }

    public function testTopActionsRespectsLimit(): void
    {
        $findings = array_map(
            static fn(int $i): array => ['product' => "lib-{$i}", 'vendor' => '', 'dep_version' => '1.0', 'severity' => 'CRITICAL'],
            range(1, 10)
        );
        $this->assertCount(2, $this->svc->computeTopActions($findings, 2));
    }

    /* ========================================================
     *  isUnchanged
     * ======================================================== */

    public function testIsUnchangedWhenBothEmpty(): void
    {
        $this->assertTrue($this->svc->isUnchanged(['new' => [], 'fixed' => [], 'unchanged_count' => 5]));
    }

    public function testIsUnchangedFalseWhenNewPresent(): void
    {
        $this->assertFalse($this->svc->isUnchanged(['new' => [['cve_id' => 'CVE-1']], 'fixed' => []]));
    }

    public function testIsUnchangedFalseWhenFixedPresent(): void
    {
        $this->assertFalse($this->svc->isUnchanged(['new' => [], 'fixed' => [['cve_id' => 'CVE-1']]]));
    }

    /* ========================================================
     *  computeScanDiff
     * ======================================================== */

    public function testScanDiffEmptyBoth(): void
    {
        $diff = $this->svc->computeScanDiff([], []);
        $this->assertSame([], $diff['new']);
        $this->assertSame([], $diff['fixed']);
        $this->assertSame(0, $diff['unchanged_count']);
    }

    public function testScanDiffAllNewWhenPreviousEmpty(): void
    {
        $current = [
            ['product' => 'lib', 'dep_version' => '1.0', 'cve_id' => 'CVE-001', 'severity' => 'HIGH'],
        ];
        $diff = $this->svc->computeScanDiff([], $current);
        $this->assertCount(1, $diff['new']);
        $this->assertSame('CVE-001', $diff['new'][0]['cve_id']);
        $this->assertSame(0, $diff['unchanged_count']);
    }

    public function testScanDiffAllFixedWhenCurrentEmpty(): void
    {
        $previous = [
            ['product' => 'lib', 'dep_version' => '1.0', 'cve_id' => 'CVE-001', 'severity' => 'HIGH'],
        ];
        $diff = $this->svc->computeScanDiff($previous, []);
        $this->assertCount(1, $diff['fixed']);
        $this->assertSame('CVE-001', $diff['fixed'][0]['cve_id']);
    }

    public function testScanDiffUnchangedWhenIdentical(): void
    {
        $findings = [
            ['product' => 'lib', 'dep_version' => '1.0', 'cve_id' => 'CVE-001', 'severity' => 'HIGH'],
        ];
        $diff = $this->svc->computeScanDiff($findings, $findings);
        $this->assertSame([], $diff['new']);
        $this->assertSame([], $diff['fixed']);
        $this->assertSame(1, $diff['unchanged_count']);
    }

    public function testScanDiffInvariantNewPlusUnchangedEqualsCurrentCount(): void
    {
        $previous = [
            ['product' => 'lib', 'dep_version' => '1.0', 'cve_id' => 'CVE-001', 'severity' => 'HIGH'],
        ];
        $current = [
            ['product' => 'lib',   'dep_version' => '1.0', 'cve_id' => 'CVE-001', 'severity' => 'HIGH'],
            ['product' => 'lib-b', 'dep_version' => '2.0', 'cve_id' => 'CVE-002', 'severity' => 'CRITICAL'],
        ];
        $diff = $this->svc->computeScanDiff($previous, $current);
        $this->assertSame(count($current), count($diff['new']) + $diff['unchanged_count']);
    }

    public function testScanDiffSameProductDifferentVersionsAreDistinct(): void
    {
        // Same CVE on 2 different versions → both should be present in diff
        $previous = [
            ['product' => 'lib', 'dep_version' => '1.0', 'cve_id' => 'CVE-001', 'severity' => 'HIGH'],
        ];
        $current = [
            ['product' => 'lib', 'dep_version' => '1.0', 'cve_id' => 'CVE-001', 'severity' => 'HIGH'],
            ['product' => 'lib', 'dep_version' => '2.0', 'cve_id' => 'CVE-001', 'severity' => 'HIGH'],
        ];
        $diff = $this->svc->computeScanDiff($previous, $current);
        // lib/2.0/CVE-001 is new, lib/1.0/CVE-001 is unchanged
        $this->assertCount(1, $diff['new']);
        $this->assertSame(1, $diff['unchanged_count']);
    }
}
