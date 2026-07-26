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

use App\Entity\UserAgentAnalysis;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-06-09 : tests Unit entité UserAgentAnalysis (14 champs). */
#[AllowMockObjectsWithoutExpectations]
class UserAgentAnalysisTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $a = new UserAgentAnalysis();
        $this->assertInstanceOf(\DateTimeImmutable::class, $a->getCreatedAt());
    }

    public function testIdIsNullByDefault(): void
    {
        $a = new UserAgentAnalysis();
        $this->assertNull($a->getId());
    }

    public function testSetGetId(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setId(1);
        $this->assertSame($a, $result);
        $this->assertSame(1, $a->getId());
    }

    public function testEventTypeIsNullByDefault(): void
    {
        $a = new UserAgentAnalysis();
        $this->assertNull($a->getEventType());
    }

    public function testSetGetEventType(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setEventType('LOGOUT');
        $this->assertSame($a, $result);
        $this->assertSame('LOGOUT', $a->getEventType());
    }

    public function testUrlIsNullByDefault(): void
    {
        $a = new UserAgentAnalysis();
        $this->assertNull($a->getUrl());
    }

    public function testSetGetUrl(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setUrl('/dashboard');
        $this->assertSame($a, $result);
        $this->assertSame('/dashboard', $a->getUrl());
    }

    public function testSetGetSessionId(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setSessionId('sess-xyz-42');
        $this->assertSame($a, $result);
        $this->assertSame('sess-xyz-42', $a->getSessionId());
    }

    public function testSetGetSessionIdNull(): void
    {
        $a = new UserAgentAnalysis();
        $a->setSessionId(null);
        $this->assertNull($a->getSessionId());
    }

    public function testSetGetUserId(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setUserId(7);
        $this->assertSame($a, $result);
        $this->assertSame(7, $a->getUserId());
    }

    public function testSetGetUserIdNull(): void
    {
        $a = new UserAgentAnalysis();
        $a->setUserId(null);
        $this->assertNull($a->getUserId());
    }

    public function testSetGetVisitorId(): void
    {
        $a = new UserAgentAnalysis();
        $uuid = '550e8400-e29b-41d4-a716-446655440000';
        $result = $a->setVisitorId($uuid);
        $this->assertSame($a, $result);
        $this->assertSame($uuid, $a->getVisitorId());
    }

    public function testSetGetVisitorIdNull(): void
    {
        $a = new UserAgentAnalysis();
        $a->setVisitorId(null);
        $this->assertNull($a->getVisitorId());
    }

    public function testSetGetDeviceType(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setDeviceType('desktop');
        $this->assertSame($a, $result);
        $this->assertSame('desktop', $a->getDeviceType());
    }

    public function testSetGetDeviceTypeNull(): void
    {
        $a = new UserAgentAnalysis();
        $a->setDeviceType(null);
        $this->assertNull($a->getDeviceType());
    }

    public function testSetGetOsName(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setOsName('Windows');
        $this->assertSame($a, $result);
        $this->assertSame('Windows', $a->getOsName());
    }

    public function testSetGetOsNameNull(): void
    {
        $a = new UserAgentAnalysis();
        $a->setOsName(null);
        $this->assertNull($a->getOsName());
    }

    public function testSetGetOsVersion(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setOsVersion('11');
        $this->assertSame($a, $result);
        $this->assertSame('11', $a->getOsVersion());
    }

    public function testSetGetOsVersionNull(): void
    {
        $a = new UserAgentAnalysis();
        $a->setOsVersion(null);
        $this->assertNull($a->getOsVersion());
    }

    public function testSetGetBrowserName(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setBrowserName('Chrome');
        $this->assertSame($a, $result);
        $this->assertSame('Chrome', $a->getBrowserName());
    }

    public function testSetGetBrowserNameNull(): void
    {
        $a = new UserAgentAnalysis();
        $a->setBrowserName(null);
        $this->assertNull($a->getBrowserName());
    }

    public function testSetGetBrowserVersion(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setBrowserVersion('125.0');
        $this->assertSame($a, $result);
        $this->assertSame('125.0', $a->getBrowserVersion());
    }

    public function testSetGetBrowserVersionNull(): void
    {
        $a = new UserAgentAnalysis();
        $a->setBrowserVersion(null);
        $this->assertNull($a->getBrowserVersion());
    }

    public function testSetIsBotTrue(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setIsBot(true);
        $this->assertSame($a, $result);
        $this->assertTrue($a->isBot());
    }

    public function testSetIsBotFalse(): void
    {
        $a = new UserAgentAnalysis();
        $a->setIsBot(false);
        $this->assertFalse($a->isBot());
    }

    public function testSetIsBotNull(): void
    {
        $a = new UserAgentAnalysis();
        $a->setIsBot(null);
        $this->assertNull($a->isBot());
    }

    public function testDetectorVersionDefaultEmpty(): void
    {
        $a = new UserAgentAnalysis();
        $this->assertSame('', $a->getDetectorVersion());
    }

    public function testSetGetDetectorVersion(): void
    {
        $a = new UserAgentAnalysis();
        $result = $a->setDetectorVersion('6.5.0');
        $this->assertSame($a, $result);
        $this->assertSame('6.5.0', $a->getDetectorVersion());
    }

    public function testSetGetCreatedAt(): void
    {
        $a = new UserAgentAnalysis();
        $dt = new \DateTimeImmutable('2026-06-09 10:00:00');
        $result = $a->setCreatedAt($dt);
        $this->assertSame($a, $result);
        $this->assertSame($dt, $a->getCreatedAt());
    }
}
