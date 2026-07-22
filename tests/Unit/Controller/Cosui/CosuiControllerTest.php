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

namespace App\Tests\Unit\Controller\Cosui;

use App\Controller\Cosui\CosuiController;
use App\Entity\{ListeProjet, Utilisateur};
use App\Repository\ListeProjetRepository;
use App\Service\ProjetCosuiService;
use App\Service\UserAgent\UserAgentTrackingFacade;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\{Request, RequestStack};
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
/* MODIF 2026-05-05 : retrait des mocks
 * pointant vers UserAgentTrackingFacade / UserAgentReportingService /
 * LogArchiveService (classes supprimées de src/). */
class CosuiControllerTest extends TestCase
{
    // Valid token: encoding "1234567890|fr.ma-moulinette:ma-moulinette" with ROT13+base64
    private string $validToken;

    /** @var EntityManagerInterface&MockObject */   private MockObject $em;
    /** @var LoggerInterface&MockObject */          private MockObject $logger;
    /** @var ProjetCosuiService&MockObject */       private MockObject $cosuiService;
    /** @var ListeProjetRepository&MockObject */    private MockObject $listeProjetRepo;
    /** @var Environment&MockObject */              private MockObject $twig;
    /** @var FlashBag&MockObject */                 private MockObject $flashBag;
    /** @var TokenStorageInterface&MockObject */    private MockObject $tokenStorage;
    /** @var TokenInterface&MockObject */           private MockObject $token;

    /** @var UserAgentTrackingFacade&MockObject */  private MockObject $tracking;

    private CosuiController $controller;

    protected function setUp(): void
    {
        $this->validToken = str_rot13(base64_encode('1234567890|fr.ma-moulinette:ma-moulinette'));

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->cosuiService = $this->createMock(ProjetCosuiService::class);
        $this->listeProjetRepo = $this->createMock(ListeProjetRepository::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);

        $this->em->method('getRepository')->willReturnMap([
            [ListeProjet::class, $this->listeProjetRepo],
        ]);

        // NB : pas de valeur par défaut pour getUser()/selectListeProjetByGroupe()
        // ici — chaque test qui atteint le contrôle de périmètre les configure
        // explicitement (un seul stub par mock évite toute ambiguïté d'ordre).

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['twig', 'request_stack', 'security.token_storage'], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['request_stack', 1, $requestStack],
            ['security.token_storage', 1, $this->tokenStorage],
        ]);

        $this->controller = new CosuiController($this->em, $this->logger, $this->cosuiService, $this->tracking);
        $this->controller->setContainer($container);
    }

    public function testProjetCosuiFlashesInfoWhenTokenEmpty(): void
    {
        // MODIF 2026-07-22 : token absent = navigation sans contexte (pas une
        // anomalie), distingué désormais d'un token invalide (test suivant) —
        // message info et statut 200, plus 400.
        $this->cosuiService->expects($this->once())
            ->method('initialRender')
            ->willReturn(['maven_key' => 'NC']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'info'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('projet/cosui.html.twig', $this->anything())
            ->willReturn('<html>no-token</html>');

        $response = $this->controller->projetCosui(new Request());
        $this->assertSame('<html>no-token</html>', $response->getContent());
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testProjetCosuiFlashesErrorOnInvalidToken(): void
    {
        $this->cosuiService->method('initialRender')->willReturn([]);

        // "only-one-part" (no |) → decode returns null
        $badToken = str_rot13(base64_encode('only-one-part'));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>bad</html>');

        $this->controller->projetCosui(new Request(['token' => $badToken]));
    }

    public function testProjetCosuiFlashesWarning404WhenNoGroupeFonctionnel(): void
    {
        // MODIF 2026-07-16 : verrouille le fix — COSUI doit désormais refuser
        // l'accès si l'utilisateur n'a aucun groupe fonctionnel, comme Suivi.
        $this->cosuiService->method('initialRender')->willReturn([]);
        $this->token->method('getUser')->willReturn($this->makeUser([]));

        $this->cosuiService->expects($this->never())->method('generateRender');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>no-groupe</html>');

        $response = $this->controller->projetCosui(new Request(['token' => $this->validToken]));
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testProjetCosuiFlashesWarning406WhenProjectNotInGroupe(): void
    {
        // MODIF 2026-07-16 : verrouille le fix — un projet hors du groupe fonctionnel
        // de l'utilisateur ne doit plus permettre d'afficher COSUI.
        $this->cosuiService->method('initialRender')->willReturn([]);
        $this->token->method('getUser')->willReturn($this->makeUser(['TeamA']));
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.autre:projet-different']],
        ]);

        $this->cosuiService->expects($this->never())->method('generateRender');

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>406</html>');

        $response = $this->controller->projetCosui(new Request(['token' => $this->validToken]));
        $this->assertSame(406, $response->getStatusCode());
    }

    public function testProjetCosuiFlashesWhenGenerateRenderReturnsError(): void
    {
        $this->cosuiService->method('initialRender')->willReturn([]);
        $this->token->method('getUser')->willReturn($this->makeUser(['TeamA']));
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.ma-moulinette:ma-moulinette']],
        ]);
        $this->cosuiService->expects($this->once())
            ->method('generateRender')
            ->with('fr.ma-moulinette:ma-moulinette')
            ->willReturn([
                'code' => 404,
                'type' => 'warning',
                'message' => 'not found',
                'trace' => 'missing',
            ]);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>404</html>');

        $response = $this->controller->projetCosui(new Request(['token' => $this->validToken]));
        $this->assertSame(404, $response->getStatusCode());
    }

    public function testProjetCosuiFlashesCriticalOnException(): void
    {
        $this->cosuiService->method('initialRender')->willReturn([]);
        $this->token->method('getUser')->willReturn($this->makeUser(['TeamA']));
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.ma-moulinette:ma-moulinette']],
        ]);
        $this->cosuiService->method('generateRender')
            ->willThrowException(new \RuntimeException('boom'));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'critical' && $v['trace'] === 'boom'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>exc</html>');

        $this->controller->projetCosui(new Request(['token' => $this->validToken]));
    }

    public function testProjetCosuiClampsNonHttpCodeTo500(): void
    {
        // MODIF 2026-07-16 : un code d'erreur métier venant du repository (SQLSTATE, ex. 42703)
        // n'est pas un code HTTP valide — il doit être ramené à 500 plutôt que renvoyé tel quel.
        $this->cosuiService->method('initialRender')->willReturn([]);
        $this->token->method('getUser')->willReturn($this->makeUser(['TeamA']));
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.ma-moulinette:ma-moulinette']],
        ]);
        $this->cosuiService->expects($this->once())
            ->method('generateRender')
            ->willReturn([
                'code' => 42703,
                'type' => 'error',
                'message' => 'colonne inconnue',
                'trace' => 'SQLSTATE[42703]',
            ]);

        $this->twig->expects($this->once())->method('render')->willReturn('<html>500</html>');

        $response = $this->controller->projetCosui(new Request(['token' => $this->validToken]));
        $this->assertSame(500, $response->getStatusCode());
    }

    public function testProjetCosuiHappyPath(): void
    {
        $this->cosuiService->method('initialRender')->willReturn([]);
        $this->token->method('getUser')->willReturn($this->makeUser(['TeamA']));
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'fr.ma-moulinette:ma-moulinette']],
        ]);
        $this->cosuiService->method('generateRender')->willReturn([
            'code' => 200,
            'data' => ['something'],
            'projet' => 'fr.ma-moulinette:ma-moulinette',
        ]);

        $this->flashBag->expects($this->never())->method('add');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('projet/cosui.html.twig', $this->callback(fn($ctx) => $ctx['code'] === 200))
            ->willReturn('<html>ok</html>');

        $this->controller->projetCosui(new Request(['token' => $this->validToken]));
    }

    private function makeUser(array $groupes): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel('user@ma-moulinette.fr');
        $u->setListeGroupeFonctionnel($groupes);
        return $u;
    }
}
