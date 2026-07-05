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

namespace App\Tests\Unit\Controller\Auth;

use App\Controller\Auth\ChangeMeController;
use App\Entity\Utilisateur;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\{TokenInterface, UsernamePasswordToken};
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;

#[AllowMockObjectsWithoutExpectations]
class ChangeMeControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */         private MockObject $em;
    /** @var Security&MockObject */                       private MockObject $security;
    /** @var LoggerInterface&MockObject */                private MockObject $logger;
    /** @var AuthorizationCheckerInterface&MockObject */  private MockObject $authChecker;
    /** @var TokenStorageInterface&MockObject */          private MockObject $tokenStorage;
    private TokenInterface|MockObject $token;

    private ChangeMeController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);

        // appUser() (trait AppUserAware) appelle AbstractController::getUser() qui interroge security.token_storage
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['security.authorization_checker', 'security.token_storage'], true)
        );
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
            ['security.token_storage', 1, $this->tokenStorage],
        ]);

        $this->controller = new ChangeMeController($this->em, $this->security, $this->logger);
        $this->controller->setContainer($container);
    }

    public function testReturns403WithoutUserRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);
        $user = $this->makeUser();
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        $response = $this->controller->userChangeMe(
            $this->jsonRequest(['avatar' => 'chiffre/01']),
            $token
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testReturns400OnInvalidBody(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $user = $this->makeUser();
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        $response = $this->controller->userChangeMe(
            $this->jsonRequest('garbage'),
            $token
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testReturns400WhenAvatarKeyMissing(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $user = $this->makeUser();
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        $response = $this->controller->userChangeMe(
            $this->jsonRequest(['other' => 'x']),
            $token
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testThrowsUserNotFoundWhenTokenUserIsNotUtilisateur(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $this->expectException(UserNotFoundException::class);

        $this->controller->userChangeMe(
            $this->jsonRequest(['avatar' => 'chiffre/01']),
            $token
        );
    }

    public function testThrowsInvalidArgumentOnBadAvatar(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $user = $this->makeUser();
        $this->security->method('getUser')->willReturn($user);
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        $this->expectException(\InvalidArgumentException::class);

        $this->controller->userChangeMe(
            $this->jsonRequest(['avatar' => 'unknown-folder/99']),
            $token
        );
    }

    public function testThrowsInvalidArgumentOnOutOfRangeAvatarNumber(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $user = $this->makeUser();
        $this->security->method('getUser')->willReturn($user);
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        // chiffre folder allows 1 max, so 99 is out of range
        $this->expectException(\InvalidArgumentException::class);

        $this->controller->userChangeMe(
            $this->jsonRequest(['avatar' => 'chiffre/99']),
            $token
        );
    }

    public function testHappyPathUpdatesAvatarAndReturns200(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $user = $this->makeUser();
        $this->security->method('getUser')->willReturn($user);
        $this->token->method('getUser')->willReturn($user);
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);

        $this->em->expects($this->once())->method('flush');

        $response = $this->controller->userChangeMe(
            $this->jsonRequest(['avatar' => 'fille-1/15']),
            $token
        );
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame('fille-1/15.png', $user->getAvatar());
    }

    /* ============ helpers ============ */

    private function jsonRequest(array|string $body): Request
    {
        if (is_string($body)) {
            $content = $body;
        } elseif ($body === []) {
            $content = '{}';
        } else {
            $content = json_encode($body);
        }
        return new Request([], [], [], [], [], [], $content);
    }

    private function makeUser(): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel('u@example.com');
        $u->setAvatar('chiffre/01.png');
        return $u;
    }
}
