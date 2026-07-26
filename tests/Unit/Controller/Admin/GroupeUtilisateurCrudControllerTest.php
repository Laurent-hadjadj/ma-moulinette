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

use App\Controller\Admin\GroupeUtilisateurCrudController;
use App\Entity\GroupeUtilisateur;
use App\Repository\GroupeUtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Actions, Crud, Filters};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;

#[AllowMockObjectsWithoutExpectations]
class GroupeUtilisateurCrudControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */        private MockObject $em;
    /** @var GroupeUtilisateurRepository&MockObject */   private MockObject $repo;
    /** @var FlashBag&MockObject */                      private MockObject $flashBag;

    private GroupeUtilisateurCrudController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->repo = $this->createMock(GroupeUtilisateurRepository::class);
        $this->flashBag = $this->createMock(FlashBag::class);

        $this->em->method('getRepository')->willReturn($this->repo);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $containerRequestStack = $this->createMock(RequestStack::class);
        $containerRequestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'request_stack'
        );
        $container->method('get')->willReturnMap([
            ['request_stack', 1, $containerRequestStack],
        ]);

        $this->controller = new GroupeUtilisateurCrudController();
        $this->controller->setContainer($container);
    }

    public function testGetEntityFqcnReturnsGroupeUtilisateur(): void
    {
        $this->assertSame(GroupeUtilisateur::class, GroupeUtilisateurCrudController::getEntityFqcn());
    }

    public function testConfigureCrudReturnsCrud(): void
    {
        $crud = $this->controller->configureCrud(Crud::new());
        $this->assertNotNull($crud->getAsDto()->getCustomPageTitle(Crud::PAGE_INDEX));
    }

    public function testConfigureActionsRemovesEditAndDetail(): void
    {
        $actions = $this->buildActionsWithDefaults();

        $result = $this->controller->configureActions($actions);

        $dto = $result->getAsDto(Crud::PAGE_INDEX);
        $this->assertNull($dto->getAction(Crud::PAGE_INDEX, \EasyCorp\Bundle\EasyAdminBundle\Config\Action::EDIT));
        $this->assertNull($dto->getAction(Crud::PAGE_INDEX, \EasyCorp\Bundle\EasyAdminBundle\Config\Action::DETAIL));
    }

    private function buildActionsWithDefaults(): Actions
    {
        return Actions::new()
            ->add(Crud::PAGE_INDEX, \EasyCorp\Bundle\EasyAdminBundle\Config\Action::new(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::EDIT)
                ->linkToCrudAction(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::EDIT))
            ->add(Crud::PAGE_INDEX, \EasyCorp\Bundle\EasyAdminBundle\Config\Action::new(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::DETAIL)
                ->linkToCrudAction(\EasyCorp\Bundle\EasyAdminBundle\Config\Action::DETAIL));
    }

    public function testConfigureFiltersAddsGroupeUtilisateur(): void
    {
        $filters = $this->controller->configureFilters(Filters::new());
        $this->assertNotNull($filters->getAsDto()->getFilter('groupeUtilisateur'));
    }

    public function testConfigureFieldsYieldsAllExpectedFields(): void
    {
        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW), false);

        // Au moins 7 champs (2 colonnes + information + groupeUtilisateur + description + groupeId + 2 dates)
        $this->assertGreaterThanOrEqual(7, count($fields));
    }

    public function testPersistEntityIgnoresNonMatchingEntity(): void
    {
        $this->em->expects($this->never())->method('persist');

        $this->controller->persistEntity($this->em, new \stdClass());
    }

    public function testPersistEntityFlashesDangerWhenGroupeExists(): void
    {
        /* MODIF 2026-07-19 : verrouille le fix — persistEntity() tombait dans
         * parent::persistEntity() même après avoir détecté un doublon (déjà
         * corrigé côté GroupeFonctionnelCrudController, qui a le `return;`),
         * ce qui provoquait une violation de contrainte d'unicité PostgreSQL
         * non rattrapée derrière un flash "Ce groupe existe déjà" pourtant
         * correctement affiché. */
        $groupe = new GroupeUtilisateur();
        $groupe->setGroupeUtilisateur('Service SI');
        $groupe->setDescription('desc');

        $existing = new GroupeUtilisateur();
        $this->repo->method('findOneBy')->willReturn($existing);

        $this->flashBag->expects($this->once())
            ->method('add')
            ->with('danger', $this->stringContains('existe déjà'));

        $this->em->expects($this->never())->method('persist');

        $this->controller->persistEntity($this->em, $groupe);

        // Normalisation doit avoir été appliquée (lowercase ; espaces et tirets préservés)
        $this->assertSame('service si', $groupe->getGroupeUtilisateur());
    }

    public function testPersistEntityNormalizesAndSetsUlid(): void
    {
        $groupe = new GroupeUtilisateur();
        $groupe->setGroupeUtilisateur('Ma-Super Équipe!!!');
        $groupe->setDescription('desc');

        $this->repo->method('findOneBy')->willReturn(null);

        $this->flashBag->expects($this->never())->method('add');

        $this->controller->persistEntity($this->em, $groupe);

        // Normalisation (accents non-ASCII supprimés, espaces → _)
        $this->assertStringNotContainsString('!', $groupe->getGroupeUtilisateur());
        // ULID généré (26 chars)
        $this->assertSame(26, strlen($groupe->getGroupeId()));
    }

    public function testPersistEntitySetsDateEnregistrement(): void
    {
        $groupe = new GroupeUtilisateur();
        $groupe->setGroupeUtilisateur('backend');
        $groupe->setDescription('desc');
        $this->repo->method('findOneBy')->willReturn(null);

        $before = new \DateTimeImmutable();
        $this->controller->persistEntity($this->em, $groupe);

        $this->assertInstanceOf(\DateTimeImmutable::class, $groupe->getDateEnregistrement());
        $this->assertGreaterThanOrEqual($before, $groupe->getDateEnregistrement());
    }

    public function testPersistEntityGeneratesUlidWhenGroupeIdIsEmpty(): void
    {
        $groupe = new GroupeUtilisateur();
        $groupe->setGroupeId(''); // force vide → doit générer un nouveau ULID
        $groupe->setGroupeUtilisateur('backend');
        $groupe->setDescription('desc');
        $this->repo->method('findOneBy')->willReturn(null);

        $this->controller->persistEntity($this->em, $groupe);

        $this->assertSame(26, strlen($groupe->getGroupeId()));
        $this->assertNotSame('', $groupe->getGroupeId());
    }
}
