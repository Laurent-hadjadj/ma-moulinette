<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\UserAgentEvent;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-06-09 : tests Unit entité UserAgentEvent (11 champs). */
#[AllowMockObjectsWithoutExpectations]
class UserAgentEventTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $e = new UserAgentEvent();
        $this->assertInstanceOf(\DateTimeImmutable::class, $e->getCreatedAt());
    }

    public function testIdIsNullByDefault(): void
    {
        $e = new UserAgentEvent();
        $this->assertNull($e->getId());
    }

    public function testSetGetId(): void
    {
        $e = new UserAgentEvent();
        $result = $e->setId(99);
        $this->assertSame($e, $result);
        $this->assertSame(99, $e->getId());
    }

    public function testSetGetEventType(): void
    {
        $e = new UserAgentEvent();
        $result = $e->setEventType('LOGIN_PAGE_VIEW');
        $this->assertSame($e, $result);
        $this->assertSame('LOGIN_PAGE_VIEW', $e->getEventType());
    }

    public function testEventTypeAcceptsAllValues(): void
    {
        $e = new UserAgentEvent();
        foreach (['LOGIN_PAGE_VIEW', 'LOGIN_SUCCESS_REDIRECT', 'LOGOUT', 'ACCUEIL'] as $type) {
            $e->setEventType($type);
            $this->assertSame($type, $e->getEventType());
        }
    }

    public function testSetGetUrl(): void
    {
        $e = new UserAgentEvent();
        $result = $e->setUrl('/login');
        $this->assertSame($e, $result);
        $this->assertSame('/login', $e->getUrl());
    }

    public function testSetGetUserAgent(): void
    {
        $e = new UserAgentEvent();
        $ua = 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36';
        $result = $e->setUserAgent($ua);
        $this->assertSame($e, $result);
        $this->assertSame($ua, $e->getUserAgent());
    }

    public function testSetGetSessionId(): void
    {
        $e = new UserAgentEvent();
        $result = $e->setSessionId('abc123session');
        $this->assertSame($e, $result);
        $this->assertSame('abc123session', $e->getSessionId());
    }

    public function testSetGetSessionIdNull(): void
    {
        $e = new UserAgentEvent();
        $e->setSessionId(null);
        $this->assertNull($e->getSessionId());
    }

    public function testSetGetUserId(): void
    {
        $e = new UserAgentEvent();
        $result = $e->setUserId(42);
        $this->assertSame($e, $result);
        $this->assertSame(42, $e->getUserId());
    }

    public function testSetGetUserIdNull(): void
    {
        $e = new UserAgentEvent();
        $e->setUserId(null);
        $this->assertNull($e->getUserId());
    }

    public function testSetGetVisitorId(): void
    {
        $e = new UserAgentEvent();
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $result = $e->setVisitorId($uuid);
        $this->assertSame($e, $result);
        $this->assertSame($uuid, $e->getVisitorId());
    }

    public function testSetGetVisitorIdNull(): void
    {
        $e = new UserAgentEvent();
        $e->setVisitorId(null);
        $this->assertNull($e->getVisitorId());
    }

    public function testSetGetAuthState(): void
    {
        $e = new UserAgentEvent();
        $result = $e->setAuthState('AUTHENTICATED');
        $this->assertSame($e, $result);
        $this->assertSame('AUTHENTICATED', $e->getAuthState());
    }

    public function testAuthStateAnonymous(): void
    {
        $e = new UserAgentEvent();
        $e->setAuthState('ANONYMOUS');
        $this->assertSame('ANONYMOUS', $e->getAuthState());
    }

    public function testSetGetProcessingStatus(): void
    {
        $e = new UserAgentEvent();
        $result = $e->setProcessingStatus('PENDING');
        $this->assertSame($e, $result);
        $this->assertSame('PENDING', $e->getProcessingStatus());
    }

    public function testProcessingStatusAcceptsDoneAndError(): void
    {
        $e = new UserAgentEvent();
        foreach (['PENDING', 'PROCESSING', 'DONE', 'ERROR'] as $status) {
            $e->setProcessingStatus($status);
            $this->assertSame($status, $e->getProcessingStatus());
        }
    }

    public function testSetGetIpHash(): void
    {
        $e = new UserAgentEvent();
        $hash = hash('sha256', '127.0.0.1mysecret');
        $result = $e->setIpHash($hash);
        $this->assertSame($e, $result);
        $this->assertSame($hash, $e->getIpHash());
        $this->assertSame(64, strlen($hash));
    }

    public function testSetGetIpHashNull(): void
    {
        $e = new UserAgentEvent();
        $e->setIpHash(null);
        $this->assertNull($e->getIpHash());
    }

    public function testSetGetCreatedAt(): void
    {
        $e = new UserAgentEvent();
        $dt = new \DateTimeImmutable('2026-06-09 10:00:00');
        $result = $e->setCreatedAt($dt);
        $this->assertSame($e, $result);
        $this->assertSame($dt, $e->getCreatedAt());
    }

    public function testProcessedAtIsNullByDefault(): void
    {
        $e = new UserAgentEvent();
        $this->assertNull($e->getProcessedAt());
    }

    public function testSetGetProcessedAt(): void
    {
        $e = new UserAgentEvent();
        $dt = new \DateTimeImmutable('2026-06-09 10:05:00');
        $result = $e->setProcessedAt($dt);
        $this->assertSame($e, $result);
        $this->assertSame($dt, $e->getProcessedAt());
    }

    public function testSetGetProcessedAtNull(): void
    {
        $e = new UserAgentEvent();
        $e->setProcessedAt(null);
        $this->assertNull($e->getProcessedAt());
    }
}
