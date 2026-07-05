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

use App\Controller\Admin\PortefeuilleCrudController;
use App\Entity\{Batch, BatchTraitement, Portefeuille};
use App\Repository\{BatchRepository, BatchTraitementRepository, PortefeuilleRepository};
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Assets, Crud, Filters};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class PortefeuilleCrudControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */   private MockObject $em;
    /** @var PortefeuilleRepository&MockObject */   private MockObject $repo;
    /** @var BatchRepository&MockObject */          private MockObject $batchRepo;
    /** @var BatchTraitementRepository&MockObject */ private MockObject $batchTraitementRepo;
    /** @var Connection&MockObject */               private MockObject $connection;
    /** @var FlashBag&MockObject */                 private MockObject $flashBag;

    private PortefeuilleCrudController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(PortefeuilleRepository::class);
        $this->batchRepo = $this->createMock(BatchRepository::class);
        $this->batchTraitementRepo = $this->createMock(BatchTraitementRepository::class);
        $this->connection = $this->createMock(Connection::class);
        $this->flashBag = $this->createMock(FlashBag::class);

        $this->em->method('getRepository')->willReturnMap([
            [Portefeuille::class, $this->repo],
            [Batch::class, $this->batchRepo],
            [BatchTraitement::class, $this->batchTraitementRepo],
        ]);
        $this->em->method('getConnection')->willReturn($this->connection);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'request_stack'
        );
        $container->method('get')->willReturnMap([
            ['request_stack', 1, $requestStack],
        ]);

        $this->controller = new PortefeuilleCrudController($this->em);
        $this->controller->setContainer($container);
    }

    public function testGetEntityFqcnReturnsPortefeuille(): void
    {
        $this->assertSame(Portefeuille::class, PortefeuilleCrudController::getEntityFqcn());
    }

    public function testConfigureCrudReturnsCrud(): void
    {
        $this->assertInstanceOf(Crud::class, $this->controller->configureCrud(Crud::new()));
    }

    public function testConfigureFiltersReturnsFilters(): void
    {
        $this->assertInstanceOf(Filters::class, $this->controller->configureFilters(Filters::new()));
    }

    public function testPersistEntityIgnoresNonMatching(): void
    {
        $this->em->expects($this->never())->method('persist');

        $this->controller->persistEntity($this->em, new \stdClass());
    }

    public function testPersistEntityDoesNothingWhenRecordExists(): void
    {
        $p = new Portefeuille();
        $p->setPortefeuille('mon-portefeuille');

        $existing = new Portefeuille();
        $this->repo->method('findOneBy')->willReturn($existing);

        $this->em->expects($this->never())->method('persist');

        $this->controller->persistEntity($this->em, $p);

        $this->assertSame('MON-PORTEFEUILLE', $p->getPortefeuille());
    }

    public function testPersistEntitySetsUppercaseAndDateWhenNew(): void
    {
        $p = new Portefeuille();
        $p->setPortefeuille('mon-portefeuille');

        $this->repo->method('findOneBy')->willReturn(null);

        $this->controller->persistEntity($this->em, $p);

        $this->assertSame('MON-PORTEFEUILLE', $p->getPortefeuille());
        $this->assertInstanceOf(\DateTimeImmutable::class, $p->getDateEnregistrement());
    }

    public function testUpdateEntityIgnoresNonMatching(): void
    {
        $this->em->expects($this->never())->method('persist');

        $this->controller->updateEntity($this->em, new \stdClass());
    }

    /* ============ updateEntity happy path ============ */

    public function testUpdateEntityUpdatesBatchWhenMatchingBatchExists(): void
    {
        $p = new Portefeuille();
        $p->setPortefeuille('MON-PF');
        $p->setListe(['a', 'b', 'c']);

        $existingBatch = new Batch();
        $this->batchRepo->method('findOneBy')->willReturn($existingBatch);
        $this->batchRepo->expects($this->once())
            ->method('updatePortefeuille')
            ->with($this->arrayHasKey('nombre_projet'));
        $this->batchTraitementRepo->expects($this->once())
            ->method('updatePortefeuille');

        $this->controller->updateEntity($this->em, $p);

        $this->assertNotNull($p->getDateModification());
    }

    public function testUpdateEntitySkipsBatchUpdateWhenNoMatchingBatch(): void
    {
        $p = new Portefeuille();
        $p->setPortefeuille('PF');
        $p->setListe([]);

        $this->batchRepo->method('findOneBy')->willReturn(null);
        $this->batchRepo->expects($this->never())->method('updatePortefeuille');
        $this->batchTraitementRepo->expects($this->never())->method('updatePortefeuille');

        $this->controller->updateEntity($this->em, $p);

        $this->assertNotNull($p->getDateModification());
    }

    public function testUpdateEntityFlashesOnException(): void
    {
        $p = new Portefeuille();
        $p->setPortefeuille('PF');
        $p->setListe([]);

        $this->batchRepo->method('findOneBy')->willThrowException(new \RuntimeException('boom'));

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('danger', $this->stringContains('boom'));

        $this->controller->updateEntity($this->em, $p);
    }

    public function testConfigureAssetsReturnsAssets(): void
    {
        $this->assertInstanceOf(Assets::class, $this->controller->configureAssets(Assets::new()));
    }

}
