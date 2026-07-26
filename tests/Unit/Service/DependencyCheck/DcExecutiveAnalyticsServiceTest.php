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

namespace App\Tests\Unit\Service\DependencyCheck;

use App\Service\DependencyCheck\DcExecutiveAnalyticsService;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-05-09 : tests Unit pour le service
 * d'analyse exécutif (helpers de la page /dependency-check/.../executive).
 *
 * Toutes les méthodes du service sont des fonctions pures sur tableaux,
 * donc 0 dépendance externe / 0 mock nécessaire.
 */
class DcExecutiveAnalyticsServiceTest extends TestCase
{
    private DcExecutiveAnalyticsService $svc;

    protected function setUp(): void
    {
        $this->svc = new DcExecutiveAnalyticsService();
    }

    /**
     * Builder de finding pour réduire le bruit dans les tests.
     *
     * @param array<string, mixed> $overrides
     *
     * @return array<string, mixed>
     */
    private function f(array $overrides = []): array
    {
        /* MODIF 2026-05-12 : la clé fixed_version
         * est retirée du builder (donnée non fiable retirée du système). Le
         * vecteur d'attaque par défaut reste NETWORK pour que les décisions
         * de base par severity soient préservées. */
        return array_merge([
            'id' => 1,
            'severity' => 'HIGH',
            'cvss' => 7.5,
            'file_name' => 'lib.jar',
            'pkg_coordinates' => null,
            'vendor' => 'acme',
            'product' => 'lib',
            'dep_version' => '1.0.0',
            'cve_id' => 'CVE-2026-0001',
            'cwes' => ['CWE-79'],
            'description' => 'desc',
            'cvss_v3_attack_vector' => 'NETWORK',
        ], $overrides);
    }

    /* ============ computeCriticityCounts ============ */

    public function testCriticityCountsAreInitialisedToZero(): void
    {
        $counts = $this->svc->computeCriticityCounts([]);
        $this->assertSame(['CRITICAL' => 0, 'HIGH' => 0, 'MEDIUM' => 0, 'LOW' => 0, 'INFO' => 0], $counts);
    }

    public function testCriticityCountsAggregate(): void
    {
        $findings = [
            $this->f(['severity' => 'CRITICAL']),
            $this->f(['severity' => 'CRITICAL']),
            $this->f(['severity' => 'HIGH']),
            $this->f(['severity' => 'MEDIUM']),
            $this->f(['severity' => 'LOW']),
            $this->f(['severity' => 'LOW']),
            $this->f(['severity' => 'LOW']),
            $this->f(['severity' => 'INFO']),
        ];
        $counts = $this->svc->computeCriticityCounts($findings);
        $this->assertSame(['CRITICAL' => 2, 'HIGH' => 1, 'MEDIUM' => 1, 'LOW' => 3, 'INFO' => 1], $counts);
    }

    public function testCriticityCountsIgnoresUnknownSeverity(): void
    {
        $findings = [
            $this->f(['severity' => 'EXOTIC']),
            $this->f(['severity' => 'CRITICAL']),
        ];
        $counts = $this->svc->computeCriticityCounts($findings);
        $this->assertSame(1, $counts['CRITICAL']);
        $this->assertSame(0, $counts['HIGH']);
    }

    /* ============ computeTopCwes ============ */

    public function testTopCwesSortsByOccurrenceDesc(): void
    {
        $findings = [
            $this->f(['cwes' => ['CWE-79', 'CWE-502']]),
            $this->f(['cwes' => ['CWE-79']]),
            $this->f(['cwes' => ['CWE-79']]),
            $this->f(['cwes' => ['CWE-502']]),
            $this->f(['cwes' => ['CWE-22']]),
        ];
        $top = $this->svc->computeTopCwes($findings, 5);
        $this->assertSame('CWE-79', $top[0]['cwe']);
        $this->assertSame(3, $top[0]['nb']);
        $this->assertSame('CWE-502', $top[1]['cwe']);
        $this->assertSame(2, $top[1]['nb']);
    }

    public function testTopCwesRespectsLimit(): void
    {
        $findings = [
            $this->f(['cwes' => ['CWE-1']]), $this->f(['cwes' => ['CWE-2']]),
            $this->f(['cwes' => ['CWE-3']]), $this->f(['cwes' => ['CWE-4']]),
        ];
        $top = $this->svc->computeTopCwes($findings, 2);
        $this->assertCount(2, $top);
    }

    public function testTopCwesIgnoresEmptyOrNonStringValues(): void
    {
        $findings = [
            $this->f(['cwes' => ['', 0, null, 'CWE-79']]),
        ];
        $top = $this->svc->computeTopCwes($findings, 5);
        $this->assertCount(1, $top);
        $this->assertSame('CWE-79', $top[0]['cwe']);
    }

    /* ============ computeFamilyBreakdown ============ */

    public function testFamilyBreakdownDetectsSpringJacksonCommonsCxf(): void
    {
        $findings = [
            $this->f(['product' => 'spring-core', 'vendor' => 'pivotal']),
            $this->f(['product' => 'spring-web', 'vendor' => 'pivotal']),
            $this->f(['product' => 'jackson-databind', 'vendor' => 'fasterxml']),
            $this->f(['product' => 'commons-text', 'vendor' => 'apache']),
            $this->f(['product' => 'cxf-core', 'vendor' => 'apache']),
            $this->f(['product' => 'foo', 'vendor' => 'random']),
        ];
        $breakdown = $this->svc->computeFamilyBreakdown($findings);
        $byFamily = array_column($breakdown, 'nb_cves', 'family');
        $this->assertSame(2, $byFamily['Spring Framework']);
        $this->assertSame(1, $byFamily['Jackson']);
        $this->assertSame(1, $byFamily['Apache Commons']);
        $this->assertSame(1, $byFamily['Apache CXF']);
        $this->assertSame(1, $byFamily['Autres']);
    }

    public function testFamilyBreakdownSortsDescByNbCves(): void
    {
        $findings = [
            $this->f(['product' => 'foo']),
            $this->f(['product' => 'spring-core']),
            $this->f(['product' => 'spring-web']),
            $this->f(['product' => 'spring-boot']),
        ];
        $breakdown = $this->svc->computeFamilyBreakdown($findings);
        $this->assertSame('Spring Framework', $breakdown[0]['family']);
        $this->assertSame(3, $breakdown[0]['nb_cves']);
    }

    /* ============ computeTopDepsBreakdown ============ */

    public function testTopDepsBreakdownAggregatesAndComputesSevMax(): void
    {
        $findings = [
            $this->f(['product' => 'log4j-core', 'severity' => 'CRITICAL']),
            $this->f(['product' => 'log4j-core', 'severity' => 'HIGH']),
            $this->f(['product' => 'log4j-core', 'severity' => 'MEDIUM']),
            $this->f(['product' => 'jackson-databind', 'severity' => 'HIGH']),
            $this->f(['product' => 'jackson-databind', 'severity' => 'HIGH']),
        ];
        $breakdown = $this->svc->computeTopDepsBreakdown($findings, 10);

        // log4j vient en premier (CRITICAL > HIGH)
        $this->assertSame('log4j-core', $breakdown[0]['product']);
        $this->assertSame('CRITICAL', $breakdown[0]['sev_max']);
        $this->assertSame(1, $breakdown[0]['nb_critical']);
        $this->assertSame(1, $breakdown[0]['nb_high']);
        $this->assertSame(1, $breakdown[0]['nb_medium']);
        $this->assertSame(3, $breakdown[0]['nb_cves']);

        $this->assertSame('jackson-databind', $breakdown[1]['product']);
        $this->assertSame('HIGH', $breakdown[1]['sev_max']);
    }

    public function testTopDepsBreakdownLimitWorks(): void
    {
        $findings = [
            $this->f(['product' => 'a', 'severity' => 'HIGH']),
            $this->f(['product' => 'b', 'severity' => 'HIGH']),
            $this->f(['product' => 'c', 'severity' => 'HIGH']),
        ];
        $this->assertCount(2, $this->svc->computeTopDepsBreakdown($findings, 2));
    }

    /* ============ computeTopPriorityCves ============ */

    public function testTopPriorityCvesSortsByCvssDescAndDeduplicates(): void
    {
        $findings = [
            $this->f(['cve_id' => 'CVE-A', 'cvss' => 5.0, 'severity' => 'MEDIUM']),
            $this->f(['cve_id' => 'CVE-B', 'cvss' => 9.8, 'severity' => 'CRITICAL']),
            $this->f(['cve_id' => 'CVE-B', 'cvss' => 9.8, 'severity' => 'CRITICAL']), // doublon dédupliqué
            $this->f(['cve_id' => 'CVE-C', 'cvss' => null, 'severity' => 'HIGH']),
        ];
        $top = $this->svc->computeTopPriorityCves($findings, 10);
        $this->assertCount(3, $top);
        $this->assertSame('CVE-B', $top[0]['cve_id']);
        $this->assertSame('CVE-A', $top[1]['cve_id']);
        $this->assertSame('CVE-C', $top[2]['cve_id']);
        $this->assertNull($top[2]['cvss']);
    }

    /* ============ computeCveDecisions ============ */

    public function testCveDecisionsAreInferredFromSeverity(): void
    {
        $findings = [
            $this->f(['cve_id' => 'CVE-1', 'severity' => 'CRITICAL']),
            $this->f(['cve_id' => 'CVE-2', 'severity' => 'HIGH']),
            $this->f(['cve_id' => 'CVE-3', 'severity' => 'MEDIUM']),
            $this->f(['cve_id' => 'CVE-4', 'severity' => 'LOW']),
            $this->f(['cve_id' => 'CVE-5', 'severity' => 'INFO']),
        ];
        $decisions = $this->svc->computeCveDecisions($findings);
        $byCve = array_column($decisions, 'decision', 'cve_id');
        $this->assertSame('Upgrade', $byCve['CVE-1']);
        $this->assertSame('Upgrade', $byCve['CVE-2']);
        $this->assertSame('Upgrade', $byCve['CVE-3']);
        $this->assertSame('Mitigation', $byCve['CVE-4']);
        $this->assertSame('Surveillance', $byCve['CVE-5']);
    }

    /* MODIF 2026-05-12 : vérifie que les CVE d'un meme product de meme sévérité
     * se suivent dans le tri (product comme tie-breaker entre severity et cve_id). */
    public function testCveDecisionsGroupCvesOfSameProductTogether(): void
    {
        $findings = [
            $this->f(['product' => 'cxf-core',         'cve_id' => 'CVE-A', 'severity' => 'CRITICAL']),
            $this->f(['product' => 'jackson-databind', 'cve_id' => 'CVE-B', 'severity' => 'CRITICAL']),
            $this->f(['product' => 'cxf-core',         'cve_id' => 'CVE-C', 'severity' => 'CRITICAL']),
            $this->f(['product' => 'jackson-databind', 'cve_id' => 'CVE-D', 'severity' => 'CRITICAL']),
            $this->f(['product' => 'cxf-core',         'cve_id' => 'CVE-E', 'severity' => 'CRITICAL']),
        ];
        $decisions = $this->svc->computeCveDecisions($findings);
        $products = array_column($decisions, 'product');
        // cxf-core (3 entrees) doit etre contigu, puis jackson-databind (2 entrees).
        $this->assertSame(['cxf-core', 'cxf-core', 'cxf-core', 'jackson-databind', 'jackson-databind'], $products);
    }

    /* MODIF 2026-05-12 : heuristique simplifiée a 2 signaux fiables (severity + attack_vector).
     * Le signal "fixed_version" a été retiré car la source disponible
     * (versionEndExcluding du JSON DC) n'était pas une fix recommandée fiable. */
    public function testDecisionMinoredByLocalAttackVector(): void
    {
        // HIGH avec vecteur LOCAL -> minoré : Upgrade -> Mitigation
        // MEDIUM avec vecteur LOCAL -> minoré : Upgrade -> Mitigation
        // LOW avec vecteur LOCAL -> minoré : Mitigation -> Surveillance
        $findings = [
            $this->f(['cve_id' => 'CVE-H', 'severity' => 'HIGH',   'cvss_v3_attack_vector' => 'LOCAL']),
            $this->f(['cve_id' => 'CVE-M', 'severity' => 'MEDIUM', 'cvss_v3_attack_vector' => 'LOCAL']),
            $this->f(['cve_id' => 'CVE-L', 'severity' => 'LOW',    'cvss_v3_attack_vector' => 'LOCAL']),
        ];
        $byCve = array_column($this->svc->computeCveDecisions($findings), 'decision', 'cve_id');
        $this->assertSame('Mitigation',   $byCve['CVE-H']);
        $this->assertSame('Mitigation',   $byCve['CVE-M']);
        $this->assertSame('Surveillance', $byCve['CVE-L']);
    }

    public function testDecisionMinoredByPhysicalAttackVector(): void
    {
        // PHYSICAL = même comportement que LOCAL (exploitation locale)
        $findings = [
            $this->f(['cve_id' => 'CVE-H', 'severity' => 'HIGH', 'cvss_v3_attack_vector' => 'PHYSICAL']),
        ];
        $byCve = array_column($this->svc->computeCveDecisions($findings), 'decision', 'cve_id');
        $this->assertSame('Mitigation', $byCve['CVE-H']);
    }

    public function testCriticalKeepsUpgradeEvenWithLocalVector(): void
    {
        // CRITICAL n'est jamais minoré par le vecteur (escalade post-compromise toujours grave)
        $findings = [
            $this->f(['cve_id' => 'CVE-X', 'severity' => 'CRITICAL', 'cvss_v3_attack_vector' => 'LOCAL']),
            $this->f(['cve_id' => 'CVE-Y', 'severity' => 'CRITICAL', 'cvss_v3_attack_vector' => 'PHYSICAL']),
        ];
        $byCve = array_column($this->svc->computeCveDecisions($findings), 'decision', 'cve_id');
        $this->assertSame('Upgrade', $byCve['CVE-X']);
        $this->assertSame('Upgrade', $byCve['CVE-Y']);
    }

    public function testNetworkVectorDoesNotMinor(): void
    {
        // NETWORK et ADJACENT = pas de minoration (vecteurs exploitables a distance)
        $findings = [
            $this->f(['cve_id' => 'CVE-N', 'severity' => 'HIGH', 'cvss_v3_attack_vector' => 'NETWORK']),
            $this->f(['cve_id' => 'CVE-A', 'severity' => 'HIGH', 'cvss_v3_attack_vector' => 'ADJACENT']),
        ];
        $byCve = array_column($this->svc->computeCveDecisions($findings), 'decision', 'cve_id');
        $this->assertSame('Upgrade', $byCve['CVE-N']);
        $this->assertSame('Upgrade', $byCve['CVE-A']);
    }

    public function testJustificationContainsSeverityAndVector(): void
    {
        $findings = [$this->f([
            'severity' => 'HIGH', 'cvss_v3_attack_vector' => 'NETWORK',
        ])];
        $just = $this->svc->computeCveDecisions($findings)[0]['justification'];
        $this->assertStringContainsString('Sévérité HIGH', $just);
        $this->assertStringContainsString('vecteur NETWORK', $just);
    }

    public function testCveDecisionsExposeAttackVector(): void
    {
        // Le shape de retour expose le vecteur pour le template.
        $findings = [$this->f([
            'severity' => 'HIGH', 'cvss_v3_attack_vector' => 'NETWORK',
        ])];
        $row = $this->svc->computeCveDecisions($findings)[0];
        $this->assertSame('NETWORK', $row['attack_vector']);
        $this->assertArrayNotHasKey('fixed_version', $row);
    }

    /* ============ computeRemediationPlan ============ */

    /* MODIF 2026-05-12 : formule simplifiée par dep
     * (1.5 base + 1 si CRITICAL). Le bonus no-fix a été retiré car il
     * s'appuyait sur fixed_version, donnée non fiable. */

    public function testRemediationPlanCostsFlatPerDepUpgrade(): void
    {
        // 1 dep avec 3 HIGH + 4 MEDIUM (PAS de CRITICAL)
        // -> JH = 1.5 (base upgrade), peu importe le nombre de CVE corrigées par cet upgrade
        $findings = array_merge(
            array_fill(0, 3, $this->f(['product' => 'jackson-databind', 'severity' => 'HIGH'])),
            array_fill(0, 4, $this->f(['product' => 'jackson-databind', 'severity' => 'MEDIUM'])),
        );
        $plan = $this->svc->computeRemediationPlan($findings);
        $this->assertCount(1, $plan['par_dependance']);
        $this->assertSame(1.5, $plan['par_dependance'][0]['jh']);
        $this->assertSame(1.5, $plan['total_jh']);
    }

    public function testRemediationPlanAddsBonusWhenCriticalPresent(): void
    {
        /* MODIF 2026-05-12 : formule paramétrable.
         * spring-core CRITICAL : famille = 'Spring Framework' (effort 2.0)
         * -> JH = (2.0 + 0.2) × 1.2 + 0.5 (bonus CRITICAL) = 3.14 -> 3.0 */
        $findings = array_merge(
            [$this->f(['product' => 'spring-core', 'severity' => 'CRITICAL'])],
            array_fill(0, 5, $this->f(['product' => 'spring-core', 'severity' => 'HIGH'])),
        );
        $plan = $this->svc->computeRemediationPlan($findings);
        $this->assertSame(3.0, $plan['par_dependance'][0]['jh']);
    }

    public function testRemediationPlanLowOnlyDoesNotTriggerBonus(): void
    {
        /* lib-z LOW : famille = 'Autres' (effort 1.0, fallback car nom inconnu)
         * -> JH = (1.0 + 0.2) × 1.2 + 0.0 = 1.44 -> 1.5 */
        $findings = array_fill(0, 5, $this->f(['product' => 'lib-z', 'severity' => 'LOW']));
        $plan = $this->svc->computeRemediationPlan($findings);
        $this->assertSame(1.5, $plan['par_dependance'][0]['jh']);
    }

    public function testRemediationPlanSumsAcrossDeps(): void
    {
        /* MODIF 2026-05-12 : 3 deps "Autres" (effort 1.0).
         *   a HIGH      -> (1.0 + 0.2) × 1.2 + 0.3 = 1.74 -> 1.5
         *   b CRITICAL  -> (1.0 + 0.2) × 1.2 + 0.5 = 1.94 -> 2.0
         *   c HIGH      -> 1.5
         *   Total = 5.0 */
        $findings = [
            $this->f(['product' => 'a', 'severity' => 'HIGH']),
            $this->f(['product' => 'b', 'severity' => 'CRITICAL']),
            $this->f(['product' => 'c', 'severity' => 'HIGH']),
        ];
        $plan = $this->svc->computeRemediationPlan($findings);
        $this->assertCount(3, $plan['par_dependance']);
        $this->assertSame(5.0, $plan['total_jh']);
    }

    public function testRemediationPlanDoesNotExposeBlockingWithoutFixFlag(): void
    {
        // Le flag has_blocking_without_fix a été retiré du shape de retour.
        $findings = [$this->f(['product' => 'lib', 'severity' => 'HIGH'])];
        $plan = $this->svc->computeRemediationPlan($findings);
        $this->assertArrayNotHasKey('has_blocking_without_fix', $plan['par_dependance'][0]);
    }

    /* ============ computeDepJh (formule paramétrable) ============
     * MODIF 2026-05-12 : nouvelle méthode publique qui
     * centralise le calcul JH pour service+controller. */

    public function testComputeDepJhAppliesFamilyAndSeverityCorrectly(): void
    {
        // log4j-core CRITICAL : famille Log4j (0.5) -> (0.5+0.2)×1.2 + 0.5 = 1.34 -> 1.5
        $this->assertSame(1.5, $this->svc->computeDepJh('Log4j', 'CRITICAL'));
        // cxf-core CRITICAL : Apache CXF (1.5) -> (1.5+0.2)×1.2 + 0.5 = 2.54 -> 2.5
        $this->assertSame(2.5, $this->svc->computeDepJh('Apache CXF', 'CRITICAL'));
        // spring-core CRITICAL : Spring Framework (2.0) -> 3.14 -> 3.0
        $this->assertSame(3.0, $this->svc->computeDepJh('Spring Framework', 'CRITICAL'));
        // Jackson MEDIUM : (1.0+0.2)×1.2 + 0.1 = 1.54 -> 1.5
        $this->assertSame(1.5, $this->svc->computeDepJh('Jackson', 'MEDIUM'));
        // Apache Commons LOW : (0.5+0.2)×1.2 + 0.0 = 0.84 -> 1.0
        $this->assertSame(1.0, $this->svc->computeDepJh('Apache Commons', 'LOW'));
    }

    public function testComputeDepJhFallsBackOnAutresFamily(): void
    {
        // Famille inconnue -> fallback "Autres" (effort 1.0)
        // -> (1.0+0.2)×1.2 + 0.5 = 1.94 -> 2.0
        $this->assertSame(2.0, $this->svc->computeDepJh('FamilleInconnue', 'CRITICAL'));
    }

    public function testComputeDepJhSeverityIsCaseInsensitive(): void
    {
        // strtoupper appliqué sur la sévérité -> case insensitive
        $this->assertSame(
            $this->svc->computeDepJh('Log4j', 'CRITICAL'),
            $this->svc->computeDepJh('Log4j', 'critical')
        );
    }

    public function testComputeDepJhUnknownSeverityGetsNoBonus(): void
    {
        // Sévérité non listée dans severity_bonus -> bonus 0
        // Log4j sans bonus : (0.5+0.2)×1.2 = 0.84 -> 1.0
        $this->assertSame(1.0, $this->svc->computeDepJh('Log4j', 'UNKNOWN'));
    }

    /* ============ getFamily ============ */

    public function testGetFamilyDelegatesToGuessFamily(): void
    {
        // Smoke test sur la heuristique exposée publiquement
        $this->assertSame('Log4j', $this->svc->getFamily('apache', 'log4j-core'));
        $this->assertSame('Apache CXF', $this->svc->getFamily('apache', 'cxf-rt-core'));
        $this->assertSame('Spring Framework', $this->svc->getFamily('springframework', 'spring-web'));
        $this->assertSame('Jackson', $this->svc->getFamily('com.fasterxml', 'jackson-databind'));
        $this->assertSame('Apache Commons', $this->svc->getFamily('apache', 'commons-text'));
        $this->assertSame('Autres', $this->svc->getFamily('xxx', 'yyy'));
    }

    /* ============ computeTopActions ============ */

    public function testTopActionsKeepsOnlyDepsWithCriticalOrHigh(): void
    {
        $findings = [
            $this->f(['product' => 'a', 'severity' => 'CRITICAL']),
            $this->f(['product' => 'b', 'severity' => 'HIGH']),
            $this->f(['product' => 'c', 'severity' => 'MEDIUM']), // ignorée
            $this->f(['product' => 'd', 'severity' => 'LOW']),    // ignorée
        ];
        $actions = $this->svc->computeTopActions($findings, 10);
        $names = array_column($actions, 'product');
        $this->assertContains('a', $names);
        $this->assertContains('b', $names);
        $this->assertNotContains('c', $names);
        $this->assertNotContains('d', $names);
    }

    /* MODIF 2026-05-12 : formule paramétrable.
     * Familles "Autres" (effort 1.0) sauf si explicitement matché. */
    public function testTopActionsSortsByJhDescAndRespectsLimit(): void
    {
        $findings = [
            // a (famille Autres) : 2 CRITICAL -> JH = (1.0+0.2)×1.2 + 0.5 = 1.94 -> 2.0
            $this->f(['product' => 'a', 'severity' => 'CRITICAL']),
            $this->f(['product' => 'a', 'severity' => 'CRITICAL']),
            // b (famille Autres) : 1 HIGH -> JH = (1.0+0.2)×1.2 + 0.3 = 1.74 -> 1.5
            $this->f(['product' => 'b', 'severity' => 'HIGH']),
            // c (famille Autres) : 1 CRITICAL -> JH = 2.0 (égalité avec a)
            $this->f(['product' => 'c', 'severity' => 'CRITICAL']),
        ];
        $actions = $this->svc->computeTopActions($findings, 2);
        $this->assertCount(2, $actions);
        // Les 2 dépendances CRITICAL (a et c) sortent en tête à 2.0 ; b à 1.5 exclue par limit=2.
        $this->assertSame(2.0, $actions[0]['jh']);
        $this->assertSame(2.0, $actions[1]['jh']);
        $products = array_column($actions, 'product');
        $this->assertNotContains('b', $products);
    }

    /* ============ computeScanDiff ============ */

    public function testScanDiffDetectsNewFixedAndUnchanged(): void
    {
        $previous = [
            $this->f(['product' => 'a', 'cve_id' => 'CVE-1', 'severity' => 'HIGH']),
            $this->f(['product' => 'a', 'cve_id' => 'CVE-2', 'severity' => 'MEDIUM']),
            $this->f(['product' => 'b', 'cve_id' => 'CVE-3', 'severity' => 'LOW']),
        ];
        $current = [
            $this->f(['product' => 'a', 'cve_id' => 'CVE-1', 'severity' => 'HIGH']), // unchanged
            $this->f(['product' => 'a', 'cve_id' => 'CVE-9', 'severity' => 'CRITICAL']), // new
            // CVE-2 et CVE-3 absents → fixed
        ];
        $diff = $this->svc->computeScanDiff($previous, $current);
        $this->assertSame(1, $diff['unchanged_count']);
        $this->assertCount(1, $diff['new']);
        $this->assertSame('CVE-9', $diff['new'][0]['cve_id']);
        $this->assertCount(2, $diff['fixed']);
        $fixedIds = array_column($diff['fixed'], 'cve_id');
        $this->assertContains('CVE-2', $fixedIds);
        $this->assertContains('CVE-3', $fixedIds);
    }

    public function testScanDiffWithEmptyPreviousMarksAllAsNew(): void
    {
        $current = [
            $this->f(['product' => 'a', 'cve_id' => 'CVE-1']),
            $this->f(['product' => 'a', 'cve_id' => 'CVE-2']),
        ];
        $diff = $this->svc->computeScanDiff([], $current);
        $this->assertCount(2, $diff['new']);
        $this->assertCount(0, $diff['fixed']);
        $this->assertSame(0, $diff['unchanged_count']);
    }

    public function testScanDiffWithEmptyCurrentMarksAllAsFixed(): void
    {
        $previous = [
            $this->f(['product' => 'a', 'cve_id' => 'CVE-1']),
            $this->f(['product' => 'a', 'cve_id' => 'CVE-2']),
        ];
        $diff = $this->svc->computeScanDiff($previous, []);
        $this->assertCount(0, $diff['new']);
        $this->assertCount(2, $diff['fixed']);
        $this->assertSame(0, $diff['unchanged_count']);
    }

    /* MODIF 2026-05-12 : la clé inclut
     * dep_version, sinon une CVE qui touche 2 versions du meme product dans
     * le même scan était perdue (écrasement silencieux dans `$prevByKey[$key]`).
      */
    public function testScanDiffDistinguishesSameProductDifferentVersions(): void
    {
        // log4j-core 2.14.0 ET log4j-core 2.13.0 dans le meme scan, meme CVE
        $previous = [
            $this->f(['product' => 'log4j-core', 'dep_version' => '2.14.0', 'cve_id' => 'CVE-2021-44228']),
            $this->f(['product' => 'log4j-core', 'dep_version' => '2.13.0', 'cve_id' => 'CVE-2021-44228']),
        ];
        $current = [
            $this->f(['product' => 'log4j-core', 'dep_version' => '2.14.0', 'cve_id' => 'CVE-2021-44228']),
            $this->f(['product' => 'log4j-core', 'dep_version' => '2.13.0', 'cve_id' => 'CVE-2021-44228']),
        ];
        $diff = $this->svc->computeScanDiff($previous, $current);
        // Les 2 paires (product, version, cve_id) sont identiques dans les 2 scans
        // -> 2 unchanged, 0 new, 0 fixed
        $this->assertSame(2, $diff['unchanged_count']);
        $this->assertCount(0, $diff['new']);
        $this->assertCount(0, $diff['fixed']);
    }

    public function testScanDiffInvariantNewPlusUnchangedEqualsCurrentCount(): void
    {
        // Verifie l'invariant algebrique : new + unchanged_count == count(findings courants),
        // a condition que toutes les paires (product, dep_version, cve_id) du courant
        // soient distinctes. Cassait avant le fix [dc-diff-key-precision] si plusieurs
        // findings avaient meme product mais versions differentes.
        $previous = [
            $this->f(['product' => 'log4j-core', 'dep_version' => '2.14.0', 'cve_id' => 'CVE-X']),
        ];
        $current = [
            $this->f(['product' => 'log4j-core', 'dep_version' => '2.14.0', 'cve_id' => 'CVE-X']), // unchanged
            $this->f(['product' => 'log4j-core', 'dep_version' => '2.13.0', 'cve_id' => 'CVE-X']), // new (autre version)
            $this->f(['product' => 'jackson',    'dep_version' => '2.9.0',  'cve_id' => 'CVE-Y']), // new
        ];
        $diff = $this->svc->computeScanDiff($previous, $current);
        $this->assertCount(2, $diff['new']);
        $this->assertSame(1, $diff['unchanged_count']);
        $this->assertSame(count($current), count($diff['new']) + $diff['unchanged_count']);
    }

    /* ============ isUnchanged ============
     * MODIF 2026-05-12 : helper utilisé par le template pour
     * distinguer "diff calculée et vide = pas de changement (encart vert)" de
     * "pas de scan antérieur du tout = section masquée". */

    public function testIsUnchangedReturnsTrueWhenBothEmpty(): void
    {
        $diff = ['new' => [], 'fixed' => [], 'unchanged_count' => 5];
        $this->assertTrue($this->svc->isUnchanged($diff));
    }

    public function testIsUnchangedReturnsFalseWhenNewNotEmpty(): void
    {
        $diff = ['new' => [['cve_id' => 'CVE-X']], 'fixed' => [], 'unchanged_count' => 0];
        $this->assertFalse($this->svc->isUnchanged($diff));
    }

    public function testIsUnchangedReturnsFalseWhenFixedNotEmpty(): void
    {
        $diff = ['new' => [], 'fixed' => [['cve_id' => 'CVE-X']], 'unchanged_count' => 0];
        $this->assertFalse($this->svc->isUnchanged($diff));
    }

    public function testIsUnchangedReturnsTrueWhenKeysMissing(): void
    {
        // Robustesse : si la clé `new` ou `fixed` est absente du tableau,
        // on considère true (diff vide par défaut).
        $this->assertTrue($this->svc->isUnchanged([]));
    }

    /* ============ computeScoreSecu (MODIF 2026-05-14 [dc-latest-version-filter]) ============
     * Formule par défaut (cf dc_remediation.yaml) :
     *   poids:    CRITICAL=10  HIGH=3   MEDIUM=1   LOW=0.5
     *   plafonds: CRITICAL=5   HIGH=10  MEDIUM=20  LOW=20
     *   penalite max cumulée = 110 (capée à 100 -> score plancher 0).
     */

    public function testScoreSecuHealthyAppReturns100(): void
    {
        $this->assertSame(100.0, $this->svc->computeScoreSecu(0, 0, 0, 0));
    }

    public function testScoreSecuSingleCriticalReturns90(): void
    {
        $this->assertSame(90.0, $this->svc->computeScoreSecu(1, 0, 0, 0));
    }

    public function testScoreSecuFiveCriticalReturns50(): void
    {
        $this->assertSame(50.0, $this->svc->computeScoreSecu(5, 0, 0, 0));
    }

    public function testScoreSecuPlafondAtteintForCritical(): void
    {
        // 50 CRIT -> meme score que 5 CRIT (plafond)
        $this->assertSame(
            $this->svc->computeScoreSecu(5,  0, 0, 0),
            $this->svc->computeScoreSecu(50, 0, 0, 0)
        );
    }

    public function testScoreSecuMixedSeverities(): void
    {
        // 1 CRIT + 5 HIGH + 10 MEDIUM + 20 LOW
        // = 100 - 10*1 - 3*5 - 1*10 - 0.5*20 = 100 - 10 - 15 - 10 - 10 = 55
        $this->assertSame(55.0, $this->svc->computeScoreSecu(1, 5, 10, 20));
    }

    public function testScoreSecuWorstCaseCappedAtZero(): void
    {
        // Tous les plafonds atteints -> -110 mais capee a 0
        $this->assertSame(0.0, $this->svc->computeScoreSecu(5, 10, 20, 20));
    }

    public function testScoreSecuBeyondCapsStillZero(): void
    {
        // Tres au-dela des plafonds -> reste a 0
        $this->assertSame(0.0, $this->svc->computeScoreSecu(100, 100, 100, 100));
    }

    public function testScoreSecuLowOnlyNeverHitsZero(): void
    {
        // Avec uniquement des LOW, la penalite max LOW = 0.5*20 = 10
        // donc le score minimum theorique = 90 si pas de critical/high/medium
        $this->assertSame(90.0, $this->svc->computeScoreSecu(0, 0, 0, 100));
    }

    /* ============ getScoreSecuRule ============ */

    public function testGetScoreSecuRuleExposesAllSeverities(): void
    {
        $rule = $this->svc->getScoreSecuRule();

        // Defaults du constructeur
        $this->assertSame(10.0, $rule['weights']['CRITICAL']);
        $this->assertSame(3.0,  $rule['weights']['HIGH']);
        $this->assertSame(1.0,  $rule['weights']['MEDIUM']);
        $this->assertSame(0.5,  $rule['weights']['LOW']);

        $this->assertSame(5,  $rule['caps']['CRITICAL']);
        $this->assertSame(10, $rule['caps']['HIGH']);
        $this->assertSame(20, $rule['caps']['MEDIUM']);
        $this->assertSame(20, $rule['caps']['LOW']);

        // Penalty max = 10*5 + 3*10 + 1*20 + 0.5*20 = 50 + 30 + 20 + 10 = 110
        $this->assertSame(110.0, $rule['penalty_max']);
    }
}
