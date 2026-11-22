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

use App\Entity\NpmDependency;
use PHPUnit\Framework\TestCase;

class NpmDependencyTest extends TestCase
{
    public function testConstructorInitializesFirstAndLastSeenAt(): void
    {
        $d = new NpmDependency();
        $now = new \DateTimeImmutable();
        $this->assertLessThanOrEqual($now, $d->getFirstSeenAt());
        $this->assertLessThanOrEqual($now, $d->getLastSeenAt());
        $this->assertEquals($d->getFirstSeenAt(), $d->getLastSeenAt());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $d = new NpmDependency();
        $this->assertNull($d->getId());
    }

    public function testSetGetName(): void
    {
        $d = new NpmDependency();
        $result = $d->setName('vite');
        $this->assertSame($d, $result);
        $this->assertSame('vite', $d->getName());
    }

    public function testSetGetFirstSeenAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $d = new NpmDependency();
        $result = $d->setFirstSeenAt($dt);
        $this->assertSame($d, $result);
        $this->assertSame($dt, $d->getFirstSeenAt());
    }

    public function testSetGetLastSeenAt(): void
    {
        $dt = new \DateTimeImmutable('2026-05-01 00:00:00');
        $d = new NpmDependency();
        $result = $d->setLastSeenAt($dt);
        $this->assertSame($d, $result);
        $this->assertSame($dt, $d->getLastSeenAt());
    }
}
