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

namespace App\Tests\Unit\Command\Dev;

use App\Command\Dev\NpmAuditIngestLocalCommand;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * Tests Unit de la garde env=dev et du comportement de base de
 * NpmAuditIngestLocalCommand. Contrairement à DcIngestLocalCommand (glob
 * sur un dossier, projectInfo lu depuis le JSON), cette commande prend un
 * seul fichier et des options --group/--artifact/--project-version requises
 * (npm audit --json ne porte aucune info projet).
 */
#[AllowMockObjectsWithoutExpectations]
class NpmAuditIngestLocalCommandTest extends TestCase
{
    /** @var EntityManagerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $em;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
    }

    public function testRefusedInProdEnv(): void
    {
        $cmd    = new NpmAuditIngestLocalCommand($this->em, 'prod');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute(['path' => '/tmp/report.json']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('dev', $tester->getDisplay());
    }

    public function testRefusedInTestEnv(): void
    {
        $cmd    = new NpmAuditIngestLocalCommand($this->em, 'test');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute(['path' => '/tmp/report.json']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('dev', $tester->getDisplay());
    }

    public function testMissingOptionsReturnsFailure(): void
    {
        $cmd    = new NpmAuditIngestLocalCommand($this->em, 'dev');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute(['path' => '/tmp/report.json']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('--group', $tester->getDisplay());
    }

    public function testPathNotFoundReturnsFailure(): void
    {
        $cmd    = new NpmAuditIngestLocalCommand($this->em, 'dev');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute([
            'path' => '/chemin/qui/nexiste/pas.json',
            '--group' => 'fr.ma-moulinette',
            '--artifact' => 'angular-demo',
            '--project-version' => '1.0.0',
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('introuvable', $tester->getDisplay());
    }

    public function testInvalidJsonReturnsFailure(): void
    {
        $tmpFile = sys_get_temp_dir() . '/npmaudit_test_' . uniqid() . '.json';
        file_put_contents($tmpFile, 'not json at all');

        try {
            $cmd    = new NpmAuditIngestLocalCommand($this->em, 'dev');
            $tester = new CommandTester($cmd);

            $exit = $tester->execute([
                'path' => $tmpFile,
                '--group' => 'fr.ma-moulinette',
                '--artifact' => 'angular-demo',
                '--project-version' => '1.0.0',
            ]);

            $this->assertSame(1, $exit);
            $this->assertStringContainsString('JSON invalide', $tester->getDisplay());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testValidFileEnqueuesSuccessfully(): void
    {
        $tmpFile = sys_get_temp_dir() . '/npmaudit_test_' . uniqid() . '.json';
        file_put_contents($tmpFile, '{"auditReportVersion":2,"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}}}');

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        try {
            $cmd    = new NpmAuditIngestLocalCommand($this->em, 'dev');
            $tester = new CommandTester($cmd);

            $exit = $tester->execute([
                'path' => $tmpFile,
                '--group' => 'fr.ma-moulinette',
                '--artifact' => 'angular-demo',
                '--project-version' => '1.0.0',
            ]);

            $this->assertSame(0, $exit);
            $this->assertStringContainsString('fr.ma-moulinette:angular-demo:1.0.0', $tester->getDisplay());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testProdOnlyFileNotFoundReturnsFailure(): void
    {
        $tmpFile = sys_get_temp_dir() . '/npmaudit_test_' . uniqid() . '.json';
        file_put_contents($tmpFile, '{"auditReportVersion":2,"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}}}');

        try {
            $cmd    = new NpmAuditIngestLocalCommand($this->em, 'dev');
            $tester = new CommandTester($cmd);

            $exit = $tester->execute([
                'path' => $tmpFile,
                '--group' => 'fr.ma-moulinette',
                '--artifact' => 'angular-demo',
                '--project-version' => '1.0.0',
                '--prod-only' => '/chemin/qui/nexiste/pas-prod.json',
            ]);

            $this->assertSame(1, $exit);
            $this->assertStringContainsString('prod-only', $tester->getDisplay());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testInstalledFileNotFoundReturnsFailure(): void
    {
        $tmpFile = sys_get_temp_dir() . '/npmaudit_test_' . uniqid() . '.json';
        file_put_contents($tmpFile, '{"auditReportVersion":2,"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}}}');

        try {
            $cmd    = new NpmAuditIngestLocalCommand($this->em, 'dev');
            $tester = new CommandTester($cmd);

            $exit = $tester->execute([
                'path' => $tmpFile,
                '--group' => 'fr.ma-moulinette',
                '--artifact' => 'angular-demo',
                '--project-version' => '1.0.0',
                '--installed' => '/chemin/qui/nexiste/pas-installed.json',
            ]);

            $this->assertSame(1, $exit);
            $this->assertStringContainsString('installed', $tester->getDisplay());
        } finally {
            @unlink($tmpFile);
        }
    }

    public function testValidFileWithInstalledEnqueuesSuccessfully(): void
    {
        $tmpFile = sys_get_temp_dir() . '/npmaudit_test_' . uniqid() . '.json';
        file_put_contents($tmpFile, '{"auditReportVersion":2,"vulnerabilities":{},"metadata":{"vulnerabilities":{},"dependencies":{}}}');
        $installedFile = sys_get_temp_dir() . '/npmaudit_installed_' . uniqid() . '.json';
        file_put_contents($installedFile, '{"dependencies":{"body-parser":{"version":"1.19.0"}}}');

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        try {
            $cmd    = new NpmAuditIngestLocalCommand($this->em, 'dev');
            $tester = new CommandTester($cmd);

            $exit = $tester->execute([
                'path' => $tmpFile,
                '--group' => 'fr.ma-moulinette',
                '--artifact' => 'angular-demo',
                '--project-version' => '1.0.0',
                '--installed' => $installedFile,
            ]);

            $this->assertSame(0, $exit);
            $this->assertStringContainsString('Installed inclus', $tester->getDisplay());
        } finally {
            @unlink($tmpFile);
            @unlink($installedFile);
        }
    }
}
