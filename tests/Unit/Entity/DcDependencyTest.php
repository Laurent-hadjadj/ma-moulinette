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

use App\Entity\DcDependency;
use PHPUnit\Framework\TestCase;

/* MODIF 2026-06-08 : couverture complète DcDependency (26 méthodes). */
class DcDependencyTest extends TestCase
{
    public function testConstructorInitializesDates(): void
    {
        $d = new DcDependency();
        $this->assertInstanceOf(\DateTimeImmutable::class, $d->getFirstSeenAt());
        $this->assertInstanceOf(\DateTimeImmutable::class, $d->getLastSeenAt());
    }

    public function testGetIdReturnsNullByDefault(): void
    {
        $d = new DcDependency();
        $this->assertNull($d->getId());
    }

    public function testSetGetSha1(): void
    {
        $d = new DcDependency();
        $sha1 = str_repeat('a', 40);
        $result = $d->setSha1($sha1);
        $this->assertSame($d, $result);
        $this->assertSame($sha1, $d->getSha1());
    }

    public function testSetGetMd5(): void
    {
        $d = new DcDependency();
        $d->setMd5('abc123def456abc1');
        $this->assertSame('abc123def456abc1', $d->getMd5());
    }

    public function testSetGetMd5Null(): void
    {
        $d = new DcDependency();
        $d->setMd5(null);
        $this->assertNull($d->getMd5());
    }

    public function testSetGetSha256(): void
    {
        $d = new DcDependency();
        $sha256 = str_repeat('b', 64);
        $result = $d->setSha256($sha256);
        $this->assertSame($d, $result);
        $this->assertSame($sha256, $d->getSha256());
    }

    public function testSetGetSha256Null(): void
    {
        $d = new DcDependency();
        $d->setSha256(null);
        $this->assertNull($d->getSha256());
    }

    public function testSetGetFileName(): void
    {
        $d = new DcDependency();
        $result = $d->setFileName('spring-core-5.3.10.jar');
        $this->assertSame($d, $result);
        $this->assertSame('spring-core-5.3.10.jar', $d->getFileName());
    }

    public function testSetGetPkgCoordinates(): void
    {
        $d = new DcDependency();
        $result = $d->setPkgCoordinates('pkg:maven/org.springframework/spring-core@5.3.10');
        $this->assertSame($d, $result);
        $this->assertSame('pkg:maven/org.springframework/spring-core@5.3.10', $d->getPkgCoordinates());
    }

    public function testSetGetPkgCoordinatesNull(): void
    {
        $d = new DcDependency();
        $d->setPkgCoordinates(null);
        $this->assertNull($d->getPkgCoordinates());
    }

    public function testSetGetVendor(): void
    {
        $d = new DcDependency();
        $result = $d->setVendor('org.springframework');
        $this->assertSame($d, $result);
        $this->assertSame('org.springframework', $d->getVendor());
    }

    public function testSetGetVendorNull(): void
    {
        $d = new DcDependency();
        $d->setVendor(null);
        $this->assertNull($d->getVendor());
    }

    public function testSetGetProduct(): void
    {
        $d = new DcDependency();
        $result = $d->setProduct('spring-core');
        $this->assertSame($d, $result);
        $this->assertSame('spring-core', $d->getProduct());
    }

    public function testSetGetProductNull(): void
    {
        $d = new DcDependency();
        $d->setProduct(null);
        $this->assertNull($d->getProduct());
    }

    public function testSetGetVersion(): void
    {
        $d = new DcDependency();
        $result = $d->setVersion('5.3.10');
        $this->assertSame($d, $result);
        $this->assertSame('5.3.10', $d->getVersion());
    }

    public function testSetGetVersionNull(): void
    {
        $d = new DcDependency();
        $d->setVersion(null);
        $this->assertNull($d->getVersion());
    }

    public function testSetGetLicense(): void
    {
        $d = new DcDependency();
        $result = $d->setLicense('Apache-2.0');
        $this->assertSame($d, $result);
        $this->assertSame('Apache-2.0', $d->getLicense());
    }

    public function testSetGetLicenseNull(): void
    {
        $d = new DcDependency();
        $d->setLicense(null);
        $this->assertNull($d->getLicense());
    }

    public function testSetGetDescription(): void
    {
        $d = new DcDependency();
        $result = $d->setDescription('Core support for Spring Framework.');
        $this->assertSame($d, $result);
        $this->assertSame('Core support for Spring Framework.', $d->getDescription());
    }

    public function testSetGetDescriptionNull(): void
    {
        $d = new DcDependency();
        $d->setDescription(null);
        $this->assertNull($d->getDescription());
    }

    public function testSetGetFirstSeenAt(): void
    {
        $dt = new \DateTimeImmutable('2026-01-01 00:00:00');
        $d = new DcDependency();
        $result = $d->setFirstSeenAt($dt);
        $this->assertSame($d, $result);
        $this->assertSame($dt, $d->getFirstSeenAt());
    }

    public function testSetGetLastSeenAt(): void
    {
        $dt = new \DateTimeImmutable('2026-06-01 12:00:00');
        $d = new DcDependency();
        $result = $d->setLastSeenAt($dt);
        $this->assertSame($d, $result);
        $this->assertSame($dt, $d->getLastSeenAt());
    }
}
