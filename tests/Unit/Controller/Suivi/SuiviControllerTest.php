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

namespace App\Tests\Unit\Controller\Suivi;

use App\Controller\Suivi\SuiviController;
use App\Entity\{Historique, ListeProjet, Utilisateur};
use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Repository\{HistoriqueRepository, ListeProjetRepository};
use App\Service\{LanguageDistributionService, PdfExportService};
use App\Service\RapportInsightService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class SuiviControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */   private MockObject $em;
    /** @var TokenStorageInterface&MockObject */    private MockObject $tokenStorage;
    /** @var TokenInterface&MockObject */           private MockObject $token;
    /** @var ParameterBagInterface&MockObject */    private MockObject $params;
    /** @var LoggerInterface&MockObject */          private MockObject $logger;
    /** @var HistoriqueRepository&MockObject */     private MockObject $historiqueRepo;
    /** @var ListeProjetRepository&MockObject */    private MockObject $listeProjetRepo;
    /** @var Environment&MockObject */              private MockObject $twig;
    /** @var FlashBag&MockObject */                 private MockObject $flashBag;
    /** @var RouterInterface&MockObject */          private MockObject $router;
    /** @var Session&MockObject */                  private MockObject $session;

    /** @var UserAgentTrackingFacade&MockObject */  private MockObject $tracking;

    private SuiviController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->historiqueRepo = $this->createMock(HistoriqueRepository::class);
        $this->listeProjetRepo = $this->createMock(ListeProjetRepository::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->router = $this->createMock(RouterInterface::class);
        $this->session = $this->createMock(Session::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

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
                'twig', 'router', 'request_stack', 'parameter_bag', 'security.token_storage',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['router', 1, $this->router],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
            ['security.token_storage', 1, $this->tokenStorage],
        ]);

        /* MODIF 2026-05-07 : ajout 3 services injectés.
         * LanguageDistributionService et RapportInsightService n'ont pas de deps → instance directe.
         * PdfExportService est mockable. */
        $this->controller = new SuiviController(
            $this->em,
            $this->params,
            $this->logger,
            new LanguageDistributionService(),
            $this->createMock(PdfExportService::class),
            new RapportInsightService(),
            $this->tracking,
        );
        $this->controller->setContainer($container);
    }

    /* ============ setSession ============ */

    public function testSetSessionStoresMavenKeyAndRedirects(): void
    {
        $this->session->expects($this->once())
            ->method('set')
            ->with('maven_key', 'fr.ma-moulinette:ma-moulinette');

        $this->router->expects($this->once())
            ->method('generate')
            ->with('suivi', [], $this->anything())
            ->willReturn('/suivi');

        $request = new Request(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);
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
        $this->token->method('getUser')->willReturn($user);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

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
        $this->session->method('get')->willReturn('fr.ma-moulinette:ma-moulinette');

        // Empty groupes via getListeGroupeFonctionnel
        $user = $this->makeUser([]);
        $this->token->method('getUser')->willReturn($user);

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
        $this->session->method('get')->willReturn('fr.ma-moulinette:ma-moulinette');

        $user = $this->makeUser(['TeamA']);
        $this->token->method('getUser')->willReturn($user);

        $this->listeProjetRepo->expects($this->once())
            ->method('selectListeProjetByGroupe')
            ->willReturn([
                'code' => 200,
                'liste' => [['id' => 'other.project', 'nom' => 'Other']], // does not include fr.ma-moulinette:ma-moulinette
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
        $this->session->method('get')->willReturn('fr.ma-moulinette:ma-moulinette');

        $user = $this->makeUser(['TeamA']);
        $this->token->method('getUser')->willReturn($user);

        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.ma-moulinette:ma-moulinette']],
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
        $this->session->method('get')->willReturn('fr.ma-moulinette:ma-moulinette');

        $user = $this->makeUser(['TeamA']);
        $this->token->method('getUser')->willReturn($user);

        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.ma-moulinette:ma-moulinette']],
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

        /* MODIF 2026-05-07 : init [] (intelephense by-ref). */
        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('suivi/index.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->controller->suivi($request);

        $this->assertSame('App', $capturedCtx['nom']);
        $this->assertSame('fr.ma-moulinette:ma-moulinette', $capturedCtx['mavenKey']);
        $this->assertSame('[2]', $capturedCtx['data1']);
        $this->assertSame('[1]', $capturedCtx['data2']);
        $this->assertSame('[3]', $capturedCtx['data3']);
    }

    public function testSuiviFlashesAlertWhenFetchDataThrows(): void
    {
        $request = new Request();
        $request->setSession($this->session);
        $this->session->method('get')->willReturn('fr.ma-moulinette:ma-moulinette');

        $user = $this->makeUser(['TeamA']);
        $this->token->method('getUser')->willReturn($user);

        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.ma-moulinette:ma-moulinette']],
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
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>throw</html>');

        $this->controller->suivi($request);
    }

    private function makeUser(array $groupes): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel('user@ma-moulinette.fr');
        $u->setListeGroupeFonctionnel($groupes);
        return $u;
    }
}
