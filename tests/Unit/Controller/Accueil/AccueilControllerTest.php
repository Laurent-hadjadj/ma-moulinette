<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Accueil;

use App\Controller\Accueil\AccueilController;
use App\Entity\Historique;
use App\Entity\ListeProjet;
use App\Entity\MaMoulinette;
use App\Entity\Profiles;
use App\Entity\Properties;
use App\Entity\Utilisateur;
use App\Repository\HistoriqueRepository;
use App\Repository\ListeProjetRepository;
use App\Repository\MaMoulinetteRepository;
use App\Repository\ProfilesRepository;
use App\Repository\PropertiesRepository;
use App\Service\ClientService;
use App\Service\UrlBuilderService;
use App\Service\UserAgentTrackingFacade;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBag;
use Symfony\Component\HttpFoundation\Session\Session;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class AccueilControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */    private MockObject $em;
    /** @var ClientService&MockObject */             private MockObject $client;
    /** @var ParameterBagInterface&MockObject */     private MockObject $params;
    /** @var UrlBuilderService&MockObject */         private MockObject $urlBuilder;
    /** @var LoggerInterface&MockObject */           private MockObject $logger;
    /** @var TokenStorageInterface&MockObject */     private MockObject $tokenStorage;
    /** @var TokenInterface&MockObject */            private MockObject $token;
    /** @var UserAgentTrackingFacade&MockObject */   private MockObject $tracking;
    /** @var ListeProjetRepository&MockObject */     private MockObject $listeProjetRepo;
    /** @var ProfilesRepository&MockObject */        private MockObject $profilesRepo;
    /** @var PropertiesRepository&MockObject */      private MockObject $propertiesRepo;
    /** @var MaMoulinetteRepository&MockObject */    private MockObject $maMoulinetteRepo;
    /** @var HistoriqueRepository&MockObject */      private MockObject $historiqueRepo;
    /** @var AuthorizationCheckerInterface&MockObject */ private MockObject $authChecker;
    /** @var Environment&MockObject */               private MockObject $twig;
    /** @var FlashBag&MockObject */                  private MockObject $flashBag;

    private AccueilController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->client = $this->createMock(ClientService::class);
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->urlBuilder = $this->createMock(UrlBuilderService::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->token = $this->createMock(TokenInterface::class);
        $this->tokenStorage->method('getToken')->willReturn($this->token);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->listeProjetRepo = $this->createMock(ListeProjetRepository::class);
        $this->profilesRepo = $this->createMock(ProfilesRepository::class);
        $this->propertiesRepo = $this->createMock(PropertiesRepository::class);
        $this->maMoulinetteRepo = $this->createMock(MaMoulinetteRepository::class);
        $this->historiqueRepo = $this->createMock(HistoriqueRepository::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->twig = $this->createMock(Environment::class);
        $this->flashBag = $this->createMock(FlashBag::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
            ['sonar.url', 'https://sonar.example.com'],
            ['maj.projet', 7],
            ['maj.profil', 7],
            ['nombre.favori', 10],
        ]);
        $this->urlBuilder->method('build')->willReturn('https://sonar/api/...');

        $this->em->method('getRepository')->willReturnMap([
            [ListeProjet::class, $this->listeProjetRepo],
            [Profiles::class, $this->profilesRepo],
            [Properties::class, $this->propertiesRepo],
            [MaMoulinette::class, $this->maMoulinetteRepo],
            [Historique::class, $this->historiqueRepo],
        ]);

        $session = $this->createMock(Session::class);
        $session->method('getFlashBag')->willReturn($this->flashBag);
        $requestStack = $this->createMock(RequestStack::class);
        $requestStack->method('getSession')->willReturn($session);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'twig', 'request_stack', 'parameter_bag', 'security.authorization_checker', 'security.token_storage',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
            ['request_stack', 1, $requestStack],
            ['parameter_bag', 1, $this->params],
            ['security.authorization_checker', 1, $this->authChecker],
            ['security.token_storage', 1, $this->tokenStorage],
        ]);

        $this->controller = new AccueilController(
            $this->em, $this->client, $this->params, $this->urlBuilder,
            $this->logger, $this->tracking
        );
        $this->controller->setContainer($container);
    }

    /* ============ getters ============ */

    public function testGetCountProjetBDReturnsZeroWhenEmpty(): void
    {
        $this->listeProjetRepo->expects($this->once())
            ->method('countListeProjet')
            ->willReturn(['code' => 200, 'request' => []]);

        $this->assertSame(0, $this->controller->getCountProjetBD());
    }

    public function testGetCountProjetBDReturnsTotalWhenAvailable(): void
    {
        $this->listeProjetRepo->method('countListeProjet')->willReturn([
            'code' => 200, 'request' => [['total' => 42]],
        ]);

        $this->assertSame(42, $this->controller->getCountProjetBD());
    }

    public function testGetCountProjetSonarCountsValidProjectsOnly(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['components' => [
                ['project' => 'app1'],
                ['project' => 'app2-SVN'],
                ['project' => 'app3'],
            ]],
        ]);

        $this->assertSame(2, $this->controller->getCountProjetSonar());
    }

    public function testGetCountProjetSonarReturnsMinusOneOnSonarFailure(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 503, 'erreur' => 'timeout',
        ]);
        $this->flashBag->expects($this->once())->method('add');

        $this->assertSame(-1, $this->controller->getCountProjetSonar());
    }

    public function testGetCountProfilBDReturnsTotal(): void
    {
        $this->profilesRepo->method('countProfiles')->willReturn([
            'request' => [['total' => 7]],
        ]);

        $this->assertSame(7, $this->controller->getCountProfilBD());
    }

    public function testGetCountProfilBDReturnsZeroWhenEmpty(): void
    {
        $this->profilesRepo->method('countProfiles')->willReturn([
            'request' => [],
        ]);

        $this->assertSame(0, $this->controller->getCountProfilBD());
    }

    public function testGetCountProfilSonarReturnsCount(): void
    {
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200,
            'json' => ['profiles' => [['key' => 'p1'], ['key' => 'p2'], ['key' => 'p3']]],
        ]);

        $this->assertSame(3, $this->controller->getCountProfilSonar());
    }

    public function testGetGetVersionReturnsDefaultWhenNoData(): void
    {
        $this->maMoulinetteRepo->method('getMaMoulinetteVersion')->willReturn([
            'request' => [],
        ]);

        $this->assertSame('0.0.0', $this->controller->getGetVersion());
    }

    public function testGetGetVersionReturnsDbVersion(): void
    {
        $this->maMoulinetteRepo->method('getMaMoulinetteVersion')->willReturn([
            'request' => [['version' => '2.1.0']],
        ]);

        $this->assertSame('2.1.0', $this->controller->getGetVersion());
    }

    public function testGetGetPropertiesInitializesWhenEmpty(): void
    {
        $this->propertiesRepo->expects($this->once())
            ->method('getProperties')
            ->willReturn(['request' => []]);

        $this->propertiesRepo->expects($this->once())
            ->method('insertProperties')
            ->willReturn(['code' => 200]);

        $result = $this->controller->getGetProperties();

        $this->assertSame(0, $result['projet_bd']);
        $this->assertSame(0, $result['profil_sonar']);
    }

    public function testGetGetPropertiesReturnsErrorOnInsertFailure(): void
    {
        $this->propertiesRepo->method('getProperties')->willReturn(['request' => []]);
        $this->propertiesRepo->method('insertProperties')->willReturn([
            'code' => 500, 'erreur' => 'db fail',
        ]);
        $this->flashBag->expects($this->once())->method('add');

        $result = $this->controller->getGetProperties();

        $this->assertSame(500, $result['code']);
    }

    public function testGetGetPropertiesReturnsExistingRow(): void
    {
        $this->propertiesRepo->method('getProperties')->willReturn([
            'request' => [[
                'projet_bd' => 10, 'projet_sonar' => 11,
                'profil_bd' => 5, 'profil_sonar' => 5,
                'date_modification_projet' => '2026-04-10',
                'date_modification_profil' => '2026-04-10',
            ]],
        ]);

        $result = $this->controller->getGetProperties();

        $this->assertSame(10, $result['projet_bd']);
        $this->assertSame(11, $result['projet_sonar']);
    }

    /* ============ construitMaRequest ============ */

    public function testConstruitMaRequestBuildsSqlWithSingleVersion(): void
    {
        $liste = [
            ['com.acme:app' => ['1.0-RELEASE']],
        ];
        $maven_key = ['com.acme:app'];

        $result = $this->controller->construitMaRequest($liste, $maven_key, 0);

        $this->assertStringContainsString("maven_key='com.acme:app'", $result);
        $this->assertStringContainsString("version='1.0-RELEASE'", $result);
    }

    public function testConstruitMaRequestBuildsSqlWithMultipleVersions(): void
    {
        $liste = [
            ['com.acme:app' => ['1.0', '1.1', '1.2']],
        ];
        $maven_key = ['com.acme:app'];

        $result = $this->controller->construitMaRequest($liste, $maven_key, 0);

        $this->assertStringContainsString("version='1.0'", $result);
        $this->assertStringContainsString("version='1.1'", $result);
        $this->assertStringContainsString("version='1.2'", $result);
        $this->assertStringEndsWith(')', $result);
    }

    /* ============ index ============ */

    public function testIndexRendersHappyPathWithFreshPropertiesAndFavori(): void
    {
        $this->tracking->expects($this->once())->method('track')->with('ACCUEIL');

        $today = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $this->propertiesRepo->method('getProperties')->willReturn([
            'request' => [[
                'projet_bd' => 10, 'projet_sonar' => 10,
                'profil_bd' => 5, 'profil_sonar' => 5,
                'date_modification_projet' => $today,
                'date_modification_profil' => $today,
            ]],
        ]);

        // visibility + tags
        $this->listeProjetRepo->method('countListeProjetVisibility')->willReturnMap([
            ['public', ['request' => [['visibility' => 6]]]],
            ['private', ['request' => [['visibility' => 4]]]],
        ]);
        $this->listeProjetRepo->method('countListeProjetTags')->willReturn([
            'nombre' => [['tag' => 12]],
        ]);

        // version app == version bd → no flash
        $this->maMoulinetteRepo->method('getMaMoulinetteVersion')->willReturn([
            'request' => [['version' => '2.0.0']],
        ]);

        // favori : utilisateur avec statut true + 1 favori + historique returns something
        $user = new Utilisateur();
        $user->setCourriel('u@x');
        $user->setPreference([
            'statut' => ['favori_projet' => true, 'favori_version' => false],
            'favori_projet' => ['acme:app'],
            'favori_version' => [],
        ]);
        $this->token->method('getUser')->willReturn($user);
        $this->historiqueRepo->method('selectHistoriqueProjetFavori')->willReturn([
            'code' => 200, 'request' => [['maven_key' => 'acme:app']],
        ]);

        // SonarQube version lookup
        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200, 'json' => ['texte' => '10.4'],
        ]);

        // isGranted
        $this->authChecker->method('isGranted')->willReturn(true);

        $this->twig->expects($this->once())
            ->method('render')
            ->with('accueil/index.html.twig', $this->callback(function ($ctx): bool {
                return $ctx['projet_bd'] === 10
                    && $ctx['projet_sonar'] === 10
                    && $ctx['composant'] === 'projet'
                    && $ctx['public'] === 6
                    && $ctx['private'] === 4
                    && $ctx['nombre_tag'] === 12
                    && $ctx['version_serveur_sonar'] === '10.4'
                    && $ctx['refresh_bd'] === true;
            }))
            ->willReturn('<html>accueil</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>accueil</html>', $response->getContent());
    }

    public function testIndexFlashesWhenVersionsDiffer(): void
    {
        $today = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
        $this->propertiesRepo->method('getProperties')->willReturn([
            'request' => [[
                'projet_bd' => 10, 'projet_sonar' => 10,
                'profil_bd' => 5, 'profil_sonar' => 5,
                'date_modification_projet' => $today,
                'date_modification_profil' => $today,
            ]],
        ]);
        $this->listeProjetRepo->method('countListeProjetVisibility')->willReturn([
            'request' => [['visibility' => 1]],
        ]);
        $this->listeProjetRepo->method('countListeProjetTags')->willReturn([
            'nombre' => [['tag' => 0]],
        ]);

        // version mismatch → flash warning
        $this->maMoulinetteRepo->method('getMaMoulinetteVersion')->willReturn([
            'request' => [['version' => '1.0.0']],
        ]);

        $user = new Utilisateur();
        $user->setPreference([
            'statut' => ['favori_projet' => false, 'favori_version' => false],
            'favori_projet' => [],
            'favori_version' => [],
        ]);
        $this->token->method('getUser')->willReturn($user);

        $this->client->method('httpSonarQube')->willReturn([
            'code' => 200, 'json' => ['texte' => '10.4'],
        ]);

        // we expect at least one flash call for the version warning
        $this->flashBag->expects($this->atLeastOnce())->method('add');

        $this->authChecker->method('isGranted')->willReturn(false);

        $this->twig->method('render')->willReturn('<html>v</html>');

        $this->controller->index();

        $this->assertTrue(true);
    }

    public function testIndexShortCircuitsWhenPropertiesInsertFails(): void
    {
        // Empty properties, insert fails → returns early at line 634 with render
        $this->propertiesRepo->method('getProperties')->willReturn(['request' => []]);
        $this->propertiesRepo->method('insertProperties')->willReturn([
            'code' => 500, 'erreur' => 'db fail',
        ]);

        $this->flashBag->expects($this->atLeastOnce())->method('add');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('accueil/index.html.twig', $this->callback(fn($ctx) =>
                $ctx['projet_bd'] === 0 && $ctx['composant'] === 0
            ))
            ->willReturn('<html>early</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>early</html>', $response->getContent());
    }
}
