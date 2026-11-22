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

    /**
     * MODIF 2026-11-22 : sans périmètre configuré (sonar_tags.target_groups
     * vide), un projet dont aucun groupe ne figure dans une liste vide par
     * définition tombe dans le cas "aucun groupe valide" — tag "aucun" +
     * warning, pas d'erreur bloquante (comportement de repli, pas une
     * détection heuristique : le critère permissions+marqueur "2021" a été
     * retiré, les groupes cibles sont désormais toujours explicites).
     */
    public function testNoTargetGroupsConfiguredWarnsPerProject(): void
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
                    return ['groups' => [['name' => 'Groupe Projet A']]];
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
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

    /**
     * MODIF 2026-11-22 : périmètre de groupes cibles (sonar_tags.target_groups)
     * — un projet dont le groupe détecté n'est pas dans la liste doit être
     * ignoré : aucun appel de tag (ni "aucun" ni le nom du groupe), compté
     * dans "Ignorés".
     */
    /**
     * Garde-fou demandé explicitement (SONAR_TAGS_TARGET_GROUPS vient de
     * .env.local via le processeur "csv", qui transforme une valeur vide en
     * [""] et non []) : un tableau contenant uniquement des chaînes vides
     * doit être traité exactement comme un périmètre vide (comportement
     * historique, aucune restriction, aucune note de périmètre affichée).
     */
    public function testBlankTargetGroupsEntryIsNormalizedToEmptyScope(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([
                $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                [''],
            ])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturnCallback(
            function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => []];
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
        $this->assertStringNotContainsString('Périmètre restreint', $tester->getDisplay());
    }

    /**
     * Les espaces superflus (saisie CSV humaine, ex. "LOT-1, LOT-2 ") et les
     * doublons sont normalisés avant comparaison — un groupe saisi avec des
     * espaces autour doit tout de même matcher le nom exact du groupe
     * SonarQube.
     */
    public function testTargetGroupsAreTrimmedAndDeduplicated(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([
                $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                [' LOT-1 ', 'LOT-1', ''],
            ])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $tagCalls = [];
        $cmd->method('callApi')->willReturnCallback(
            function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint, array $params = []) use (&$tagCalls): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:projet-lot1']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    // MODIF 2026-11-22 : permissions non vides obligatoire — un groupe
                    // renvoyé par l'API avec permissions:[] n'a en réalité aucun droit
                    // sur ce projet (voir le commentaire de bug dans getProjectGroup()).
                    return ['groups' => [['name' => 'LOT-1', 'permissions' => ['scan', 'user']]]];
                }
                if (str_contains($endpoint, 'project_tags/set')) {
                    $tagCalls[] = $params['tags'] ?? null;
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
        $this->assertSame(['aucun', 'lot-1'], $tagCalls);
        $this->assertStringContainsString('Périmètre restreint à 1 groupe(s)', $tester->getDisplay());
    }

    public function testProjectOutsideTargetGroupsIsSkipped(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([
                $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                ['Groupe Projet A'],
            ])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $tagCalls = [];
        $cmd->method('callApi')->willReturnCallback(
            function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint, array $params = []) use (&$tagCalls): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:projet-hors-perimetre']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    return ['groups' => [['name' => 'Groupe Projet B']]];
                }
                if (str_contains($endpoint, 'project_tags/set')) {
                    $tagCalls[] = $params;
                }
                return [];
            }
        );
        $cmd->expects($this->once())->method('writeMappingFile')
            ->with($this->anything(), $this->isEmpty());

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
        $this->assertSame([], $tagCalls, 'Aucun appel de tag ne doit être fait sur un projet hors périmètre.');
        $this->assertStringContainsString('Ignorés: 1', $tester->getDisplay());
    }

    /**
     * Régression confirmée en conditions réelles contre une vraie instance
     * SonarQube : `/api/permissions/groups` renvoie TOUJOURS tous les
     * groupes de l'instance, avec `permissions: []` pour un groupe qui n'a
     * en réalité aucun droit sur ce projet précis. Un projet dont le VRAI
     * groupe ("AMOI" ici, permissions non vides) est hors périmètre, mais
     * qui porte AUSSI "LOT-1" (dans le périmètre) avec des permissions
     * vides — car LOT-1 existe ailleurs dans l'instance, pas parce que ce
     * projet lui appartient — doit être ignoré, pas tagué "lot-1" à tort.
     */
    public function testProjectWithOnlyEmptyPermissionsOnTargetGroupIsSkippedNotMismatched(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([
                $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                ['LOT-1', 'LOT-2', 'LOT-3', 'LOT-4'],
            ])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $tagCalls = [];
        $cmd->method('callApi')->willReturnCallback(
            function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint, array $params = []) use (&$tagCalls): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:projet-amoi']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    // Ordre et forme exacts observés en réel (SonarQube trie le vrai
                    // groupe du projet en premier, les autres groupes du système
                    // suivent avec permissions:[]).
                    return ['groups' => [
                        ['name' => 'AMOI', 'permissions' => ['scan', 'user']],
                        ['name' => 'LOT-1', 'permissions' => []],
                        ['name' => 'LOT-2', 'permissions' => []],
                        ['name' => 'LOT-3', 'permissions' => []],
                        ['name' => 'LOT-4', 'permissions' => []],
                        ['name' => 'sonar-administrators', 'permissions' => []],
                        ['name' => 'sonar-users', 'permissions' => []],
                    ]];
                }
                if (str_contains($endpoint, 'project_tags/set')) {
                    $tagCalls[] = $params;
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
        $this->assertSame([], $tagCalls, 'Aucun appel de tag ne doit être fait : le vrai groupe (AMOI) est hors périmètre.');
        $this->assertStringContainsString('Ignorés: 1', $tester->getDisplay());
    }

    /**
     * Pendant de testProjectOutsideTargetGroupsIsSkipped : un projet dont le
     * groupe détecté FIGURE dans le périmètre est traité normalement (clean
     * "aucun" puis retag avec le nom du groupe).
     */
    public function testProjectInsideTargetGroupsIsProcessedNormally(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([
                $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                ['Groupe Projet A'],
            ])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $tagCalls = [];
        $cmd->method('callApi')->willReturnCallback(
            function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint, array $params = []) use (&$tagCalls): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:projet-dans-perimetre']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    return ['groups' => [['name' => 'Groupe Projet A', 'permissions' => ['scan', 'user']]]];
                }
                if (str_contains($endpoint, 'project_tags/set')) {
                    $tagCalls[] = $params['tags'] ?? null;
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
        $this->assertSame(['aucun', 'groupe-projet-a'], $tagCalls);
        $this->assertStringContainsString('Tagué', $tester->getDisplay());
        $this->assertStringContainsString('Ignorés: 0', $tester->getDisplay());
    }

    /**
     * Un projet archivé (groupe "Archive" uniquement, sans son groupe
     * d'équipe) n'appartient à aucun des groupes cibles configurés : il
     * doit être ignoré, pas retagué "archive" — l'archive ne redevient un
     * cas traité que si le projet porte AUSSI le groupe cible (couvert par
     * testArchivedProjectGetsArchiveTag, sans périmètre).
     */
    public function testArchivedProjectWithoutTargetGroupIsSkippedWhenScopeIsSet(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([
                $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                ['Groupe Projet A'],
            ])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $tagCalls = [];
        $cmd->method('callApi')->willReturnCallback(
            function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint, array $params = []) use (&$tagCalls): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => [['key' => 'fr.ma-moulinette:projet-archive']]];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    return ['groups' => [['name' => 'Archive', 'permissions' => ['user'], 'description' => '']]];
                }
                if (str_contains($endpoint, 'project_tags/set')) {
                    $tagCalls[] = $params;
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
        $this->assertSame([], $tagCalls);
        $this->assertStringContainsString('Ignorés: 1', $tester->getDisplay());
        $this->assertStringContainsString('Archivés: 0', $tester->getDisplay());
    }

    /**
     * Régression confirmée en conditions réelles (403 "Insufficient
     * privileges" SonarQube sur le retag, après un clean "aucun" réussi) :
     * un projet dont le clean réussit mais dont le retag échoue ne doit PAS
     * être compté "Tagué" dans le résumé final — le tag de groupe n'a en
     * réalité jamais été appliqué. Le traitement doit aussi continuer sur
     * les projets suivants (pas d'arrêt de la boucle).
     */
    public function testRetagFailureAfterSuccessfulCleanIsNotCountedAsProcessed(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([
                $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                ['Groupe Projet A'],
            ])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $tagSetCalls = 0;
        $cmd->method('callApi')->willReturnCallback(
            function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint, array $params = []) use (&$tagSetCalls): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return [
                        'components' => [
                            ['key' => 'fr.ma-moulinette:projet-a'],
                            ['key' => 'fr.ma-moulinette:projet-b'],
                        ],
                    ];
                }
                if (str_contains($endpoint, 'permissions/groups')) {
                    return ['groups' => [['name' => 'Groupe Projet A', 'permissions' => ['scan', 'user']]]];
                }
                if (str_contains($endpoint, 'project_tags/set')) {
                    $tagSetCalls++;
                    // 1er appel (clean "aucun") du 1er projet : OK.
                    // 2ᵉ appel (retag "groupe-projet-a") du 1er projet : 403.
                    if ($tagSetCalls === 2) {
                        throw new SonarCommandException(
                            'Erreur API 403 sur .../api/project_tags/set : {"errors":[{"msg":"Insufficient privileges"}]}'
                        );
                    }
                    return [];
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
        $output = $tester->getDisplay();
        $this->assertStringContainsString('Erreur sur fr.ma-moulinette:projet-a', $output);
        // Seul le 2ᵉ projet (sans échec) doit être compté "Tagué".
        $this->assertStringContainsString('Tagués: 1', $output);
    }

    /**
     * Phase 3 : un projet dont le maven_key figure dans le fichier de tags
     * métier reçoit, en UN SEUL appel de tag, le tag de groupe ET le tag
     * métier combinés (virgule) — pas un 3ᵉ appel séparé qui écraserait le
     * tag de groupe (l'API SonarQube remplace toute la liste de tags).
     */
    public function testBusinessTagIsAddedWhenMavenKeyMatches(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sonartags_');
        self::assertIsString($file);
        file_put_contents($file, json_encode([
            ['project_key' => 'fr.ma-moulinette:projet-avec-tag', 'code_apli' => 'ADEM '],
        ]));

        try {
            $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
                ->setConstructorArgs([
                    $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                    ['Groupe Projet A'],
                    [$file],
                ])
                ->onlyMethods(['callApi', 'writeMappingFile'])
                ->getMock();

            $tagCalls = [];
            $cmd->method('callApi')->willReturnCallback(
                function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint, array $params = []) use (&$tagCalls): mixed {
                    if (str_contains($endpoint, 'search_projects')) {
                        return ['components' => [['key' => 'fr.ma-moulinette:projet-avec-tag']]];
                    }
                    if (str_contains($endpoint, 'permissions/groups')) {
                        return ['groups' => [['name' => 'Groupe Projet A', 'permissions' => ['scan', 'user']]]];
                    }
                    if (str_contains($endpoint, 'project_tags/set')) {
                        $tagCalls[] = $params['tags'] ?? null;
                    }
                    return [];
                }
            );

            $tester = new CommandTester($cmd);
            $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

            $this->assertSame(0, $exit);
            $this->assertSame(['aucun', 'groupe-projet-a,adem'], $tagCalls);
            $this->assertStringContainsString('Tag métier: 1', $tester->getDisplay());
        } finally {
            unlink($file);
        }
    }

    /**
     * Pendant de testBusinessTagIsAddedWhenMavenKeyMatches : un projet dont
     * le maven_key n'est PAS dans le fichier garde uniquement son tag de
     * groupe, sans virgule ni tag vide.
     */
    public function testBusinessTagIsOmittedWhenMavenKeyNotInFile(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sonartags_');
        self::assertIsString($file);
        file_put_contents($file, json_encode([
            ['project_key' => 'fr.ma-moulinette:un-autre-projet', 'code_apli' => 'ADEM'],
        ]));

        try {
            $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
                ->setConstructorArgs([
                    $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                    ['Groupe Projet A'],
                    [$file],
                ])
                ->onlyMethods(['callApi', 'writeMappingFile'])
                ->getMock();

            $tagCalls = [];
            $cmd->method('callApi')->willReturnCallback(
                function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint, array $params = []) use (&$tagCalls): mixed {
                    if (str_contains($endpoint, 'search_projects')) {
                        return ['components' => [['key' => 'fr.ma-moulinette:projet-sans-tag']]];
                    }
                    if (str_contains($endpoint, 'permissions/groups')) {
                        return ['groups' => [['name' => 'Groupe Projet A', 'permissions' => ['scan', 'user']]]];
                    }
                    if (str_contains($endpoint, 'project_tags/set')) {
                        $tagCalls[] = $params['tags'] ?? null;
                    }
                    return [];
                }
            );

            $tester = new CommandTester($cmd);
            $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

            $this->assertSame(0, $exit);
            $this->assertSame(['aucun', 'groupe-projet-a'], $tagCalls);
            $this->assertStringContainsString('Tag métier: 0', $tester->getDisplay());
        } finally {
            unlink($file);
        }
    }

    /**
     * Aucun des fichiers candidats n'existe : l'étape 3 est ignorée
     * silencieusement (pas une erreur), le reste du traitement est inchangé.
     */
    public function testMissingBusinessTagFileIsNotAnError(): void
    {
        $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
            ->setConstructorArgs([
                $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                [],
                ['/chemin/inexistant/liste_code_appli.json'],
            ])
            ->onlyMethods(['callApi', 'writeMappingFile'])
            ->getMock();

        $cmd->method('callApi')->willReturnCallback(
            function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint): mixed {
                if (str_contains($endpoint, 'search_projects')) {
                    return ['components' => []];
                }
                return [];
            }
        );

        $tester = new CommandTester($cmd);
        $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

        $this->assertSame(0, $exit);
        $this->assertStringContainsString('aucun fichier trouvé', $tester->getDisplay());
    }

    /**
     * Un fichier candidat existe mais contient un JSON invalide : avertissement
     * loggé, étape 3 ignorée (même traitement qu'un fichier absent), le reste
     * du traitement n'est pas bloqué.
     */
    public function testMalformedBusinessTagFileLogsWarningAndIsIgnored(): void
    {
        $file = tempnam(sys_get_temp_dir(), 'sonartags_');
        self::assertIsString($file);
        file_put_contents($file, '{ceci n\'est pas du JSON valide');

        try {
            $cmd = $this->getMockBuilder(UpdateSonarqubeTagsCommand::class)
                ->setConstructorArgs([
                    $this->logger, 0, 0, 'DEFAULT:!DH', 0, '', '', '',
                    [],
                    [$file],
                ])
                ->onlyMethods(['callApi', 'writeMappingFile'])
                ->getMock();

            $cmd->method('callApi')->willReturnCallback(
                function (string $sonarUrl, string $auth, array $ssl, array $proxy, string $endpoint): mixed {
                    if (str_contains($endpoint, 'search_projects')) {
                        return ['components' => []];
                    }
                    return [];
                }
            );

            $this->logger->expects($this->once())->method('warning')
                ->with($this->stringContains('Fichier de tags métier illisible'), $this->anything());

            $tester = new CommandTester($cmd);
            $exit   = $tester->execute(['--url' => 'https://sonar.test', '--token' => 'tok']);

            $this->assertSame(0, $exit);
            $this->assertStringContainsString('aucun fichier trouvé', $tester->getDisplay());
        } finally {
            unlink($file);
        }
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
