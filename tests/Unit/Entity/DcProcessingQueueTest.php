<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\DcProcessingQueue;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-06-08 : couverture complète DcProcessingQueue (36 méthodes). */

class DcProcessingQueueTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $q = new DcProcessingQueue();
        $this->assertInstanceOf(\DateTimeImmutable::class, $q->getCreatedAt());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $q = new DcProcessingQueue();
        $this->assertNull($q->getId());
    }

    public function testSetGetUlid(): void
    {
        $q = new DcProcessingQueue();
        $ulid = '01HZ7X3QABCDEFGH1234567890';
        $result = $q->setUlid($ulid);
        $this->assertSame($q, $result);
        $this->assertSame($ulid, $q->getUlid());
    }

    public function testSetGetPayloadGz(): void
    {
        $q = new DcProcessingQueue();
        $data = gzencode('test-payload');
        $result = $q->setPayloadGz($data);
        $this->assertSame($q, $result);
        $this->assertSame($data, $q->getPayloadGz());
    }

    public function testSetGetPayloadSha256(): void
    {
        $q = new DcProcessingQueue();
        $sha = hash('sha256', 'test-payload');
        $result = $q->setPayloadSha256($sha);
        $this->assertSame($q, $result);
        $this->assertSame($sha, $q->getPayloadSha256());
    }

    public function testSetGetPayloadSize(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setPayloadSize(1024);
        $this->assertSame($q, $result);
        $this->assertSame(1024, $q->getPayloadSize());
    }

    public function testSetGetContentType(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setContentType('application/json');
        $this->assertSame($q, $result);
        $this->assertSame('application/json', $q->getContentType());
    }

    public function testSetGetProjectGroup(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setProjectGroup('fr.ma-moulinette');
        $this->assertSame($q, $result);
        $this->assertSame('fr.ma-moulinette', $q->getProjectGroup());
    }

    public function testSetGetProjectArtifact(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setProjectArtifact('mon-app');
        $this->assertSame($q, $result);
        $this->assertSame('mon-app', $q->getProjectArtifact());
    }

    public function testSetGetProjectVersion(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setProjectVersion('1.0.0');
        $this->assertSame($q, $result);
        $this->assertSame('1.0.0', $q->getProjectVersion());
    }

    public function testSetGetStatus(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setStatus('pending');
        $this->assertSame($q, $result);
        $this->assertSame('pending', $q->getStatus());
    }

    public function testSetGetAttempts(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setAttempts(3);
        $this->assertSame($q, $result);
        $this->assertSame(3, $q->getAttempts());
    }

    public function testSetGetCreatedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $q = new DcProcessingQueue();
        $result = $q->setCreatedAt($dt);
        $this->assertSame($q, $result);
        $this->assertSame($dt, $q->getCreatedAt());
    }

    public function testSetGetStartedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 09:00:00');
        $q = new DcProcessingQueue();
        $result = $q->setStartedAt($dt);
        $this->assertSame($q, $result);
        $this->assertSame($dt, $q->getStartedAt());
    }

    public function testSetGetStartedAtNull(): void
    {
        $q = new DcProcessingQueue();
        $q->setStartedAt(null);
        $this->assertNull($q->getStartedAt());
    }

    public function testSetGetProcessedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 10:00:00');
        $q = new DcProcessingQueue();
        $result = $q->setProcessedAt($dt);
        $this->assertSame($q, $result);
        $this->assertSame($dt, $q->getProcessedAt());
    }

    public function testSetGetProcessedAtNull(): void
    {
        $q = new DcProcessingQueue();
        $q->setProcessedAt(null);
        $this->assertNull($q->getProcessedAt());
    }

    public function testSetGetErrorMessage(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setErrorMessage('Timeout during processing.');
        $this->assertSame($q, $result);
        $this->assertSame('Timeout during processing.', $q->getErrorMessage());
    }

    public function testSetGetErrorMessageNull(): void
    {
        $q = new DcProcessingQueue();
        $q->setErrorMessage(null);
        $this->assertNull($q->getErrorMessage());
    }

    public function testSetGetParentLabel(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setParentLabel('springboot-socle-config');
        $this->assertSame($q, $result);
        $this->assertSame('springboot-socle-config', $q->getParentLabel());
    }

    public function testSetGetParentLabelNull(): void
    {
        $q = new DcProcessingQueue();
        $q->setParentLabel(null);
        $this->assertNull($q->getParentLabel());
    }

    public function testSetGetParentVersion(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setParentVersion('3.1.0');
        $this->assertSame($q, $result);
        $this->assertSame('3.1.0', $q->getParentVersion());
    }

    public function testSetGetParentVersionNull(): void
    {
        $q = new DcProcessingQueue();
        $q->setParentVersion(null);
        $this->assertNull($q->getParentVersion());
    }

    public function testSetGetArchetypeVersion(): void
    {
        $q = new DcProcessingQueue();
        $result = $q->setArchetypeVersion('2.0.0');
        $this->assertSame($q, $result);
        $this->assertSame('2.0.0', $q->getArchetypeVersion());
    }

    public function testSetGetArchetypeVersionNull(): void
    {
        $q = new DcProcessingQueue();
        $q->setArchetypeVersion(null);
        $this->assertNull($q->getArchetypeVersion());
    }
}
