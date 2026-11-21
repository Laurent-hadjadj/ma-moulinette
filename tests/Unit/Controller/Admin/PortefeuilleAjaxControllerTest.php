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

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\PortefeuilleAjaxController;
use Doctrine\DBAL\{Connection, Result, Statement};
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Request;

/**
 * MODIF 2026-06-03 : tests extraits de PortefeuilleCrudControllerTest
 * suite au déplacement de listProjets vers PortefeuilleAjaxController.
 *
 * MODIF 2026-11-19 : suppression du repli sur le périmètre complet quand
 * aucun projet ne porte le tag — retrait des mocks fetchOne() devenus
 * inutiles (le COUNT(*) intermédiaire n'existe plus).
 */
#[AllowMockObjectsWithoutExpectations]
class PortefeuilleAjaxControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */
    private MockObject $em;
    /** @var Connection&MockObject */
    private MockObject $connection;

    private PortefeuilleAjaxController $controller;

    protected function setUp(): void
    {
        $this->em         = $this->createMock(EntityManagerInterface::class);
        $this->connection = $this->createMock(Connection::class);
        $this->em->method('getConnection')->willReturn($this->connection);

        $logger = $this->createMock(LoggerInterface::class);
        $this->controller = new PortefeuilleAjaxController($this->em, $logger);
    }

    public function testListProjetsReturnsJsonForSingleGroupe(): void
    {
        $statement = $this->createMock(Statement::class);
        $result    = $this->createMock(Result::class);
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('jsonb_exists'))
            ->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([
            ['name' => 'App1', 'maven_key' => 'fr.ma-moulinette:ma-moulinette'],
        ]);

        $response = $this->controller->listProjets(new Request(['groupe' => 'java']));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertCount(1, $data);
        $this->assertSame('App1', $data[0]['name']);
    }

    /**
     * Régression 2026-11-19 : BUG CORRIGÉ — un groupe fonctionnel dont le tag
     * SonarQube ne correspond à aucun projet (tag jamais posé, ou retiré
     * entre-temps) renvoyait par repli le périmètre complet au lieu d'une
     * liste vide. Un groupe sans projet doit afficher une liste vide.
     */
    public function testListProjetsReturnsEmptyWhenTagHasNoMatches(): void
    {
        $statement = $this->createMock(Statement::class);
        $result    = $this->createMock(Result::class);
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('jsonb_exists'))
            ->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([]);

        $response = $this->controller->listProjets(new Request(['groupe' => 'groupe-sans-tag']));
        $data = json_decode((string) $response->getContent(), true);

        $this->assertCount(0, $data);
    }

    public function testListProjetsReturnsAllWhenNoGroupe(): void
    {
        $statement = $this->createMock(Statement::class);
        $result    = $this->createMock(Result::class);
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->logicalNot($this->stringContains('jsonb_exists')))
            ->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([
            ['name' => 'AppA', 'maven_key' => 'a'],
            ['name' => 'AppB', 'maven_key' => 'b'],
        ]);

        $response = $this->controller->listProjets(new Request());
        $data = json_decode((string) $response->getContent(), true);

        $this->assertCount(2, $data);
    }

    public function testListProjetsBindsTagParameter(): void
    {
        $statement     = $this->createMock(Statement::class);
        $result        = $this->createMock(Result::class);
        $capturedBinds = [];
        $this->connection->method('prepare')->willReturn($statement);
        $statement->method('bindValue')->willReturnCallback(function ($k, $v) use (&$capturedBinds) {
            $capturedBinds[$k] = $v;
            return true;
        });
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([]);

        $this->controller->listProjets(new Request(['groupe' => 'java']));

        $this->assertArrayHasKey('tag', $capturedBinds);
        $this->assertSame('java', $capturedBinds['tag']);
    }

    public function testListProjetsLowercasesGroupeNames(): void
    {
        $statement     = $this->createMock(Statement::class);
        $result        = $this->createMock(Result::class);
        $capturedBinds = [];
        $this->connection->method('prepare')->willReturn($statement);
        $statement->method('bindValue')->willReturnCallback(function ($k, $v) use (&$capturedBinds) {
            $capturedBinds[$k] = $v;
            return true;
        });
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([]);

        // Mixed case → lowercased avant le bind (tags JSON stockés en lowercase)
        $this->controller->listProjets(new Request(['groupe' => 'JAVA']));

        $this->assertSame('java', $capturedBinds['tag']);
    }
}
