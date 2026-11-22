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

use App\Entity\{NpmProcessingQueue, NpmScan};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NpmScanTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $s = new NpmScan();
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $s->getCreatedAt());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $s = new NpmScan();
        $this->assertNull($s->getId());
    }

    public function testSetGetQueue(): void
    {
        $q = $this->createMock(NpmProcessingQueue::class);
        $s = new NpmScan();
        $result = $s->setQueue($q);
        $this->assertSame($s, $result);
        $this->assertSame($q, $s->getQueue());
    }

    public function testSetGetQueueNull(): void
    {
        $s = new NpmScan();
        $s->setQueue(null);
        $this->assertNull($s->getQueue());
    }

    public function testSetGetProjectGroup(): void
    {
        $s = new NpmScan();
        $result = $s->setProjectGroup('fr.ma-moulinette');
        $this->assertSame($s, $result);
        $this->assertSame('fr.ma-moulinette', $s->getProjectGroup());
    }

    public function testSetGetProjectArtifact(): void
    {
        $s = new NpmScan();
        $result = $s->setProjectArtifact('angular-demo');
        $this->assertSame($s, $result);
        $this->assertSame('angular-demo', $s->getProjectArtifact());
    }

    public function testSetGetProjectVersion(): void
    {
        $s = new NpmScan();
        $result = $s->setProjectVersion('1.2.3');
        $this->assertSame($s, $result);
        $this->assertSame('1.2.3', $s->getProjectVersion());
    }

    public function testSetGetScanDate(): void
    {
        $dt = new \DateTimeImmutable('2026-05-01 12:00:00');
        $s = new NpmScan();
        $result = $s->setScanDate($dt);
        $this->assertSame($s, $result);
        $this->assertSame($dt, $s->getScanDate());
    }

    public function testSetGetAuditReportVersion(): void
    {
        $s = new NpmScan();
        $result = $s->setAuditReportVersion('2');
        $this->assertSame($s, $result);
        $this->assertSame('2', $s->getAuditReportVersion());
    }

    public function testSetGetAuditReportVersionNull(): void
    {
        $s = new NpmScan();
        $s->setAuditReportVersion(null);
        $this->assertNull($s->getAuditReportVersion());
    }

    public function testSetGetVulnCountCritical(): void
    {
        $s = new NpmScan();
        $result = $s->setVulnCountCritical(1);
        $this->assertSame($s, $result);
        $this->assertSame(1, $s->getVulnCountCritical());
    }

    public function testSetGetVulnCountHigh(): void
    {
        $s = new NpmScan();
        $result = $s->setVulnCountHigh(3);
        $this->assertSame($s, $result);
        $this->assertSame(3, $s->getVulnCountHigh());
    }

    public function testSetGetVulnCountModerate(): void
    {
        $s = new NpmScan();
        $result = $s->setVulnCountModerate(2);
        $this->assertSame($s, $result);
        $this->assertSame(2, $s->getVulnCountModerate());
    }

    public function testSetGetVulnCountLow(): void
    {
        $s = new NpmScan();
        $result = $s->setVulnCountLow(1);
        $this->assertSame($s, $result);
        $this->assertSame(1, $s->getVulnCountLow());
    }

    public function testSetGetVulnCountInfo(): void
    {
        $s = new NpmScan();
        $result = $s->setVulnCountInfo(0);
        $this->assertSame($s, $result);
        $this->assertSame(0, $s->getVulnCountInfo());
    }

    public function testSetGetVulnCountTotal(): void
    {
        $s = new NpmScan();
        $result = $s->setVulnCountTotal(6);
        $this->assertSame($s, $result);
        $this->assertSame(6, $s->getVulnCountTotal());
    }

    public function testSetGetDepCountProd(): void
    {
        $s = new NpmScan();
        $result = $s->setDepCountProd(90);
        $this->assertSame($s, $result);
        $this->assertSame(90, $s->getDepCountProd());
    }

    public function testSetGetDepCountDev(): void
    {
        $s = new NpmScan();
        $result = $s->setDepCountDev(415);
        $this->assertSame($s, $result);
        $this->assertSame(415, $s->getDepCountDev());
    }

    public function testSetGetDepCountOptional(): void
    {
        $s = new NpmScan();
        $result = $s->setDepCountOptional(156);
        $this->assertSame($s, $result);
        $this->assertSame(156, $s->getDepCountOptional());
    }

    public function testSetGetDepCountPeer(): void
    {
        $s = new NpmScan();
        $result = $s->setDepCountPeer(18);
        $this->assertSame($s, $result);
        $this->assertSame(18, $s->getDepCountPeer());
    }

    public function testSetGetDepCountPeerOptional(): void
    {
        $s = new NpmScan();
        $result = $s->setDepCountPeerOptional(0);
        $this->assertSame($s, $result);
        $this->assertSame(0, $s->getDepCountPeerOptional());
    }

    public function testSetGetDepCountTotal(): void
    {
        $s = new NpmScan();
        $result = $s->setDepCountTotal(504);
        $this->assertSame($s, $result);
        $this->assertSame(504, $s->getDepCountTotal());
    }

    public function testSetGetCreatedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $s = new NpmScan();
        $result = $s->setCreatedAt($dt);
        $this->assertSame($s, $result);
        $this->assertSame($dt, $s->getCreatedAt());
    }
}
