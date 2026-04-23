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

use App\Entity\UserAgentEvent;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Uid\Ulid;

/**
 * [Description UserAgentEventCaseTest]
 *
 * v2.0.0 : couvre les 12 attributs de l'entite.
 */
class UserAgentEventCaseTest extends TestCase
{
    public static function attributesProvider(): iterable
    {
        yield 'eventType' => ['EventType', 'LOGIN_PAGE_VIEW'];
        yield 'url' => ['Url', '/login'];
        yield 'userAgent' => ['UserAgent', 'Mozilla/5.0 (Windows NT 10.0; Win64; x64) AppleWebKit/537.36'];
        yield 'sessionId' => ['SessionId', 'sess_abc123'];
        yield 'userId' => ['UserId', 42];
        yield 'visitorId' => ['VisitorId', new Ulid()];
        yield 'authState' => ['AuthState', 'ANONYMOUS'];
        yield 'processingStatus' => ['ProcessingStatus', 'PENDING'];
        yield 'ipHash' => ['IpHash', hash('sha256', '127.0.0.1')];
    }

    #[DataProvider('attributesProvider')]
    public function testGetterSetter(string $suffix, mixed $value): void
    {
        $entity = new UserAgentEvent();
        $entity->{'set' . $suffix}($value);
        $this->assertSame($value, $entity->{'get' . $suffix}());
    }

    public function testSettingAndGettingId(): void
    {
        $entity = new UserAgentEvent();
        $entity->setId(42);
        $this->assertSame(42, $entity->getId());
    }

    public function testSettingAndGettingCreatedAt(): void
    {
        $entity = new UserAgentEvent();
        $date = new \DateTimeImmutable('2024-06-28 17:55:45');
        $entity->setCreatedAt($date);
        $this->assertEquals($date, $entity->getCreatedAt());
    }

    public function testSettingAndGettingProcessedAt(): void
    {
        $entity = new UserAgentEvent();
        $date = new \DateTimeImmutable('2024-06-28 17:56:00');
        $entity->setProcessedAt($date);
        $this->assertEquals($date, $entity->getProcessedAt());
    }

    public function testProcessedAtCanBeNull(): void
    {
        $entity = new UserAgentEvent();
        $entity->setProcessedAt(null);
        $this->assertNull($entity->getProcessedAt());
    }

    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new UserAgentEvent());
        $this->assertEquals(12, count($reflectionClass->getProperties()));
    }
}
