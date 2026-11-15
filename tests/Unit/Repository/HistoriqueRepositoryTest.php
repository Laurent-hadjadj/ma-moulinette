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

namespace App\Tests\Unit\Repository;

use App\Entity\Historique;
use App\Repository\HistoriqueRepository;
use Doctrine\DBAL\Exception as DBALException;
use Doctrine\DBAL\{Connection, Result, Statement};
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\Persistence\ManagerRegistry;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * MODIF 2026-07-23 : tests Unit pour HistoriqueRepository::selectHistoriqueActuatorInfo
 * (remise à niveau du module Actuator, pastille + modale page Projet).
 */
#[AllowMockObjectsWithoutExpectations]
class HistoriqueRepositoryTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */ private MockObject $em;
    /** @var Connection&MockObject */             private MockObject $connection;
    /** @var Statement&MockObject */              private MockObject $statement;
    /** @var Result&MockObject */                 private MockObject $result;

    private HistoriqueRepository $repo;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->statement  = $this->createMock(Statement::class);
        $this->result     = $this->createMock(Result::class);

        $classMetadata = new ClassMetadata(Historique::class);
        $this->em->method('getClassMetadata')->willReturn($classMetadata);
        $this->em->method('getConnection')->willReturn($this->connection);

        $registry = $this->createMock(ManagerRegistry::class);
        $registry->method('getManagerForClass')->willReturn($this->em);

        $this->repo = new HistoriqueRepository($registry);
    }

    public function testSelectHistoriqueActuatorInfoReturnsNullWhenNoRow(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->expects($this->once())
            ->method('bindValue')
            ->with(':maven_key', 'fr.ma-moulinette:ma-moulinette');
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAssociative')->willReturn(false);

        $response = $this->repo->selectHistoriqueActuatorInfo(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertSame(200, $response['code']);
        $this->assertNull($response['actuator_info']);
    }

    public function testSelectHistoriqueActuatorInfoDecodesJsonColumn(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAssociative')->willReturn([
            'actuator_info' => '{"date_extraction":"2026-07-23 20:00:26","code":200,"app.version":"1.9.1"}',
        ]);

        $response = $this->repo->selectHistoriqueActuatorInfo(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertSame(200, $response['code']);
        $this->assertSame('1.9.1', $response['actuator_info']['app.version']);
    }

    public function testSelectHistoriqueActuatorInfoReturnsNullWhenColumnIsNull(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchAssociative')->willReturn(['actuator_info' => null]);

        $response = $this->repo->selectHistoriqueActuatorInfo(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertNull($response['actuator_info']);
    }

    public function testSelectHistoriqueActuatorInfoReturnsErrorOnDBALException(): void
    {
        $exception = new class('SQL syntax error') extends \RuntimeException implements DBALException {};

        $this->connection->method('prepare')->willThrowException($exception);

        $response = $this->repo->selectHistoriqueActuatorInfo(['maven_key' => 'fr.ma-moulinette:ma-moulinette']);

        $this->assertSame(500, $response['code']);
        $this->assertStringContainsString('SQL syntax error', $response['erreur']);
    }
    /**
     * Non-régression : app:historique:rebuild passe `date_version` en DateTime. Avant correction, le cast (string) de la
     * comparaison avec la ligne existante levait « Object of class DateTime could not be converted to string ».
     */
    public function testInsertHistoriqueAjoutProjetAccepteUneDateVersionDateTimeQuandLaVersionExisteDeja(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchFirstColumn')->willReturn(['2026-11-15 12:17:15+01']);

        $response = $this->repo->insertHistoriqueAjoutProjet([
            'maven_key' => 'fr.ma.moulinette:track-logger-method',
            'version' => '2.1.0-RELEASE',
            'date_version' => new \DateTime('2026-11-15 12:17:15+01:00'),
        ], []);

        $this->assertSame(23505, $response['code']);
    }

    public function testInsertHistoriqueAjoutProjetRemplaceLaLigneExistanteSiLaDateVersionDateTimeEstPlusRecente(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchFirstColumn')->willReturn(['2026-11-14 08:00:00+01']);

        $liaisons = [];
        $this->statement->method('bindValue')->willReturnCallback(
            function (string|int $param, mixed $valeur) use (&$liaisons): bool {
                $liaisons[(string) $param] = $valeur;
                return true;
            }
        );

        $response = $this->repo->insertHistoriqueAjoutProjet([
            'maven_key' => 'fr.ma.moulinette:track-logger-method',
            'version' => '2.1.0-RELEASE',
            'date_version' => new \DateTime('2026-11-15 12:17:15+01:00'),
        ], []);

        $this->assertSame(200, $response['code']);
        $this->assertSame('2026-11-15 12:17:15+0100', $liaisons[':date_version']);
    }

    public function testInsertHistoriqueAjoutProjetLaisseUneDateVersionTexteInchangee(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchFirstColumn')->willReturn([]);

        $liaisons = [];
        $this->statement->method('bindValue')->willReturnCallback(
            function (string|int $param, mixed $valeur) use (&$liaisons): bool {
                $liaisons[(string) $param] = $valeur;
                return true;
            }
        );

        $response = $this->repo->insertHistoriqueAjoutProjet([
            'maven_key' => 'fr.ma.moulinette:track-logger-method',
            'version' => '2.1.0-RELEASE',
            'date_version' => '2026-11-15T12:17:15+0100',
        ], []);

        $this->assertSame(200, $response['code']);
        $this->assertSame('2026-11-15T12:17:15+0100', $liaisons[':date_version']);
    }

    public function testAnalyseExisteRendVraiQuandUneLigneCorrespond(): void
    {
        $liaisons = [];
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('bindValue')->willReturnCallback(
            function (string|int $param, mixed $valeur) use (&$liaisons): bool {
                $liaisons[(string) $param] = $valeur;
                return true;
            }
        );
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchOne')->willReturn(1);

        $this->assertTrue($this->repo->analyseExiste('fr.ma:projet', 'AK-1'));
        $this->assertSame(['maven_key' => 'fr.ma:projet', 'analyse_key' => 'AK-1'], $liaisons);
    }

    public function testAnalyseExisteRendFauxQuandAucuneLigneNeCorrespond(): void
    {
        $this->connection->method('prepare')->willReturn($this->statement);
        $this->statement->method('executeQuery')->willReturn($this->result);
        $this->result->method('fetchOne')->willReturn(false);

        $this->assertFalse($this->repo->analyseExiste('fr.ma:projet', 'AK-inconnue'));
    }
}
