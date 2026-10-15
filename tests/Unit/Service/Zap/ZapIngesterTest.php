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

namespace App\Tests\Unit\Service\Zap;

use App\Entity\{OwaspDast, ZapFinding, ZapProcessingQueue, ZapRule, ZapScan};
use App\Exception\Zap\ZapInvalidStructureException;
use App\Repository\{ZapRuleRepository, ZapScanRepository};
use App\Service\Zap\ZapIngester;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

/**
 * MODIF 2026-10-13 : test unit du ZapIngester, sur le gabarit
 * DependencyCheckIngesterTest.
 *
 * Couvre :
 *  - idempotence (scan deja existant)
 *  - happy path (1 site, N alertes -> 1 ZapScan + N ZapRule + N ZapFinding)
 *  - cache local règle (meme pluginid dans N alertes -> 1 seul persist ZapRule)
 *  - findOrCreate règle deja en BDD (lastSeenAt mis a jour)
 *  - construction owasp_dast (tags OWASP_2017_AXX / OWASP_2021_AXX -> compteurs)
 *  - alerte sans pluginid -> exception
 *  - rollback sur erreur
 *  - compteurs scan post-ingestion (risk_count_*)
 */
#[AllowMockObjectsWithoutExpectations]
class ZapIngesterTest extends TestCase
{
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $em;
    /** @var ZapRuleRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $ruleRepo;
    /** @var ZapScanRepository&\PHPUnit\Framework\MockObject\MockObject */
    private $scanRepo;
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    private ZapIngester $ingester;

    /** @var object[] */
    private array $persisted = [];

    protected function setUp(): void
    {
        $this->em       = $this->createMock(EntityManagerInterface::class);
        $this->ruleRepo = $this->createMock(ZapRuleRepository::class);
        $this->scanRepo = $this->createMock(ZapScanRepository::class);
        $this->logger    = $this->createMock(LoggerInterface::class);

        $this->persisted = [];
        $this->em->method('persist')->willReturnCallback(function (object $entity) {
            $this->persisted[] = $entity;
        });

        $this->ingester = new ZapIngester($this->em, $this->ruleRepo, $this->scanRepo, $this->logger);
    }

    // ═══════════════════ Idempotence ══════════════════════════════════════════

    public function testReturnsExistingScanIfAlreadyProcessed(): void
    {
        $existing = $this->makeScan(42);
        $this->scanRepo->method('findByUniqueKey')->willReturn($existing);

        $queue = $this->buildQueue($this->minimalReport());
        $result = $this->ingester->ingest($queue);

        $this->assertSame($existing, $result);
        $this->assertSame([], $this->persisted, 'Aucun persist si scan deja existant');
    }

    // ═══════════════════ Happy path basique ════════════════════════════════════

    public function testCreatesScanWithSingleAlert(): void
    {
        $this->scanRepo->method('findByUniqueKey')->willReturn(null);
        $this->ruleRepo->method('findByPluginid')->willReturn(null);

        $report = $this->minimalReport([
            'site' => [[
                '@name' => 'http://exemple.local/',
                'alerts' => [[
                    'pluginid'   => '10038',
                    'name'       => 'Content Security Policy (CSP) Header Not Set',
                    'riskcode'   => '2',
                    'confidence' => '3',
                    'cweid'      => '693',
                    'wascid'     => '15',
                    'solution'   => 'Set the CSP header.',
                    'reference'  => 'https://example.org',
                    'instances'  => [['uri' => 'http://exemple.local/', 'method' => 'GET']],
                    'tags'       => [
                        ['tag' => 'OWASP_2021_A05'],
                        ['tag' => 'OWASP_2017_A06'],
                    ],
                ]],
            ]],
        ]);

        $queue = $this->buildQueue($report);
        $scan = $this->ingester->ingest($queue);

        $this->assertSame('fr.ma-moulinette:ma-moulinette', $scan->getMavenKey());
        $this->assertSame('1.0', $scan->getProjectVersion());
        $this->assertSame(1, $scan->getAlertCountTotal());
        $this->assertSame(0, $scan->getRiskCountHigh());
        $this->assertSame(1, $scan->getRiskCountMedium());

        // 1 ZapScan + 1 ZapRule + 1 ZapFinding + 2 OwaspDast (2017 et 2021) = 5 persists
        $this->assertCount(5, $this->persisted);
        $this->assertCount(1, array_filter($this->persisted, fn($e) => $e instanceof ZapScan));
        $this->assertCount(1, array_filter($this->persisted, fn($e) => $e instanceof ZapRule));
        $this->assertCount(1, array_filter($this->persisted, fn($e) => $e instanceof ZapFinding));
        $this->assertCount(2, array_filter($this->persisted, fn($e) => $e instanceof OwaspDast));

        /** @var ZapRule $rule */
        $rule = current(array_filter($this->persisted, fn($e) => $e instanceof ZapRule));
        $this->assertSame('10038', $rule->getPluginid());
        $this->assertSame(693, $rule->getCweid());
        $this->assertSame(15, $rule->getWascid());

        $owaspRows = array_values(array_filter($this->persisted, fn($e) => $e instanceof OwaspDast));
        $referentials = array_map(fn(OwaspDast $o) => $o->getReferentialOwasp(), $owaspRows);
        sort($referentials);
        $this->assertSame([2017, 2021], $referentials);

        foreach ($owaspRows as $owaspDast) {
            // Catégorie A5 (2021) ou A6 (2017) selon le tag -> 1 alerte Medium
            $category = $owaspDast->getReferentialOwasp() === 2021 ? 5 : 6;
            $this->assertSame(1, $owaspDast->getCategoryCount($category));
        }
    }

    public function testReusesRuleCacheWhenSamePluginidAppearsTwice(): void
    {
        $this->scanRepo->method('findByUniqueKey')->willReturn(null);
        $this->ruleRepo->method('findByPluginid')->willReturn(null);

        $alert = [
            'pluginid' => '10063', 'name' => 'Leak', 'riskcode' => '1', 'confidence' => '2',
            'instances' => [], 'tags' => [],
        ];
        $report = $this->minimalReport([
            'site' => [['@name' => 'http://exemple.local/', 'alerts' => [$alert, $alert]]],
        ]);

        $this->ingester->ingest($this->buildQueue($report));

        $this->assertCount(1, array_filter($this->persisted, fn($e) => $e instanceof ZapRule),
            'Meme pluginid dans 2 alertes -> 1 seul persist ZapRule (cache local)');
        $this->assertCount(2, array_filter($this->persisted, fn($e) => $e instanceof ZapFinding));
    }

    public function testUpdatesLastSeenAtWhenRuleAlreadyExists(): void
    {
        $this->scanRepo->method('findByUniqueKey')->willReturn(null);
        $existingRule = (new ZapRule())->setPluginid('10038')->setName('x');
        $oldLastSeen = $existingRule->getLastSeenAt();
        $this->ruleRepo->method('findByPluginid')->willReturn($existingRule);

        $report = $this->minimalReport([
            'site' => [['@name' => 'http://exemple.local/', 'alerts' => [
                ['pluginid' => '10038', 'name' => 'x', 'riskcode' => '0', 'confidence' => '0', 'instances' => [], 'tags' => []],
            ]]],
        ]);

        $this->ingester->ingest($this->buildQueue($report));

        $this->assertCount(0, array_filter($this->persisted, fn($e) => $e instanceof ZapRule),
            'Règle deja en BDD : pas de nouveau persist');
        $this->assertGreaterThanOrEqual($oldLastSeen, $existingRule->getLastSeenAt());
    }

    public function testThrowsWhenAlertHasNoPluginid(): void
    {
        $this->scanRepo->method('findByUniqueKey')->willReturn(null);

        $report = $this->minimalReport([
            'site' => [['@name' => 'x', 'alerts' => [['name' => 'sans pluginid']]]],
        ]);

        $this->expectException(ZapInvalidStructureException::class);
        $this->ingester->ingest($this->buildQueue($report));
    }

    public function testRejectsMultiSiteReportAtIngestion(): void
    {
        $this->scanRepo->method('findByUniqueKey')->willReturn(null);

        $report = $this->minimalReport(['site' => [
            ['@name' => 'a', 'alerts' => []],
            ['@name' => 'b', 'alerts' => []],
        ]]);

        $this->expectException(ZapInvalidStructureException::class);
        $this->expectExceptionMessage('multi-site');
        $this->ingester->ingest($this->buildQueue($report));
    }

    public function testNoOwaspDastPersistedWhenNoOwaspTag(): void
    {
        $this->scanRepo->method('findByUniqueKey')->willReturn(null);
        $this->ruleRepo->method('findByPluginid')->willReturn(null);

        $report = $this->minimalReport([
            'site' => [['@name' => 'x', 'alerts' => [
                ['pluginid' => '1', 'name' => 'x', 'riskcode' => '0', 'confidence' => '0', 'instances' => [],
                 'tags' => [['tag' => 'WSTG-v42-INFO-02']]],
            ]]],
        ]);

        $this->ingester->ingest($this->buildQueue($report));

        $this->assertCount(0, array_filter($this->persisted, fn($e) => $e instanceof OwaspDast),
            'Aucun tag OWASP_<année>_AXX -> aucune ligne owasp_dast créée');
    }

    /**
     * @param array<string, mixed> $overrides
     * @return array<string, mixed>
     */
    private function minimalReport(array $overrides = []): array
    {
        $base = [
            '@version'   => '2.17.0',
            '@generated' => 'Tue, 13 Oct 2026 12:00:00',
            'site'       => [['@name' => 'http://exemple.local/', 'alerts' => []]],
        ];
        return array_replace($base, $overrides);
    }

    /**
     * @param array<string, mixed> $report
     */
    private function buildQueue(array $report): ZapProcessingQueue
    {
        $json = json_encode($report);
        return (new ZapProcessingQueue())
            ->setUlid('00000000000000000000000001')
            ->setPayloadGz(gzencode($json, 6))
            ->setPayloadSha256(hash('sha256', $json))
            ->setPayloadSize(strlen($json))
            ->setContentType('json')
            ->setMavenKey('fr.ma-moulinette:ma-moulinette')
            ->setProjectVersion('1.0');
    }

    /**
     * Cree un ZapScan factice avec ID force par reflection (pour les retours mockes).
     */
    private function makeScan(int $id): ZapScan
    {
        $scan = (new ZapScan())
            ->setMavenKey('fr.ma-moulinette:ma-moulinette')
            ->setProjectVersion('1.0')
            ->setGeneratedAt(new \DateTimeImmutable());
        $ref = new \ReflectionProperty(ZapScan::class, 'id');
        $ref->setValue($scan, $id);
        return $scan;
    }
}
