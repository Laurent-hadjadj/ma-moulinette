<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Controller\DependencyCheck;

use App\Controller\DependencyCheck\DependencyCheckPageController;
use App\Repository\{DcCveRepository, DcDependencyRepository, DcFindingRepository, DcScanRepository};
use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Service\MesProjets;
use App\Service\DependencyCheck\DcExecutiveAnalyticsService;
use App\Service\PdfExportService;
use Knp\Component\Pager\PaginatorInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use ReflectionMethod;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

/**
 * MODIF 2026-05-16 : tests Unit des helpers privés
 * du DependencyCheckPageController qui sont des fonctions pures (n'utilisent
 * ni addFlash ni request_stack). 3 helpers couverts :
 *   - severityRank()  : mapping rang sévérité (5 branches)
 *   - computeTopDeps(): tri findings par nb CVE + fallback label
 *   - computeDepJh()  : délégation à DcExecutiveAnalyticsService
 *
 * extractListeOrFlash() et fetchFindingsOrEmpty() ne sont PAS testés ici :
 * ils appellent addFlash() qui requiert le container Symfony (request_stack),
 * trop coûteux à isoler en Unit. Couverts indirectement par les tests
 * Integration (vagues 5A/5B/5C).
 */
#[AllowMockObjectsWithoutExpectations]
class DependencyCheckPageControllerHelpersTest extends TestCase
{
    private DependencyCheckPageController $controller;
    private DcExecutiveAnalyticsService $dcAnalytics;

    /** @var UserAgentTrackingFacade&MockObject */
    private MockObject $tracking;

    protected function setUp(): void
    {
        $params = $this->createMock(ParameterBagInterface::class);
        $params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'ACME'],
            ['marque.entreprise.long', 'ACME Corp'],
            ['environnement', 'test'],
            ['version', '0.0.0'],
        ]);

        /* MODIF 2026-05-16 : DcExecutiveAnalyticsService
         * est `final` -> pas mockable. On instancie le vrai service avec ses
         * valeurs par défaut (constructor pleinement optionnel). Tests
         * computeDepJh valident la delegation + branche match sévérité, pas
         * la valeur exacte (depend des params injectes). */
        $this->dcAnalytics = new DcExecutiveAnalyticsService();
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

        $this->controller = new DependencyCheckPageController(
            $params,
            $this->createMock(DcScanRepository::class),
            $this->createMock(DcCveRepository::class),
            $this->createMock(DcDependencyRepository::class),
            $this->createMock(DcFindingRepository::class),
            $this->createMock(PdfExportService::class),
            $this->createMock(PaginatorInterface::class),
            $this->createMock(LoggerInterface::class),
            $this->dcAnalytics,
            $this->createMock(MesProjets::class),
            $this->createMock(UserAgentTrackingFacade::class),
        );
    }

    /**
     * @param array<int, mixed> $args
     */
    private function callPrivate(string $method, array $args = []): mixed
    {
        return (new ReflectionMethod($this->controller, $method))
            ->invoke($this->controller, ...$args);
    }

    /* ====================================================================
     * severityRank — 5 branches mapping
     * ==================================================================== */

    public function testSeverityRankReturns4WhenCriticalPresent(): void
    {
        $scan = ['cve_count_critical' => 1, 'cve_count_high' => 5];
        $this->assertSame(4, $this->callPrivate('severityRank', [$scan]));
    }

    public function testSeverityRankReturns3WhenOnlyHighPresent(): void
    {
        $scan = ['cve_count_critical' => 0, 'cve_count_high' => 2];
        $this->assertSame(3, $this->callPrivate('severityRank', [$scan]));
    }

    public function testSeverityRankReturns2WhenOnlyMediumPresent(): void
    {
        $scan = ['cve_count_critical' => 0, 'cve_count_high' => 0, 'cve_count_medium' => 7];
        $this->assertSame(2, $this->callPrivate('severityRank', [$scan]));
    }

    public function testSeverityRankReturns1WhenOnlyLowPresent(): void
    {
        $scan = [
            'cve_count_critical' => 0,
            'cve_count_high'     => 0,
            'cve_count_medium'   => 0,
            'cve_count_low'      => 3,
        ];
        $this->assertSame(1, $this->callPrivate('severityRank', [$scan]));
    }

    public function testSeverityRankReturns0WhenAllAbsent(): void
    {
        $this->assertSame(0, $this->callPrivate('severityRank', [[]]));
        $this->assertSame(0, $this->callPrivate('severityRank', [[
            'cve_count_critical' => 0,
            'cve_count_high'     => 0,
            'cve_count_medium'   => 0,
            'cve_count_low'      => 0,
        ]]));
    }

    /* ====================================================================
     * computeTopDeps — agrégation par label + tri par nb_cves + limit
     * ==================================================================== */

    public function testComputeTopDepsAggregatesByProduct(): void
    {
        $findings = [
            ['product' => 'amqp-client', 'file_name' => 'a.jar', 'dep_version' => '5.9.0'],
            ['product' => 'amqp-client', 'file_name' => 'a.jar', 'dep_version' => '5.9.0'],
            ['product' => 'jackson',     'file_name' => 'j.jar', 'dep_version' => '2.13.0'],
        ];
        $top = $this->callPrivate('computeTopDeps', [$findings, 10]);
        $this->assertCount(2, $top);
        $this->assertSame('amqp-client', $top[0]['label']);
        $this->assertSame(2, $top[0]['nb_cves']);
        $this->assertSame('jackson', $top[1]['label']);
        $this->assertSame(1, $top[1]['nb_cves']);
    }

    public function testComputeTopDepsFallsBackToFileNameWhenProductEmpty(): void
    {
        $findings = [
            ['product' => '', 'file_name' => 'mystery.jar', 'dep_version' => null],
        ];
        $top = $this->callPrivate('computeTopDeps', [$findings, 5]);
        $this->assertSame('mystery.jar', $top[0]['label']);
    }

    public function testComputeTopDepsFallsBackToInconnuWhenAllEmpty(): void
    {
        $findings = [
            ['product' => '', 'file_name' => '', 'dep_version' => null],
        ];
        $top = $this->callPrivate('computeTopDeps', [$findings, 5]);
        $this->assertSame('inconnu', $top[0]['label']);
    }

    public function testComputeTopDepsRespectsLimit(): void
    {
        $findings = [];
        foreach (range(1, 15) as $i) {
            $findings[] = ['product' => 'dep' . $i, 'file_name' => 'd.jar', 'dep_version' => '1.0'];
        }
        $top = $this->callPrivate('computeTopDeps', [$findings, 5]);
        $this->assertCount(5, $top);
    }

    public function testComputeTopDepsReturnsEmptyForEmptyInput(): void
    {
        $this->assertSame([], $this->callPrivate('computeTopDeps', [[], 10]));
    }

    /* ====================================================================
     * computeDepJh — délégation à analytics (vrai service, branches match)
     * Service final non mockable : on instancie le vrai et on valide que
     * le résultat est cohérent (float >= 0, sévérité plus forte => JH >= sévérité plus faible).
     * ==================================================================== */

    public function testComputeDepJhReturnsFloatGreaterThanZero(): void
    {
        $dep = [
            'vendor'      => 'rabbitmq',
            'product'     => 'amqp-client',
            'nb_critical' => 1,
        ];
        $jh = $this->callPrivate('computeDepJh', [$dep]);
        $this->assertIsFloat($jh);
        $this->assertGreaterThan(0.0, $jh);
    }

    public function testComputeDepJhPicksHighestSeverityFirst(): void
    {
        // nb_high > 0 ET nb_medium > 0 -> branche HIGH (l'ordre du match prend critical->high->medium->low)
        $depHigh = [
            'vendor'      => 'v',
            'product'     => 'p',
            'nb_critical' => 0,
            'nb_high'     => 3,
            'nb_medium'   => 5,
        ];
        $depMedium = [
            'vendor'      => 'v',
            'product'     => 'p',
            'nb_critical' => 0,
            'nb_high'     => 0,
            'nb_medium'   => 5,
        ];
        $jhHigh = $this->callPrivate('computeDepJh', [$depHigh]);
        $jhMedium = $this->callPrivate('computeDepJh', [$depMedium]);

        // HIGH bonus > MEDIUM bonus (cf severityBonus du service) -> jhHigh >= jhMedium
        $this->assertGreaterThanOrEqual($jhMedium, $jhHigh);
    }

    public function testComputeDepJhFallsBackToInfoWhenNoSeverity(): void
    {
        $depEmpty = ['vendor' => 'v', 'product' => 'p']; // aucun nb_* > 0 -> branche default INFO
        $jh = $this->callPrivate('computeDepJh', [$depEmpty]);
        $this->assertIsFloat($jh);
        $this->assertGreaterThanOrEqual(0.0, $jh);
    }
}
