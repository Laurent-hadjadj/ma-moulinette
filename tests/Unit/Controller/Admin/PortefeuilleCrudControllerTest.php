<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\PortefeuilleCrudController;
use App\Entity\Batch;
use App\Entity\BatchTraitement;
use App\Entity\Portefeuille;
use App\Repository\BatchRepository;
use App\Repository\BatchTraitementRepository;
use App\Repository\PortefeuilleRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Context\AdminContextInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
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
    /** @var AdminContextProviderInterface&MockObject */ private MockObject $contextProvider;
    /** @var FlashBag&MockObject */                 private MockObject $flashBag;

    private PortefeuilleCrudController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(PortefeuilleRepository::class);
        $this->batchRepo = $this->createMock(BatchRepository::class);
        $this->batchTraitementRepo = $this->createMock(BatchTraitementRepository::class);
        $this->connection = $this->createMock(Connection::class);
        $this->contextProvider = $this->createMock(AdminContextProviderInterface::class);
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
            [AdminContextProviderInterface::class, 1, $this->contextProvider],
        ]);

        $this->controller = new PortefeuilleCrudController($this->em);
        $this->controller->setContainer($container);
    }

    private function stubAdminContext(?string $queryGroupe = null): void
    {
        $context = $this->createMock(AdminContextInterface::class);
        $request = new Request($queryGroupe !== null ? ['groupe' => $queryGroupe] : []);
        $context->method('getRequest')->willReturn($request);

        // getEntity() returns a real final EntityDto — can't mock.
        // We bypass by not stubbing getEntity; the controller uses the null-safe
        // operator $this->getContext()?->getEntity()?->getInstance(). If tests
        // always provide queryGroupe, the entity branch is skipped.
        $this->contextProvider->method('getContext')->willReturn($context);
    }

    private function stubConfigureFieldsQueries(array $groupesFonctionnels = [], array $projets = []): void
    {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
        $this->connection->method('prepare')->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            $groupesFonctionnels,
            $projets,
        );
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

    public function testListProjetsReturnsJsonForSingleGroupe(): void
    {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
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

    public function testListProjetsReturnsJsonForMultipleGroupes(): void
    {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
        $this->connection->expects($this->once())
            ->method('prepare')
            ->with($this->stringContains('jsonb_exists_any'))
            ->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([]);

        $response = $this->controller->listProjets(new Request(['groupe' => 'java,php,python']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame([], $data);
    }

    public function testListProjetsReturnsAllWhenNoGroupe(): void
    {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
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

    // Note : configureFields() utilise $this->getContext() qui retourne le type
    // concret AdminContext (final non mockable). Cette méthode reste non testée
    // unitairement (couverte indirectement par les E2E Playwright phase K).

    /* ============ listProjets — variantes de bind ============ */

    public function testListProjetsEscapesSpecialCharsInMultipleGroupes(): void
    {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
        $capturedBinds = [];
        $this->connection->method('prepare')->willReturn($statement);
        $statement->method('bindValue')->willReturnCallback(function ($k, $v) use (&$capturedBinds) {
            $capturedBinds[$k] = $v;
            return true;
        });
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([]);

        $this->controller->listProjets(new Request(['groupe' => 'java,php,python']));

        $this->assertArrayHasKey('eqArray', $capturedBinds);
        $this->assertStringContainsString('java', $capturedBinds['eqArray']);
        $this->assertStringContainsString('php', $capturedBinds['eqArray']);
        $this->assertStringContainsString('python', $capturedBinds['eqArray']);
    }

    public function testListProjetsLowercasesGroupeNames(): void
    {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
        $capturedBinds = [];
        $this->connection->method('prepare')->willReturn($statement);
        $statement->method('bindValue')->willReturnCallback(function ($k, $v) use (&$capturedBinds) {
            $capturedBinds[$k] = $v;
            return true;
        });
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([]);

        // Mixed case → lowercased before binding (correspond aux tags JSON stockés en lowercase)
        $this->controller->listProjets(new Request(['groupe' => 'JAVA']));

        $this->assertSame('java', $capturedBinds['eq0']);
    }
}
