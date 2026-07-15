<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use App\Security\CustomAuthenticator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request, Response, Session\SessionInterface};
use Symfony\Component\Ldap\Adapter\AdapterInterface;
use Symfony\Component\Ldap\Adapter\ConnectionInterface as LdapConnectionInterface;
use Symfony\Component\Ldap\Exception\InvalidCredentialsException;
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\{AuthenticationException,CustomUserMessageAuthenticationException};
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;

/**
 * MODIF 2026-05-15 : tests Unit pour CustomAuthenticator.
 * Couvre les chemins testables sans serveur LDAP réel :
 *   - start (XHR / Accept JSON / redirect)
 *   - getLoginUrl
 *   - onAuthenticationSuccess (reset / pas reset)
 *   - onAuthenticationFailure
 *   - authenticate (local user OK, ancien hash, LDAP InvalidCredentials)
 *
 * Note : Ldap est `final` ; on l'instancie via un AdapterInterface mocké
 * (constructeur sans I/O réseau). Pour les tests LDAP, on mocke connection->bind().
 * Les chemins auto-provision LDAP complets sont laissés en Integration.
 */
#[AllowMockObjectsWithoutExpectations]
class CustomAuthenticatorTest extends TestCase
{
    /** @var UtilisateurRepository&MockObject */         private MockObject $utilisateurRepo;
    /** @var EntityManagerInterface&MockObject */        private MockObject $em;
    /** @var UserPasswordHasherInterface&MockObject */   private MockObject $passwordHasher;
    /** @var RouterInterface&MockObject */               private MockObject $router;
    /** @var LoggerInterface&MockObject */               private MockObject $logger;
    /** @var AdapterInterface&MockObject */              private MockObject $ldapAdapter;
    private Ldap $ldap;

    private CustomAuthenticator $authenticator;

    protected function setUp(): void
    {
        $this->utilisateurRepo = $this->createMock(UtilisateurRepository::class);
        $this->em              = $this->createMock(EntityManagerInterface::class);
        $this->passwordHasher  = $this->createMock(UserPasswordHasherInterface::class);
        $this->router          = $this->createMock(RouterInterface::class);
        $this->logger          = $this->createMock(LoggerInterface::class);
        $this->ldapAdapter     = $this->createMock(AdapterInterface::class);
        $this->ldap            = new Ldap($this->ldapAdapter);

        $this->router->method('generate')->willReturnCallback(fn ($route) => match ($route) {
            'login'             => '/login',
            'accueil'           => '/accueil',
            'reset_mot_de_passe'=> '/reset',
            default             => '/'. $route,
        });

        $this->authenticator = new CustomAuthenticator(
            $this->utilisateurRepo,
            $this->em,
            $this->passwordHasher,
            $this->router,
            $this->logger,
            $this->ldap,
            '@ma-moulinette.fr'
        );
    }

    /* ============ start ============ */

    public function testStartReturnsJsonResponseForXhrRequest(): void
    {
        $request = Request::create('/api/x');
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $response = $this->authenticator->start($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertTrue($payload['x-api-custom-401']);
        $this->assertSame(401, $payload['code']);
    }

    public function testStartReturnsJsonResponseForAcceptJsonHeader(): void
    {
        $request = Request::create('/api/x');
        $request->headers->set('Accept', 'application/json');

        $response = $this->authenticator->start($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testStartReturnsRedirectForBrowserRequest(): void
    {
        $request = Request::create('/page');

        $response = $this->authenticator->start($request);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
    }

    /* ============ getLoginUrl (via reflection : protected) ============ */

    public function testGetLoginUrlGeneratesLoginRoute(): void
    {
        $request    = Request::create('/');
        $reflection = new \ReflectionClass(CustomAuthenticator::class);
        $method     = $reflection->getMethod('getLoginUrl');

        $url = $method->invoke($this->authenticator, $request);

        $this->assertSame('/login', $url);
    }

    /* ============ onAuthenticationSuccess ============ */

    public function testOnAuthenticationSuccessRedirectsToResetWhenUserHasResetFlag(): void
    {
        $user = new Utilisateur();
        $user->setResetPassword(true);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $response = $this->authenticator->onAuthenticationSuccess(Request::create('/'), $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/reset', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessRedirectsToAccueilWhenNoResetFlag(): void
    {
        $user = new Utilisateur();
        $user->setResetPassword(false);

        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);

        $response = $this->authenticator->onAuthenticationSuccess(Request::create('/'), $token, 'main');

        $this->assertSame('/accueil', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessFallsBackToAccueilForNonUtilisateurUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $response = $this->authenticator->onAuthenticationSuccess(Request::create('/'), $token, 'main');

        $this->assertSame('/accueil', $response->getTargetUrl());
    }

    /* ============ onAuthenticationFailure ============ */

    public function testOnAuthenticationFailurePersistsExceptionAndRedirectsToLogin(): void
    {
        $request   = Request::create('/login', 'POST');
        $session   = $this->createMock(SessionInterface::class);
        $request->setSession($session);

        $exception = new AuthenticationException('bad creds');

        $session->expects($this->once())->method('set');

        $response = $this->authenticator->onAuthenticationFailure($request, $exception);

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/login', $response->getTargetUrl());
        /* L'exception doit aussi atterrir dans les attributs de la requête */
        $this->assertSame($exception, $request->attributes->get('_security.last_error'));
    }

    /* ============ authenticate : chemins locaux ============ */

    public function testAuthenticateLocalUserWithValidNormalizedPasswordReturnsPassport(): void
    {
        $request = $this->buildLoginRequest('alice@ma-moulinette.fr', 'secret', 'csrf-token');

        $user = new Utilisateur();
        $user->setCourriel('alice@ma-moulinette.fr');

        // MODIF 2026-05-16 : expects($this->any()) ajoute pour PHPUnit 14.
        $this->utilisateurRepo->expects($this->once())->method('findOneBy')
            ->with(['courriel' => 'alice@ma-moulinette.fr', 'actif' => true])
            ->willReturn($user);

        /* Mot de passe normalisé valide d'emblée. */
        $this->passwordHasher->method('isPasswordValid')->willReturnCallback(
            fn ($u, $pwd) => $pwd === \Normalizer::normalize('secret', \Normalizer::FORM_C)
        );

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(Passport::class, $passport);
    }

    public function testAuthenticateLocalUserUpgradesLegacyHash(): void
    {
        $request = $this->buildLoginRequest('bob@ma-moulinette.fr', 'pwd-raw', 'csrf-token');

        $session = $this->createMock(\Symfony\Component\HttpFoundation\Session\FlashBagAwareSessionInterface::class);
        $flashBag = new \Symfony\Component\HttpFoundation\Session\Flash\FlashBag();
        $session->method('getFlashBag')->willReturn($flashBag);
        $request->setSession($session);

        $user = new Utilisateur();
        $user->setCourriel('bob@ma-moulinette.fr');

        $this->utilisateurRepo->method('findOneBy')
            ->willReturn($user);

        /* Premier appel (normalisé) → false ; second appel (raw) → true → upgrade. */
        $callCount = 0;
        $this->passwordHasher->method('isPasswordValid')->willReturnCallback(
            function () use (&$callCount) {
                $callCount += 1;
                return $callCount >= 2;
            }
        );
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('hashed-correct');
        $this->em->expects($this->once())->method('flush');

        $passport = $this->authenticator->authenticate($request);

        $this->assertInstanceOf(Passport::class, $passport);
        $this->assertSame('hashed-correct', $user->getPassword());
        $this->assertCount(1, $flashBag->get('notice'));
    }

    public function testAuthenticateThrowsCustomMessageWhenLdapBindReturnsInvalidCredentials(): void
    {
        $request = $this->buildLoginRequest('charlie@ma-moulinette.fr', 'wrong', 'csrf-token');

        $this->utilisateurRepo->method('findOneBy')->willReturn(null);

        /* Premier bind utilisateur lève InvalidCredentialsException. */
        $ldapConn = $this->createMock(LdapConnectionInterface::class);
        $ldapConn->method('bind')
            ->willThrowException(new InvalidCredentialsException('Invalid creds'));
        $this->ldapAdapter->method('getConnection')->willReturn($ldapConn);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Identifiant ou mot de passe incorrect');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateBuildsCourrielFromShortLoginUsingConfiguredSuffix(): void
    {
        /* Identifiant court (pas une adresse) → l'adresse est reconstruite avec le
           domaine de repli injecté, jamais un domaine codé en dur. */
        $request = $this->buildLoginRequest('charlie', 'wrong', 'csrf-token');

        $this->utilisateurRepo->method('findOneBy')->willReturn(null);

        $capturedDn = null;
        $ldapConn = $this->createMock(LdapConnectionInterface::class);
        $ldapConn->method('bind')->willReturnCallback(
            function (string $dn) use (&$capturedDn): void {
                $capturedDn = $dn;
                throw new InvalidCredentialsException('Invalid creds');
            }
        );
        $this->ldapAdapter->method('getConnection')->willReturn($ldapConn);

        try {
            $this->authenticator->authenticate($request);
        } catch (CustomUserMessageAuthenticationException) {
            /* Attendu : bind LDAP en échec → message générique. */
        }

        $this->assertSame('charlie@ma-moulinette.fr', $capturedDn);
    }

    /* ============ helpers ============ */

    private function buildLoginRequest(string $login, string $password, string $csrf): Request
    {
        $request = new Request(
            [],
            ['login' => $login, 'password' => $password, '_csrf_token' => $csrf],
            [],
            [],
            [],
            ['REQUEST_METHOD' => 'POST']
        );
        $session = $this->createMock(SessionInterface::class);
        $request->setSession($session);
        return $request;
    }
}
