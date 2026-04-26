<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Projet;

use App\Controller\Projet\ApiEnregistrementController;
use App\Entity\Historique;
use App\Entity\Utilisateur;
use App\Repository\HistoriqueRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AllowMockObjectsWithoutExpectations]
class ApiEnregistrementControllerTest extends TestCase
{
    /** @var EntityManagerInterface&MockObject */          private MockObject $em;
    /** @var Security&MockObject */                        private MockObject $security;
    /** @var LoggerInterface&MockObject */                 private MockObject $logger;
    /** @var HistoriqueRepository&MockObject */            private MockObject $historiqueRepo;
    /** @var AuthorizationCheckerInterface&MockObject */   private MockObject $authChecker;

    private ApiEnregistrementController $controller;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->historiqueRepo = $this->createMock(HistoriqueRepository::class);
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);

        $this->em->method('getRepository')->willReturn($this->historiqueRepo);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => $id === 'security.authorization_checker'
        );
        $container->method('get')->willReturnMap([
            ['security.authorization_checker', 1, $this->authChecker],
        ]);

        $this->controller = new ApiEnregistrementController($this->em, $this->security, $this->logger);
        $this->controller->setContainer($container);
    }

    public function testEnregistrementReturns403WithoutRole(): void
    {
        $this->authChecker->method('isGranted')->willReturn(false);

        $response = $this->controller->enregistrement($this->jsonRequest(['maven_key' => 'k']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testEnregistrementReturns400OnInvalidJson(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);

        $response = $this->controller->enregistrement($this->jsonRequest('garbage'));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testEnregistrementReturnsErrorOnInsertFailure(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->security->method('getUser')->willReturn($this->makeUser());

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 500, 'erreur' => 'db fail']);

        $response = $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('db fail', $data['trace']);
    }

    public function testEnregistrementReturns23505OnDuplicate(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->security->method('getUser')->willReturn($this->makeUser());

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 23505, 'erreur' => 'duplicate key']);

        $response = $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(23505, $data['code']);
        $this->assertSame('duplicate key', $data['erreur']);
    }

    public function testEnregistrementHappyPath(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->security->method('getUser')->willReturn($this->makeUser());

        $this->historiqueRepo->expects($this->once())
            ->method('insertHistoriqueAjoutProjet')
            ->willReturn(['code' => 200]);

        $response = $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    public function testEnregistrementReturns500OnException(): void
    {
        $this->authChecker->method('isGranted')->willReturn(true);
        $this->security->method('getUser')->willReturn($this->makeUser());

        $this->historiqueRepo->method('insertHistoriqueAjoutProjet')
            ->willThrowException(new \RuntimeException('boom'));

        $response = $this->controller->enregistrement($this->jsonRequest($this->buildPayload()));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('boom', $data['trace']);
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

    private function makeUser(): Utilisateur
    {
        $u = new Utilisateur();
        $u->setCourriel('u@x');
        return $u;
    }

    private function buildPayload(): array
    {
        return [
            'maven_key' => 'k', 'analyse_key' => 'a', 'version' => '1.0', 'date_version' => '2026-04-10',
            'nom_projet' => 'App', 'version_release' => 1, 'version_snapshot' => 0, 'version_autre' => 0,
            'suppress_warning' => 0, 'no_sonar' => 0, 'todo' => 0,
            'logger_info' => 0, 'logger_warn' => 0, 'logger_error' => 0, 'logger_debug' => 0,
            'nombre_ligne' => 1000, 'nombre_ligne_code' => 800,
            'nombre_files' => 5, 'nombre_classes' => 10, 'nombre_functions' => 20,
            'coverage' => 80, 'duplicated_lines_density' => 1, 'sqale_debt_ratio' => 2,
            'tests' => 10, 'violations' => 5, 'dette' => 100,
            'nombre_bug' => 1, 'nombre_vulnerability' => 0, 'nombre_code_smell' => 3,
            'bug_blocker' => 0, 'bug_critical' => 1, 'bug_major' => 0, 'bug_minor' => 0, 'bug_info' => 0,
            'vulnerability_blocker' => 0, 'vulnerability_critical' => 0, 'vulnerability_major' => 0,
            'vulnerability_minor' => 0, 'vulnerability_info' => 0,
            'code_smell_blocker' => 0, 'code_smell_critical' => 0, 'code_smell_major' => 0,
            'code_smell_minor' => 0, 'code_smell_info' => 3,
            'frontend' => 1, 'backend' => 2, 'autre' => 0, 'inconnu' => 0,
            'nombre_anomalie_bloquant' => 0, 'nombre_anomalie_critique' => 1,
            'nombre_anomalie_majeur' => 0, 'nombre_anomalie_mineur' => 0, 'nombre_anomalie_info' => 0,
            'note_reliability' => 2.0, 'note_security' => 1.0, 'note_sqale' => 1.0,
            'note_hotspot' => 1.0, 'menace_potentielle_totale' => 0,
            'menace_potentielle_to_review_high' => 0, 'menace_potentielle_to_review_medium' => 0,
            'menace_potentielle_to_review_low' => 0, 'menace_potentielle_reviewed_high' => 0,
            'menace_potentielle_reviewed_medium' => 0, 'menace_potentielle_reviewed_low' => 0,
        ];
    }
}
