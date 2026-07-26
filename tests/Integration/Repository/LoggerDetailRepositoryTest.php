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

namespace App\Tests\Integration\Repository;

use App\Entity\LoggerDetail;
use App\Repository\LoggerDetailRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Couvre LoggerDetailRepository contre la base de test réelle : insertion
 * batch, lecture par maven_key, agrégations par level/framework, suppression,
 * et le mapping d'erreurs de handleDatabaseException().
 */
#[AllowMockObjectsWithoutExpectations]
class LoggerDetailRepositoryTest extends KernelTestCase
{
    private const MAVEN_KEY = 'fr.ma-moulinette:logger-detail-repository-test';

    private EntityManagerInterface $em;
    private LoggerDetailRepository $repo;

    protected function setUp(): void
    {
        self::bootKernel();
        $container = static::getContainer();
        $this->em = $container->get(EntityManagerInterface::class);
        $this->repo = $this->em->getRepository(LoggerDetail::class);

        $this->em->createQuery('DELETE FROM ' . LoggerDetail::class . ' l WHERE l.mavenKey = :mk')
            ->setParameter('mk', self::MAVEN_KEY)
            ->execute();
    }

    protected function tearDown(): void
    {
        $this->em->createQuery('DELETE FROM ' . LoggerDetail::class . ' l WHERE l.mavenKey = :mk')
            ->setParameter('mk', self::MAVEN_KEY)
            ->execute();
        parent::tearDown();
    }

    /**
     * @param array<string, mixed> $overrides
     *
     * @return array{maven_key: string, project_version: ?string, level: string, framework: ?string,
     *     file_path: string, file_name: string, class_name: ?string, line_number: ?int,
     *     sonar_issue_key: ?string, mode_collecte: ?string, utilisateur_collecte: ?string,
     *     date_enregistrement: \DateTimeImmutable}
     */
    private function row(array $overrides = []): array
    {
        return array_merge([
            'maven_key' => self::MAVEN_KEY,
            'project_version' => '2.0.0-RELEASE',
            'level' => 'info',
            'framework' => 'SLF4J',
            'file_path' => 'src/main/java/App.java',
            'file_name' => 'App.java',
            'class_name' => 'App',
            'line_number' => 10,
            'sonar_issue_key' => null,
            'mode_collecte' => '[COLLECTE]',
            'utilisateur_collecte' => 'admin@ma-moulinette.fr',
            'date_enregistrement' => new \DateTimeImmutable('2026-01-01 08:00:00'),
        ], $overrides);
    }

    public function testInsertLoggerDetailBatchWithEmptyRowsReturnsZeroWithoutQuerying(): void
    {
        $result = $this->repo->insertLoggerDetailBatch([]);

        $this->assertSame(['code' => 200, 'erreur' => '', 'nombre' => 0], $result);
    }

    public function testInsertLoggerDetailBatchThenSelectByMavenKeyReturnsInsertedRows(): void
    {
        $insert = $this->repo->insertLoggerDetailBatch([
            $this->row(['level' => 'error', 'line_number' => 5]),
            $this->row(['level' => 'warn', 'line_number' => 20]),
        ]);
        $this->assertSame(['code' => 200, 'erreur' => '', 'nombre' => 2], $insert);

        $select = $this->repo->selectByMavenKey(self::MAVEN_KEY);

        $this->assertSame(200, $select['code']);
        $this->assertCount(2, $select['liste']);
        // Triées par file_path ASC puis line_number ASC : line 5 (error) avant line 20 (warn).
        $this->assertSame('error', $select['liste'][0]['level']);
        $this->assertSame('warn', $select['liste'][1]['level']);
    }

    public function testCountByMavenKeyGroupedByLevelAggregatesCorrectly(): void
    {
        $this->repo->insertLoggerDetailBatch([
            $this->row(['level' => 'error', 'file_path' => 'A.java']),
            $this->row(['level' => 'error', 'file_path' => 'B.java']),
            $this->row(['level' => 'warn', 'file_path' => 'C.java']),
        ]);

        $result = $this->repo->countByMavenKeyGroupedByLevel(self::MAVEN_KEY);

        $this->assertSame(200, $result['code']);
        $byLevel = array_column($result['liste'], 'nb', 'level');
        $this->assertSame(2, $byLevel['error']);
        $this->assertSame(1, $byLevel['warn']);
    }

    public function testCountByMavenKeyGroupedByFrameworkAggregatesCorrectly(): void
    {
        $this->repo->insertLoggerDetailBatch([
            $this->row(['framework' => 'SLF4J', 'file_path' => 'A.java']),
            $this->row(['framework' => 'SLF4J', 'file_path' => 'B.java']),
            $this->row(['framework' => 'Commons Logging', 'file_path' => 'C.java']),
        ]);

        $result = $this->repo->countByMavenKeyGroupedByFramework(self::MAVEN_KEY);

        $this->assertSame(200, $result['code']);
        $byFramework = array_column($result['liste'], 'nb', 'framework');
        $this->assertSame(2, $byFramework['SLF4J']);
        $this->assertSame(1, $byFramework['Commons Logging']);
    }

    public function testCountByMavenKeyGroupedByLevelAndFrameworkAggregatesCorrectly(): void
    {
        $this->repo->insertLoggerDetailBatch([
            $this->row(['level' => 'error', 'framework' => 'SLF4J', 'file_path' => 'A.java']),
            $this->row(['level' => 'error', 'framework' => 'SLF4J', 'file_path' => 'B.java']),
            $this->row(['level' => 'error', 'framework' => 'Commons Logging', 'file_path' => 'C.java']),
        ]);

        $result = $this->repo->countByMavenKeyGroupedByLevelAndFramework(self::MAVEN_KEY);

        $this->assertSame(200, $result['code']);
        $rows = $result['liste'];
        $this->assertCount(2, $rows);
        $bySlf4j = current(array_filter($rows, static fn ($r) => $r['framework'] === 'SLF4J'));
        $this->assertSame(2, $bySlf4j['nb']);
    }

    public function testDeleteLoggerDetailMavenKeyRemovesRows(): void
    {
        $this->repo->insertLoggerDetailBatch([$this->row()]);
        $this->assertSame(200, $this->repo->selectByMavenKey(self::MAVEN_KEY)['code']);
        $this->assertCount(1, $this->repo->selectByMavenKey(self::MAVEN_KEY)['liste']);

        $result = $this->repo->deleteLoggerDetailMavenKey(['maven_key' => self::MAVEN_KEY]);

        $this->assertSame(['code' => 200, 'erreur' => ''], $result);
        $this->assertSame([], $this->repo->selectByMavenKey(self::MAVEN_KEY)['liste']);
    }

    public function testHandleDatabaseExceptionMapsUniqueConstraintViolation(): void
    {
        $exception = $this->createMock(\Doctrine\DBAL\Exception\UniqueConstraintViolationException::class);

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(['code' => 23505, 'erreur' => 'Les informations existent déjà.'], $result);
    }

    public function testHandleDatabaseExceptionMapsConnectionException(): void
    {
        $exception = $this->createMock(\Doctrine\DBAL\Exception\ConnectionException::class);

        $result = $this->repo->handleDatabaseException($exception);

        $this->assertSame(500, $result['code']);
        $this->assertSame('La connexion à la base de données a échoué.', $result['erreur']);
    }

    public function testHandleDatabaseExceptionFallsBackToGenericMessage(): void
    {
        $result = $this->repo->handleDatabaseException(new \RuntimeException('boom'));

        $this->assertSame(['code' => 500, 'erreur' => 'boom'], $result);
    }
}
