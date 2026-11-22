<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\NpmProcessingQueue;
use PHPUnit\Framework\TestCase;

class NpmProcessingQueueTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $q = new NpmProcessingQueue();
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $q->getCreatedAt());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $q = new NpmProcessingQueue();
        $this->assertNull($q->getId());
    }

    public function testDefaultStatusIsQueued(): void
    {
        $q = new NpmProcessingQueue();
        $this->assertSame(NpmProcessingQueue::STATUS_QUEUED, $q->getStatus());
    }

    public function testSetGetUlid(): void
    {
        $q = new NpmProcessingQueue();
        $ulid = '01HZ7X3QABCDEFGH1234567890';
        $result = $q->setUlid($ulid);
        $this->assertSame($q, $result);
        $this->assertSame($ulid, $q->getUlid());
    }

    public function testSetGetPayloadGz(): void
    {
        $q = new NpmProcessingQueue();
        $data = gzencode('{"full":{}}');
        self::assertNotFalse($data);
        $result = $q->setPayloadGz($data);
        $this->assertSame($q, $result);
        $this->assertSame($data, $q->getPayloadGz());
    }

    public function testSetGetPayloadSha256(): void
    {
        $q = new NpmProcessingQueue();
        $sha = hash('sha256', '{"full":{}}');
        $result = $q->setPayloadSha256($sha);
        $this->assertSame($q, $result);
        $this->assertSame($sha, $q->getPayloadSha256());
    }

    public function testSetGetPayloadSize(): void
    {
        $q = new NpmProcessingQueue();
        $result = $q->setPayloadSize(1024);
        $this->assertSame($q, $result);
        $this->assertSame(1024, $q->getPayloadSize());
    }

    public function testSetGetContentType(): void
    {
        $q = new NpmProcessingQueue();
        $result = $q->setContentType(NpmProcessingQueue::CONTENT_JSON);
        $this->assertSame($q, $result);
        $this->assertSame('json', $q->getContentType());
    }

    public function testSetGetProjectGroup(): void
    {
        $q = new NpmProcessingQueue();
        $result = $q->setProjectGroup('fr.ma-moulinette');
        $this->assertSame($q, $result);
        $this->assertSame('fr.ma-moulinette', $q->getProjectGroup());
    }

    public function testSetGetProjectArtifact(): void
    {
        $q = new NpmProcessingQueue();
        $result = $q->setProjectArtifact('angular-demo');
        $this->assertSame($q, $result);
        $this->assertSame('angular-demo', $q->getProjectArtifact());
    }

    public function testSetGetProjectVersion(): void
    {
        $q = new NpmProcessingQueue();
        $result = $q->setProjectVersion('1.0.0');
        $this->assertSame($q, $result);
        $this->assertSame('1.0.0', $q->getProjectVersion());
    }

    public function testSetGetStatus(): void
    {
        $q = new NpmProcessingQueue();
        $result = $q->setStatus(NpmProcessingQueue::STATUS_PROCESSING);
        $this->assertSame($q, $result);
        $this->assertSame('processing', $q->getStatus());
    }

    public function testSetGetAttempts(): void
    {
        $q = new NpmProcessingQueue();
        $result = $q->setAttempts(3);
        $this->assertSame($q, $result);
        $this->assertSame(3, $q->getAttempts());
    }

    public function testSetGetCreatedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $q = new NpmProcessingQueue();
        $result = $q->setCreatedAt($dt);
        $this->assertSame($q, $result);
        $this->assertSame($dt, $q->getCreatedAt());
    }

    public function testSetGetStartedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 09:00:00');
        $q = new NpmProcessingQueue();
        $result = $q->setStartedAt($dt);
        $this->assertSame($q, $result);
        $this->assertSame($dt, $q->getStartedAt());
    }

    public function testSetGetStartedAtNull(): void
    {
        $q = new NpmProcessingQueue();
        $q->setStartedAt(null);
        $this->assertNull($q->getStartedAt());
    }

    public function testSetGetProcessedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 10:00:00');
        $q = new NpmProcessingQueue();
        $result = $q->setProcessedAt($dt);
        $this->assertSame($q, $result);
        $this->assertSame($dt, $q->getProcessedAt());
    }

    public function testSetGetProcessedAtNull(): void
    {
        $q = new NpmProcessingQueue();
        $q->setProcessedAt(null);
        $this->assertNull($q->getProcessedAt());
    }

    public function testSetGetErrorMessage(): void
    {
        $q = new NpmProcessingQueue();
        $result = $q->setErrorMessage('Timeout during processing.');
        $this->assertSame($q, $result);
        $this->assertSame('Timeout during processing.', $q->getErrorMessage());
    }

    public function testSetGetErrorMessageNull(): void
    {
        $q = new NpmProcessingQueue();
        $q->setErrorMessage(null);
        $this->assertNull($q->getErrorMessage());
    }
}
