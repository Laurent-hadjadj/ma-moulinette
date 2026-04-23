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

namespace App\Tests\Unit\Entity\Case;

use App\Entity\UserAgentAnalysis;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * [Description UserAgentAnalysisCaseTest]
 *
 * v2.0.0 : couvre les 14 attributs de l'entite.
 */
class UserAgentAnalysisCaseTest extends TestCase
{
    public static function attributesProvider(): iterable
    {
        yield 'eventType' => ['EventType', 'LOGIN_PAGE_VIEW'];
        yield 'url' => ['Url', '/login'];
        yield 'sessionId' => ['SessionId', 'sess_abc123'];
        yield 'userId' => ['UserId', 42];
        yield 'visitorId' => ['VisitorId', new Ulid()];
        yield 'deviceType' => ['DeviceType', 'desktop'];
        yield 'osName' => ['OsName', 'Windows'];
        yield 'osVersion' => ['OsVersion', '11'];
        yield 'browserName' => ['BrowserName', 'Chrome'];
        yield 'browserVersion' => ['BrowserVersion', '120.0'];
        yield 'detectorVersion' => ['DetectorVersion', '6.4.5'];
    }

    #[DataProvider('attributesProvider')]
    public function testGetterSetter(string $suffix, mixed $value): void
    {
        $entity = new UserAgentAnalysis();
        $entity->{'set' . $suffix}($value);
        $this->assertSame($value, $entity->{'get' . $suffix}());
    }

    public function testSettingAndGettingId(): void
    {
        $entity = new UserAgentAnalysis();
        $entity->setId(42);
        $this->assertSame(42, $entity->getId());
    }

    public function testSettingAndGettingIsBot(): void
    {
        $entity = new UserAgentAnalysis();
        $entity->setIsBot(true);
        $this->assertTrue($entity->isBot());
        $entity->setIsBot(false);
        $this->assertFalse($entity->isBot());
    }

    public function testSettingAndGettingCreatedAt(): void
    {
        $entity = new UserAgentAnalysis();
        $date = new \DateTimeImmutable('2024-06-28 17:55:45');
        $entity->setCreatedAt($date);
        $this->assertEquals($date, $entity->getCreatedAt());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new UserAgentAnalysis());
        $this->assertEquals(14, count($reflectionClass->getProperties()));
    }
}
