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

use App\Entity\{NpmDependency, NpmFinding, NpmScan, NpmVulnerability};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class NpmFindingTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $f = new NpmFinding();
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $f->getCreatedAt());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $f = new NpmFinding();
        $this->assertNull($f->getId());
    }

    public function testSetGetScan(): void
    {
        $scan = $this->createMock(NpmScan::class);
        $f = new NpmFinding();
        $result = $f->setScan($scan);
        $this->assertSame($f, $result);
        $this->assertSame($scan, $f->getScan());
    }

    public function testSetGetDependency(): void
    {
        $dep = $this->createMock(NpmDependency::class);
        $f = new NpmFinding();
        $result = $f->setDependency($dep);
        $this->assertSame($f, $result);
        $this->assertSame($dep, $f->getDependency());
    }

    public function testSetGetVulnerability(): void
    {
        $vuln = $this->createMock(NpmVulnerability::class);
        $f = new NpmFinding();
        $result = $f->setVulnerability($vuln);
        $this->assertSame($f, $result);
        $this->assertSame($vuln, $f->getVulnerability());
    }

    public function testSetGetSeverityAtScan(): void
    {
        $f = new NpmFinding();
        $result = $f->setSeverityAtScan('high');
        $this->assertSame($f, $result);
        $this->assertSame('high', $f->getSeverityAtScan());
    }

    public function testSetGetCvssAtScan(): void
    {
        $f = new NpmFinding();
        $result = $f->setCvssAtScan('7.5');
        $this->assertSame($f, $result);
        $this->assertSame('7.5', $f->getCvssAtScan());
    }

    public function testSetGetCvssAtScanNull(): void
    {
        $f = new NpmFinding();
        $f->setCvssAtScan(null);
        $this->assertNull($f->getCvssAtScan());
    }

    public function testSetGetRange(): void
    {
        $f = new NpmFinding();
        $result = $f->setRange('>=2.0.0 <2.4.0');
        $this->assertSame($f, $result);
        $this->assertSame('>=2.0.0 <2.4.0', $f->getRange());
    }

    public function testSetGetRangeNull(): void
    {
        $f = new NpmFinding();
        $f->setRange(null);
        $this->assertNull($f->getRange());
    }

    public function testSetGetInstalledVersion(): void
    {
        $f = new NpmFinding();
        $this->assertNull($f->getInstalledVersion());
        $result = $f->setInstalledVersion('1.19.0');
        $this->assertSame($f, $result);
        $this->assertSame('1.19.0', $f->getInstalledVersion());
    }

    public function testSetGetInstalledVersionNull(): void
    {
        $f = new NpmFinding();
        $f->setInstalledVersion('1.0.0');
        $f->setInstalledVersion(null);
        $this->assertNull($f->getInstalledVersion());
    }

    public function testSetGetIsDirect(): void
    {
        $f = new NpmFinding();
        $this->assertFalse($f->isDirect());
        $result = $f->setIsDirect(true);
        $this->assertSame($f, $result);
        $this->assertTrue($f->isDirect());
    }

    public function testSetGetFixAvailable(): void
    {
        $f = new NpmFinding();
        $this->assertFalse($f->isFixAvailable());
        $result = $f->setFixAvailable(true);
        $this->assertSame($f, $result);
        $this->assertTrue($f->isFixAvailable());
    }

    public function testSetGetFixToVersion(): void
    {
        $f = new NpmFinding();
        $result = $f->setFixToVersion('5.0.0');
        $this->assertSame($f, $result);
        $this->assertSame('5.0.0', $f->getFixToVersion());
    }

    public function testSetGetFixToVersionNull(): void
    {
        $f = new NpmFinding();
        $f->setFixToVersion(null);
        $this->assertNull($f->getFixToVersion());
    }

    public function testSetGetFixIsSemVerMajor(): void
    {
        $f = new NpmFinding();
        $this->assertNull($f->getFixIsSemVerMajor());
        $result = $f->setFixIsSemVerMajor(true);
        $this->assertSame($f, $result);
        $this->assertTrue($f->getFixIsSemVerMajor());
    }

    public function testSetGetIsProdDefaultsToNull(): void
    {
        $f = new NpmFinding();
        $this->assertNull($f->isProd());
    }

    public function testSetGetIsProdTrue(): void
    {
        $f = new NpmFinding();
        $result = $f->setIsProd(true);
        $this->assertSame($f, $result);
        $this->assertTrue($f->isProd());
    }

    public function testSetGetIsProdFalse(): void
    {
        $f = new NpmFinding();
        $f->setIsProd(false);
        $this->assertFalse($f->isProd());
    }

    public function testSetGetCreatedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $f = new NpmFinding();
        $result = $f->setCreatedAt($dt);
        $this->assertSame($f, $result);
        $this->assertSame($dt, $f->getCreatedAt());
    }
}
