<?php

namespace App\Tests\Functional;

use App\Service\UrlBuilderService;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class UrlBuilderKernelTest extends KernelTestCase
{
    private UrlBuilderService $urlBuilder;

    protected function setUp(): void
    {
        self::bootKernel();

        // Récupération du service depuis le container Symfony
        $this->urlBuilder = self::getContainer()->get(UrlBuilderService::class);
    }

    public function testUrlIsBuiltCorrectlyFromService(): void
    {
        $url = $this->urlBuilder->build(
            'https://example.com/',
            '/api/test',
            ['p' => 1, 'limit' => 10]
        );

        $this->assertSame('https://example.com/api/test?p=1&limit=10', $url);
    }

    public function testThrowsExceptionWithInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL invalide générée');

        $this->urlBuilder->build('http://', '/test');
    }
}
