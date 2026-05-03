<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Projet;

use App\Controller\Projet\ApiProjetController;
use App\Entity\ListeProjet;
use App\Entity\Utilisateur;
use App\Repository\ListeProjetRepository;
use App\Repository\UtilisateurRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiProjetControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */  private MockObject $em;
    /** @var LoggerInterface&MockObject */         private MockObject $logger;
    /** @var Security&MockObject */                private MockObject $security;
    /** @var UtilisateurRepository&MockObject */   private MockObject $utilisateurRepo;
    /** @var ListeProjetRepository&MockObject */   private MockObject $listeProjetRepo;
    /** @var TokenInterface&MockObject */          private MockObject $token;

    private ApiProjetController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->utilisateurRepo = $this->createMock(UtilisateurRepository::class);
        $this->listeProjetRepo = $this->createMock(ListeProjetRepository::class);

        $this->em->method('getRepository')->willReturnMap([
            [Utilisateur::class, $this->utilisateurRepo],
            [ListeProjet::class, $this->listeProjetRepo],
        ]);

        // appUser() (trait AppUserAware) appelle AbstractController::getUser() qui interroge security.token_storage
        $this->token = $this->createMock(TokenInterface::class);
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn($this->token);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'security.token_storage'
        );
        $container->method('get')->willReturnMap([
            ['security.token_storage', 1, $tokenStorage],
        ]);
        $this->controller = new ApiProjetController($this->em, $this->logger, $this->security);
        $this->controller->setContainer($container);
    }

    /* ============ favori ============ */

    public function testFavoriReturns400OnInvalidJson(): void
    {
        $this->security->method('getUser')->willReturn($this->makeUser('u@x', []));

        $response = $this->controller->favori($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testFavoriReturns400OnMissingMavenKey(): void
    {
        $this->security->method('getUser')->willReturn($this->makeUser('u@x', []));

        $response = $this->controller->favori($this->jsonRequest(['other' => 'x']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testFavoriReturnsErrorWhenUpdateFails(): void
    {
        $this->security->method('getUser')->willReturn($this->makeUser('u@x', []));
        $this->utilisateurRepo->expects($this->once())
            ->method('updateUtilisateurFavoriProjet')
            ->willReturn(['code' => 500, 'erreur' => 'db fail']);

        $response = $this->controller->favori($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testFavoriHappyPath(): void
    {
        $this->security->method('getUser')->willReturn($this->makeUser('u@x', []));
        $this->utilisateurRepo->method('updateUtilisateurFavoriProjet')->willReturn([
            'code' => 200, 'statut' => true,
        ]);

        $response = $this->controller->favori($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertTrue($data['statut']);
    }

    /* ============ favoriCheck ============ */

    public function testFavoriCheckReturns400OnMissingMavenKey(): void
    {
        $this->security->method('getUser')->willReturn($this->makeUser('u@x', []));

        $response = $this->controller->favoriCheck($this->jsonRequest([]));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testFavoriCheckReturns500WhenNoFavoriProjetPreference(): void
    {
        $user = $this->makeUser('u@x', []);
        $user->setPreference(['other' => 'x']);
        $this->security->method('getUser')->willReturn($user);

        $response = $this->controller->favoriCheck($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testFavoriCheckReturnsTrueWhenInList(): void
    {
        $user = $this->makeUser('u@x', []);
        $user->setPreference(['favori_projet' => ['k', 'other']]);
        $this->security->method('getUser')->willReturn($user);

        $response = $this->controller->favoriCheck($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertTrue($data['favori']);
    }

    public function testFavoriCheckReturnsFalseWhenNotInList(): void
    {
        $user = $this->makeUser('u@x', []);
        $user->setPreference(['favori_projet' => ['other']]);
        $this->security->method('getUser')->willReturn($user);

        $response = $this->controller->favoriCheck($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertFalse($data['favori']);
    }

    /* ============ liste_projet ============ */

    public function testListeProjetReturns404WhenNoGroupes(): void
    {
        $this->security->method('getUser')->willReturn($this->makeUser('u@x', []));

        $response = $this->controller->liste_projet(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(404, $data['code']);
    }

    public function testListeProjetReturnsErrorWhenRepoFails(): void
    {
        $this->security->method('getUser')->willReturn($this->makeUser('u@x', ['TeamA']));
        $this->listeProjetRepo->expects($this->once())
            ->method('selectListeProjetByGroupe')
            ->willReturn(['code' => 500, 'erreur' => 'db']);

        $response = $this->controller->liste_projet(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testListeProjetReturns406WhenEmpty(): void
    {
        $this->security->method('getUser')->willReturn($this->makeUser('u@x', ['TeamA']));
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200, 'liste' => [],
        ]);

        $response = $this->controller->liste_projet(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(406, $data['code']);
    }

    public function testListeProjetHappyPath(): void
    {
        $this->security->method('getUser')->willReturn($this->makeUser('u@x', ['TeamA']));
        $this->listeProjetRepo->method('selectListeProjetByGroupe')->willReturn([
            'code' => 200,
            'liste' => [['id' => 'k1'], ['id' => 'k2']],
        ]);

        $response = $this->controller->liste_projet(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertCount(2, $data['projet']);
    }

    /* ============ helpers ============ */

    private function jsonRequest(array|string $body): Request
    {
        if (is_string($body)) {
            $content = $body;
        } elseif ($body === []) {
            $content = '{}';
        } else {
            $content = json_encode($body);
        }
        return new Request([], [], [], [], [], [], $content);
    }

    private function makeUser(string $courriel, array $groupes): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel($courriel);
        $u->setListeGroupeFonctionnel($groupes);
        $u->setPreference([]);
        // Synchronise avec le mock token pour que appUser() (trait AppUserAware) renvoie le même user
        $this->token->method('getUser')->willReturn($u);
        return $u;
    }
}
