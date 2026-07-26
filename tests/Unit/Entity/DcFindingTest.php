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

use App\Entity\{DcCve, DcDependency, DcFinding, DcScan};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-06-08 : couverture complète DcFinding (16 méthodes). */
#[AllowMockObjectsWithoutExpectations]
class DcFindingTest extends TestCase
{
    public function testConstructorInitializesCreatedAt(): void
    {
        $f = new DcFinding();
        $this->assertLessThanOrEqual(new \DateTimeImmutable(), $f->getCreatedAt());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $f = new DcFinding();
        $this->assertNull($f->getId());
    }

    public function testSetGetScanReturnsSelfAndSameInstance(): void
    {
        $scan = $this->createMock(DcScan::class);
        $f = new DcFinding();
        $result = $f->setScan($scan);
        $this->assertSame($f, $result);
        $this->assertSame($scan, $f->getScan());
    }

    public function testSetGetDependencyReturnsSelfAndSameInstance(): void
    {
        $dep = $this->createMock(DcDependency::class);
        $f = new DcFinding();
        $result = $f->setDependency($dep);
        $this->assertSame($f, $result);
        $this->assertSame($dep, $f->getDependency());
    }

    public function testSetGetCveReturnsSelfAndSameInstance(): void
    {
        $cve = $this->createMock(DcCve::class);
        $f = new DcFinding();
        $result = $f->setCve($cve);
        $this->assertSame($f, $result);
        $this->assertSame($cve, $f->getCve());
    }

    public function testSetGetSeverityAtScan(): void
    {
        $f = new DcFinding();
        $result = $f->setSeverityAtScan('HIGH');
        $this->assertSame($f, $result);
        $this->assertSame('HIGH', $f->getSeverityAtScan());
    }

    public function testSetGetCvssAtScan(): void
    {
        $f = new DcFinding();
        $result = $f->setCvssAtScan('7.5');
        $this->assertSame($f, $result);
        $this->assertSame('7.5', $f->getCvssAtScan());
    }

    public function testSetGetCvssAtScanNull(): void
    {
        $f = new DcFinding();
        $f->setCvssAtScan(null);
        $this->assertNull($f->getCvssAtScan());
    }

    public function testSetGetFixedVersion(): void
    {
        $f = new DcFinding();
        $result = $f->setFixedVersion('2.1.0');
        $this->assertSame($f, $result);
        $this->assertSame('2.1.0', $f->getFixedVersion());
    }

    public function testSetGetFixedVersionNull(): void
    {
        $f = new DcFinding();
        $f->setFixedVersion(null);
        $this->assertNull($f->getFixedVersion());
    }

    public function testSetGetCreatedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-15 10:00:00');
        $f = new DcFinding();
        $result = $f->setCreatedAt($dt);
        $this->assertSame($f, $result);
        $this->assertSame($dt, $f->getCreatedAt());
    }
}
