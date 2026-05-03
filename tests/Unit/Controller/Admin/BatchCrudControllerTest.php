<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\BatchCrudController;
use App\Entity\Batch;
use App\Entity\BatchExecution;
use App\Entity\BatchTraitement;
use App\Entity\Utilisateur;
use App\Exception\SqlRequestException;
use App\Repository\BatchExecutionRepository;
use App\Repository\BatchTraitementRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[AllowMockObjectsWithoutExpectations]
class BatchCrudControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */       private MockObject $em;
    /** @var TokenStorageInterface&MockObject */        private MockObject $token;
    /** @var BatchTraitementRepository&MockObject */    private MockObject $batchTraitementRepo;
    /** @var BatchExecutionRepository&MockObject */     private MockObject $batchExecutionRepo;
    /** @var Connection&MockObject */                   private MockObject $connection;

    private BatchCrudController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->token = $this->createMock(TokenStorageInterface::class);
        $this->batchTraitementRepo = $this->createMock(BatchTraitementRepository::class);
        $this->batchExecutionRepo = $this->createMock(BatchExecutionRepository::class);
        $this->connection = $this->createMock(Connection::class);

        $this->em->method('getConnection')->willReturn($this->connection);
        $this->em->method('getRepository')->willReturnMap([
            [BatchTraitement::class, $this->batchTraitementRepo],
            [BatchExecution::class, $this->batchExecutionRepo],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'security.token_storage'
        );
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $this->token],
        ]);

        $this->controller = new BatchCrudController($this->em);
        $this->controller->setContainer($container);
    }

    public function testGetEntityFqcnReturnsBatch(): void
    {
        $this->assertSame(Batch::class, BatchCrudController::getEntityFqcn());
    }

    public function testConfigureActionsReturnsActions(): void
    {
        $actions = Actions::new()
            ->add(Crud::PAGE_INDEX, \EasyCorp\Bundle\EasyAdminBundle\Config\Action::new(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::DETAIL)
                ->linkToCrudAction(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::DETAIL));

        $this->assertInstanceOf(Actions::class, $this->controller->configureActions($actions));
    }

    public function testConfigureFiltersReturnsFilters(): void
    {
        $this->assertInstanceOf(Filters::class, $this->controller->configureFilters(Filters::new()));
    }

    public function testConfigureFieldsYieldsFieldsWhenPortefeuillesExist(): void
    {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
        $this->connection->method('prepare')->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([
            ['titre' => 'P1'],
            ['titre' => 'P2'],
        ]);

        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW), false);

        $this->assertGreaterThanOrEqual(8, count($fields));
    }

    public function testConfigureFieldsHandlesEmptyPortefeuilleList(): void
    {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
        $this->connection->method('prepare')->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([]);

        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW), false);

        $this->assertGreaterThanOrEqual(8, count($fields));
    }

    public function testPersistEntityIgnoresNonBatch(): void
    {
        $this->em->expects($this->never())->method('persist');

        $this->controller->persistEntity($this->em, new \stdClass());
    }

    public function testPersistEntityThrowsOnInsertBatchTraitementFailure(): void
    {
        $batch = $this->makeBatch();
        $this->stubUser();

        // connection->executeQuery() directly for the portefeuille query
        $result = $this->createMock(Result::class);
        $this->connection->method('executeQuery')->willReturn($result);
        $result->method('fetchAssociative')->willReturn(['liste' => '["a","b"]']);

        $this->batchTraitementRepo->expects($this->once())
            ->method('insertBatchTraitement')
            ->willReturn(['code' => 500, 'erreur' => 'insert fail']);

        $this->expectException(SqlRequestException::class);

        $this->controller->persistEntity($this->em, $batch);
    }

    public function testUpdateEntityIgnoresNonBatch(): void
    {
        $this->em->expects($this->never())->method('flush');

        $this->controller->updateEntity($this->em, new \stdClass());
    }

    public function testUpdateEntityUsesBindParametersForSelectAndUpdates(): void
    {
        $batch = $this->makeBatch();
        $batch->setNombreProjet(0);
        $batch->setTraitementId(new \Symfony\Component\Uid\Ulid());

        // connection->executeQuery() returns result with liste
        $result = $this->createMock(Result::class);
        $this->connection->expects($this->once())
            ->method('executeQuery')
            ->with(
                $this->stringContains(':portefeuille'),
                $this->arrayHasKey('portefeuille')
            )
            ->willReturn($result);
        $result->method('fetchAssociative')->willReturn(['liste' => '["a","b","c"]']);

        // Two executeStatement calls expected : UPDATE batch + UPDATE batch_traitement
        $this->connection->expects($this->exactly(2))
            ->method('executeStatement')
            ->willReturn(1);

        $this->controller->updateEntity($this->em, $batch);

        $this->assertNotNull($batch->getDateModification());
    }

    public function testDeleteEntityIgnoresNonBatch(): void
    {
        $this->em->expects($this->never())->method('remove');

        $this->controller->deleteEntity($this->em, new \stdClass());
    }

    public function testDeleteEntityRemovesAndFlushesOnHappyPath(): void
    {
        $batch = $this->makeBatch();

        $this->batchTraitementRepo->expects($this->once())
            ->method('deleteTraitement')
            ->willReturn(['code' => 200]);
        $this->batchExecutionRepo->expects($this->once())
            ->method('deleteTraitement')
            ->willReturn(['code' => 200]);

        $this->em->expects($this->once())->method('remove')->with($batch);
        $this->em->expects($this->once())->method('flush');

        $this->controller->deleteEntity($this->em, $batch);
    }

    public function testDeleteEntityThrowsOnTraitementFailure(): void
    {
        $batch = $this->makeBatch();

        $this->batchTraitementRepo->method('deleteTraitement')->willReturn([
            'code' => 500, 'erreur' => 'fail',
        ]);

        $this->expectException(SqlRequestException::class);

        $this->controller->deleteEntity($this->em, $batch);
    }

    private function stubUser(): void
    {
        $user = new Utilisateur();
        $user->setPrenom('Alice');
        $user->setNom('Dupont');
        $user->setCourriel('alice@x');
        $this->token->method('getToken')
            ->willReturn(new UsernamePasswordToken($user, 'main', ['ROLE_USER']));
    }

    private function makeBatch(): Batch
    {
        $b = new Batch();
        $b->setActivated(true);
        $b->setAutomatique(false);
        $b->setTitre('Ma App');
        $b->setPortefeuille('pfl');
        $b->setDescription('Description');
        return $b;
    }
}
