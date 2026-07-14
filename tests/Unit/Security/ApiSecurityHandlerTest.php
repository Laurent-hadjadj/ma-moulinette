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

use App\Security\ApiSecurityHandler;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\{JsonResponse, Request, Response};
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\{AccessDeniedException, AuthenticationException};
use Symfony\Component\Security\Core\User\UserInterface;

/**
 * MODIF 2026-05-15 : tests Unit pour ApiSecurityHandler.
 * Couvre start() (401) avec/sans header x-api-custom-401
 * et handle() (403) avec ses 3 branches (no token / 200-web / 403-api).
 */
#[AllowMockObjectsWithoutExpectations]
class ApiSecurityHandlerTest extends TestCase
{
    /** @var LoggerInterface&MockObject */       private MockObject $logger;
    /** @var TokenStorageInterface&MockObject */ private MockObject $tokenStorage;

    private ApiSecurityHandler $handler;

    protected function setUp(): void
    {
        $this->logger       = $this->createMock(LoggerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->handler      = new ApiSecurityHandler($this->logger, $this->tokenStorage);
    }

    /* ============ start (401) ============ */

    public function testStartReturnsHttp401WhenHeaderAbsent(): void
    {
        $request = Request::create('/api/secure/something');

        $response = $this->handler->start($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertSame(401, $payload['code']);
        $this->assertSame('unauthorized', $payload['status']);
        $this->assertStringContainsString('authentifié', $payload['message']);
    }

    public function testStartReturnsHttp200WhenCustomHeaderPresent(): void
    {
        $request = Request::create('/api/secure/something');
        $request->headers->set('x-api-custom-401', '1');

        $response = $this->handler->start($request);

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertTrue($payload['X-Api-Custom-401']);
        $this->assertSame(401, $payload['code']);
        $this->assertStringContainsString('session', $payload['message']);
    }

    public function testStartPassesAuthExceptionMessageToLogger(): void
    {
        $request   = Request::create('/api/x');
        $exception = new AuthenticationException('token expired');

        $this->logger->expects($this->once())
            ->method('error')
            ->with(
                $this->stringContains('☠️'),
                $this->callback(fn ($ctx) => $ctx['message'] === 'token expired')
            );

        $this->handler->start($request, $exception);
    }

    /* ============ handle (403) ============ */

    public function testHandleReturnsStart401WhenNoToken(): void
    {
        $request = Request::create('/api/secure/x');
        $this->tokenStorage->method('getToken')->willReturn(null);

        $response = $this->handler->handle($request, new AccessDeniedException('denied'));

        /* Pas de token = délégué à start() → 401 unauthorized */
        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testHandleReturnsStart401WhenTokenWithoutUser(): void
    {
        $request = Request::create('/api/secure/x');
        $token   = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn(null);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $response = $this->handler->handle($request, new AccessDeniedException('denied'));

        $this->assertSame(Response::HTTP_UNAUTHORIZED, $response->getStatusCode());
    }

    public function testHandleReturnsHttp403WithApiMessageWhenNoCustomHeader(): void
    {
        $request = Request::create('/api/admin/x');
        $user    = $this->createMock(UserInterface::class);
        $token   = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $response = $this->handler->handle($request, new AccessDeniedException('Access Denied.'));

        $this->assertSame(Response::HTTP_FORBIDDEN, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertTrue($payload['x-api-custom-403']);
        $this->assertSame(403, $payload['code']);
        $this->assertStringContainsString('[API-Credential]', $payload['message']);
    }

    public function testHandleReturnsHttp200WithWebMessageWhenCustomHeaderPresent(): void
    {
        $request = Request::create('/api/admin/x');
        $request->headers->set('X-Api-Custom-403', '1');
        $request->attributes->set('role', 'ROLE_ADMIN');

        $user  = $this->createMock(UserInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $response = $this->handler->handle($request, new AccessDeniedException('Access Denied.'));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $payload = json_decode($response->getContent(), true);
        $this->assertStringContainsString('ROLE_ADMIN', $payload['message']);
        $this->assertStringContainsString('Erreur 403', $payload['message']);
    }

    public function testHandleWebMessageFallsBackWithoutRoleAttribute(): void
    {
        $request = Request::create('/api/x');
        $request->headers->set('X-Api-Custom-403', '1');

        $user  = $this->createMock(UserInterface::class);
        $token = $this->createMock(TokenInterface::class);
        $token->method('getUser')->willReturn($user);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $response = $this->handler->handle($request, new AccessDeniedException('denied'));

        $payload = json_decode($response->getContent(), true);
        $this->assertStringContainsString('pas les droits', $payload['message']);
    }
}
