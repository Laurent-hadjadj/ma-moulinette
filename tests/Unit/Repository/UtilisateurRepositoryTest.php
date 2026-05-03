<?php

declare(strict_types=1);

namespace App\Tests\Unit\Repository;

use App\Entity\Utilisateur;
use App\Repository\UtilisateurRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\ConnectionException;
use Doctrine\DBAL\Exception\NotNullConstraintViolationException;
use Doctrine\DBAL\Exception\UniqueConstraintViolationException;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

#[AllowMockObjectsWithoutExpectations]
class UtilisateurRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;

    private UtilisateurRepository $repo;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement = $this->createMock(Statement::class);

        $classMetadata = new ClassMetadata(Utilisateur::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new UtilisateurRepository($registry);
    }

    /* ============ handleDatabaseException ============ */

    public function testHandleDatabaseExceptionReturnsConnectionMessage(): void
    {
        $exception = new class('SQLSTATE[08006]') extends ConnectionException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertStringContainsString('connexion', $result['erreur']);
    }

    public function testHandleDatabaseExceptionReturnsUniqueConstraintMessage(): void
    {
        $exception = new class('unique') extends UniqueConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(23505, $result['code']);
        $this->assertStringContainsString('existent déjà', $result['erreur']);
    }

    public function testHandleDatabaseExceptionReturnsNotNullMessage(): void
    {
        $exception = new class('column cannot be null') extends NotNullConstraintViolationException {
            public function __construct(string $message) { \Exception::__construct($message); }
        };

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertSame('column cannot be null', $result['erreur']);
    }

    public function testHandleDatabaseExceptionFallsBackToGenericMessage(): void
    {
        $exception = new \RuntimeException('boom');

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertSame('boom', $result['erreur']);
    }

    /* ============ countUtilisateurDisponible / countUtilisateurActif ============ */

    public function testCountUtilisateurDisponibleReturnsTotal(): void
    {
        $result = $this->createMock(Result::class);
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAssociative')->willReturn(['total' => 42]);

        $response = $this->repo->countUtilisateurDisponible();

        $this->assertSame(200, $response['code']);
        $this->assertSame(42, $response['total']);
    }

    public function testCountUtilisateurDisponibleHandlesException(): void
    {
        $this->connection->method('prepare')->willThrowException(new \RuntimeException('db down'));

        $response = $this->repo->countUtilisateurDisponible();

        $this->assertSame(500, $response['code']);
        $this->assertSame('db down', $response['erreur']);
    }

    public function testCountUtilisateurActifReturnsTotal(): void
    {
        $result = $this->createMock(Result::class);
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAssociative')->willReturn(['total' => 15]);

        $response = $this->repo->countUtilisateurActif();

        $this->assertSame(200, $response['code']);
        $this->assertSame(15, $response['total']);
    }

    public function testCountUtilisateurActifFallsBackToZeroOnEmptyResult(): void
    {
        $result = $this->createMock(Result::class);
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAssociative')->willReturn([]);

        $response = $this->repo->countUtilisateurActif();

        $this->assertSame(200, $response['code']);
        $this->assertSame(0, $response['total']);
    }

    public function testCountUtilisateurActifHandlesException(): void
    {
        $this->connection->method('prepare')->willThrowException(new \RuntimeException('db fail'));

        $response = $this->repo->countUtilisateurActif();

        $this->assertSame(500, $response['code']);
    }

    /* ============ updateUtilisateurResetPassword ============ */

    public function testUpdateUtilisateurResetPasswordSucceeds(): void
    {
        $this->connection->expects($this->once())->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->expects($this->once())->method('commit');

        $map = [
            'reset_password' => true,
            'courriel' => 'u@x',
            'date_modification' => new \DateTime('2026-04-24 10:00:00'),
        ];

        $response = $this->repo->updateUtilisateurResetPassword($map);

        $this->assertSame(200, $response['code']);
    }

    public function testUpdateUtilisateurResetPasswordRollsBackOnException(): void
    {
        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willThrowException(new \RuntimeException('db fail'));
        $this->connection->expects($this->once())->method('rollBack');

        $response = $this->repo->updateUtilisateurResetPassword([
            'reset_password' => true,
            'courriel' => 'u@x',
            'date_modification' => new \DateTime(),
        ]);

        $this->assertSame(500, $response['code']);
        $this->assertSame('db fail', $response['erreur']);
    }

    /* ============ updateUtilisateurFavoriProjet ============ */

    public function testUpdateUtilisateurFavoriProjetReturns400OnInvalidPreferenceStructure(): void
    {
        $response = $this->repo->updateUtilisateurFavoriProjet(
            ['wrong' => 'shape'],
            ['maven_key' => 'k', 'courriel' => 'u@x'],
        );

        $this->assertSame(400, $response['code']);
    }

    public function testUpdateUtilisateurFavoriProjetAddsNewFavori(): void
    {
        $preference = $this->buildValidPreference();

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->method('commit');

        $response = $this->repo->updateUtilisateurFavoriProjet(
            $preference,
            ['maven_key' => 'acme:new', 'courriel' => 'u@x'],
        );

        $this->assertSame(200, $response['code']);
        $this->assertSame(1, $response['statut']);
    }

    public function testUpdateUtilisateurFavoriProjetRemovesExistingFavori(): void
    {
        $preference = $this->buildValidPreference();
        $preference['favori_projet'] = ['acme:existing'];

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->method('commit');

        $response = $this->repo->updateUtilisateurFavoriProjet(
            $preference,
            ['maven_key' => 'acme:existing', 'courriel' => 'u@x'],
        );

        $this->assertSame(200, $response['code']);
        $this->assertSame(0, $response['statut']);
    }

    public function testUpdateUtilisateurFavoriProjetRollsBackOnException(): void
    {
        $preference = $this->buildValidPreference();

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willThrowException(new \RuntimeException('fail'));
        $this->connection->expects($this->once())->method('rollBack');

        $response = $this->repo->updateUtilisateurFavoriProjet(
            $preference,
            ['maven_key' => 'acme:new', 'courriel' => 'u@x'],
        );

        $this->assertSame(500, $response['code']);
    }

    /* ============ updateUtilisateurFavoriVersion ============ */

    public function testUpdateUtilisateurFavoriVersionAddsNewProjectWithVersion(): void
    {
        $preference = $this->buildValidPreference();

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->method('commit');

        $response = $this->repo->updateUtilisateurFavoriVersion(
            $preference,
            [
                'maven_key' => 'acme:app',
                'version' => '1.0.0',
                'favori' => 1,
                'courriel' => 'u@x',
            ],
        );

        $this->assertSame(200, $response['code']);
    }

    public function testUpdateUtilisateurFavoriVersionAddsVersionToExistingProject(): void
    {
        $preference = $this->buildValidPreference();
        $preference['favori_projet'] = ['acme:app'];
        $preference['favori_version'] = [['acme:app' => ['1.0.0']]];

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->method('commit');

        $response = $this->repo->updateUtilisateurFavoriVersion(
            $preference,
            [
                'maven_key' => 'acme:app',
                'version' => '2.0.0',
                'favori' => 1,
                'courriel' => 'u@x',
            ],
        );

        $this->assertSame(200, $response['code']);
    }

    public function testUpdateUtilisateurFavoriVersionRemovesProjectWhenLastVersion(): void
    {
        $preference = $this->buildValidPreference();
        $preference['favori_projet'] = ['acme:app'];
        $preference['favori_version'] = [['acme:app' => ['1.0.0']]];

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->method('commit');

        $response = $this->repo->updateUtilisateurFavoriVersion(
            $preference,
            [
                'maven_key' => 'acme:app',
                'version' => '1.0.0',
                'favori' => 0,
                'courriel' => 'u@x',
            ],
        );

        $this->assertSame(200, $response['code']);
    }

    public function testUpdateUtilisateurFavoriVersionRemovesOneVersionWhenMultiple(): void
    {
        $preference = $this->buildValidPreference();
        $preference['favori_projet'] = ['acme:app'];
        $preference['favori_version'] = [['acme:app' => ['1.0.0', '2.0.0']]];

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeStatement')->willReturn(1);
        $this->connection->method('commit');

        $response = $this->repo->updateUtilisateurFavoriVersion(
            $preference,
            [
                'maven_key' => 'acme:app',
                'version' => '1.0.0',
                'favori' => 0,
                'courriel' => 'u@x',
            ],
        );

        $this->assertSame(200, $response['code']);
    }

    public function testUpdateUtilisateurFavoriVersionRollsBackOnException(): void
    {
        $preference = $this->buildValidPreference();

        $this->connection->method('beginTransaction');
        $this->connection->method('prepare')->willThrowException(new \RuntimeException('fail'));
        $this->connection->expects($this->once())->method('rollBack');

        $response = $this->repo->updateUtilisateurFavoriVersion(
            $preference,
            [
                'maven_key' => 'acme:app', 'version' => '1.0.0', 'favori' => 1, 'courriel' => 'u@x',
            ],
        );

        $this->assertSame(500, $response['code']);
    }

    /* ============ helpers ============ */

    private function buildValidPreference(): array
    {
        return [
            'statut' => ['favori_projet' => false, 'favori_version' => false],
            'suivi_projet' => [],
            'favori_projet' => [],
            'favori_version' => [],
        ];
    }
}
