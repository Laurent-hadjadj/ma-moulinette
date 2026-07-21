<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright © 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\UserRoleLogController;
use App\Repository\UserRoleLogRepository;
use App\Service\PdfExportService;
use App\Service\UserAgent\UserAgentTrackingFacade;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Twig\Environment;

/**
 * Tests unitaires de UserRoleLogController.
 *
 * Couvre :
 *  - index   : rendu du template
 *  - list    : 200 avec décodage JSON des colonnes, 500 si erreur repository
 *  - archive : 400 si vide, CSV en cas de succès
 *  - delete  : 403 CSRF invalide, 400 si vide, succès, 500 si erreur repository
 *  - pdf     : 400 si vide, PDF en cas de succès
 */
#[AllowMockObjectsWithoutExpectations]
class UserRoleLogControllerTest extends TestCase
{
    /** @var ParameterBagInterface&MockObject */ private MockObject $params;
    /** @var LoggerInterface&MockObject */ private MockObject $logger;
    /** @var UserRoleLogRepository&MockObject */ private MockObject $repository;
    /** @var PdfExportService&MockObject */ private MockObject $pdfExport;
    /** @var UserAgentTrackingFacade&MockObject */ private MockObject $tracking;
    /** @var Environment&MockObject */ private MockObject $twig;
    /** @var TokenStorageInterface&MockObject */ private MockObject $tokenStorage;
    /** @var CsrfTokenManagerInterface&MockObject */ private MockObject $csrfManager;

    private UserRoleLogController $controller;

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->repository = $this->createMock(UserRoleLogRepository::class);
        $this->pdfExport = $this->createMock(PdfExportService::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);
        $this->twig = $this->createMock(Environment::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenStorage->method('getToken')->willReturn(null);
        $this->csrfManager = $this->createMock(CsrfTokenManagerInterface::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise',         'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long',  'Ma-Moulinette'],
            ['environnement',           'prod'],
            ['version',                 '2.0.0'],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['twig', 'security.token_storage', 'security.csrf.token_manager'], true)
        );
        $container->method('get')->willReturnMap([
            ['twig',                       ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->twig],
            ['security.token_storage',     ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->tokenStorage],
            ['security.csrf.token_manager', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->csrfManager],
        ]);

        $this->controller = new UserRoleLogController(
            $this->params,
            $this->logger,
            $this->repository,
            $this->pdfExport,
            $this->tracking
        );
        $this->controller->setContainer($container);
    }

    private function ligneBrute(int $id = 1): array
    {
        return [
            'id' => $id,
            'user_email' => 'emma.durand@ma-moulinette.fr',
            'editor_email' => 'admin@ma-moulinette.fr',
            'old_roles' => '["ROLE_UTILISATEUR"]',
            'new_roles' => '["ROLE_GESTIONNAIRE"]',
            'old_active' => true,
            'new_active' => true,
            'alerts' => '["ATTRIBUTION_ROLE_SENSIBLE"]',
            'created_at' => '2026-07-20 10:30:00',
        ];
    }

    /* ================================================================
     * index
     * ================================================================ */

    public function testIndexRendersTemplate(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with('admin/user_role_log.html.twig', $this->anything())
            ->willReturn('<html>journal</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>journal</html>', $response->getContent());
    }

    /* ================================================================
     * list
     * ================================================================ */

    public function testListReturns200WithDecodedRoles(): void
    {
        $this->repository->method('findFiltered')
            ->willReturn(['code' => 200, 'liste' => [$this->ligneBrute()], 'erreur' => '']);

        $response = $this->controller->list(new Request());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertSame(200, $data['code']);
        $this->assertSame(1, $data['count']);
        $this->assertSame(['ROLE_UTILISATEUR'], $data['lignes'][0]['oldRoles']);
        $this->assertSame(['ROLE_GESTIONNAIRE'], $data['lignes'][0]['newRoles']);
        $this->assertSame(['ATTRIBUTION_ROLE_SENSIBLE'], $data['lignes'][0]['alerts']);
        $this->assertTrue($data['lignes'][0]['oldActive']);
    }

    public function testListPassesCourrielFilterToRepository(): void
    {
        $this->repository->expects($this->once())
            ->method('findFiltered')
            ->with($this->callback(fn($f) => ($f['courriel'] ?? null) === 'emma.durand'))
            ->willReturn(['code' => 200, 'liste' => [], 'erreur' => '']);

        $this->controller->list(new Request(['courriel' => 'emma.durand']));
    }

    public function testListReturns500WhenRepositoryFails(): void
    {
        $this->repository->method('findFiltered')
            ->willReturn(['code' => 500, 'erreur' => 'boom']);

        $response = $this->controller->list(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('critical', $data['type']);
    }

    /* ================================================================
     * archive
     * ================================================================ */

    public function testArchiveReturns400WhenNoSelection(): void
    {
        $response = $this->controller->archive(new Request());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertSame(400, $data['code']);
    }

    public function testArchiveReturnsCsvOnSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('findByIds')
            ->with([1])
            ->willReturn(['code' => 200, 'liste' => [$this->ligneBrute(1)], 'erreur' => '']);

        $response = $this->controller->archive(new Request([], ['ids' => ['1']]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('text/csv', $response->headers->get('Content-Type') ?? '');
        $this->assertStringContainsString('.csv', $response->headers->get('Content-Disposition') ?? '');
        $this->assertStringContainsString('emma.durand@ma-moulinette.fr', $response->getContent());
    }

    /* ================================================================
     * delete
     * ================================================================ */

    public function testDeleteReturns403WhenCsrfInvalid(): void
    {
        $this->csrfManager->method('isTokenValid')->willReturn(false);

        $response = $this->controller->delete(new Request([], ['ids' => ['1'], '_token' => 'bad']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(403, $data['code']);
    }

    public function testDeleteReturns400WhenNoSelection(): void
    {
        $this->csrfManager->method('isTokenValid')->willReturn(true);

        $response = $this->controller->delete(new Request([], ['_token' => 'ok']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testDeleteReturnsSuccessCountOnSuccess(): void
    {
        $this->csrfManager->method('isTokenValid')->willReturn(true);
        $this->repository->expects($this->once())
            ->method('deleteByIds')
            ->with([1, 2])
            ->willReturn(['code' => 200, 'supprime' => 2, 'erreur' => '']);

        $response = $this->controller->delete(new Request([], ['ids' => ['1', '2'], '_token' => 'ok']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(2, $data['supprime']);
    }

    public function testDeleteReturns500WhenRepositoryFails(): void
    {
        $this->csrfManager->method('isTokenValid')->willReturn(true);
        $this->repository->method('deleteByIds')
            ->willReturn(['code' => 500, 'erreur' => 'boom']);

        $response = $this->controller->delete(new Request([], ['ids' => ['1'], '_token' => 'ok']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
        $this->assertSame('critical', $data['type']);
    }

    /* ================================================================
     * pdf
     * ================================================================ */

    public function testPdfReturns400WhenNoSelection(): void
    {
        $response = $this->controller->pdf(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testPdfReturnsPdfResponseOnSuccess(): void
    {
        $this->repository->expects($this->once())
            ->method('findByIds')
            ->with([1])
            ->willReturn(['code' => 200, 'liste' => [$this->ligneBrute(1)], 'erreur' => '']);

        $this->pdfExport->expects($this->once())
            ->method('generateUserRoleLogPdf')
            ->willReturn('%PDF-1.4 binary content');

        $response = $this->controller->pdf(new Request([], ['ids' => ['1']]));

        $this->assertSame(Response::HTTP_OK, $response->getStatusCode());
        $this->assertStringContainsString('application/pdf', $response->headers->get('Content-Type') ?? '');
        $this->assertSame('%PDF-1.4 binary content', $response->getContent());
    }
}
