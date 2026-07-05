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

namespace App\Tests\Unit\Command\Prod;

use App\Command\Prod\RunMoulinetteCollecteCommand;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * MODIF 2026-05-21 : tests Unit de la commande
 * app:collecte:run convertie depuis run_ma-moulinette_collecte_cli.php (v1.4.1).
 *
 * Stratégie : mock partiel de callApi() (protected) pour éviter tout appel
 * cURL réel — uniquement la logique de traitement est testée.
 */
#[AllowMockObjectsWithoutExpectations]
class RunMoulinetteCollecteCommandTest extends TestCase
{
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testMissingUrlReturnsFailure(): void
    {
        $cmd    = new RunMoulinetteCollecteCommand($this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute(['--token' => 'test-token']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('--url', $tester->getDisplay());
    }

    public function testMissingTokenReturnsFailure(): void
    {
        $cmd    = new RunMoulinetteCollecteCommand($this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute(['--url' => 'https://ma.moulinette.test']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('--token', $tester->getDisplay());
    }

    public function testEmptyTraitementsSucceeds(): void
    {
        $cmd = $this->getMockBuilder(RunMoulinetteCollecteCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi'])
            ->getMock();

        // /api/public/traitement/automatique/liste renvoie liste vide
        $cmd->method('callApi')->willReturn(['code' => 200, 'liste_traitement' => []]);

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'   => 'https://ma.moulinette.test',
            '--token' => 'tok',
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Aucun traitement', $tester->getDisplay());
    }

    public function testDryRunSimulatesTraitement(): void
    {
        $cmd = $this->getMockBuilder(RunMoulinetteCollecteCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi'])
            ->getMock();

        $cmd->method('callApi')->willReturn([
            'code'              => 200,
            'liste_traitement'  => [
                ['nom_traitement' => 'batch-test', 'nombre_projet' => 5, 'portefeuille' => 'p1', 'traitement_id' => '1'],
            ],
        ]);

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'     => 'https://ma.moulinette.test',
            '--token'   => 'tok',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('simulation', strtolower($output));
        $this->assertStringContainsString('batch-test', $output);
    }

    public function testFlushLimitsTraitements(): void
    {
        $cmd = $this->getMockBuilder(RunMoulinetteCollecteCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi'])
            ->getMock();

        $liste = [
            ['nom_traitement' => 'batch-1', 'nombre_projet' => 2, 'portefeuille' => 'p1', 'traitement_id' => '1'],
            ['nom_traitement' => 'batch-2', 'nombre_projet' => 3, 'portefeuille' => 'p2', 'traitement_id' => '2'],
            ['nom_traitement' => 'batch-3', 'nombre_projet' => 1, 'portefeuille' => 'p3', 'traitement_id' => '3'],
        ];

        $cmd->method('callApi')->willReturn(['code' => 200, 'liste_traitement' => $liste]);

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'     => 'https://ma.moulinette.test',
            '--token'   => 'tok',
            '--flush'   => 1,
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $output = $tester->getDisplay();
        // batch-1 traité, batch-2/3 ignorés (flush=1)
        $this->assertStringContainsString('batch-1', $output);
        $this->assertStringNotContainsString('batch-2', $output);
    }

    public function testApiErrorOnListeReturnsFailure(): void
    {
        $cmd = $this->getMockBuilder(RunMoulinetteCollecteCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi'])
            ->getMock();

        $cmd->method('callApi')->willThrowException(new \RuntimeException('connexion refusée'));

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'   => 'https://ma.moulinette.test',
            '--token' => 'tok',
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('connexion refusée', $tester->getDisplay());
    }

    public function testApiErrorOnStartReturnsFailure(): void
    {
        $cmd = $this->getMockBuilder(RunMoulinetteCollecteCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi'])
            ->getMock();

        $callCount = 0;
        $cmd->method('callApi')->willReturnCallback(
            function (string $baseUrl, array $ssl, array $proxy, string $endpoint) use (&$callCount): array {
                $callCount++;
                // Premier appel = liste
                if (str_contains($endpoint, 'liste')) {
                    return [
                        'code'             => 200,
                        'liste_traitement' => [
                            ['nom_traitement' => 'batch-err', 'nombre_projet' => 1, 'portefeuille' => 'p1', 'traitement_id' => '99'],
                        ],
                    ];
                }
                // Deuxième appel = start → KO
                return ['code' => 500, 'message' => 'Erreur serveur'];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'   => 'https://ma.moulinette.test',
            '--token' => 'tok',
        ]);

        // hasError = true → Command::FAILURE
        $this->assertSame(1, $exit);
        $this->assertStringContainsString('batch-err', $tester->getDisplay());
    }
}
