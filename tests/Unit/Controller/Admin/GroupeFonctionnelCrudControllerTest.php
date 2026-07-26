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

use App\Controller\Admin\GroupeFonctionnelCrudController;
use App\Entity\GroupeFonctionnel;
use App\Repository\GroupeFonctionnelRepository;
use Doctrine\DBAL\{Connection, Result,  Statement};
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Actions, Assets, Crud, Filters};
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
    /** @var GroupeFonctionnelRepository&MockObject */private MockObject $repo;
    /** @var Connection&MockObject */                 private MockObject $connection;
    /** @var FlashBag&MockObject */                   private MockObject $flashBag;

    private GroupeFonctionnelCrudController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
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

        $this->controller = new GroupeFonctionnelCrudController($this->em);
        $this->controller->setContainer($container);
    }

    public function testGetEntityFqcnReturnsGroupeFonctionnel(): void
    {
        $this->assertSame(GroupeFonctionnel::class, GroupeFonctionnelCrudController::getEntityFqcn());
    }

    public function testConfigureCrudReturnsCrud(): void
    {
        $result = $this->controller->configureCrud(Crud::new());

        $this->assertNotNull($result->getAsDto()->getCustomPageTitle(Crud::PAGE_INDEX));
    }

    public function testConfigureActionsReturnsActions(): void
    {
        $actions = Actions::new()
            ->add(Crud::PAGE_INDEX, \EasyCorp\Bundle\EasyAdminBundle\Config\Action::new(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::DETAIL)
                ->linkToCrudAction(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::DETAIL));

        $result = $this->controller->configureActions($actions);

        $this->assertNull($result->getAsDto(Crud::PAGE_INDEX)->getAction(Crud::PAGE_INDEX, \EasyCorp\Bundle\EasyAdminBundle\Config\Action::DETAIL));
    }

    public function testConfigureFiltersReturnsFilters(): void
    {
        $result = $this->controller->configureFilters(Filters::new());

        $this->assertNotNull($result->getAsDto()->getFilter('groupeFonctionnel'));
    }

    public function testConfigureAssetsAddsJsFile(): void
    {
        $result = $this->controller->configureAssets(Assets::new());

        $jsPaths = array_map(
            static fn ($asset) => $asset->getValue(),
            $result->getAsDto()->getJsAssets()
        );
        $this->assertContains('js/easy-admin/groupe-fonctionnel.js', $jsPaths);
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

    public function testPersistEntitySetsDateEnregistrement(): void
    {
        $groupe = new GroupeFonctionnel();
        $groupe->setGroupeFonctionnel('backend');
        $groupe->setDescription('desc');
        $this->repo->method('findOneBy')->willReturn(null);

        $before = new \DateTimeImmutable();
        $this->controller->persistEntity($this->em, $groupe);

        $this->assertGreaterThanOrEqual($before, $groupe->getDateEnregistrement());
    }

    public function testNormalizeConvertsToLowercase(): void
    {
        $groupe = new GroupeFonctionnel();
        $groupe->setGroupeFonctionnel('JAVA-BACKEND');
        $groupe->setDescription('desc');
        $this->repo->method('findOneBy')->willReturn(null);

        $this->controller->persistEntity($this->em, $groupe);

        $this->assertSame('java-backend', $groupe->getGroupeFonctionnel());
    }

    public function testNormalizeReplacesSpacesWithUnderscore(): void
    {
        $groupe = new GroupeFonctionnel();
        $groupe->setGroupeFonctionnel('my group');
        $groupe->setDescription('desc');
        $this->repo->method('findOneBy')->willReturn(null);

        $this->controller->persistEntity($this->em, $groupe);

        $this->assertSame('my_group', $groupe->getGroupeFonctionnel());
    }

    public function testNormalizeTrimsDashesAndUnderscores(): void
    {
        $groupe = new GroupeFonctionnel();
        $groupe->setGroupeFonctionnel('---java---');
        $groupe->setDescription('desc');
        $this->repo->method('findOneBy')->willReturn(null);

        $this->controller->persistEntity($this->em, $groupe);

        $this->assertSame('java', $groupe->getGroupeFonctionnel());
    }
}
