<?php

declare(strict_types=1);

namespace App\Tests\Unit\Controller;

use App\Controller\ErrorController;
use App\Service\UserAgentTrackingFacade;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\ErrorHandler\Exception\FlattenException;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;
use Twig\Loader\LoaderInterface;

#[AllowMockObjectsWithoutExpectations]
class ErrorControllerTest extends TestCase
{
    /** @var Environment&MockObject */
    private MockObject $twig;

    /** @var LoaderInterface&MockObject */
    private MockObject $loader;

    /** @var UserAgentTrackingFacade&MockObject */
    private MockObject $tracking;

    private ErrorController $controller;

    protected function setUp(): void
    {
        $this->twig = $this->createMock(Environment::class);
        $this->loader = $this->createMock(LoaderInterface::class);
        $this->tracking = $this->createMock(UserAgentTrackingFacade::class);

        $this->twig->method('getLoader')->willReturn($this->loader);

        // AbstractController::render() récupère Twig via le container
        $container = $this->createMock(ContainerInterface::class);
        $container->method('has')->willReturnMap([
            ['twig', true],
            ['parameter_bag', false],
        ]);
        $container->method('get')->willReturnMap([
            ['twig', 1, $this->twig],
        ]);

        $this->controller = new ErrorController();
        $this->controller->setContainer($container);
    }

    public function testShowRendersStatusSpecificTemplateWhenAvailable(): void
    {
        $exception = $this->createMock(FlattenException::class);
        $exception->method('getStatusCode')->willReturn(404);

        // La vue spécifique existe
        $this->loader->expects($this->once())
            ->method('exists')
            ->with('bundles/TwigBundle/Exception/error404.html.twig')
            ->willReturn(true);

        $this->tracking->expects($this->once())
            ->method('track')
            ->with('ERROR_404');

        // Twig rend la vue spécifique
        $this->twig->expects($this->once())
            ->method('render')
            ->with('bundles/TwigBundle/Exception/error404.html.twig', $this->anything())
            ->willReturn('<html>404 page</html>');

        $response = $this->controller->show($exception, $this->twig, $this->tracking);

        $this->assertInstanceOf(Response::class, $response);
        $this->assertSame('<html>404 page</html>', $response->getContent());
    }

    public function testShowFallsBackToGenericTemplateWhenStatusSpecificMissing(): void
    {
        $exception = $this->createMock(FlattenException::class);
        $exception->method('getStatusCode')->willReturn(418);

        // La vue error418 n'existe pas → fallback
        $this->loader->expects($this->once())
            ->method('exists')
            ->with('bundles/TwigBundle/Exception/error418.html.twig')
            ->willReturn(false);

        $this->tracking->expects($this->once())
            ->method('track')
            ->with('ERROR_418');

        $this->twig->expects($this->once())
            ->method('render')
            ->with('bundles/TwigBundle/Exception/error.html.twig', $this->anything())
            ->willReturn('<html>Generic error</html>');

        $response = $this->controller->show($exception, $this->twig, $this->tracking);

        $this->assertSame('<html>Generic error</html>', $response->getContent());
    }
}
