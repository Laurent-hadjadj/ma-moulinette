<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Suivi;

use App\Controller\Suivi\SuiviController;
use App\Entity\Historique;
use App\Entity\ListeProjet;
use App\Entity\Utilisateur;
use App\Repository\HistoriqueRepository;
use App\Repository\ListeProjetRepository;
use App\Service\UserAgentTrackingFacade;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class SuiviControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */   private MockObject $em;
    /** @var Security&MockObject */                 private MockObject $security;
    /** @var ParameterBagInterface&MockObject */    private MockObject $params;
    /** @var LoggerInterface&MockObject */          private MockObject $logger;
    /** @var UserAgentTrackingFacade&MockObject */  private MockObject $tracking;
    /** @var HistoriqueRepository&MockObject */     private MockObject $historiqueRepo;
    /** @var ListeProjetRepository&MockObject */    private MockObject $listeProjetRepo;
    /** @var Environment&MockObject */              private MockObject $twig;
    /** @var FlashBag&MockObject */                 private MockObject $flashBag;
    /** @var RouterInterface&MockObject */          private MockObject $router;
    /** @var Session&MockObject */                  private MockObject $session;

    private SuiviController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->historiqueRepo = $this->createMock(HistoriqueRepository::class);
        $this->listeProjetRepo = $this->createMock(ListeProjetRepository::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->session = $this->createMock(Session::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
            ['nombre.favori', 10],
        ]);

        $this->em->method('getRepository')->willReturnMap([
            [Historique::class, $this->historiqueRepo],
            [ListeProjet::class, $this->listeProjetRepo],
        ]);

        $this->session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($this->session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'twig', 'router', 'request_stack', 'parameter_bag',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['router', 1, $this->router],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new SuiviController(
            $this->em, $this->security, $this->params, $this->logger, $this->tracking
        );
        $this->controller->setContainer($container);
    }

    /* ============ setSession ============ */

    public function testSetSessionStoresMavenKeyAndRedirects(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with('maven_key', 'com.acme:app');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('suivi', [], $this->anything())
            ->willReturn('/suivi');

        $request = new Request(['maven_key' => 'com.acme:app']);
        $request->setSession($this->session);

        $response = $this->controller->setSession($request);

        $this->assertSame('/suivi', $response->headers->get('Location'));
    }

    /* ============ suivi ============ */

    public function testSuiviFlashesAlertWhenMavenKeyMissing(): void
    {
        $request = new Request();
        $request->setSession($this->session);
        $this->session->method('get')->willReturn(null);

        $user = $this->makeUser([]);
        $this->security->method('getUser')->willReturn($user);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'alert'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('suivi/index.html.twig', $this->anything())
            ->willReturn('<html>no-key</html>');

        $this->controller->suivi($request);
    }

    public function testSuiviFlashesWarningWhenNoGroupes(): void
    {
        $request = new Request();
        $request->setSession($this->session);
        $this->session->method('get')->willReturn('com.acme:app');

        // Empty groupes via getListeGroupeFonctionnel
        $user = $this->makeUser([]);
        $this->security->method('getUser')->willReturn($user);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>no-team</html>');

        $this->controller->suivi($request);
    }

    public function testSuiviFlashesWarningWhenProjectNotInListeProjet(): void
    {
        $request = new Request();
        $request->setSession($this->session);
        $this->session->method('get')->willReturn('com.acme:app');

        $user = $this->makeUser(['TeamA']);
        $this->security->method('getUser')->willReturn($user);

        $this->listeProjetRepo->expects($this->once())
            ->method('selectListeProjetByGroupe')
            ->willReturn([
                'code' => 200,
                'liste' => [['id' => 'other.project', 'nom' => 'Other']], // does not include com.acme:app
            ]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>not-yours</html>');

        $this->controller->suivi($request);
    }

    public function testSuiviFlashesWarningWhenHistoriqueEmpty(): void
    {
        $request = new Request();
        $request->setSession($this->session);
        $this->session->method('get')->willReturn('com.acme:app');

        $user = $this->makeUser(['TeamA']);
        $this->security->method('getUser')->willReturn($user);

        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'com.acme:app']],
        ]);
        $this->historiqueRepo->expects($this->once())
            ->method('countHistoriqueProjet')
            ->willReturn(['code' => 200, 'nombre' => 0]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->historiqueRepo->expects($this->never())->method('selectUnionHistoriqueProjet');

        $this->twig->expects($this->once())->method('render')->willReturn('<html>no-hist</html>');

        $this->controller->suivi($request);
    }

    public function testSuiviHappyPath(): void
    {
        $request = new Request();
        $request->setSession($this->session);
        $this->session->method('get')->willReturn('com.acme:app');

        $user = $this->makeUser(['TeamA']);
        $this->security->method('getUser')->willReturn($user);

        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'com.acme:app']],
        ]);
        $this->historiqueRepo->method('countHistoriqueProjet')->willReturn([
            'code' => 200, 'nombre' => 5,
        ]);
        $this->historiqueRepo->method('selectUnionHistoriqueProjet')->willReturn([
            'code' => 200,
            'request' => [['nom' => 'App']],
        ]);
        $this->historiqueRepo->method('selectUnionHistoriqueMesure')->willReturn([
            'code' => 200, 'request' => [],
        ]);
        $this->historiqueRepo->method('selectUnionHistoriqueAnomalie')->willReturn([
            'code' => 200, 'request' => [],
        ]);
        $this->historiqueRepo->method('selectUnionHistoriqueDetails')->willReturn([
            'code' => 200, 'request' => [],
        ]);
        $this->historiqueRepo->method('selectHistoriqueAnomalieGraphique')->willReturn([
            'code' => 200,
            'request' => [
                ['bug' => 2, 'sec' => 1, 'code_smell' => 3, 'date' => '2026-04-10'],
            ],
        ]);

        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with('suivi/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'success'));

        $this->controller->suivi($request);

        $this->assertSame('App', $capturedCtx['nom']);
        $this->assertSame('com.acme:app', $capturedCtx['mavenKey']);
        $this->assertSame('[2]', $capturedCtx['data1']);
        $this->assertSame('[1]', $capturedCtx['data2']);
        $this->assertSame('[3]', $capturedCtx['data3']);
    }

    public function testSuiviFlashesAlertWhenFetchDataThrows(): void
    {
        $request = new Request();
        $request->setSession($this->session);
        $this->session->method('get')->willReturn('com.acme:app');

        $user = $this->makeUser(['TeamA']);
        $this->security->method('getUser')->willReturn($user);

        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'com.acme:app']],
        ]);
        $this->historiqueRepo->method('countHistoriqueProjet')->willReturn([
            'code' => 200, 'nombre' => 5,
        ]);
        // The first fetchData throws
        $this->historiqueRepo->method('selectUnionHistoriqueProjet')->willReturn([
            'code' => 500, 'erreur' => 'boom',
        ]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'alert'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>throw</html>');

        $this->controller->suivi($request);
    }

    private function makeUser(array $groupes): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel('user@example.com');
        $u->setListeGroupeFonctionnel($groupes);
        return $u;
    }
}
