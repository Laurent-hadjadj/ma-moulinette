<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

declare(strict_types=1);

namespace App\Tests\Unit\Command\Dev;

use App\Command\Dev\SeedProjetFixtureCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * MODIF 2026-05-21 : tests Unit de la garde env=dev
 * de SeedProjetFixtureCommand.
 */
#[AllowMockObjectsWithoutExpectations]
class SeedProjetFixtureCommandTest extends TestCase
{
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testRefusedInProdEnv(): void
    {
        $cmd    = new SeedProjetFixtureCommand($this->em, 'prod');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute([]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('dev', $tester->getDisplay());
    }

    public function testRefusedInTestEnv(): void
    {
        $cmd    = new SeedProjetFixtureCommand($this->em, 'test');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute([]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('dev', $tester->getDisplay());
    }
}
