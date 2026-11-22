<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\Security;

use App\Entity\{GroupeUtilisateur, Utilisateur};
use App\Repository\UtilisateurRepository;
use App\Security\CustomAuthenticator;
use Doctrine\ORM\{EntityManagerInterface, EntityRepository};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, RedirectResponse, Request, Response, Session\SessionInterface};
use Symfony\Component\Ldap\Adapter\AdapterInterface;
use Symfony\Component\Ldap\Adapter\CollectionInterface;
use Symfony\Component\Ldap\Adapter\ConnectionInterface as LdapConnectionInterface;
use Symfony\Component\Ldap\Adapter\QueryInterface;
use Symfony\Component\Ldap\Entry;
use Symfony\Component\Ldap\Exception\{ConnectionException, InvalidCredentialsException};
use Symfony\Component\Ldap\Ldap;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\{AuthenticationException,CustomUserMessageAuthenticationException};

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
        $payload = json_decode((string) $response->getContent(), true);
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

        $this->assertInstanceOf(RedirectResponse::class, $response);
        $this->assertSame('/accueil', $response->getTargetUrl());
    }

    public function testOnAuthenticationSuccessFallsBackToAccueilForNonUtilisateurUser(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);

        $response = $this->authenticator->onAuthenticationSuccess(Request::create('/'), $token, 'main');

        $this->assertInstanceOf(RedirectResponse::class, $response);
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

        $this->assertSame('alice@ma-moulinette.fr', $passport->getUser()->getUserIdentifier());
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

    /* ============ authenticate : chemins LDAP (bind OK) ============ */

    public function testAuthenticateLdapBindSuccessWithExistingActiveUserReturnsPassport(): void
    {
        $request = $this->buildLoginRequest('dupont.jean@ma-moulinette.fr', 'secret', 'csrf-token');

        $existingUser = new Utilisateur();
        $existingUser->setCourriel('dupont.jean@ma-moulinette.fr');
        $existingUser->setActif(true);

        // 1er appel (avec filtre actif) : pas de compte local -> on tente LDAP.
        // 2e appel (sans filtre actif, ligne 171) : le compte LDAP existe déjà.
        $this->utilisateurRepo->method('findOneBy')->willReturnCallback(
            fn (array $criteria) => isset($criteria['actif']) ? null : $existingUser
        );

        $this->mockSuccessfulLdapBindAndQuery('CN=Dupont Jean,OU=UTILISATEURS');

        $passport = $this->authenticator->authenticate($request);

        $this->assertSame('dupont.jean@ma-moulinette.fr', $passport->getUser()->getUserIdentifier());
    }

    public function testAuthenticateLdapBindSuccessWithExistingInactiveUserThrows(): void
    {
        $request = $this->buildLoginRequest('dupont.jean@ma-moulinette.fr', 'secret', 'csrf-token');

        $existingUser = new Utilisateur();
        $existingUser->setCourriel('dupont.jean@ma-moulinette.fr');
        $existingUser->setActif(false);

        $this->utilisateurRepo->method('findOneBy')->willReturnCallback(
            fn (array $criteria) => isset($criteria['actif']) ? null : $existingUser
        );

        $this->mockSuccessfulLdapBindAndQuery('CN=Dupont Jean,OU=UTILISATEURS');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('pas encore habilité');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateLdapAutoProvisionsNewUserThenThrowsNotAuthorized(): void
    {
        $request = $this->buildLoginRequest('nouveau.user@ma-moulinette.fr', 'secret', 'csrf-token');

        $this->utilisateurRepo->method('findOneBy')->willReturn(null);

        $groupeRepo = $this->createMock(EntityRepository::class);
        $groupeRepo->method('findOneBy')->willReturnCallback(
            fn (array $criteria) => $criteria === ['groupeId' => '00000000000000000000000000']
                ? $this->createMock(GroupeUtilisateur::class)
                : null
        );
        $this->em->method('getRepository')->willReturn($groupeRepo);

        $this->passwordHasher->method('hashPassword')->willReturn('hash-aleatoire');

        $persisted = null;
        $this->em->expects($this->once())->method('persist')->willReturnCallback(
            function (object $entity) use (&$persisted): void { $persisted = $entity; }
        );
        $this->em->expects($this->once())->method('flush');

        $this->mockSuccessfulLdapBindAndQuery('CN=Nouveau User,OU=UTILISATEURS');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('créé mais n’est pas encore autorisé');

        try {
            $this->authenticator->authenticate($request);
        } finally {
            $this->assertInstanceOf(Utilisateur::class, $persisted);
            $this->assertSame('nouveau.user@ma-moulinette.fr', $persisted->getCourriel());
            $this->assertSame(['ROLE_NONE'], $persisted->getRoles());
            $this->assertFalse($persisted->isActif(), 'le compte auto-provisionné ne doit jamais être actif par défaut');
        }
    }

    public function testAuthenticateLdapAutoProvisionThrowsWhenDefaultGroupMissing(): void
    {
        $request = $this->buildLoginRequest('nouveau.user@ma-moulinette.fr', 'secret', 'csrf-token');

        $this->utilisateurRepo->method('findOneBy')->willReturn(null);

        $groupeRepo = $this->createMock(EntityRepository::class);
        $groupeRepo->method('findOneBy')->willReturn(null);
        $this->em->method('getRepository')->willReturn($groupeRepo);
        $this->em->expects($this->never())->method('persist');

        $this->mockSuccessfulLdapBindAndQuery('CN=Nouveau User,OU=UTILISATEURS');

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('groupe par défaut');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateLdapUserNotFoundInDirectoryThrowsGenericMessage(): void
    {
        $request = $this->buildLoginRequest('fantome@ma-moulinette.fr', 'secret', 'csrf-token');

        $this->utilisateurRepo->method('findOneBy')->willReturn(null);

        $collection = $this->createMock(CollectionInterface::class);
        $collection->method('count')->willReturn(0);
        $query = $this->createMock(QueryInterface::class);
        $query->method('execute')->willReturn($collection);
        $this->ldapAdapter->method('createQuery')->willReturn($query);

        $ldapConn = $this->createMock(LdapConnectionInterface::class);
        $this->ldapAdapter->method('getConnection')->willReturn($ldapConn);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage('Identifiant ou mot de passe incorrect.');

        $this->authenticator->authenticate($request);
    }

    public function testAuthenticateLdapGenericConnectionErrorThrowsAnnuaireMessage(): void
    {
        $request = $this->buildLoginRequest('qui.importe@ma-moulinette.fr', 'secret', 'csrf-token');

        $this->utilisateurRepo->method('findOneBy')->willReturn(null);

        $ldapConn = $this->createMock(LdapConnectionInterface::class);
        $ldapConn->method('bind')->willThrowException(new ConnectionException('annuaire injoignable'));
        $this->ldapAdapter->method('getConnection')->willReturn($ldapConn);

        $this->expectException(CustomUserMessageAuthenticationException::class);
        $this->expectExceptionMessage("Impossible de se connecter à l'annuaire d'entreprise.");

        $this->authenticator->authenticate($request);
    }

    /**
     * Configure un bind LDAP réussi (utilisateur + technique) et une requête
     * LDAP retournant 1 entrée — pour tester les branches "utilisateur LDAP
     * trouvé" (existant actif/inactif, ou auto-provisionné).
     */
    private function mockSuccessfulLdapBindAndQuery(string $dn): void
    {
        $ldapConn = $this->createMock(LdapConnectionInterface::class);
        // bind() ne lève rien -> succès pour les 2 binds (utilisateur puis technique).
        $this->ldapAdapter->method('getConnection')->willReturn($ldapConn);

        $entry = new Entry($dn, ['sn' => ['Jean'], 'givenName' => ['Dupont']]);
        $collection = $this->createMock(CollectionInterface::class);
        $collection->method('count')->willReturn(1);
        $collection->method('offsetGet')->willReturn($entry);

        $query = $this->createMock(QueryInterface::class);
        $query->method('execute')->willReturn($collection);
        $this->ldapAdapter->method('createQuery')->willReturn($query);
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
