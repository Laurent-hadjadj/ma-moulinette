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

use App\Entity\DcCve;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-06-08 : couverture complète DcCve (30 méthodes). */
class DcCveTest extends TestCase
{
    public function testConstructorInitializesDates(): void
    {
        $c = new DcCve();
        $this->assertEquals($c->getFirstSeenAt(), $c->getLastSeenAt());
        $this->assertEquals($c->getFirstSeenAt(), $c->getUpdatedAt());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $c = new DcCve();
        $this->assertNull($c->getId());
    }

    public function testSetGetCveId(): void
    {
        $c = new DcCve();
        $result = $c->setCveId('CVE-2025-12345');
        $this->assertSame($c, $result);
        $this->assertSame('CVE-2025-12345', $c->getCveId());
    }

    public function testSetGetSource(): void
    {
        $c = new DcCve();
        $result = $c->setSource('NVD');
        $this->assertSame($c, $result);
        $this->assertSame('NVD', $c->getSource());
    }

    public function testSetGetSeverity(): void
    {
        $c = new DcCve();
        $result = $c->setSeverity('HIGH');
        $this->assertSame($c, $result);
        $this->assertSame('HIGH', $c->getSeverity());
    }

    public function testSetGetCvssV3Score(): void
    {
        $c = new DcCve();
        $result = $c->setCvssV3Score('8.1');
        $this->assertSame($c, $result);
        $this->assertSame('8.1', $c->getCvssV3Score());
    }

    public function testSetGetCvssV3ScoreNull(): void
    {
        $c = new DcCve();
        $c->setCvssV3Score(null);
        $this->assertNull($c->getCvssV3Score());
    }

    public function testSetGetCvssV3Severity(): void
    {
        $c = new DcCve();
        $result = $c->setCvssV3Severity('HIGH');
        $this->assertSame($c, $result);
        $this->assertSame('HIGH', $c->getCvssV3Severity());
    }

    public function testSetGetCvssV3SeverityNull(): void
    {
        $c = new DcCve();
        $c->setCvssV3Severity(null);
        $this->assertNull($c->getCvssV3Severity());
    }

    public function testSetGetCvssV3AttackVector(): void
    {
        $c = new DcCve();
        $result = $c->setCvssV3AttackVector('NETWORK');
        $this->assertSame($c, $result);
        $this->assertSame('NETWORK', $c->getCvssV3AttackVector());
    }

    public function testSetGetCvssV3AttackVectorNull(): void
    {
        $c = new DcCve();
        $c->setCvssV3AttackVector(null);
        $this->assertNull($c->getCvssV3AttackVector());
    }

    public function testSetGetCvssV3AttackComplexity(): void
    {
        $c = new DcCve();
        $result = $c->setCvssV3AttackComplexity('LOW');
        $this->assertSame($c, $result);
        $this->assertSame('LOW', $c->getCvssV3AttackComplexity());
    }

    public function testSetGetCvssV3AttackComplexityNull(): void
    {
        $c = new DcCve();
        $c->setCvssV3AttackComplexity(null);
        $this->assertNull($c->getCvssV3AttackComplexity());
    }

    public function testSetGetCvssV3Vector(): void
    {
        $c = new DcCve();
        $result = $c->setCvssV3Vector('CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H');
        $this->assertSame($c, $result);
        $this->assertSame('CVSS:3.1/AV:N/AC:L/PR:N/UI:N/S:U/C:H/I:H/A:H', $c->getCvssV3Vector());
    }

    public function testSetGetCvssV3VectorNull(): void
    {
        $c = new DcCve();
        $c->setCvssV3Vector(null);
        $this->assertNull($c->getCvssV3Vector());
    }

    public function testSetGetCwes(): void
    {
        $c = new DcCve();
        $result = $c->setCwes(['CWE-79', 'CWE-89']);
        $this->assertSame($c, $result);
        $this->assertSame(['CWE-79', 'CWE-89'], $c->getCwes());
    }

    public function testSetGetDescription(): void
    {
        $c = new DcCve();
        $result = $c->setDescription('A critical vulnerability in spring-core.');
        $this->assertSame($c, $result);
        $this->assertSame('A critical vulnerability in spring-core.', $c->getDescription());
    }

    public function testSetGetCveReferences(): void
    {
        $refs = [['url' => 'https://nvd.nist.gov/vuln/detail/CVE-2025-12345', 'name' => 'NVD']];
        $c = new DcCve();
        $result = $c->setCveReferences($refs);
        $this->assertSame($c, $result);
        $this->assertSame($refs, $c->getCveReferences());
    }

    public function testSetGetFirstSeenAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $c = new DcCve();
        $result = $c->setFirstSeenAt($dt);
        $this->assertSame($c, $result);
        $this->assertSame($dt, $c->getFirstSeenAt());
    }

    public function testSetGetLastSeenAt(): void
    {
        $dt = new \DateTimeImmutable('2026-06-01 12:00:00');
        $c = new DcCve();
        $result = $c->setLastSeenAt($dt);
        $this->assertSame($c, $result);
        $this->assertSame($dt, $c->getLastSeenAt());
    }

    public function testSetGetUpdatedAt(): void
    {
        $dt = new \DateTimeImmutable('2026-06-08 09:00:00');
        $c = new DcCve();
        $result = $c->setUpdatedAt($dt);
        $this->assertSame($c, $result);
        $this->assertSame($dt, $c->getUpdatedAt());
    }
}
