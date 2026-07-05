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
        // fetchOne simule un tag correspondant → filtre appliqué
        $this->connection->method('fetchOne')->willReturn(1);

        $statement = $this->createMock(Statement::class);
        $result    = $this->createMock(Result::class);
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('jsonb_exists'))
            ->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([
            ['name' => 'App1', 'maven_key' => 'com.acme:app1'],
        ]);

        $response = $this->controller->listProjets(new Request(['groupe' => 'java']));
        $data = json_decode($response->getContent(), true);

        $this->assertCount(1, $data);
        $this->assertSame('App1', $data[0]['name']);
    }

    public function testListProjetsReturnsAllWhenTagHasNoMatches(): void
    {
        // fetchOne retourne 0 → tag sans correspondance → périmètre complet (Option A)
        $this->connection->method('fetchOne')->willReturn(0);

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

        $response = $this->controller->listProjets(new Request(['groupe' => 'groupe-sans-tag']));
        $data = json_decode($response->getContent(), true);

        $this->assertCount(2, $data);
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
        $data = json_decode($response->getContent(), true);

        $this->assertCount(2, $data);
    }

    public function testListProjetsBindsTagParameter(): void
    {
        $this->connection->method('fetchOne')->willReturn(1);

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
        $this->connection->method('fetchOne')->willReturn(1);

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
