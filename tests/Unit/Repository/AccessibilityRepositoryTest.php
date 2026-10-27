<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Accessibility;
use App\Repository\AccessibilityRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\Exception\{ConnectionException, NotNullConstraintViolationException, UniqueConstraintViolationException};
use Doctrine\DBAL\{Connection, Result, Statement};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * [Description AccessibilityRepositoryTest]
 *
 * MODIF 27/10/2026 : couverture — seul le chemin de
 * deleteAccessibilityMavenKey/selectAccessibility/insertAccessibility était
 * exercé (via AccessibilityRepositoryTest d'intégration) ; handleDatabaseException
 * et les branches catch/rollback n'étaient jamais atteintes. Pattern calqué
 * sur ActuatorRepositoryTest.
 */
#[AllowMockObjectsWithoutExpectations]
class AccessibilityRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;
    /** @var Result&MockObject */                 private MockObject $result;

    private AccessibilityRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);
        $this->result     = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(Accessibility::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new AccessibilityRepository($registry);
    }

    /** @return array<string, mixed> */
    private function sampleMap(): array
    {
        return [
            'maven_key' => 'fr.ma-moulinette:ma-moulinette',
            'accessibility_anomalies' => 12,
            'accessibility_dette_minute' => 60,
            'accessibility_rating' => 'B',
            'accessibility_css_anomalies' => 5,
            'accessibility_css_dette_minute' => 20,
            'accessibility_css_rating' => 'A',
            'accessibility_web_anomalies' => null,
            'accessibility_web_dette_minute' => null,
            'accessibility_web_rating' => null,
            'accessibility_javascript_anomalies' => 7,
            'accessibility_javascript_dette_minute' => 40,
            'accessibility_javascript_rating' => 'C',
            'accessibility_typescript_anomalies' => null,
            'accessibility_typescript_dette_minute' => null,
            'accessibility_typescript_rating' => null,
            'mode_collecte' => 'COLLECTE',
            'utilisateur_collecte' => 'admin@ma-moulinette.fr',
            'date_enregistrement' => new \DateTimeImmutable('2026-10-27 12:00:00'),
        ];
    }

    /* ============ handleDatabaseException ============ */

    public function testHandleDatabaseExceptionGenericMessage(): void
    {
        $result = $this->repo->handleDatabaseException(new \RuntimeException('boom'));
        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    public function testHandleDatabaseExceptionConnection(): void
    {
        $exception = new class('SQLSTATE[08006]') extends ConnectionException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $result = $this->repo->handleDatabaseException($exception);
        $this->assertSame(500, $result['code']);
        $this->assertStringContainsString('connexion', $result['erreur']);
    }

    public function testHandleDatabaseExceptionNotNullKeepsMessage(): void
    {
        $exception = new class('not null') extends NotNullConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $result = $this->repo->handleDatabaseException($exception);
        $this->assertSame(500, $result['code']);
        $this->assertSame('not null', $result['erreur']);
    }

    public function testHandleDatabaseExceptionUniqueReturnsCustomCode(): void
    {
        $exception = new class('dup') extends UniqueConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $result = $this->repo->handleDatabaseException($exception);
        $this->assertSame(23505, $result['code']);
        $this->assertSame('Les informations existent déjà.', $result['erreur']);
    }

    /* ============ deleteAccessibilityMavenKey ============ */

    public function testDeleteAccessibilityMavenKeyCommitsOnSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())->method('bindValue')->with(':maven_key', 'fr.ma-moulinette:ma-moulinette');
        $this->statement->expects($this->once())->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollback');

        $response = $this->repo->deleteAccessibilityMavenKey(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertSame(200, $response['code']);
        $this->assertSame('', $response['erreur']);
    }

    public function testDeleteAccessibilityMavenKeyRollsBackOnException(): void
    {
        $exception = new class('cannot delete') extends \RuntimeException implements DBALException {};
        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willThrowException($exception);
        $this->connection->expects($this->once())->method('rollback');

        $response = $this->repo->deleteAccessibilityMavenKey(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertSame(500, $response['code']);
        $this->assertStringContainsString('cannot delete', $response['erreur']);
    }

    /* ============ selectAccessibility ============ */

    public function testSelectAccessibilityReturnsRow(): void
    {
        $row = ['maven_key' => 'fr.ma-moulinette:ma-moulinette', 'accessibility_rating' => 'B'];
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())->method('bindValue')->with(':maven_key', 'fr.ma-moulinette:ma-moulinette');
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAssociative')->willReturn($row);

        $response = $this->repo->selectAccessibility(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertSame(200, $response['code']);
        $this->assertSame($row, $response['liste']);
    }

    public function testSelectAccessibilityReturnsFalseWhenNeverCollected(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAssociative')->willReturn(false);

        $response = $this->repo->selectAccessibility(['maven_key' => 'inconnu']);

        $this->assertSame(200, $response['code']);
        $this->assertFalse($response['liste']);
    }

    public function testSelectAccessibilityReturnsErrorOnException(): void
    {
        $exception = new class('SQL boom') extends \RuntimeException implements DBALException {};
        $this->connection->method('prepare')->willThrowException($exception);

        $response = $this->repo->selectAccessibility(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertSame(500, $response['code']);
        $this->assertStringContainsString('SQL boom', $response['erreur']);
    }

    /* ============ insertAccessibility ============ */

    public function testInsertAccessibilityCommitsOnSuccess(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('bindValue');
        $this->statement->expects($this->once())->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->once())->method('commit');
        $this->connection->expects($this->never())->method('rollback');

        $response = $this->repo->insertAccessibility($this->sampleMap());

        $this->assertSame(200, $response['code']);
        $this->assertSame('', $response['erreur']);
    }

    public function testInsertAccessibilityRollsBackOnException(): void
    {
        $exception = new class('dup') extends UniqueConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };
        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('bindValue');
        $this->statement->method('executeStatement')->willThrowException($exception);
        $this->connection->expects($this->once())->method('rollback');

        $response = $this->repo->insertAccessibility($this->sampleMap());

        $this->assertSame(23505, $response['code']);
        $this->assertSame('Les informations existent déjà.', $response['erreur']);
    }
}
