<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller\Admin;

use App\Controller\Admin\AdminLogController;
use App\Service\LogArchiveService;
use App\Service\UserAgentTrackingFacade;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBagInterface;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

#[AllowMockObjectsWithoutExpectations]
class AdminLogControllerTest extends TestCase
{
    /** @var ParameterBagInterface&MockObject */    private MockObject $params;
    /** @var LoggerInterface&MockObject */          private MockObject $logger;
    /** @var LogArchiveService&MockObject */        private MockObject $logArchive;
    /** @var UserAgentTrackingFacade&MockObject */  private MockObject $tracking;

    private AdminLogController $controller;
    private string $tmpLogDir;

    protected function setUp(): void
    {
        $this->params = $this->createMock(ParameterBagInterface::class);
        $this->logger = $this->createMock(LoggerInterface::class);
        $this->logArchive = $this->createMock(LogArchiveService::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

        $this->tmpLogDir = sys_get_temp_dir() . '/adminlog_test_' . uniqid();
        mkdir($this->tmpLogDir);

        $this->params->method('get')->willReturnMap([
            ['logo.entreprise', 'logo.png'],
            ['marque.entreprise.short', 'MM'],
            ['marque.entreprise.long', 'Ma Moulinette'],
            ['environnement', 'test'],
            ['version', '2.0.0'],
            ['kernel.logs_dir', $this->tmpLogDir],
            ['kernel.environment', 'test'],
        ]);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, ['parameter_bag', 'security.token_storage'], true)
        );
        $container->method('get')->willReturnMap([
            ['parameter_bag', 1, $this->params],
            ['security.token_storage', 1, $tokenStorage],
        ]);

        $this->controller = new AdminLogController(
            $this->params, $this->logger, $this->logArchive, $this->tracking
        );
        $this->controller->setContainer($container);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpLogDir)) {
            array_map('unlink', glob($this->tmpLogDir . '/*'));
            rmdir($this->tmpLogDir);
        }
    }

    /* ============ list ============ */

    public function testListReturnsEmptyWhenNoLogs(): void
    {
        $this->logArchive->expects($this->once())
            ->method('listLogs')
            ->with('dev', null)
            ->willReturn([]);

        $response = $this->controller->list(new Request(['env' => 'dev']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
        $this->assertSame(0, $data['count']);
        $this->assertSame([], $data['logs']);
    }

    public function testListUsesCurrentEnvWhenNoEnvProvided(): void
    {
        $this->logArchive->expects($this->once())
            ->method('listLogs')
            ->with('test', null)  // falls back to kernel.environment
            ->willReturn([]);

        $response = $this->controller->list(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(200, $data['code']);
    }

    public function testListReturnsFormattedLogs(): void
    {
        $mtime = mktime(10, 0, 0, 4, 10, 2026);
        $this->logArchive->method('listLogs')->willReturn([
            ['name' => 'dev.log', 'env' => 'dev', 'type' => 'symfony', 'size' => 1024, 'mtime' => $mtime],
        ]);

        $response = $this->controller->list(new Request(['env' => 'dev']));
        $data = json_decode($response->getContent(), true);

        $this->assertSame(1, $data['count']);
        $this->assertSame('dev.log', $data['logs'][0]['name']);
        $this->assertSame(1024, $data['logs'][0]['size']);
        $this->assertSame(date('Y-m-d H:i:s', $mtime), $data['logs'][0]['mtime']);
    }

    /* ============ downloadSelection ============ */

    public function testDownloadSelectionReturns400WhenNoFiles(): void
    {
        $response = $this->controller->downloadSelection(new Request());
        $data = json_decode($response->getContent(), true);

        $this->assertSame(400, $data['code']);
    }

    public function testDownloadSelectionReturns500OnException(): void
    {
        $this->logArchive->method('createZipFromFilenames')
            ->willThrowException(new \RuntimeException('zip fail'));

        $request = new Request([], ['files' => ['dev.log', 'prod.log']]);

        $response = $this->controller->downloadSelection($request);
        $data = json_decode($response->getContent(), true);

        $this->assertSame(500, $data['code']);
    }

    public function testDownloadSelectionReturnsBinaryFileResponseOnSuccess(): void
    {
        $zipPath = $this->tmpLogDir . '/logs.zip';
        file_put_contents($zipPath, 'fake-zip-content');

        $this->logArchive->expects($this->once())
            ->method('createZipFromFilenames')
            ->with(['dev.log'])
            ->willReturn($zipPath);

        $request = new Request([], ['files' => ['dev.log']]);

        $response = $this->controller->downloadSelection($request);

        $this->assertInstanceOf(BinaryFileResponse::class, $response);
        $this->assertSame('application/zip', $response->headers->get('Content-Type'));
    }

    /* ============ index ============ */

    public function testIndexListsFilesAndRendersTemplate(): void
    {
        // Create some log files in tmpLogDir
        file_put_contents($this->tmpLogDir . '/dev.log', 'log1');
        file_put_contents($this->tmpLogDir . '/prod.log', 'log2');

        $this->tracking->expects($this->once())->method('track')->with('ADMIN_LOGS');

        $twig = $this->createMock(Environment::class);
        $twig->expects($this->once())
            ->method('render')
            ->with('admin/admin_log.html.twig', $this->callback(function ($ctx) {
                return isset($ctx['files'])
                    && is_array($ctx['files'])
                    && count($ctx['files']) === 2;
            }))
            ->willReturn('<html>admin-log</html>');

        // rebuild container with twig
        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->method('getToken')->willReturn(null);

        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnCallback(
            fn(string $id): bool => in_array($id, [
                'parameter_bag', 'security.token_storage', 'twig',
            ], true)
        );
        $container->method('get')->willReturnMap([
            ['parameter_bag', 1, $this->params],
            ['security.token_storage', 1, $tokenStorage],
            ['twig', 1, $twig],
        ]);
        $this->controller->setContainer($container);

        $response = $this->controller->index();

        $this->assertSame('<html>admin-log</html>', $response->getContent());
    }
}
