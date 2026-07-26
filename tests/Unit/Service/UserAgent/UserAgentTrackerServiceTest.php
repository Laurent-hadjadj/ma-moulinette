<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 */

namespace App\Tests\Unit\Service\UserAgent;

use App\Entity\Utilisateur;
use App\Repository\UserAgentEventRepository;
use App\Service\UserAgent\UserAgentTrackerService;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * [Description UserAgentTrackerServiceTest]
 * MODIF 2026-06-10 : couverture track() — 4 scénarios.
 */
class UserAgentTrackerServiceTest extends TestCase
{
    /* Stubs : pas d'expectations directes sur ces objets */
    private RequestStack $requestStack;
    private Security $security;

    protected function setUp(): void
    {
        $this->requestStack = $this->createStub(RequestStack::class);
        $this->security     = $this->createStub(Security::class);
    }

    private function makeService(UserAgentEventRepository $repo): UserAgentTrackerService
    {
        return new UserAgentTrackerService(
            $this->requestStack,
            $repo,
            $this->security,
            'test-salt'
        );
    }

    /**
     * @param array<string, string> $cookies
     */
    private function makeRequest(array $cookies = []): Request
    {
        $request = Request::create('/test', 'GET', [], $cookies);
        $session = $this->createStub(SessionInterface::class);
        $session->method('getId')->willReturn('session-test-123');
        $request->setSession($session);
        return $request;
    }

    public function testTrackReturns500WhenNoActiveRequest(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn(null);
        $repo = $this->createStub(UserAgentEventRepository::class);

        $result = $this->makeService($repo)->track('LOGIN_PAGE_VIEW');

        $this->assertSame(500, $result['code']);
        $this->assertSame('Pas de requête active', $result['erreur']);
    }

    public function testTrackAnonymousUserWithoutVisitorId(): void
    {
        $this->requestStack->method('getCurrentRequest')->willReturn($this->makeRequest());
        $this->security->method('getUser')->willReturn(null);

        $repo = $this->createMock(UserAgentEventRepository::class);
        $repo->expects($this->once())
            ->method('insertUserAgentEvent')
            ->with($this->callback(fn(array $m) =>
                $m['auth_state'] === 'ANONYMOUS' &&
                $m['user_id'] === null &&
                $m['event_type'] === 'LOGIN_PAGE_VIEW'
            ))
            ->willReturn(['code' => 200, 'erreur' => null]);

        $result = $this->makeService($repo)->track('LOGIN_PAGE_VIEW');

        $this->assertSame(200, $result['code']);
    }

    public function testTrackUsesExistingVisitorIdFromCookie(): void
    {
        $this->requestStack->method('getCurrentRequest')
            ->willReturn($this->makeRequest(['visitor_id' => 'known-visitor-uuid']));
        $this->security->method('getUser')->willReturn(null);

        $repo = $this->createMock(UserAgentEventRepository::class);
        $repo->expects($this->once())
            ->method('insertUserAgentEvent')
            ->with($this->callback(fn(array $m) => $m['visitor_id'] === 'known-visitor-uuid'))
            ->willReturn(['code' => 200, 'erreur' => null]);

        $result = $this->makeService($repo)->track('LOGOUT');

        $this->assertSame(200, $result['code']);
    }

    public function testTrackAuthenticatedUserSetsUserIdAndAuthState(): void
    {
        $user = $this->createStub(Utilisateur::class);
        $user->method('getId')->willReturn(7);

        $this->requestStack->method('getCurrentRequest')->willReturn($this->makeRequest());
        $this->security->method('getUser')->willReturn($user);

        $repo = $this->createMock(UserAgentEventRepository::class);
        $repo->expects($this->once())
            ->method('insertUserAgentEvent')
            ->with($this->callback(fn(array $m) =>
                $m['user_id'] === 7 &&
                $m['auth_state'] === 'AUTHENTICATED'
            ))
            ->willReturn(['code' => 200, 'erreur' => null]);

        $result = $this->makeService($repo)->track('LOGIN_SUCCESS_REDIRECT');

        $this->assertSame(200, $result['code']);
    }
}
