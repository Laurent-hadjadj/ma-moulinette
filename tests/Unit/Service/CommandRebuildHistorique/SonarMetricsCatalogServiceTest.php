<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service\CommandRebuildHistorique;

use App\Service\CommandRebuildHistorique\SonarMetricsCatalogService;
use PHPUnit\Framework\TestCase;

class SonarMetricsCatalogServiceTest extends TestCase
{
    private SonarMetricsCatalogService $service;

    protected function setUp(): void
    {
        $this->service = new SonarMetricsCatalogService();
    }

    public function testMetricsDefinitionReturnsNonEmptyArray(): void
    {
        $definitions = $this->service->metricsDefinition();

        $this->assertIsArray($definitions);
        $this->assertNotEmpty($definitions);
    }

    public function testEachEntryHasTheExpectedFourKeys(): void
    {
        $requiredKeys = ['domaine', 'version', 'type', 'description'];

        foreach ($this->service->metricsDefinition() as $metricName => $definition) {
            $this->assertIsString($metricName, 'Le nom de métrique doit être une clé string');
            $this->assertIsArray($definition, sprintf('Entrée "%s" doit être un tableau', $metricName));

            foreach ($requiredKeys as $key) {
                $this->assertArrayHasKey(
                    $key,
                    $definition,
                    sprintf('Entrée "%s" ne contient pas la clé "%s"', $metricName, $key)
                );
            }
        }
    }

    public function testEachEntryFieldsHaveExpectedTypes(): void
    {
        $allowedDomaines = ['SonarQube', 'ma-moulinette'];
        $allowedTypes = ['int', 'float', 'string', 'bool', 'PERCENT', 'MILLISEC', 'WORK_DUR', 'RATING'];

        foreach ($this->service->metricsDefinition() as $metricName => $definition) {
            $this->assertContains(
                $definition['domaine'],
                $allowedDomaines,
                sprintf('Entrée "%s" : domaine inattendu', $metricName)
            );

            $this->assertTrue(
                $definition['version'] === null || is_int($definition['version']),
                sprintf('Entrée "%s" : version doit être null ou int', $metricName)
            );

            $this->assertContains(
                $definition['type'],
                $allowedTypes,
                sprintf('Entrée "%s" : type "%s" inattendu', $metricName, $definition['type'])
            );

            $this->assertIsString(
                $definition['description'],
                sprintf('Entrée "%s" : description doit être une string', $metricName)
            );
        }
    }

    public function testKnownCoreMetricsArePresent(): void
    {
        $definitions = $this->service->metricsDefinition();

        // Métriques SonarQube cœur métier qui doivent absolument exister.
        $coreMetrics = [
            'lines', 'ncloc', 'coverage', 'bugs', 'vulnerabilities',
            'code_smells', 'duplicated_lines_density', 'security_hotspots',
            'reliability_rating', 'security_rating', 'sqale_index',
            'complexity', 'cognitive_complexity', 'blocker_violations',
        ];

        foreach ($coreMetrics as $metric) {
            $this->assertArrayHasKey(
                $metric,
                $definitions,
                sprintf('La métrique cœur "%s" est absente du catalogue', $metric)
            );
        }
    }

    public function testAllMetricKeysFollowSnakeCaseConvention(): void
    {
        foreach ($this->service->metricsDefinition() as $metricName => $_definition) {
            $this->assertMatchesRegularExpression(
                '/^[a-z][a-z0-9_]*$/',
                $metricName,
                sprintf('La clé "%s" ne suit pas la convention snake_case', $metricName)
            );
        }
    }
}
