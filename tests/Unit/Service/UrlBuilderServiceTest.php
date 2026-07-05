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

namespace App\Tests\Unit\Service;

use App\Service\UrlBuilderService;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\TestCase;
use Psr\Log\NullLogger;

#[CoversClass(UrlBuilderService::class)]
final class UrlBuilderServiceTest extends TestCase
{
    private UrlBuilderService $service;

    protected function setUp(): void
    {
        $this->service = new UrlBuilderService(new NullLogger());
    }

    public function testBuildSimpleUrlWithoutQueryParams(): void
    {
        $url = $this->service->build('https://sonar.example.com', '/api/measures/component');

        self::assertSame('https://sonar.example.com/api/measures/component', $url);
    }

    public function testTrimsTrailingSlashOnBaseAndLeadingSlashOnPath(): void
    {
        $url = $this->service->build('https://sonar.example.com/', '/api/measures/component');

        self::assertSame('https://sonar.example.com/api/measures/component', $url);
    }

    public function testAppendsQueryParametersWithQuestionMark(): void
    {
        $url = $this->service->build(
            'https://sonar.example.com',
            '/api/measures/component',
            ['component' => 'fr.example:my-app', 'metricKeys' => 'reliability_rating']
        );

        self::assertStringContainsString('?component=', $url);
        self::assertStringContainsString('metricKeys=reliability_rating', $url);
        self::assertStringContainsString('component=fr.example%3Amy-app', $url);
    }

    public function testUsesAmpersandWhenBaseUrlAlreadyHasQueryString(): void
    {
        $url = $this->service->build(
            'https://sonar.example.com',
            '/api/measures/component?existing=1',
            ['extra' => 'value']
        );

        self::assertStringContainsString('?existing=1&extra=value', $url);
    }

    public function testEmptyQueryParamsArrayDoesNotAddSeparator(): void
    {
        $url = $this->service->build('https://sonar.example.com', '/api/health', []);

        self::assertSame('https://sonar.example.com/api/health', $url);
    }

    public function testThrowsOnInvalidUrl(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('URL invalide');

        $this->service->build('not-a-valid-base', '/path');
    }
}
