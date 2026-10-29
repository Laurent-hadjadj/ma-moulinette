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

declare(strict_types=1);

namespace App\Tests\Unit\Service\Owasp;

use App\Service\Owasp\OwaspRuleClassifierService;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
final class OwaspRuleClassifierServiceTest extends TestCase
{
    private OwaspRuleClassifierService $service;

    protected function setUp(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['module.frontend', 'webapp'],
            ['module.backend', 'metier'],
            ['module.autre', 'batch'],
        ]);

        $this->service = new OwaspRuleClassifierService($params);
    }

    // ─── allRuleKeys() ──────────────────────────────────────────────────────

    public function testAllRuleKeysReturns126UniqueKeysMixingCoreAndFindsecbugs(): void
    {
        $keys = $this->service->allRuleKeys();

        self::assertCount(126, $keys);
        self::assertCount(126, array_unique($keys), 'Aucune clé ne doit être dupliquée entre core et findsecbugs.');
        self::assertContains('java:S2077', $keys);
        self::assertContains('findsecbugs:SQL_INJECTION_JPA', $keys);
    }

    // ─── ruleCount() ────────────────────────────────────────────────────────

    public function testRuleCount2017CombinesCoreAndFindsecbugs(): void
    {
        // 56 règles core avec securityStandards.OWASP (2017) non vide + 61 règles findsecbugs taguées owasp-aX.
        self::assertSame(117, $this->service->ruleCount(2017));
    }

    public function testRuleCount2021OnlyCountsCoreRules(): void
    {
        // FindSecBugs ne cite jamais OWASP 2021 (toujours 2013/2017) : exclu de ce comptage.
        self::assertSame(62, $this->service->ruleCount(2021));
    }

    // ─── classify() ─────────────────────────────────────────────────────────

    /**
     * @return array<int, array{rule: string, component: string, severity: string, line: ?int, message: string}>
     */
    private function sampleIssues(): array
    {
        return [
            // core seul, contribue à 2017/a1 ET 2021/a3 — module frontend.
            ['rule' => 'java:S2077', 'component' => 'webapp/Foo.java', 'severity' => 'CRITICAL', 'line' => 10, 'message' => 'SQL injection risk A'],
            // findsecbugs seul, contribue à 2017/a1 (jamais 2021) — module backend.
            ['rule' => 'findsecbugs:SQL_INJECTION_JPA', 'component' => 'metier/Bar.java', 'severity' => 'CRITICAL', 'line' => 20, 'message' => 'SQL injection risk B'],
            // findsecbugs seul, catégorie distincte (2017/a6) — module autre.
            ['rule' => 'findsecbugs:HARD_CODE_PASSWORD', 'component' => 'batch/Baz.java', 'severity' => 'MINOR', 'line' => 30, 'message' => 'Hardcoded password'],
            // règle inconnue de la classification OWASP : entièrement ignorée.
            ['rule' => 'java:S9999', 'component' => 'Unknown.java', 'severity' => 'MAJOR', 'line' => 40, 'message' => 'ignored rule'],
            // sévérité absente de la correspondance (ex. valeur non gérée) : entièrement ignorée.
            ['rule' => 'java:S2077', 'component' => 'Unknown2.java', 'severity' => 'BOGUS', 'line' => null, 'message' => 'ignored severity'],
            // core seul, même règle que le 1er cas mais fichier non classé par les 3 regex module -> "autre".
            // line=null : certaines règles n'ont pas de ligne (niveau fichier/architecture).
            ['rule' => 'java:S2077', 'component' => 'src/Unknown3.java', 'severity' => 'INFO', 'line' => null, 'message' => 'unclassified module'],
        ];
    }

    public function testClassifyAggregatesTotalsAndSeveritiesPerCategory(): void
    {
        $result = $this->service->classify($this->sampleIssues());

        self::assertSame(3, $result[2017]['a1']['total'], 'S2077 (critical+info) + SQL_INJECTION_JPA (critical).');
        self::assertSame(2, $result[2017]['a1']['critical']);
        self::assertSame(1, $result[2017]['a1']['info']);
        self::assertSame(0, $result[2017]['a1']['minor']);

        self::assertSame(1, $result[2017]['a6']['total']);
        self::assertSame(1, $result[2017]['a6']['minor']);

        self::assertSame(2, $result[2021]['a3']['total'], 'Seul S2077 (core) classe en 2021, jamais findsecbugs.');
        self::assertSame(1, $result[2021]['a3']['critical']);
        self::assertSame(1, $result[2021]['a3']['info']);
        self::assertArrayNotHasKey('a6', $result[2021], 'HARD_CODE_PASSWORD (findsecbugs) ne doit jamais apparaître sous 2021.');
    }

    public function testClassifySourceIsMixedWhenCoreAndFindsecbugsShareACategory(): void
    {
        $result = $this->service->classify($this->sampleIssues());

        self::assertSame('mixed', $result[2017]['a1']['source'], 'S2077 (core) + SQL_INJECTION_JPA (findsecbugs) contribuent tous deux à 2017/a1.');
        self::assertSame('findsecbugs', $result[2017]['a6']['source'], 'Seule HARD_CODE_PASSWORD (findsecbugs) contribue à 2017/a6.');
        self::assertSame('core', $result[2021]['a3']['source'], 'Seul S2077 (core) contribue à 2021/a3.');
    }

    public function testClassifyCountsModulesIncludingUnclassifiedAsAutre(): void
    {
        $result = $this->service->classify($this->sampleIssues());

        // 2017 : les 4 issues classées matchent toutes 2017 (S2077, SQL_INJECTION_JPA, HARD_CODE_PASSWORD).
        self::assertSame(1, $result['module'][2017]['frontend'], 'webapp/Foo.java');
        self::assertSame(1, $result['module'][2017]['backend'], 'metier/Bar.java');
        self::assertSame(2, $result['module'][2017]['autre'], 'batch/Baz.java (regex autre) + src/Unknown3.java (non classé, replié sur autre).');
    }

    public function testClassifyCountsModulesPerReferentialNotGlobally(): void
    {
        // BUG CORRIGÉ 28/10/2026 : la répartition module était comptée UNE SEULE
        // FOIS globalement (toute issue matchant au moins un référentiel), puis
        // reportée identique sur les 2 référentiels — ici seul S2077 (core) matche
        // 2021 (SQL_INJECTION_JPA/HARD_CODE_PASSWORD sont findsecbugs, jamais 2021).
        $result = $this->service->classify($this->sampleIssues());

        self::assertSame(1, $result['module'][2021]['frontend'], 'webapp/Foo.java (S2077, 2021/a3).');
        self::assertSame(0, $result['module'][2021]['backend'], 'SQL_INJECTION_JPA (findsecbugs) ne matche jamais 2021.');
        self::assertSame(1, $result['module'][2021]['autre'], 'src/Unknown3.java (S2077, 2021/a3, module non classé).');
    }

    public function testClassifyIgnoresUnmappedRuleAndUnmappedSeverity(): void
    {
        $result = $this->service->classify($this->sampleIssues());

        $totalIssuesCounted = array_sum(array_column($result[2017], 'total'));
        self::assertSame(4, $totalIssuesCounted, 'java:S9999 (règle inconnue) et la sévérité BOGUS doivent être ignorées, pas comptées.');
    }

    public function testClassifyReturnsEmptyStructureForNoIssues(): void
    {
        $result = $this->service->classify([]);

        self::assertSame([], $result[2017]);
        self::assertSame([], $result[2021]);
        self::assertSame(['frontend' => 0, 'backend' => 0, 'autre' => 0], $result['module'][2017]);
        self::assertSame(['frontend' => 0, 'backend' => 0, 'autre' => 0], $result['module'][2021]);
        self::assertSame([], $result['details'][2017]);
        self::assertSame([], $result['details'][2021]);
    }

    // ─── classify() : details (détail individuel par issue, pour owasp_details) ──

    public function testClassifyDetailsListsOneRowPerIssueMatchingTheReferential(): void
    {
        $result = $this->service->classify($this->sampleIssues());

        // 2017 : S2077/Foo, SQL_INJECTION_JPA/Bar, HARD_CODE_PASSWORD/Baz, S2077/Unknown3 (4 issues classées).
        // S9999 (règle inconnue) et S2077/Unknown2 (sévérité BOGUS) n'apparaissent jamais.
        self::assertCount(4, $result['details'][2017]);
        // 2021 : seules les 2 occurrences de S2077 (core) classent en 2021 — jamais findsecbugs.
        self::assertCount(2, $result['details'][2021]);
    }

    public function testClassifyDetailsRowHasRuleCategoriesSourceModuleLineAndMessage(): void
    {
        $result = $this->service->classify($this->sampleIssues());

        $row = $result['details'][2017][0];
        self::assertSame('java:S2077', $row['rule']);
        self::assertSame('A1', $row['categories']);
        self::assertSame('CRITICAL', $row['severity']);
        self::assertSame('core', $row['source']);
        self::assertSame('webapp/Foo.java', $row['component']);
        self::assertSame('frontend', $row['module']);
        self::assertSame(10, $row['line']);
        self::assertSame('SQL injection risk A', $row['message']);
    }

    public function testClassifyDetailsRowAllowsNullLine(): void
    {
        $result = $this->service->classify($this->sampleIssues());

        // S2077/Unknown3 (INFO, src/) : 4ᵉ ligne de details[2017], line=null dans la fixture.
        $row = $result['details'][2017][3];
        self::assertSame('java:S2077', $row['rule']);
        self::assertNull($row['line']);
        self::assertSame('unclassified module', $row['message']);
        // 'inconnu' (valeur brute de classifieModule()), pas 'autre' — au niveau
        // ligne de détail, "inconnu" reste distinct, contrairement à l'agrégat
        // module (owasp.module_autre) qui replie "inconnu" sur "autre".
        self::assertSame('inconnu', $row['module'], 'src/Unknown3.java ne matche aucune des 3 regex module.');
    }

    public function testClassifyDetailsConcatenatesMultipleCategoriesForTheSameIssue(): void
    {
        // java:S2092 est classé sur 2 catégories 2021 simultanément (a4, a5).
        $issues = [
            ['rule' => 'java:S2092', 'component' => 'webapp/Multi.java', 'severity' => 'MAJOR', 'line' => 5, 'message' => 'multi-category'],
        ];

        $result = $this->service->classify($issues);

        self::assertSame('A4, A5', $result['details'][2021][0]['categories']);
    }

    public function testClassifyDetailsRowSourceIsFindsecbugsNotMixedEvenWhenCategoryItselfIsMixed(): void
    {
        // 2017/a1 est 'mixed' au niveau catégorie (core + findsecbugs y contribuent tous les deux),
        // mais chaque LIGNE de détail individuelle garde sa propre origine, jamais 'mixed'.
        $result = $this->service->classify($this->sampleIssues());

        $findsecbugsRow = array_values(array_filter(
            $result['details'][2017],
            static fn(array $row): bool => $row['rule'] === 'findsecbugs:SQL_INJECTION_JPA'
        ))[0];

        self::assertSame('findsecbugs', $findsecbugsRow['source']);
    }
}
