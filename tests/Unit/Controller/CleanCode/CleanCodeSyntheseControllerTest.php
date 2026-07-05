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

namespace App\Tests\Unit\Controller\CleanCode;

/* MODIF 2026-05-19 : tests unitaires pour
 * CleanCodeSyntheseController (6 scénarios : pas d'équipe / pas de projets /
 * pas d'historique / happy path index / pdf no-data redirect / pdf happy path). */

use App\Controller\CleanCode\CleanCodeSyntheseController;
use App\Entity\{Historique, Utilisateur};
use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Repository\HistoriqueRepository;
use App\Service\{MesProjets, PdfExportService};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class CleanCodeSyntheseControllerTest extends TestCase
{
    /** @var MesProjets&MockObject */               private MockObject $mesProjets;
    /** @var EntityManagerInterface&MockObject */   private MockObject $em;
    /** @var HistoriqueRepository&MockObject */     private MockObject $historiqueRepo;
    /** @var ParameterBagInterface&MockObject */    private MockObject $params;
    /** @var LoggerInterface&MockObject */          private MockObject $logger;
    /** @var PdfExportService&MockObject */         private MockObject $pdfExportService;
    /** @var TokenStorageInterface&MockObject */    private MockObject $tokenStorage;
    /** @var TokenInterface&MockObject */           private MockObject $token;
    /** @var Environment&MockObject */              private MockObject $twig;
    /** @var FlashBag&MockObject */                 private MockObject $flashBag;
    /** @var RouterInterface&MockObject */          private MockObject $router;

    /** @var UserAgentTrackingFacade&MockObject */  private MockObject $tracking;

    private CleanCodeSyntheseController $controller;

    protected function setUp(): void
    {
        $this->mesProjets       = $this->createMock(MesProjets::class);
        $this->em               = $this->createMock(EntityManagerInterface::class);
        $this->historiqueRepo   = $this->createMock(HistoriqueRepository::class);
        $this->params           = $this->createMock(ParameterBagInterface::class);
        $this->logger           = $this->createMock(LoggerInterface::class);
        $this->pdfExportService = $this->createMock(PdfExportService::class);
        $this->tokenStorage     = $this->createMock(TokenStorageInterface::class);
        $this->token            = $this->createMock(TokenInterface::class);
        $this->twig             = $this->createMock(Environment::class);
        $this->flashBag         = $this->createMock(FlashBag::class);
        $this->router           = $this->createMock(RouterInterface::class);
        $this->tracking         = $this->createMock(UserAgentTrackingFacade::class);

        $this->tokenStorage->method('getToken')->willReturn($this->token);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise',          'logo.png'],
            ['marque.entreprise.short',  'MM'],
            ['marque.entreprise.long',   'Ma Moulinette'],
            ['environnement',            'test'],
            ['version',                  '2.0.0'],
        ]);

        /* MODIF 2026-05-24 : suppression du .with()
         * sur un stub — with() sans expects() est déprécié en PHPUnit 13.1 (erreur en 14). */
        $this->em->method('getRepository')
            ->willReturn($this->historiqueRepo);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'twig', 'router', 'request_stack', 'parameter_bag', 'security.token_storage',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['twig',                   1, $this->twig],
            ['router',                 1, $this->router],
            ['request_stack',          1, $requestStack],
            ['parameter_bag',          1, $this->params],
            ['security.token_storage', 1, $this->tokenStorage],
        ]);

        $this->controller = new CleanCodeSyntheseController(
            $this->params,
            $this->mesProjets,
            $this->em,
            $this->logger,
            $this->pdfExportService,
            $this->tracking
        );
        $this->controller->setContainer($container);
    }

    // ─── helpers ─────────────────────────────────────────────────────────────

    private function makeUser(array $groupes): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel('user@example.com');
        $u->setListeGroupeFonctionnel($groupes);
        return $u;
    }

    private function buildRow(string $mavenKey = 'com.acme:app'): array
    {
        return [
            'maven_key'                      => $mavenKey,
            'project_name'                   => 'Mon App',
            'version'                        => '1.0.0',
            'date_enregistrement'            => '2026-05-01 10:00:00',
            'alert_status'                   => 'OK',
            'note_mnt'                       => '1',
            'note_fia'                       => '2',
            'note_sec'                       => '3',
            'note_hsp'                       => '1',
            'note_cpx'                       => '2',
            'note_cpx_cog'                   => '3',
            'violations'                     => 100,
            'bugs'                           => 2,
            'vulnerabilities'                => 1,
            'coverage'                       => '75.5',
            'duplicated_lines_density'       => '3.2',
            'cc_consistent'                  => 40,
            'cc_intentional'                 => 30,
            'cc_adaptable'                   => 20,
            'cc_responsible'                 => 10,
            'impact_blocker'                 => 2,
            'impact_high'                    => 5,
            'impact_medium'                  => 10,
            'impact_low'                     => 20,
            'impact_info'                    => 8,
            'owasp_top10'                    => 3,
        ];
    }

    // ─── 1. Pas d'équipe → flash 404 ─────────────────────────────────────────

    public function testIndexFlashesWarningWhenUserHasNoGroupes(): void
    {
        $this->token->method('getUser')->willReturn($this->makeUser([]));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning' && str_contains($v['message'], '404')));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('clean-code/synthese.html.twig', $this->anything())
            ->willReturn('<html>no-team</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>no-team</html>', $response->getContent());
    }

    // ─── 2. Pas de projets dans le portefeuille → flash 406 ──────────────────

    public function testIndexFlashesWarningWhenNoProjectsInPortfolio(): void
    {
        $this->token->method('getUser')->willReturn($this->makeUser(['TeamA']));

        $this->mesProjets->expects($this->once())
            ->method('liste')
            ->willReturn(['code' => 406, 'projets' => []]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning' && str_contains($v['message'], '406')));

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>no-proj</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>no-proj</html>', $response->getContent());
    }

    // ─── 3. Pas de données clean code dans l'historique → flash 503 ──────────

    public function testIndexFlashesWarningWhenNoHistoriqueData(): void
    {
        $this->token->method('getUser')->willReturn($this->makeUser(['TeamA']));

        $this->mesProjets->method('liste')->willReturn([
            'code' => 200, 'projets' => [['id' => 'com.acme:app']],
        ]);
        $this->historiqueRepo->method('selectHistoriqueCleanCodeSynthese')
            ->willReturn(['code' => 200, 'liste' => []]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())
            ->method('render')
            ->willReturn('<html>no-data</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>no-data</html>', $response->getContent());
    }

    // ─── 4. Happy path : render avec projets calculés ────────────────────────

    public function testIndexRendersWithCalculatedProjetsOnHappyPath(): void
    {
        $this->token->method('getUser')->willReturn($this->makeUser(['TeamA']));

        $this->mesProjets->method('liste')->willReturn([
            'code' => 200, 'projets' => [['id' => 'com.acme:app']],
        ]);
        $this->historiqueRepo->method('selectHistoriqueCleanCodeSynthese')
            ->willReturn(['code' => 200, 'liste' => [$this->buildRow()]]);

        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'clean-code/synthese.html.twig',
                $this->callback(function (array $ctx) use (&$capturedCtx) {
                    $capturedCtx = $ctx;
                    return true;
                })
            )
            ->willReturn('<html>ok</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>ok</html>', $response->getContent());
        $this->assertCount(1, $capturedCtx['projets']);
        $this->assertSame('Mon App', $capturedCtx['projets'][0]['project_name']);
        $this->assertSame('A', $capturedCtx['projets'][0]['note_mnt']);
        $this->assertSame('Périmètre : TeamA', $capturedCtx['scope_label']);
        $this->assertIsFloat($capturedCtx['projets'][0]['risk_score']);
        $this->assertContains($capturedCtx['projets'][0]['risk_level'], ['critical', 'high', 'medium', 'low']);
    }

    // ─── 5. PDF : pas de données → redirect ──────────────────────────────────

    public function testPdfRedirectsWhenNoData(): void
    {
        $this->token->method('getUser')->willReturn($this->makeUser([]));

        /* MODIF 2026-05-24 : expects() requis avant with(). */
        $this->router->expects($this->once())
            ->method('generate')
            ->with('clean_code_synthese', [], $this->anything())
            ->willReturn('/clean-code/synthese');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->anything());

        $response = $this->controller->pdf();

        $this->assertSame(302, $response->getStatusCode());
        $this->assertStringContainsString('/clean-code/synthese', $response->headers->get('Location') ?? '');
    }

    // ─── 6. PDF happy path → binaire PDF ────────────────────────────────────

    public function testPdfReturnsPdfResponseOnHappyPath(): void
    {
        $this->token->method('getUser')->willReturn($this->makeUser(['TeamA']));

        $this->mesProjets->method('liste')->willReturn([
            'code' => 200, 'projets' => [['id' => 'com.acme:app']],
        ]);
        $this->historiqueRepo->method('selectHistoriqueCleanCodeSynthese')
            ->willReturn(['code' => 200, 'liste' => [$this->buildRow()]]);

        $this->pdfExportService->expects($this->once())
            ->method('generateCleanCodeSynthesePdf')
            ->willReturn('%PDF-1.4 fake-pdf-content');

        $response = $this->controller->pdf();

        $this->assertSame(200, $response->getStatusCode());
        $this->assertSame('application/pdf', $response->headers->get('Content-Type'));
        $this->assertStringStartsWith('%PDF-', $response->getContent());
    }
}
