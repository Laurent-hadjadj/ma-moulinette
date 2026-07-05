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

use App\Controller\Auth\LoginController;
use App\Entity\Utilisateur;
use App\Service\UserAgent\UserAgentTrackingFacade;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationUtils;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class LoginControllerTest extends TestCase
{
    /** @var UrlGeneratorInterface&MockObject */    private MockObject $router;
    /** @var ParameterBagInterface&MockObject */    private MockObject $params;
    /** @var LoggerInterface&MockObject */          private MockObject $logger;
    /** @var AuthenticationUtils&MockObject */      private MockObject $authUtils;
    /** @var Environment&MockObject */              private MockObject $twig;
    /** @var TokenStorageInterface&MockObject */    private MockObject $tokenStorage;
    // MODIF 2026-06-09 : mock UserAgentTrackingFacade (bug $tracking null)
    /** @var UserAgentTrackingFacade&MockObject */  private MockObject $tracking;

    private LoginController $controller;

    protected function setUp(): void
    {
        $this->router    = $this->createMock(UrlGeneratorInterface::class);
        $this->params    = $this->createMock(ParameterBagInterface::class);
        $this->logger    = $this->createMock(LoggerInterface::class);
        $this->authUtils = $this->createMock(AuthenticationUtils::class);
        $this->twig      = $this->createMock(Environment::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tracking  = $this->createMock(UserAgentTrackingFacade::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
            ['rgaa', '4.1.2'],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'twig', 'router', 'security.token_storage', 'parameter_bag',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['router', 1, $this->router],
            ['security.token_storage', 1, $this->tokenStorage],
            ['parameter_bag', 1, $this->params],
        ]);

        $this->controller = new LoginController(
            $this->router, $this->params, $this->logger, $this->authUtils, $this->tracking
        );
        $this->controller->setContainer($container);
    }

    /* ============ reLogin ============ */

    public function testReLoginRedirectsToAccueilWhenUserIsAuthenticated(): void
    {
        $this->stubUser($this->makeUser('u@x'));

        $this->router->expects($this->once())
            ->method('generate')
            ->with('accueil', [], $this->anything())
            ->willReturn('/accueil');

        $response = $this->controller->reLogin();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/accueil', $response->headers->get('Location'));
    }

    public function testReLoginRendersLoginFormWhenAnonymous(): void
    {
        $this->stubUser(null);
        $this->authUtils->method('getLastAuthenticationError')->willReturn(null);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('auth/login.html.twig', $this->callback(
                fn($ctx) => $ctx['error'] === null && $ctx['type_footer'] === 'complet'
            ))
            ->willReturn('<html>login-form</html>');

        $response = $this->controller->reLogin();

        $this->assertSame('<html>login-form</html>', $response->getContent());
    }

    public function testReLoginLogsErrorWhenAuthenticationFailed(): void
    {
        $this->stubUser(null);
        $error = new AuthenticationException('bad credentials');
        $this->authUtils->method('getLastAuthenticationError')->willReturn($error);

        $this->logger->expects($this->atLeastOnce())
            ->method('warning')
            ->with($this->stringContains("Échec d'authentification"), $this->anything());

        $this->twig->method('render')->willReturn('<html>err</html>');

        $this->controller->reLogin();
    }

    /* ============ login ============ */

    public function testLoginRedirectsToAccueilWhenUserExists(): void
    {
        $this->stubUser($this->makeUser('u@x'));

        $this->router->expects($this->once())
            ->method('generate')
            ->with('accueil', [], $this->anything())
            ->willReturn('/accueil');

        $response = $this->controller->login();

        $this->assertSame('/accueil', $response->headers->get('Location'));
    }

    public function testLoginRendersFormWhenAnonymous(): void
    {
        $this->stubUser(null);
        $this->authUtils->method('getLastAuthenticationError')->willReturn(null);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('auth/login.html.twig', $this->anything())
            ->willReturn('<html>login</html>');

        $this->controller->login();
    }

    /* ============ logout ============ */

    public function testLogoutRedirectsToLoginRoute(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with('login')
            ->willReturn('/login');

        $response = $this->controller->logout();

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->headers->get('Location'));
    }

    /* ============ logoutExit ============ */

    public function testLogoutExitTracksAndRedirectsToLogout(): void
    {
        $this->router->expects($this->once())
            ->method('generate')
            ->with('logout', [], $this->anything())
            ->willReturn('/logout');

        $response = $this->controller->logoutExit();

        $this->assertSame('/logout', $response->headers->get('Location'));
    }

    /* ============ helpers ============ */

    private function stubUser(?Utilisateur $user): void
    {
        if ($user === null) {
            $this->tokenStorage->method('getToken')->willReturn(null);
            return;
        }
        $token = new UsernamePasswordToken($user, 'main', ['ROLE_USER']);
        $this->tokenStorage->method('getToken')->willReturn($token);
    }

    private function makeUser(string $courriel): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel($courriel);
        return $u;
    }
}
