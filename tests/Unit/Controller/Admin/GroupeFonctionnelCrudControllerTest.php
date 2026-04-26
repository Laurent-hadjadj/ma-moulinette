<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\GroupeFonctionnelCrudController;
use App\Entity\GroupeFonctionnel;
use App\Repository\GroupeFonctionnelRepository;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Assets;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class GroupeFonctionnelCrudControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */     private MockObject $em;
    /** @var RequestStack&MockObject */               private MockObject $requestStack;
    /** @var GroupeFonctionnelRepository&MockObject */private MockObject $repo;
    /** @var Connection&MockObject */                 private MockObject $connection;
    /** @var FlashBag&MockObject */                   private MockObject $flashBag;

    private GroupeFonctionnelCrudController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->requestStack = $this->createMock(RequestStack::class);
        $this->repo = $this->createMock(GroupeFonctionnelRepository::class);
        $this->connection = $this->createMock(Connection::class);
        $this->flashBag = $this->createMock(FlashBag::class);

        $this->em->method('getRepository')->willReturn($this->repo);
        $this->em->method('getConnection')->willReturn($this->connection);

        // Stub DBAL chain: prepare()->executeQuery()->fetchAllAssociative()
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
        $this->connection->method('prepare')->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturn([
            ['tag' => 'java'],
            ['tag' => 'php'],
        ]);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $containerRequestStack = $this->createMock(RequestStack::class);
        $containerRequestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'request_stack'
        );
        $container->method('get')->willReturnMap([
            ['request_stack', 1, $containerRequestStack],
        ]);

        $this->controller = new GroupeFonctionnelCrudController($this->em, $this->requestStack);
        $this->controller->setContainer($container);
    }

    public function testGetEntityFqcnReturnsGroupeFonctionnel(): void
    {
        $this->assertSame(GroupeFonctionnel::class, GroupeFonctionnelCrudController::getEntityFqcn());
    }

    public function testConfigureCrudReturnsCrud(): void
    {
        $this->assertInstanceOf(Crud::class, $this->controller->configureCrud(Crud::new()));
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

    public function testConfigureAssetsAddsJsFile(): void
    {
        $this->assertInstanceOf(Assets::class, $this->controller->configureAssets(Assets::new()));
    }

    public function testConfigureFieldsYieldsExpectedFields(): void
    {
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW), false);

        $this->assertGreaterThanOrEqual(7, count($fields));
    }

    public function testPersistEntityIgnoresNonMatching(): void
    {
        $this->em->expects($this->never())->method('persist');

        $this->controller->persistEntity($this->em, new \stdClass());
    }

    public function testPersistEntityFlashesDangerWhenExists(): void
    {
        $groupe = new GroupeFonctionnel();
        $groupe->setGroupeFonctionnel('Java');
        $groupe->setDescription('desc');

        $this->repo->method('findOneBy')->willReturn(new GroupeFonctionnel());

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('danger', $this->stringContains('existe déjà'));

        $this->em->expects($this->never())->method('persist');

        $this->controller->persistEntity($this->em, $groupe);
    }

    public function testPersistEntityNormalizesGroupeName(): void
    {
        $groupe = new GroupeFonctionnel();
        $groupe->setGroupeFonctionnel('Java-Cool!!!');
        $groupe->setDescription('desc');

        $this->repo->method('findOneBy')->willReturn(null);

        $this->controller->persistEntity($this->em, $groupe);

        $this->assertStringNotContainsString('!', $groupe->getGroupeFonctionnel());
    }
}
