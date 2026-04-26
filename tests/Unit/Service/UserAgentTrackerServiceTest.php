<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Entity\Utilisateur;
use App\Repository\UserAgentEventRepository;
use App\Service\UserAgentTrackerService;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class UserAgentTrackerServiceTest extends TestCase
{
    private const APP_SALT = 'test-salt-42';

    /** @var RequestStack&MockObject */
    private MockObject $requestStack;

    /** @var UserAgentEventRepository&MockObject */
    private MockObject $repository;

    /** @var Security&MockObject */
    private MockObject $security;

    private UserAgentTrackerService $service;

    protected function setUp(): void
    {
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->repository = $this->createMock(UserAgentEventRepository::class);
        $this->security = $this->createMock(Security::class);

        $this->service = new UserAgentTrackerService(
            $this->requestStack,
            $this->repository,
            $this->security,
            self::APP_SALT
        );
    }

    public function testTrackReturnsErrorWhenNoActiveRequest(): void
    {
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn(null);

        $this->security->expects($this->never())->method('getUser');
        $this->repository->expects($this->never())->method('insertUserAgentEvent');

        $this->assertSame(
            ['code' => 500, 'erreur' => 'Pas de requête active'],
            $this->service->track('LOGIN_PAGE_VIEW')
        );
    }

    public function testTrackReturnsRepositoryResultWithExpectedPayloadWhenUserIsAuthenticated(): void
    {
        $request = $this->buildRequestWithCookie('visitor_id', 'existing-visitor-xyz');
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $user = $this->createMock(Utilisateur::class);
        $user->expects($this->once())->method('getId')->willReturn(77);
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn($user);

        $expectedMap = [
            'event_type' => 'LOGIN_SUCCESS_REDIRECT',
            'url' => '/login',
            'user_agent' => 'TestAgent/1.0',
            'session_id' => 'session-123',
            'user_id' => 77,
            'visitor_id' => 'existing-visitor-xyz',
            'auth_state' => 'AUTHENTICATED',
            'processing_status' => 'PENDING',
            'ip_hash' => hash('sha256', '10.1.2.3' . self::APP_SALT),
        ];

        $this->repository->expects($this->once())
            ->method('insertUserAgentEvent')
            ->with($this->callback(function (array $map) use ($expectedMap) {
                foreach ($expectedMap as $key => $value) {
                    if (($map[$key] ?? null) !== $value) {
                        return false;
                    }
                }
                return $map['created_at'] instanceof \DateTimeImmutable;
            }))
            ->willReturn(['code' => 200, 'erreur' => null]);

        $this->assertSame(
            ['code' => 200, 'erreur' => null],
            $this->service->track('LOGIN_SUCCESS_REDIRECT')
        );
    }

    public function testTrackMarksAnonymousWhenNoUserIsAuthenticated(): void
    {
        $request = $this->buildRequestWithCookie('visitor_id', 'visitor-anon');
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('insertUserAgentEvent')
            ->with($this->callback(function (array $map) {
                return $map['user_id'] === null
                    && $map['auth_state'] === 'ANONYMOUS';
            }))
            ->willReturn(['code' => 200, 'erreur' => null]);

        $this->service->track('LOGIN_PAGE_VIEW');
    }

    public function testTrackGeneratesUlidVisitorIdWhenCookieMissing(): void
    {
        $request = $this->buildRequestWithCookie(null, null);
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $this->repository->expects($this->once())
            ->method('insertUserAgentEvent')
            ->with($this->callback(function (array $map) {
                // Ulid en base32 Crockford = 26 caractères alphanumériques
                return is_string($map['visitor_id'])
                    && preg_match('/^[0-9A-Z]{26}$/i', $map['visitor_id']) === 1;
            }))
            ->willReturn(['code' => 200, 'erreur' => null]);

        $this->service->track('LOGIN_PAGE_VIEW');

        // La valeur générée doit aussi avoir été posée sur les attributs de la requête
        $this->assertNotNull($request->attributes->get('visitor_id'));
    }

    public function testTrackHashesClientIpWithAppSalt(): void
    {
        $request = $this->buildRequestWithCookie('visitor_id', 'v');
        $request->server->set('REMOTE_ADDR', '192.168.0.42');
        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);
        $this->security->expects($this->once())
            ->method('getUser')
            ->willReturn(null);

        $expectedHash = hash('sha256', '192.168.0.42' . self::APP_SALT);

        $this->repository->expects($this->once())
            ->method('insertUserAgentEvent')
            ->with($this->callback(fn (array $map) => $map['ip_hash'] === $expectedHash))
            ->willReturn(['code' => 200, 'erreur' => null]);

        $this->service->track('LOGOUT');
    }

    private function buildRequestWithCookie(?string $cookieName, ?string $cookieValue): Request
    {
        $cookies = ($cookieName && $cookieValue !== null) ? [$cookieName => $cookieValue] : [];
        $request = Request::create('/login', 'GET', [], $cookies);
        $request->headers->set('User-Agent', 'TestAgent/1.0');
        $request->server->set('REMOTE_ADDR', '10.1.2.3');

        $session = $this->createMock(SessionInterface::class);
        $session->expects($this->once())
            ->method('getId')
            ->willReturn('session-123');
        $request->setSession($session);

        return $request;
    }
}
