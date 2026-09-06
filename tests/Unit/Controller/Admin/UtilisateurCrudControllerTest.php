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

use App\Controller\Admin\UtilisateurCrudController;
use App\Entity\Utilisateur;
use App\Security\SuspiciousActivityDetector;
use App\Service\{RoleManagerService, UserRoleLoggerService};
use Doctrine\DBAL\{Connection, Result, Statement};
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\{Crud, Filters};
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Asset\Packages;
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
    /** @var Packages&MockObject */                          private MockObject $assets;

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
        $this->assets = $this->createMock(Packages::class);

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
            $this->roleManager, $this->roleLogger, $this->detector, $this->assets
        );
        $this->controller->setContainer($container);
    }

    public function testGetEntityFqcnReturnsUtilisateur(): void
    {
        $this->assertSame(Utilisateur::class, UtilisateurCrudController::getEntityFqcn());
    }

    public function testConfigureCrudReturnsCrud(): void
    {
        $crud = $this->controller->configureCrud(Crud::new());
        $this->assertNotNull($crud->getAsDto()->getCustomPageTitle(Crud::PAGE_INDEX));
    }

    public function testConfigureFiltersReturnsFilters(): void
    {
        $filters = $this->controller->configureFilters(Filters::new());
        $this->assertNotNull($filters->getAsDto()->getFilter('courriel'));
    }

    public function testPersistEntityIgnoresNonUtilisateur(): void
    {
        $this->em->expects($this->never())->method('persist');

        $this->controller->persistEntity($this->em, new \stdClass());
    }

    public function testPersistEntitySetsDefaultAvatarAndHashedPassword(): void
    {
        $user = new Utilisateur();
        $user->setCourriel('new@ma-moulinette.fr');
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
        $user->setCourriel('u@ma-moulinette.fr');
        $user->setRoles(['ROLE_UTILISATEUR']);
        $user->setActif(true);
        $user->setGroupeUtilisateur('default');

        // current roles + actif from DB = same as entity soumise (aucun changement)
        $this->connection->method('fetchAssociative')->willReturn(
            ['roles' => json_encode(['ROLE_UTILISATEUR']), 'actif' => true]
        );
        $this->connection->method('fetchOne')->willReturn('group-ulid'); // select groupe_id

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
        $user->setCourriel('u@ma-moulinette.fr');
        $user->setRoles(['ROLE_COLLECTE']);
        $user->setActif(true);
        $user->setGroupeUtilisateur('default');

        // old roles from DB (actif inchangé)
        $this->connection->method('fetchAssociative')->willReturn(
            ['roles' => json_encode(['ROLE_UTILISATEUR']), 'actif' => true]
        );
        $this->connection->method('fetchOne')->willReturn('group-ulid'); // select groupe_id

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

    public function testUpdateEntityLogsTrueOldActiveWhenOnlyActifChanged(): void
    {
        // Régression : $oldActive était auparavant lu sur $entityInstance (déjà lié
        // au formulaire soumis, donc déjà à la NOUVELLE valeur), rendant oldActive
        // toujours égal à newActive pour ce champ. Doit désormais venir d'un SELECT
        // frais, comme les rôles.
        $user = new Utilisateur();
        $user->setCourriel('u@ma-moulinette.fr');
        $user->setRoles(['ROLE_COLLECTE']);
        $user->setActif(false); // valeur SOUMISE par le formulaire (désactivation)
        $user->setGroupeUtilisateur('default');

        // avant édition : actif=true en base, mêmes rôles
        $this->connection->method('fetchAssociative')->willReturn(
            ['roles' => json_encode(['ROLE_COLLECTE']), 'actif' => true]
        );
        $this->connection->method('fetchOne')->willReturn('group-ulid');

        $this->roleManager->method('normalize')->willReturn(['ROLE_COLLECTE']);
        $this->detector->method('analyze')->willReturn([]);

        $this->roleLogger->expects($this->once())
            ->method('log')
            ->with(
                $this->anything(),
                $this->anything(),
                $this->anything(),
                $this->anything(),
                true,  // oldActive : vraie valeur avant édition, pas la valeur soumise
                false, // newActive
                $this->anything(),
            );

        $editor = new Utilisateur();
        $editor->setCourriel('admin@x');
        $editor->setRoles(['ROLE_INTERNAL']);
        $this->tokenStorage->method('getToken')
            ->willReturn(new UsernamePasswordToken($editor, 'main', ['ROLE_INTERNAL']));

        $this->controller->updateEntity($this->em, $user);
    }

    /**
     * @param array<int, array{groupe_utilisateur: string, description: string}> $groupesUtilisateur
     * @param array<int, array{groupe_fonctionnel: string, description: string}> $groupesFonctionnels
     */
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
