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

namespace App\Tests\Unit\Controller\Repartition;

use App\Controller\Batch\BatchCollecteRepartitionController;
use App\Controller\Repartition\RepartitionController;
use App\Service\UserAgent\UserAgentTrackingFacade;
use App\Entity\Utilisateur;
use App\Repository\RepartitionRepository;
use App\Service\ExtractName;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\{Request,RequestStack};
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class RepartitionControllerTest extends TestCase
{
    // Valid token encoding "1234567890|com.acme:app" (pipe-separated)
    // We pre-compute: str_rot13(base64_encode("1234567890|com.acme:app"))
    private string $validToken;

    /** @var EntityManagerInterface&MockObject */              private MockObject $em;
    /** @var ExtractName&MockObject */                         private MockObject $extractName;
    /** @var ParameterBagInterface&MockObject */               private MockObject $params;
    /** @var TokenStorageInterface&MockObject */               private MockObject $tokenStorage;
    /** @var TokenInterface&MockObject */                      private MockObject $token;
    /** @var BatchCollecteRepartitionController&MockObject */  private MockObject $batchCollecte;
    /** @var LoggerInterface&MockObject */                     private MockObject $logger;
    /** @var RepartitionRepository&MockObject */               private MockObject $repo;
    /** @var Environment&MockObject */                         private MockObject $twig;
    /** @var FlashBag&MockObject */                            private MockObject $flashBag;
    /** @var AuthorizationCheckerInterface&MockObject */       private MockObject $authChecker;

    /** @var UserAgentTrackingFacade&MockObject */             private MockObject $tracking;

    private RepartitionController $controller;

    protected function setUp(): void
    {
        $this->validToken = str_rot13(base64_encode('1234567890|com.acme:app'));

        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->extractName = $this->createMock(ExtractName::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);
        $this->batchCollecte = $this->createMock(BatchCollecteRepartitionController::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repo = $this->createMock(RepartitionRepository::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
        ]);

        $this->em->method('getRepository')->willReturn($this->repo);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'twig', 'security.authorization_checker', 'security.token_storage', 'request_stack', 'parameter_bag',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['security.authorization_checker', 1, $this->authChecker],
            ['security.token_storage', 1, $this->tokenStorage],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new RepartitionController(
            $this->em,
            $this->extractName,
            $this->params,
            $this->batchCollecte,
            $this->logger,
            $this->tracking
        );
        $this->controller->setContainer($container);
    }

    public function testRepartitionThrowsAccessDeniedWhenUserMissing(): void
    {
        $this->token->method('getUser')->willReturn(null);

        $this->expectException(\Symfony\Component\Security\Core\Exception\AccessDeniedException::class);

        $this->controller->repartition(new Request());
    }

    public function testRepartitionFlashes400WhenTokenEmpty(): void
    {
        $user = $this->makeUser();
        $this->token->method('getUser')->willReturn($user);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())
            ->method('render')
            ->with('projet/repartition-module.html.twig', $this->callback(fn($ctx) => $ctx['maven_key'] === 'N.C'))
            ->willReturn('<html>no-token</html>');

        $response = $this->controller->repartition(new Request());
        $this->assertSame('<html>no-token</html>', $response->getContent());
    }

    public function testRepartitionFlashes403WhenNoCollecteRole(): void
    {
        $user = $this->makeUser();
        $this->token->method('getUser')->willReturn($user);
        $this->authChecker->method('isGranted')->willReturn(false);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'warning'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>no-role</html>');

        $this->controller->repartition(new Request(['token' => $this->validToken]));
    }

    public function testRepartitionFlashes400OnInvalidTokenDecoding(): void
    {
        $user = $this->makeUser();
        $this->token->method('getUser')->willReturn($user);
        $this->authChecker->method('isGranted')->willReturn(true);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error'));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>bad-token</html>');

        // Token with only one part (no pipe) → decodeToken returns null
        $badToken = str_rot13(base64_encode('only-one-part'));
        $this->controller->repartition(new Request(['token' => $badToken]));
    }

    public function testRepartitionFlashesAlertWhenBatchFails(): void
    {
        $user = $this->makeUser();
        $this->token->method('getUser')->willReturn($user);
        $this->authChecker->method('isGranted')->willReturn(true);

        // BUG → fail
        $this->batchCollecte->expects($this->once())
            ->method('CollecteRepartitionModule')
            ->willReturn(['code' => 500, 'erreur' => 'fail']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error' && str_contains($v['message'], 'collecte')));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>batch-fail</html>');

        $this->controller->repartition(new Request(['token' => $this->validToken]));
    }

    public function testRepartitionHappyPath(): void
    {
        $user = $this->makeUser();
        $this->token->method('getUser')->willReturn($user);
        $this->authChecker->method('isGranted')->willReturn(true);

        // 3 categories: BUG / VULNERABILITY / CODE_SMELL
        $this->batchCollecte->expects($this->exactly(3))
            ->method('CollecteRepartitionModule')
            ->willReturn([
                'code' => 200,
                'blocker' => 1, 'critical' => 2, 'major' => 3, 'minor' => 4, 'info' => 5,
            ]);

        $this->extractName->expects($this->once())
            ->method('extractNameFromMavenKey')
            ->with('com.acme:app')
            ->willReturn('app');

        $this->repo->expects($this->once())
            ->method('selectOrUpdateRepartitionInitial')
            ->willReturn(['code' => 200]);

        $this->flashBag->expects($this->never())->method('add');

        /* MODIF 2026-05-07 [tests-validators] : init [] (intelephense by-ref). */
        $capturedCtx = [];
        $this->twig->expects($this->once())
            ->method('render')
            ->with('projet/repartition-module.html.twig', $this->callback(function ($ctx) use (&$capturedCtx) {
                $capturedCtx = $ctx;
                return true;
            }))
            ->willReturn('<html>ok</html>');

        $this->controller->repartition(new Request(['token' => $this->validToken]));

        $this->assertSame('app', $capturedCtx['mon_application']);
        $this->assertSame('com.acme:app', $capturedCtx['maven_key']);
        $this->assertSame('initial', $capturedCtx['statut']);
    }

    public function testRepartitionFlashesWhenInsertFails(): void
    {
        $user = $this->makeUser();
        $this->token->method('getUser')->willReturn($user);
        $this->authChecker->method('isGranted')->willReturn(true);

        $this->batchCollecte->method('CollecteRepartitionModule')->willReturn([
            'code' => 200,
            'blocker' => 0, 'critical' => 0, 'major' => 0, 'minor' => 0, 'info' => 0,
        ]);
        $this->extractName->method('extractNameFromMavenKey')->willReturn('app');

        $this->repo->expects($this->once())
            ->method('selectOrUpdateRepartitionInitial')
            ->willReturn(['code' => 500, 'erreur' => 'insert fail']);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('notice', $this->callback(fn($v) => $v['type'] === 'error' && str_contains($v['debug'], 'insert fail')));

        $this->twig->expects($this->once())->method('render')->willReturn('<html>insert-fail</html>');

        $this->controller->repartition(new Request(['token' => $this->validToken]));
    }

    private function makeUser(): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel('user@example.com');
        $u->setPrenom('User');
        $u->setNom('Test');
        return $u;
    }
}
