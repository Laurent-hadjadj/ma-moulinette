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

use App\Entity\{DcProcessingQueue, DcScan};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-06-08 : couverture complète DcScan (46 méthodes). */

#[AllowMockObjectsWithoutExpectations]
class DcScanTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $s = new DcScan();
        $this->assertInstanceOf(\DateTimeImmutable::class, $s->getCreatedAt());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $s = new DcScan();
        $this->assertNull($s->getId());
    }

    public function testSetGetQueue(): void
    {
        $q = $this->createMock(DcProcessingQueue::class);
        $s = new DcScan();
        $result = $s->setQueue($q);
        $this->assertSame($s, $result);
        $this->assertSame($q, $s->getQueue());
    }

    public function testSetGetQueueNull(): void
    {
        $s = new DcScan();
        $s->setQueue(null);
        $this->assertNull($s->getQueue());
    }

    public function testSetGetMavenKey(): void
    {
        $s = new DcScan();
        $result = $s->setMavenKey('fr.test:mon-app');
        $this->assertSame($s, $result);
        $this->assertSame('fr.test:mon-app', $s->getMavenKey());
    }

    public function testSetGetProjectGroup(): void
    {
        $s = new DcScan();
        $result = $s->setProjectGroup('fr.test');
        $this->assertSame($s, $result);
        $this->assertSame('fr.test', $s->getProjectGroup());
    }

    public function testSetGetProjectArtifact(): void
    {
        $s = new DcScan();
        $result = $s->setProjectArtifact('mon-app');
        $this->assertSame($s, $result);
        $this->assertSame('mon-app', $s->getProjectArtifact());
    }

    public function testSetGetProjectVersion(): void
    {
        $s = new DcScan();
        $result = $s->setProjectVersion('1.2.3');
        $this->assertSame($s, $result);
        $this->assertSame('1.2.3', $s->getProjectVersion());
    }

    public function testSetGetScanDate(): void
    {
        $dt = new \DateTimeImmutable('2026-05-01 12:00:00');
        $s = new DcScan();
        $result = $s->setScanDate($dt);
        $this->assertSame($s, $result);
        $this->assertSame($dt, $s->getScanDate());
    }

    public function testSetGetEngineVersion(): void
    {
        $s = new DcScan();
        $result = $s->setEngineVersion('9.3.0');
        $this->assertSame($s, $result);
        $this->assertSame('9.3.0', $s->getEngineVersion());
    }

    public function testSetGetEngineVersionNull(): void
    {
        $s = new DcScan();
        $s->setEngineVersion(null);
        $this->assertNull($s->getEngineVersion());
    }

    public function testSetGetReportSchema(): void
    {
        $s = new DcScan();
        $result = $s->setReportSchema('1.3');
        $this->assertSame($s, $result);
        $this->assertSame('1.3', $s->getReportSchema());
    }

    public function testSetGetReportSchemaNull(): void
    {
        $s = new DcScan();
        $s->setReportSchema(null);
        $this->assertNull($s->getReportSchema());
    }

    public function testSetGetDepCountTotal(): void
    {
        $s = new DcScan();
        $result = $s->setDepCountTotal(42);
        $this->assertSame($s, $result);
        $this->assertSame(42, $s->getDepCountTotal());
    }

    public function testSetGetDepCountVulnerable(): void
    {
        $s = new DcScan();
        $result = $s->setDepCountVulnerable(5);
        $this->assertSame($s, $result);
        $this->assertSame(5, $s->getDepCountVulnerable());
    }

    public function testSetGetCveCountCritical(): void
    {
        $s = new DcScan();
        $result = $s->setCveCountCritical(2);
        $this->assertSame($s, $result);
        $this->assertSame(2, $s->getCveCountCritical());
    }

    public function testSetGetCveCountHigh(): void
    {
        $s = new DcScan();
        $result = $s->setCveCountHigh(7);
        $this->assertSame($s, $result);
        $this->assertSame(7, $s->getCveCountHigh());
    }

    public function testSetGetCveCountMedium(): void
    {
        $s = new DcScan();
        $result = $s->setCveCountMedium(3);
        $this->assertSame($s, $result);
        $this->assertSame(3, $s->getCveCountMedium());
    }

    public function testSetGetCveCountLow(): void
    {
        $s = new DcScan();
        $result = $s->setCveCountLow(1);
        $this->assertSame($s, $result);
        $this->assertSame(1, $s->getCveCountLow());
    }

    public function testSetGetCveCountInfo(): void
    {
        $s = new DcScan();
        $result = $s->setCveCountInfo(0);
        $this->assertSame($s, $result);
        $this->assertSame(0, $s->getCveCountInfo());
    }

    public function testSetGetCveCountTotal(): void
    {
        $s = new DcScan();
        $result = $s->setCveCountTotal(13);
        $this->assertSame($s, $result);
        $this->assertSame(13, $s->getCveCountTotal());
    }

    public function testSetGetParentLabel(): void
    {
        $s = new DcScan();
        $result = $s->setParentLabel('springboot-config');
        $this->assertSame($s, $result);
        $this->assertSame('springboot-config', $s->getParentLabel());
    }

    public function testSetGetParentLabelNull(): void
    {
        $s = new DcScan();
        $s->setParentLabel(null);
        $this->assertNull($s->getParentLabel());
    }

    public function testSetGetParentVersion(): void
    {
        $s = new DcScan();
        $result = $s->setParentVersion('3.1.0');
        $this->assertSame($s, $result);
        $this->assertSame('3.1.0', $s->getParentVersion());
    }

    public function testSetGetParentVersionNull(): void
    {
        $s = new DcScan();
        $s->setParentVersion(null);
        $this->assertNull($s->getParentVersion());
    }

    public function testSetGetArchetypeVersion(): void
    {
        $s = new DcScan();
        $result = $s->setArchetypeVersion('2.0.0');
        $this->assertSame($s, $result);
        $this->assertSame('2.0.0', $s->getArchetypeVersion());
    }

    public function testSetGetArchetypeVersionNull(): void
    {
        $s = new DcScan();
        $s->setArchetypeVersion(null);
        $this->assertNull($s->getArchetypeVersion());
    }

    public function testSetGetIsLatestOverall(): void
    {
        $s = new DcScan();
        $result = $s->setIsLatestOverall(true);
        $this->assertSame($s, $result);
        $this->assertTrue($s->isLatestOverall());
    }

    public function testSetGetIsLatestOverallFalse(): void
    {
        $s = new DcScan();
        $s->setIsLatestOverall(false);
        $this->assertFalse($s->isLatestOverall());
    }

    public function testSetGetIsLatestRelease(): void
    {
        $s = new DcScan();
        $result = $s->setIsLatestRelease(true);
        $this->assertSame($s, $result);
        $this->assertTrue($s->isLatestRelease());
    }

    public function testSetGetIsLatestReleaseFalse(): void
    {
        $s = new DcScan();
        $s->setIsLatestRelease(false);
        $this->assertFalse($s->isLatestRelease());
    }

    public function testSetGetCreatedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $s = new DcScan();
        $result = $s->setCreatedAt($dt);
        $this->assertSame($s, $result);
        $this->assertSame($dt, $s->getCreatedAt());
    }
}
