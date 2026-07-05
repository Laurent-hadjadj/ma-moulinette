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

use App\Controller\Admin\AdminLogController;
use App\Service\LogArchive\LogArchiveService;
use App\Service\UserAgent\UserAgentTrackingFacade;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

/**
 * Tests unitaires de AdminLogController.
 *
 * Couvre :
 *  - index       : rendu du template avec liste des fichiers
 *  - list        : filtrage env/types, fallback kernel.environment, réponse JSON
 *  - downloadSelection : 400 si vide, BinaryFileResponse si ok, 500 si exception
 */
#[AllowMockObjectsWithoutExpectations]
class AdminLogControllerTest extends TestCase
{
    /** @var ParameterBagInterface&MockObject */
    private MockObject $params;
    /** @var LoggerInterface&MockObject */
    private MockObject $logger;
    /** @var LogArchiveService&MockObject */
    private MockObject $logArchive;
    /** @var Environment&MockObject */
    private MockObject $twig;
    /** @var TokenStorageInterface&MockObject */
    private MockObject $tokenStorage;

    /** @var UserAgentTrackingFacade&MockObject */
    private MockObject $tracking;

    private AdminLogController $controller;

    /** Chemin du ZIP temporaire à supprimer dans tearDown. */
    private ?string $tempZip = null;

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logArchive = $this->createMock(LogArchiveService::class);
        $this->twig = $this->createMock(Environment::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->tokenStorage->method('getToken')->willReturn(null);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise',         'logo.png'],
            ['marque.entreprise.short', 'FAM'],
            ['marque.entreprise.long',  'FAM Long'],
            ['environnement',           'prod'],
            ['version',                 '2.0.0'],
            ['kernel.logs_dir',         sys_get_temp_dir()],
            ['kernel.environment',      'prod'],
        ]);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['twig', 'parameter_bag', 'security.token_storage'], true)
        );
        $container->method('get')->willReturnMap([
            ['twig',                   ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->twig],
            ['parameter_bag',          ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->params],
            ['security.token_storage', ContainerInterface::EXCEPTION_ON_INVALID_REFERENCE, $this->tokenStorage],
        ]);

        $this->controller = new AdminLogController(
            $this->params,
            $this->logger,
            $this->logArchive,
            $this->tracking
        );
        $this->controller->setContainer($container);
    }

    protected function tearDown(): void
    {
        if ($this->tempZip !== null && file_exists($this->tempZip)) {
            unlink($this->tempZip);
        }
    }

    /* ================================================================
     * index
     * ================================================================ */

    public function testIndexRendersAdminLogTemplate(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'admin/admin_log.html.twig',
                $this->callback(fn($ctx) => isset($ctx['files']) && is_array($ctx['files']))
            )
            ->willReturn('<html>log</html>');

        $response = $this->controller->index();

        $this->assertSame('<html>log</html>', $response->getContent());
    }

    public function testIndexPassesFilesArrayToTemplate(): void
    {
        $this->twig->expects($this->once())
            ->method('render')
            ->with(
                'admin/admin_log.html.twig',
                $this->callback(fn($ctx) =>
                    array_key_exists('files', $ctx)
                    && array_key_exists('env', $ctx)
                    && array_key_exists('version', $ctx)
                )
            )
            ->willReturn('<html></html>');

        $this->controller->index();
    }

    /* ================================================================
     * list
     * ================================================================ */

    public function testListReturns200WithLogs(): void
    {
        $this->logArchive->method('listLogs')
            ->willReturn([
                ['name' => 'prod.log', 'env' => 'prod', 'type' => 'main', 'size' => 100, 'mtime' => 1700000000],
            ]);

        $response = $this->controller->list(new Request(['env' => 'prod']));

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertSame(200, $data['code']);
        $this->assertSame(1, $data['count']);
        $this->assertCount(1, $data['logs']);
        $this->assertSame('prod.log', $data['logs'][0]['name']);
    }

    public function testListPassesEnvAndTypesToService(): void
    {
        $this->logArchive->expects($this->once())
            ->method('listLogs')
            ->with('dev', ['application', 'request'])
            ->willReturn([]);

        $response = $this->controller->list(
            new Request(['env' => 'dev', 'types' => ['application', 'request']])
        );

        $data = json_decode($response->getContent(), true);
        $this->assertSame(0, $data['count']);
    }

    public function testListFallsBackToKernelEnvironmentWhenEnvAbsent(): void
    {
        $this->logArchive->expects($this->once())
            ->method('listLogs')
            ->with('prod', null)
            ->willReturn([]);

        $this->controller->list(new Request());
    }

    public function testListPassesNullTypesWhenTypesAbsent(): void
    {
        $this->logArchive->expects($this->once())
            ->method('listLogs')
            ->with($this->anything(), null)
            ->willReturn([]);

        $this->controller->list(new Request(['env' => 'prod']));
    }

    public function testListFormatsDateInLogs(): void
    {
        $mtime = mktime(10, 30, 0, 1, 15, 2026);

        $this->logArchive->method('listLogs')
            ->willReturn([
                ['name' => 'app-prod.log', 'env' => 'prod', 'type' => 'application', 'size' => 50, 'mtime' => $mtime],
            ]);

        $response = $this->controller->list(new Request(['env' => 'prod']));
        $data = json_decode($response->getContent(), true);

        $this->assertMatchesRegularExpression('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $data['logs'][0]['mtime']);
    }

    /* ================================================================
     * downloadSelection
     * ================================================================ */

    public function testDownloadSelectionReturns400WhenNoFiles(): void
    {
        $response = $this->controller->downloadSelection(new Request());

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertSame(400, $data['code']);
        $this->assertSame('warning', $data['type']);
    }

    public function testDownloadSelectionReturns400WhenFilesEmpty(): void
    {
        $request = new Request([], ['files' => []]);

        $response = $this->controller->downloadSelection($request);

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertSame(400, $data['code']);
    }

    public function testDownloadSelectionReturnsBinaryResponseOnSuccess(): void
    {
        $this->tempZip = sys_get_temp_dir() . '/test_logs_' . uniqid('', true) . '.zip';
        file_put_contents($this->tempZip, 'PK');

        $this->logArchive->method('createZipFromFilenames')
            ->willReturn($this->tempZip);

        $request = new Request([], ['files' => ['prod.log', 'dev.log']]);

        $response = $this->controller->downloadSelection($request);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame(200, $response->getStatusCode());
    }

    public function testDownloadSelectionSetsAttachmentDisposition(): void
    {
        $this->tempZip = sys_get_temp_dir() . '/test_logs_' . uniqid('', true) . '.zip';
        file_put_contents($this->tempZip, 'PK');

        $this->logArchive->method('createZipFromFilenames')
            ->willReturn($this->tempZip);

        $response = $this->controller->downloadSelection(
            new Request([], ['files' => ['prod.log']])
        );

        $disposition = $response->headers->get('Content-Disposition');
        $this->assertStringContainsString('attachment', $disposition ?? '');
        $this->assertStringContainsString('logs_', $disposition ?? '');
        $this->assertStringContainsString('.zip', $disposition ?? '');
    }

    public function testDownloadSelectionSetsZipContentType(): void
    {
        $this->tempZip = sys_get_temp_dir() . '/test_logs_' . uniqid('', true) . '.zip';
        file_put_contents($this->tempZip, 'PK');

        $this->logArchive->method('createZipFromFilenames')
            ->willReturn($this->tempZip);

        $response = $this->controller->downloadSelection(
            new Request([], ['files' => ['prod.log']])
        );

        $this->assertStringContainsString('application/zip', $response->headers->get('Content-Type') ?? '');
    }

    public function testDownloadSelectionReturns500WhenServiceThrows(): void
    {
        $this->logArchive->method('createZipFromFilenames')
            ->willThrowException(new \RuntimeException('Aucun fichier valide trouvé'));

        $response = $this->controller->downloadSelection(
            new Request([], ['files' => ['fantome.log']])
        );

        $this->assertInstanceOf(JsonResponse::class, $response);
        $data = json_decode($response->getContent(), true);
        $this->assertSame(500, $data['code']);
        $this->assertSame('critical', $data['type']);
    }

    public function testDownloadSelectionForwardsFilenamesExactlyToService(): void
    {
        $expected = ['prod.log', 'app-prod.log'];

        $this->tempZip = sys_get_temp_dir() . '/test_logs_' . uniqid('', true) . '.zip';
        file_put_contents($this->tempZip, 'PK');

        $this->logArchive->expects($this->once())
            ->method('createZipFromFilenames')
            ->with($expected)
            ->willReturn($this->tempZip);

        $this->controller->downloadSelection(
            new Request([], ['files' => $expected])
        );
    }
}
