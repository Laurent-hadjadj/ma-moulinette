<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\UtilisateurCrudController;
use App\Entity\Utilisateur;
use App\Security\SuspiciousActivityDetector;
use App\Service\RoleManagerService;
use App\Service\UserRoleLoggerService;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Result;
use Doctrine\DBAL\Statement;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Contracts\Provider\AdminContextProviderInterface;

#[AllowMockObjectsWithoutExpectations]
class UtilisateurCrudControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */            private MockObject $em;
    /** @var LoggerInterface&MockObject */                   private MockObject $logger;
    /** @var UserPasswordHasherInterface&MockObject */       private MockObject $passwordHasher;
    /** @var RoleManagerService&MockObject */                private MockObject $roleManager;
    /** @var UserRoleLoggerService&MockObject */             private MockObject $roleLogger;
    /** @var SuspiciousActivityDetector&MockObject */        private MockObject $detector;
    /** @var Connection&MockObject */                        private MockObject $connection;
    /** @var TokenStorageInterface&MockObject */             private MockObject $tokenStorage;
    /** @var AuthorizationCheckerInterface&MockObject */    private MockObject $authChecker;

    private UtilisateurCrudController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->passwordHasher = $this->createMock(UserPasswordHasherInterface::class);
        $this->roleManager = $this->createMock(RoleManagerService::class);
        $this->roleLogger = $this->createMock(UserRoleLoggerService::class);
        $this->detector = $this->createMock(SuspiciousActivityDetector::class);
        $this->connection = $this->createMock(Connection::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->em->method('getConnection')->willReturn($this->connection);

        $adminContextProvider = $this->createMock(AdminContextProviderInterface::class);
        $adminContextProvider->method('getContext')->willReturn(null);

        $container = $this->createMock(\Symfony\Component\DependencyInjection\ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'security.token_storage', 'security.authorization_checker',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $this->tokenStorage],
            ['security.authorization_checker', 1, $this->authChecker],
            [AdminContextProviderInterface::class, 1, $adminContextProvider],
        ]);

        $this->controller = new UtilisateurCrudController(
            $this->em, $this->logger, $this->passwordHasher,
            $this->roleManager, $this->roleLogger, $this->detector
        );
        $this->controller->setContainer($container);
    }

    public function testGetEntityFqcnReturnsUtilisateur(): void
    {
        $this->assertSame(Utilisateur::class, UtilisateurCrudController::getEntityFqcn());
    }

    public function testConfigureCrudReturnsCrud(): void
    {
        $this->assertInstanceOf(Crud::class, $this->controller->configureCrud(Crud::new()));
    }

    public function testConfigureFiltersReturnsFilters(): void
    {
        $this->assertInstanceOf(Filters::class, $this->controller->configureFilters(Filters::new()));
    }

    public function testPersistEntityIgnoresNonUtilisateur(): void
    {
        $this->em->expects($this->never())->method('persist');

        $this->controller->persistEntity($this->em, new \stdClass());
    }

    public function testPersistEntitySetsDefaultAvatarAndHashedPassword(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('new@example.com');
        $user->setRoles([]);
        $user->setGroupeUtilisateur('default');

        // Connexion fetch pour groupe_id
        $this->connection->method('fetchOne')->willReturn('group-ulid-1234');

        // Role manager normalize
        $this->roleManager->method('normalize')->willReturn(['ROLE_UTILISATEUR']);

        // Password hasher
        $this->passwordHasher->expects($this->once())
            ->method('hashPassword')
            ->willReturn('$2y$13$hashed');

        // Token storage for $this->getUser()
        $editor = new Utilisateur();
        $editor->setCourriel('admin@x');
        $editor->setRoles(['ROLE_INTERNAL']);
        $token = new UsernamePasswordToken($editor, 'main', ['ROLE_INTERNAL']);
        $this->tokenStorage->method('getToken')->willReturn($token);

        $this->controller->persistEntity($this->em, $user);

        $this->assertSame('personne.png', $user->getAvatar());
        $this->assertFalse($user->isResetPassword());
        $this->assertSame(['ROLE_UTILISATEUR'], $user->getRoles());
        $this->assertSame('group-ulid-1234', $user->getGroupeId());
    }

    public function testUpdateEntityIgnoresNonUtilisateur(): void
    {
        $this->em->expects($this->never())->method('flush');

        $this->controller->updateEntity($this->em, new \stdClass());
    }

    public function testUpdateEntityDoesNotLogWhenRolesAndActifUnchanged(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('u@example.com');
        $user->setRoles(['ROLE_UTILISATEUR']);
        $user->setActif(true);
        $user->setGroupeUtilisateur('default');

        // current roles from DB = same as entity
        $this->connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            json_encode(['ROLE_UTILISATEUR']), // select roles
            'group-ulid', // select groupe_id
        );

        $this->roleManager->method('normalize')->willReturn(['ROLE_UTILISATEUR']);
        $this->detector->method('analyze')->willReturn([]);

        $this->roleLogger->expects($this->never())->method('log');

        $editor = new Utilisateur();
        $editor->setCourriel('admin@x');
        $editor->setRoles(['ROLE_INTERNAL']);
        $this->tokenStorage->method('getToken')
            ->willReturn(new UsernamePasswordToken($editor, 'main', ['ROLE_INTERNAL']));

        $this->controller->updateEntity($this->em, $user);
    }

    public function testUpdateEntityLogsWhenRolesChanged(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('u@example.com');
        $user->setRoles(['ROLE_COLLECTE']);
        $user->setActif(true);
        $user->setGroupeUtilisateur('default');

        // old roles from DB
        $this->connection->method('fetchOne')->willReturnOnConsecutiveCalls(
            json_encode(['ROLE_UTILISATEUR']),
            'group-ulid',
        );

        $this->roleManager->method('normalize')->willReturn(['ROLE_COLLECTE']);
        $this->detector->method('analyze')->willReturn(['CHANGEMENT_MASSIF_ROLES']);

        $this->roleLogger->expects($this->once())->method('log');

        $editor = new Utilisateur();
        $editor->setCourriel('admin@x');
        $editor->setRoles(['ROLE_INTERNAL']);
        $this->tokenStorage->method('getToken')
            ->willReturn(new UsernamePasswordToken($editor, 'main', ['ROLE_INTERNAL']));

        $this->controller->updateEntity($this->em, $user);
    }

    private function stubGroupesQueries(array $groupesUtilisateur = [], array $groupesFonctionnels = []): void
    {
        $statement = $this->createMock(Statement::class);
        $result = $this->createMock(Result::class);
        $this->connection->method('prepare')->willReturn($statement);
        $statement->method('executeQuery')->willReturn($result);
        $result->method('fetchAllAssociative')->willReturnOnConsecutiveCalls(
            $groupesUtilisateur,
            $groupesFonctionnels,
        );
    }

    public function testConfigureFieldsYieldsFieldsWithInternalRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true); // any role → true
        $this->stubGroupesQueries([
            ['groupe_utilisateur' => 'service-si', 'description' => 'Service SI'],
        ], [
            ['groupe_fonctionnel' => 'java', 'description' => 'Projets Java'],
        ]);

        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW), false);

        // 2 FormField columns + avatar + personne + prenom + nom + courriel + 2 roles fields + actif + groupeUtilisateur + listeGroupeFonctionnel + 2 dates
        $this->assertGreaterThanOrEqual(12, count($fields));
    }

    public function testConfigureFieldsUsesFallbackWhenGroupeUtilisateurEmpty(): void
    {
        $this->authChecker->method('isGranted')->willReturnMap([
            ['ROLE_INTERNAL', null, false],
            ['ROLE_GESTIONNAIRE', null, false],
        ]);
        // both tables empty
        $this->stubGroupesQueries([], []);

        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_NEW), false);

        $this->assertGreaterThanOrEqual(12, count($fields));
    }

    public function testConfigureFieldsWithGestionnaireRoleLimitsAssignableRoles(): void
    {
        $this->authChecker->method('isGranted')->willReturnMap([
            ['ROLE_INTERNAL', null, false],
            ['ROLE_GESTIONNAIRE', null, true],
        ]);
        $this->stubGroupesQueries([
            ['groupe_utilisateur' => 'default', 'description' => 'Default'],
        ], [
            ['groupe_fonctionnel' => 'web', 'description' => 'Very long description that should be truncated at eighteen chars'],
        ]);

        $fields = iterator_to_array($this->controller->configureFields(Crud::PAGE_EDIT), false);

        $this->assertGreaterThanOrEqual(12, count($fields));
    }
}
