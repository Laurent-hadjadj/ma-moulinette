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

namespace App\Tests\Unit\Controller\Batch;

/* MODIF 27/10/2026 : réécriture complète — le mécanisme facette+secours par tag
 * (catégorie par catégorie) est remplacé par un seul appel paginé
 * /api/issues/search?rules=<...> délégué à OwaspRuleClassifierService::classify().
 * Les anciens tests sur la fusion facette/tag par catégorie n'ont plus d'objet
 * (ce mécanisme n'existe plus) — remplacés par des tests sur la pagination et
 * la délégation à OwaspRuleClassifierService (mocké).
 *
 * MODIF 28/10/2026 : BatchCollecteOwasp() persiste désormais aussi le détail
 * individuel de chaque vulnérabilité classée (table owasp_details, repository
 * OwaspDetailsRepository mocké) — ajout du mock manquant (le contrôleur
 * appelle $this->em->getRepository(OwaspDetails::class) dès le début de la
 * méthode, sans lui tout appel repartait en erreur TypeError "Return value
 * must be of type EntityRepository, null returned"), du 3e couple
 * delete+insert dans le scénario nominal, et de tests dédiés sur la forme des
 * lignes owasp_details (référentiel, troncature message/file_path à 255
 * caractères, garde 2017/2021 alignée sur $owaspDataList). OwaspRuleClassifierService::classify()
 * retourne aussi 'module' par référentiel (2017/2021 séparément, cf. bug de
 * répartition corrigé le même jour) et 'details' (une ligne par issue classée).
 */

use App\Controller\Batch\BatchCollecteOwaspController;
use App\Entity\{InformationProjet, Owasp, OwaspDetails};
use App\Repository\{InformationProjetRepository, OwaspDetailsRepository, OwaspRepository};
use App\Service\{ClientService, GetSonarVersionService, UrlBuilderService};
use App\Service\Owasp\OwaspRuleClassifierService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\{AllowMockObjectsWithoutExpectations, DataProvider};
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;

#[AllowMockObjectsWithoutExpectations]
class BatchCollecteOwaspControllerTest extends TestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:ma-moulinette';
    private const SONAR_URL = 'https://sonar.example.com';
    private const BUILT_URL = 'https://sonar.example.com/api/issues/search?...';

    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;

    /** @var InformationProjetRepository&MockObject */
    private MockObject $infoRepo;

    /** @var OwaspRepository&MockObject */
    private MockObject $owaspRepo;

    /** @var OwaspDetailsRepository&MockObject */
    private MockObject $owaspDetailsRepo;

    /** @var ClientService&MockObject */
    private MockObject $client;

    /** @var UrlBuilderService&MockObject */
    private MockObject $urlBuilder;

    /** @var GetSonarVersionService&MockObject */
    private MockObject $sonarVersion;

    /** @var OwaspRuleClassifierService&MockObject */
    private MockObject $ruleClassifier;

    /** @var LoggerInterface&MockObject */
    private MockObject $logger;

    /** @var ParameterBagInterface&MockObject */
    private MockObject $parameterBag;

    private BatchCollecteOwaspController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->infoRepo = $this->createMock(InformationProjetRepository::class);
        $this->owaspRepo = $this->createMock(OwaspRepository::class);
        $this->owaspDetailsRepo = $this->createMock(OwaspDetailsRepository::class);
        $this->client = $this->createMock(ClientService::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->sonarVersion = $this->createMock(GetSonarVersionService::class);
        $this->ruleClassifier = $this->createMock(OwaspRuleClassifierService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->parameterBag = $this->createMock(ParameterBagInterface::class);

        $this->sonarVersion->method('componentKeyParam')->willReturn('components');
        $this->sonarVersion->method('issueStatusParam')->willReturn(['param' => 'issueStatuses', 'value' => 'OPEN,CONFIRMED,ACCEPTED']);
        $this->ruleClassifier->method('allRuleKeys')->willReturn(['java:S2077', 'findsecbugs:SQL_INJECTION_JPA']);

        $this->em->method('getRepository')->willReturnMap([
            [InformationProjet::class, $this->infoRepo],
            [Owasp::class, $this->owaspRepo],
            [OwaspDetails::class, $this->owaspDetailsRepo],
        ]);

        $this->urlBuilder->method('build')->willReturn(self::BUILT_URL);

        // Mock du container pour $this->getParameter() — pattern AbstractController.
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['parameter_bag', true],
            ['serializer', false],
        ]);
        $container->method('get')->willReturnMap([
            ['parameter_bag', 1, $this->parameterBag],
        ]);

        $this->controller = new BatchCollecteOwaspController(
            $this->em,
            $this->client,
            $this->urlBuilder,
            $this->sonarVersion,
            $this->ruleClassifier,
            $this->logger
        );
        $this->controller->setContainer($container);
    }

    private function stubParams(string $sonarVersion): void
    {
        $this->parameterBag->method('get')->willReturnMap([
            ['sonar.url', self::SONAR_URL],
            ['sonar.version', $sonarVersion],
        ]);
    }

    /**
     * Stub par défaut (succès) du couple delete+insert sur owasp_details —
     * à appeler dans tout test qui atteint cette étape (insertOwasp réussi).
     */
    private function stubOwaspDetailsSuccess(): void
    {
        $this->owaspDetailsRepo->method('deleteOwaspDetailsMavenKey')->willReturn(['code' => 200]);
        $this->owaspDetailsRepo->method('insertOwaspDetails')->willReturn(['code' => 200]);
    }

    /**
     * @param array<int, array<string, mixed>> $issues
     *
     * @return array{total: int, effortTotal: int, issues: array<int, array<string, mixed>>, paging: array{total: int}}
     */
    private function page(array $issues, int $total, int $effortTotal = 0): array
    {
        return ['total' => $total, 'effortTotal' => $effortTotal, 'issues' => $issues, 'paging' => ['total' => $total]];
    }

    /**
     * @return array{
     *     2017: array<string, array<string, mixed>>,
     *     2021: array<string, array<string, mixed>>,
     *     module: array{2017: array{frontend: int, backend: int, autre: int}, 2021: array{frontend: int, backend: int, autre: int}},
     *     details: array{2017: array<int, array<string, mixed>>, 2021: array<int, array<string, mixed>>}
     * }
     */
    private function emptyClassification(): array
    {
        return [
            2017 => [],
            2021 => [],
            'module' => [
                2017 => ['frontend' => 0, 'backend' => 0, 'autre' => 0],
                2021 => ['frontend' => 0, 'backend' => 0, 'autre' => 0],
            ],
            'details' => [2017 => [], 2021 => []],
        ];
    }

    // ─── 1. Erreur HTTP sur l'appel issues ────────────────────────────────────

    #[DataProvider('httpErrorCodesProvider')]
    public function testReturnsErrorWhenIssuesFetchFails(int $httpCode): void
    {
        $this->stubParams('10');

        $this->client->expects($this->once())
            ->method('httpSonarQube')
            ->willReturn(['code' => $httpCode, 'erreur' => "HTTP $httpCode"]);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => $httpCode, 'erreur' => "HTTP $httpCode"], $result);
    }

    /**
     * @return array<int, array{0: int}>
     */
    public static function httpErrorCodesProvider(): array
    {
        return [[400], [401], [500], [503]];
    }

    // ─── 2. Pagination : plusieurs pages jusqu'à paging.total ────────────────

    public function testPaginatesUntilAllIssuesAreFetched(): void
    {
        $this->stubParams('10');

        // Page 1 pleine (self::$pageSize = 500) + page 2 avec le reliquat, pour forcer
        // une 2e page (total=501 > une seule page de 500).
        $page1Issues = array_fill(0, 500, ['rule' => 'java:S2077', 'component' => 'webapp/A.java', 'severity' => 'MAJOR']);
        $page2Issues = [['rule' => 'findsecbugs:SQL_INJECTION_JPA', 'component' => 'metier/B.java', 'severity' => 'CRITICAL']];

        $call = 0;
        $this->client->expects($this->exactly(2))
            ->method('httpSonarQube')
            ->willReturnCallback(function () use (&$call, $page1Issues, $page2Issues) {
                $call++;
                return $call === 1
                    ? ['code' => 200, 'json' => $this->page($page1Issues, 501, 15)]
                    : ['code' => 200, 'json' => $this->page($page2Issues, 501, 15)];
            });

        $this->ruleClassifier->expects($this->once())
            ->method('classify')
            ->with($this->callback(fn (array $issues) => count($issues) === 501))
            ->willReturn($this->emptyClassification());

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '1.0']]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->method('insertOwasp')->willReturn(['code' => 200]);
        $this->stubOwaspDetailsSuccess();

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(200, $result['code']);
    }

    // ─── 3. selectInformationProjetVersion échoue ────────────────────────────

    public function testReturnsErrorWhenInformationProjetVersionQueryFails(): void
    {
        $this->stubParams('8');

        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);
        $this->ruleClassifier->method('classify')->willReturn($this->emptyClassification());

        $this->infoRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 503, 'erreur' => 'DB down']);

        $this->owaspRepo->expects($this->never())->method('deleteOwaspMavenKey');
        $this->owaspRepo->expects($this->never())->method('insertOwasp');
        $this->owaspDetailsRepo->expects($this->never())->method('deleteOwaspDetailsMavenKey');
        $this->owaspDetailsRepo->expects($this->never())->method('insertOwaspDetails');

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 503, 'erreur' => 'DB down'], $result);
    }

    // ─── 4. Aucune info projet ────────────────────────────────────────────────

    public function testReturns404WhenNoProjectInfoIsFound(): void
    {
        $this->stubParams('8');

        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);
        $this->ruleClassifier->method('classify')->willReturn($this->emptyClassification());

        $this->infoRepo->expects($this->once())
            ->method('selectInformationProjetVersion')
            ->willReturn(['code' => 200, 'info' => []]);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with($this->stringContains('Aucun projet trouvé'));

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(404, $result['code']);
    }

    // ─── 5. deleteOwasp échoue ───────────────────────────────────────────────

    public function testReturnsErrorWhenDeleteOwaspFails(): void
    {
        $this->stubParams('8');
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);
        $this->ruleClassifier->method('classify')->willReturn($this->emptyClassification());
        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '1.0']]);

        $this->owaspRepo->expects($this->once())
            ->method('deleteOwaspMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete failed']);
        $this->owaspRepo->expects($this->never())->method('insertOwasp');
        $this->owaspDetailsRepo->expects($this->never())->method('deleteOwaspDetailsMavenKey');

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 500, 'erreur' => 'delete failed'], $result);
    }

    // ─── 6. insertOwasp échoue ───────────────────────────────────────────────

    public function testReturnsErrorWhenInsertOwaspFails(): void
    {
        $this->stubParams('8');
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);
        $this->ruleClassifier->method('classify')->willReturn($this->emptyClassification());
        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '1.0']]);

        $this->owaspRepo->expects($this->once())->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->willReturn(['code' => 500, 'erreur' => 'insert failed']);
        $this->owaspDetailsRepo->expects($this->never())->method('deleteOwaspDetailsMavenKey');

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 500, 'erreur' => 'insert failed'], $result);
    }

    // ─── 7. sonar.version = 8 : une seule ligne (2017) ───────────────────────

    public function testSkipsOwasp2021RowWhenSonarVersionIs8(): void
    {
        $this->stubParams('8');
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);

        $this->ruleClassifier->method('classify')->willReturn([
            2017 => ['a1' => ['total' => 3, 'blocker' => 1, 'critical' => 0, 'major' => 2, 'minor' => 0, 'info' => 0, 'source' => 'core']],
            2021 => ['a3' => ['total' => 9, 'blocker' => 0, 'critical' => 9, 'major' => 0, 'minor' => 0, 'info' => 0, 'source' => 'core']],
            'module' => [
                2017 => ['frontend' => 1, 'backend' => 2, 'autre' => 0],
                2021 => ['frontend' => 1, 'backend' => 0, 'autre' => 0],
            ],
            'details' => [2017 => [], 2021 => []],
        ]);

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '1.0.0']]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->stubOwaspDetailsSuccess();

        $captured = [];
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(function (array $list) use (&$captured) {
                $captured = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(200, $result['code']);
        $this->assertSame(3, $result['owasp2017']);
        $this->assertSame('NC', $result['owasp2021'], 'Pas de ligne 2021 construite pour sonar.version = 8.');
        $this->assertCount(1, $captured);
        $this->assertSame(2017, $captured[0]['referential_owasp']);
    }

    // ─── 8. Happy path version 10+ : 2017 ET 2021 persistés depuis classify() ─

    public function testHappyPathBuildsBothRowsFromClassifierOutput(): void
    {
        $this->stubParams('10');
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 12, 42)]);

        $classified = [
            2017 => [
                'a1' => ['total' => 5, 'blocker' => 1, 'critical' => 2, 'major' => 2, 'minor' => 0, 'info' => 0, 'source' => 'mixed'],
            ],
            2021 => [
                'a3' => ['total' => 7, 'blocker' => 0, 'critical' => 7, 'major' => 0, 'minor' => 0, 'info' => 0, 'source' => 'core'],
            ],
            'module' => [
                2017 => ['frontend' => 3, 'backend' => 4, 'autre' => 5],
                2021 => ['frontend' => 2, 'backend' => 1, 'autre' => 0],
            ],
            'details' => [2017 => [], 2021 => []],
        ];
        $this->ruleClassifier->expects($this->once())->method('classify')->willReturn($classified);

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '2.0.0']]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->stubOwaspDetailsSuccess();

        $captured = [];
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(function (array $list) use (&$captured) {
                $captured = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'auto', 'batch');

        $this->assertSame(200, $result['code']);
        $this->assertSame(5, $result['owasp2017']);
        $this->assertSame(7, $result['owasp2021']);
        $this->assertStringContainsString('OWASP', $result['message']);

        $this->assertCount(2, $captured);
        $this->assertSame(2017, $captured[0]['referential_owasp']);
        $this->assertSame($classified[2017], $captured[0]['categories']);
        $this->assertSame(42, $captured[0]['effort_total']);
        $this->assertSame(3, $captured[0]['module_frontend']);
        $this->assertSame(4, $captured[0]['module_backend']);
        $this->assertSame(5, $captured[0]['module_autre']);

        $this->assertSame(2021, $captured[1]['referential_owasp']);
        $this->assertSame($classified[2021], $captured[1]['categories']);
        $this->assertSame(42, $captured[1]['effort_total'], 'Même effort global reporté sur les deux lignes (simplification assumée).');
        $this->assertSame(2, $captured[1]['module_frontend'], 'Répartition module propre au référentiel 2021, distincte de 2017.');
        $this->assertSame(self::MAVEN_KEY, $captured[0]['maven_key']);
        $this->assertSame('2.0.0', $captured[0]['version']);
        $this->assertSame('auto', $captured[0]['mode_collecte']);
        $this->assertSame('batch', $captured[0]['utilisateur_collecte']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $captured[0]['date_version']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $captured[0]['date_enregistrement']);
    }

    // ─── 9. Aucune catégorie classée : ligne 2017 quand même construite (évite le 406 front) ─

    public function testBuilds2017RowEvenWhenNoCategoryMatched(): void
    {
        $this->stubParams('8');
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);
        $this->ruleClassifier->method('classify')->willReturn($this->emptyClassification());

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '1.0']]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->stubOwaspDetailsSuccess();

        $captured = [];
        $this->owaspRepo->expects($this->once())
            ->method('insertOwasp')
            ->with($this->callback(function (array $list) use (&$captured) {
                $captured = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(0, $result['owasp2017'], 'Zéro réel, pas une absence de collecte.');
        $this->assertCount(1, $captured);
        $this->assertSame([], $captured[0]['categories']);
    }

    // ─── 10. Les clés de règles du classifier sont transmises en rules= ─────

    public function testPassesClassifierRuleKeysAsRulesParameter(): void
    {
        // setUp() stub allRuleKeys() -> ['java:S2077', 'findsecbugs:SQL_INJECTION_JPA'].
        $this->stubParams('10');
        $this->ruleClassifier->method('classify')->willReturn($this->emptyClassification());

        $this->urlBuilder->expects($this->atLeastOnce())
            ->method('build')
            ->with(
                self::SONAR_URL,
                '/api/issues/search',
                $this->callback(fn (array $params) => $params['rules'] === 'java:S2077,findsecbugs:SQL_INJECTION_JPA')
            )
            ->willReturn(self::BUILT_URL);

        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);
        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '1.0']]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->method('insertOwasp')->willReturn(['code' => 200]);
        $this->stubOwaspDetailsSuccess();

        $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');
    }

    // ─── 11. owasp_details : deleteOwaspDetailsMavenKey échoue ──────────────

    public function testReturnsErrorWhenDeleteOwaspDetailsFails(): void
    {
        $this->stubParams('8');
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);
        $this->ruleClassifier->method('classify')->willReturn($this->emptyClassification());
        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '1.0']]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->method('insertOwasp')->willReturn(['code' => 200]);

        $this->owaspDetailsRepo->expects($this->once())
            ->method('deleteOwaspDetailsMavenKey')
            ->willReturn(['code' => 500, 'erreur' => 'delete details failed']);
        $this->owaspDetailsRepo->expects($this->never())->method('insertOwaspDetails');

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 500, 'erreur' => 'delete details failed'], $result);
    }

    // ─── 12. owasp_details : insertOwaspDetails échoue ───────────────────────

    public function testReturnsErrorWhenInsertOwaspDetailsFails(): void
    {
        $this->stubParams('8');
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);
        $this->ruleClassifier->method('classify')->willReturn($this->emptyClassification());
        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '1.0']]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->method('insertOwasp')->willReturn(['code' => 200]);
        $this->owaspDetailsRepo->method('deleteOwaspDetailsMavenKey')->willReturn(['code' => 200]);

        $this->owaspDetailsRepo->expects($this->once())
            ->method('insertOwaspDetails')
            ->willReturn(['code' => 500, 'erreur' => 'insert details failed']);

        $result = $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertSame(['code' => 500, 'erreur' => 'insert details failed'], $result);
    }

    // ─── 13. owasp_details : forme des lignes persistées depuis classify()['details'] ─

    public function testHappyPathPersistsOwaspDetailsRowsFromClassifierOutput(): void
    {
        $this->stubParams('10');
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);

        $classified = $this->emptyClassification();
        $classified['details'] = [
            2017 => [
                [
                    'rule' => 'java:S2077',
                    'categories' => 'A1',
                    'severity' => 'CRITICAL',
                    'source' => 'core',
                    'component' => self::MAVEN_KEY . ':webapp/Foo.java',
                    'module' => 'frontend',
                    'line' => 10,
                    'message' => 'SQL injection risk',
                ],
            ],
            2021 => [
                [
                    'rule' => 'java:S2077',
                    'categories' => 'A3',
                    'severity' => 'CRITICAL',
                    'source' => 'core',
                    'component' => self::MAVEN_KEY . ':webapp/Foo.java',
                    'module' => 'frontend',
                    'line' => 10,
                    'message' => 'SQL injection risk',
                ],
            ],
        ];
        $this->ruleClassifier->method('classify')->willReturn($classified);

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '2.0.0']]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->method('insertOwasp')->willReturn(['code' => 200]);
        $this->owaspDetailsRepo->method('deleteOwaspDetailsMavenKey')->willReturn(['code' => 200]);

        $captured = [];
        $this->owaspDetailsRepo->expects($this->once())
            ->method('insertOwaspDetails')
            ->with($this->callback(function (array $list) use (&$captured) {
                $captured = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertCount(2, $captured, 'Une ligne par référentiel (2017 et 2021, sonar.version=10 > 8).');

        $this->assertSame(2017, $captured[0]['referential_owasp']);
        $this->assertSame(self::MAVEN_KEY, $captured[0]['maven_key']);
        $this->assertSame('java:S2077', $captured[0]['rule_key']);
        $this->assertSame('A1', $captured[0]['categories']);
        $this->assertSame('CRITICAL', $captured[0]['severity']);
        $this->assertSame('core', $captured[0]['source']);
        $this->assertSame('frontend', $captured[0]['module']);
        $this->assertSame('webapp/Foo.java', $captured[0]['file_path'], 'Préfixe "maven_key:" retiré du component brut.');
        $this->assertSame(10, $captured[0]['line']);
        $this->assertSame('SQL injection risk', $captured[0]['message']);
        $this->assertSame('manual', $captured[0]['mode_collecte']);
        $this->assertSame('admin', $captured[0]['utilisateur_collecte']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $captured[0]['date_version']);
        $this->assertInstanceOf(\DateTimeImmutable::class, $captured[0]['date_enregistrement']);

        $this->assertSame(2021, $captured[1]['referential_owasp']);
        $this->assertSame('A3', $captured[1]['categories']);
    }

    // ─── 14. owasp_details : message/file_path tronqués à 255 caractères avant insertion ─

    public function testTruncatesLongMessageAndFilePathBeforePersistingOwaspDetails(): void
    {
        $this->stubParams('8');
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);

        $longMessage = str_repeat('m', 300);
        $longFilePath = str_repeat('f', 300);

        $classified = $this->emptyClassification();
        $classified['details'][2017] = [
            [
                'rule' => 'findsecbugs:SQL_INJECTION_JPA',
                'categories' => 'A1',
                'severity' => 'CRITICAL',
                'source' => 'findsecbugs',
                'component' => self::MAVEN_KEY . ':' . $longFilePath,
                'module' => 'backend',
                'line' => null,
                'message' => $longMessage,
            ],
        ];
        $this->ruleClassifier->method('classify')->willReturn($classified);

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '1.0']]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->method('insertOwasp')->willReturn(['code' => 200]);
        $this->owaspDetailsRepo->method('deleteOwaspDetailsMavenKey')->willReturn(['code' => 200]);

        $captured = [];
        $this->owaspDetailsRepo->expects($this->once())
            ->method('insertOwaspDetails')
            ->with($this->callback(function (array $list) use (&$captured) {
                $captured = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertCount(1, $captured);
        $this->assertSame(255, strlen($captured[0]['message']), 'owasp_details.message est VARCHAR(255) : tronqué, pas rejeté.');
        $this->assertSame(str_repeat('m', 255), $captured[0]['message']);
        $this->assertSame(255, strlen($captured[0]['file_path']));
        $this->assertSame(str_repeat('f', 255), $captured[0]['file_path']);
        $this->assertNull($captured[0]['line'], 'line reste nullable (règle au niveau fichier, pas de ligne précise).');
    }

    // ─── 15. owasp_details : lignes 2021 jamais persistées si sonar.version = 8 ─

    public function testSkipsOwasp2021DetailRowsWhenSonarVersionIs8(): void
    {
        $this->stubParams('8');
        $this->client->method('httpSonarQube')->willReturn(['code' => 200, 'json' => $this->page([], 0)]);

        $detailRow2017 = [
            'rule' => 'java:S2077', 'categories' => 'A1', 'severity' => 'CRITICAL', 'source' => 'core',
            'component' => self::MAVEN_KEY . ':webapp/Foo.java', 'module' => 'frontend', 'line' => 10, 'message' => 'msg',
        ];
        $detailRow2021 = [
            'rule' => 'java:S2077', 'categories' => 'A3', 'severity' => 'CRITICAL', 'source' => 'core',
            'component' => self::MAVEN_KEY . ':webapp/Foo.java', 'module' => 'frontend', 'line' => 10, 'message' => 'msg',
        ];
        $classified = $this->emptyClassification();
        $classified['details'] = [2017 => [$detailRow2017], 2021 => [$detailRow2021]];
        $this->ruleClassifier->method('classify')->willReturn($classified);

        $this->infoRepo->method('selectInformationProjetVersion')->willReturn(['code' => 200, 'info' => ['date' => '2026-04-22', 'version' => '1.0']]);
        $this->owaspRepo->method('deleteOwaspMavenKey')->willReturn(['code' => 200]);
        $this->owaspRepo->method('insertOwasp')->willReturn(['code' => 200]);
        $this->owaspDetailsRepo->method('deleteOwaspDetailsMavenKey')->willReturn(['code' => 200]);

        $captured = [];
        $this->owaspDetailsRepo->expects($this->once())
            ->method('insertOwaspDetails')
            ->with($this->callback(function (array $list) use (&$captured) {
                $captured = $list;
                return true;
            }))
            ->willReturn(['code' => 200]);

        $this->controller->BatchCollecteOwasp(self::MAVEN_KEY, 'manual', 'admin');

        $this->assertCount(1, $captured, 'classified["details"][2021] existe mais ne doit pas être persisté (sonar.version = 8).');
        $this->assertSame(2017, $captured[0]['referential_owasp']);
    }
}
