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

use App\Command\Prod\UpdateSonarqubeTagsCommand;
use App\Exception\SonarCommandException;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Tester\CommandTester;

/**
 * MODIF 2026-05-21 : tests Unit de la commande
 * app:sonar:update-tags convertie depuis update_sonarqube_tags_cli.php.
 *
 * Stratégie : mock partiel de callApi() (protected) pour éviter tout appel
 * cURL réel — uniquement la logique de traitement est testée.
 */
#[AllowMockObjectsWithoutExpectations]
class UpdateSonarqubeTagsCommandTest extends TestCase
{
    /** @var LoggerInterface&\PHPUnit\Framework\MockObject\MockObject */
    private $logger;

    protected function setUp(): void
    {
        $this->logger = $this->createMock(LoggerInterface::class);
    }

    public function testMissingUrlReturnsFailure(): void
    {
        $cmd    = new UpdateSonarqubeTagsCommand($this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute(['--token' => 'test-token']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('--url', $tester->getDisplay());
    }

    public function testMissingAuthReturnsFailure(): void
    {
        $cmd    = new UpdateSonarqubeTagsCommand($this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '');
        $tester = new CommandTester($cmd);

        $exit = $tester->execute(['--url' => 'https://sonar.test']);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('token', $tester->getDisplay());
    }

    public function testTokenAuthIsAccepted(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturnCallback(
            static function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => []];
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'   => 'https://sonar.test',
            '--token' => 'my-token',
        ]);

        $this->assertSame(0, $exit);
    }

    public function testLoginPasswordAuthIsAccepted(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturnCallback(
            static function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => []];
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'      => 'https://sonar.test',
            '--login'    => 'user',
            '--password' => 'pass',
        ]);

        $this->assertSame(0, $exit);
    }

    public function testDryRunDisplaysSimulationNote(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturnCallback(
            static function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:ma-moulinette']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    return ['groups' => []];
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'     => 'https://sonar.test',
            '--token'   => 'tok',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('simulation', strtolower($tester->getDisplay()));
    }

    public function testDryRunDoesNotWriteMappingFile(): void
    {
        // MODIF 2026-07-21 : régression — le fichier de mapping était écrit
        // inconditionnellement, y compris en --dry-run.
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturnCallback(
            static function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:ma-moulinette']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    return ['groups' => []];
                }
                return [];
            }
        );
        $cmd->expects($this->never())->method('writeMappingFile');

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'     => 'https://sonar.test',
            '--token'   => 'tok',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('non écrit', $tester->getDisplay());
    }

    public function testRealRunWritesMappingFileOnce(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturnCallback(
            static function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:ma-moulinette']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    return ['groups' => []];
                }
                return [];
            }
        );
        $cmd->expects($this->once())->method('writeMappingFile');

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'   => 'https://sonar.test',
            '--token' => 'tok',
        ]);

        $this->assertSame(0, $exit);
    }

    public function testArchivedProjectGetsArchiveTag(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturnCallback(
            static function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint, array $params): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:projet-archive']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    return ['groups' => [['name' => 'Archive', 'permissions' => ['user'], 'description' => '']]];
                }
                // POST project_tags/set en dry-run, ne doit pas être appelé ici (dry-run=false)
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'     => 'https://sonar.test',
            '--token'   => 'tok',
            '--dry-run' => true,
        ]);

        $this->assertSame(0, $exit);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('archive', strtolower($output));
    }

    public function testDefaultGroupDescriptionMarkerIs2021(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturnCallback(
            static function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:ma-moulinette']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    return ['groups' => [[
                        'name' => 'Groupe Projet A',
                        'permissions' => ['codeviewer', 'securityhotspotadmin', 'user'],
                        'description' => 'Créé en 2021',
                    ]]];
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('Tagué', $tester->getDisplay());
    }

    public function testCustomGroupDescriptionMarkerOverridesDefault(): void
    {
        // MODIF 2026-07-21 : le critère "2021" (permissions + description) est
        // désormais injecté via config/packages/sonar_tags.yaml — ce test
        // vérifie qu'un marqueur différent est bien pris en compte, et donc
        // que "2021" n'est plus en dur dans le code.
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([
                $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                ['codeviewer', 'securityhotspotadmin', 'user'],
                '2025',
            ])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturnCallback(
            static function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:ma-moulinette']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    return ['groups' => [[
                        'name' => 'Groupe Projet 2021',
                        'permissions' => ['codeviewer', 'securityhotspotadmin', 'user'],
                        'description' => 'Créé en 2021', // ne contient pas "2025" -> ne doit PAS matcher
                    ]]];
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
        // Aucun groupe ne correspond au marqueur "2025" -> tag "aucun" + warning.
        $this->assertStringContainsString('aucun groupe valide', $tester->getDisplay());
    }

    public function testGetProjectsFailureReturnsFailure(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willThrowException(new SonarCommandException('connexion refusée'));

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'   => 'https://sonar.test',
            '--token' => 'tok',
        ]);

        $this->assertSame(1, $exit);
        $this->assertStringContainsString('connexion refusée', $tester->getDisplay());
    }

    public function testEmptyProjectListSucceeds(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([$this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', ''])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturn(['components' => []]);

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute([
            '--url'   => 'https://sonar.test',
            '--token' => 'tok',
        ]);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('0', $tester->getDisplay());
    }
}
