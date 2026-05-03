<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Projet;

use App\Controller\Projet\ProjetController;
use App\Entity\Historique;
use App\Entity\Utilisateur;
use App\Repository\HistoriqueRepository;
use App\Service\MesProjets;
use App\Service\UserAgentTrackingFacade;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class ProjetControllerTest extends TestCase
{
    /** @var MesProjets&MockObject */                private MockObject $mesProjets;
    /** @var TokenStorageInterface&MockObject */     private MockObject $tokenStorage;
    /** @var TokenInterface&MockObject */            private MockObject $token;
    /** @var EntityManagerInterface&MockObject */    private MockObject $em;
    /** @var ParameterBagInterface&MockObject */     private MockObject $params;
    /** @var LoggerInterface&MockObject */           private MockObject $logger;
    /** @var UserAgentTrackingFacade&MockObject */   private MockObject $tracking;
    /** @var HistoriqueRepository&MockObject */      private MockObject $historiqueRepo;
    /** @var Environment&MockObject */               private MockObject $twig;
    /** @var FlashBag&MockObject */                  private MockObject $flashBag;

    private ProjetController $controller;

    protected function setUp(): void
    {
        $this->mesProjets = $this->createMock(MesProjets::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->historiqueRepo = $this->createMock(HistoriqueRepository::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
        ]);

        $this->em->method('getRepository')->willReturn($this->historiqueRepo);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'twig', 'request_stack', 'parameter_bag', 'security.token_storage',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
            ['security.token_storage', 1, $this->tokenStorage],
        ]);

        $this->controller = new ProjetController(
            $this->mesProjets, $this->em, $this->params, $this->logger, $this->tracking
        );
        $this->controller->setContainer($container);
    }

    public function testIndexRendersProjetPage(): void
    {
        $this->tracking->expects($this->once())->method('track')->with('PROJET');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('projet/index.html.twig', $this->anything())
            ->willReturn('<html>projet</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>projet</html>', $response->getContent());
    }

    public function testMesProjetsFlashesWarningWhenNoGroupes(): void
    {
        $user = $this->makeUser([]);
        $this->token->method('getUser')->willReturn($user);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->mesProjets->expects($this->never())->method('liste');

        $this->twig->expects($this->once())->method('render')->willReturn('<html>no-team</html>');

        $this->controller->mesProjets();
    }

    public function testMesProjetsFlashesWarningWhenNoProjets(): void
    {
        $user = $this->makeUser(['TeamA']);
        $this->token->method('getUser')->willReturn($user);

        $this->mesProjets->expects($this->once())
            ->method('liste')
            ->with(['TeamA'])
            ->willReturn(['code' => 406, 'erreur' => 'no tag']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning' && $v['debug'] === 'no tag'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>no-proj</html>');

        $this->controller->mesProjets();
    }

    public function testMesProjetsFlashesAlertWhenRepoFails(): void
    {
        $user = $this->makeUser(['TeamA']);
        $this->token->method('getUser')->willReturn($user);

        $this->mesProjets->method('liste')->willReturn([
            'code' => 200, 'projets' => [['id' => 'com.acme:app']],
        ]);

        $this->historiqueRepo->expects($this->once())
            ->method('selectHistoriqueIndicateurs')
            ->willReturn(['code' => 500, 'erreur' => 'db down']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'alert'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>fail</html>');

        $this->controller->mesProjets();
    }

    public function testMesProjetsHappyPath(): void
    {
        $user = $this->makeUser(['TeamA']);
        $this->token->method('getUser')->willReturn($user);

        $this->mesProjets->method('liste')->willReturn([
            'code' => 200,
            'projets' => [['id' => 'com.acme:app'], ['id' => 'com.acme:other']],
        ]);
        $this->historiqueRepo->method('selectHistoriqueIndicateurs')->willReturn([
            'code' => 200, 'indicateur' => [['nom' => 'App', 'bug' => 2]],
        ]);

        $capturedCtx = null;
        $this->twig->expects($this->once())
            ->method('render')
            ->with('projet/mes-projets.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->flashBag->expects($this->never())->method('add');

        $this->controller->mesProjets();

        $this->assertCount(1, $capturedCtx['liste_projet']);
        $this->assertSame('App', $capturedCtx['liste_projet'][0]['nom']);
    }

    private function makeUser(array $groupes): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel('u@x');
        $u->setListeGroupeFonctionnel($groupes);
        return $u;
    }
}
