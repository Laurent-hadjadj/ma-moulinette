<?php

namespace App\Tests\Service;

use App\Service\UrlBuilderService;
use PHPUnit\Framework\TestCase;
use Psr\Log\LoggerInterface;

class UrlBuilderServiceTest extends TestCase
{
    public function testBuildReturnsValidUrl(): void
    {
        /** @var \Psr\Log\LoggerInterface&\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        // Vérifie qu'un log debug est bien appelé
        $logger->expects($this->once())
            ->method('debug')
            ->with(
                $this->equalTo('URLBuilder: URL construite avec succès'),
                $this->arrayHasKey('fullUrl')
            );

        $builder = new UrlBuilderService($logger);

        $url = $builder->build(
            'https://example.com/',
            '/api/data',
            ['p' => 1, 'ps' => 10]
        );

        $this->assertEquals('https://example.com/api/data?p=1&ps=10', $url);
    }

    public function testBuildThrowsExceptionOnInvalidUrl(): void
    {
        /** @var \Psr\Log\LoggerInterface&\PHPUnit\Framework\MockObject\MockObject $logger */
        $logger = $this->createMock(LoggerInterface::class);

        // Vérifie qu'un log error est bien appelé
        $logger->expects($this->once())
            ->method('error')
            ->with(
                $this->equalTo('URLBuilder: URL invalide générée'),
                $this->arrayHasKey('fullUrl')
            );

        $builder = new UrlBuilderService($logger);

        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL invalide générée');

        // Cas volontairement invalide
        $builder->build('http://', '/test');
    }
}
