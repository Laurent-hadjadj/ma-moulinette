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

namespace App\Tests\Unit\Command\Dev;

use App\Command\Dev\DcIngestLocalCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * MODIF 2026-05-21 : tests Unit de la garde env=dev
 * et du comportement de base de DcIngestLocalCommand.
 */
#[AllowMockObjectsWithoutExpectations]
class DcIngestLocalCommandTest extends TestCase
{
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testRefusedInProdEnv(): void
    {
        $cmd    = new DcIngestLocalCommand($this->em, 'prod');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute(['path' => '/tmp']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('dev', $tester->getDisplay());
    }

    public function testRefusedInTestEnv(): void
    {
        $cmd    = new DcIngestLocalCommand($this->em, 'test');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute(['path' => '/tmp']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('dev', $tester->getDisplay());
    }

    public function testPathNotFoundReturnsFailure(): void
    {
        $cmd    = new DcIngestLocalCommand($this->em, 'dev');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute(['path' => '/chemin/qui/nexiste/pas']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('introuvable', $tester->getDisplay());
    }

    public function testEmptyDirectoryReturnsSuccess(): void
    {
        // Dossier vide = aucun *.json trouvé → SUCCESS (warning)
        $tmpDir = sys_get_temp_dir() . '/dc_ingest_test_' . uniqid();
        mkdir($tmpDir);

        try {
            $cmd    = new DcIngestLocalCommand($this->em, 'dev');
            $tester = new CommandTester($cmd);

            $exit = $tester->execute(['path' => $tmpDir]);

            $this->assertSame(0, $exit);
        } finally {
            rmdir($tmpDir);
        }
    }
}
