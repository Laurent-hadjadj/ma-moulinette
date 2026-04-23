<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\Historique;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description HistoriqueValidatorTest]
 *
 * v2.0.0 : tests de validation pour les contraintes de l'entite Historique.
 */
class HistoriqueValidatorTest extends KernelTestCase
{
    private function getEntity(): Historique
    {
        return (new Historique())
            ->setMavenKey('fr.ma-petite-entreprise:ma-moulinette')
            ->setVersion('1.2.0-RELEASE')
            ->setDateVersion('2024-07-12 16:34:46')
            ->setNomProjet('ma-moulinette')
            ->setAnalyseKey('AZCc05qWgfifxdiJPzns')
            ->setInitial(false)
            ->setDateEnregistrement(new \DateTimeImmutable('2024-06-28 17:55:45+02:00'));
    }

    public function assertHasErrors(Historique $entity, int $number = 0): void
    {
        self::bootKernel();
        $errors = static::getContainer()->get('validator')->validate($entity);
        $messages = [];
        /** @var ConstraintViolation $error */
        foreach ($errors as $error) {
            $messages[] = $error->getPropertyPath() . ' => ' . $error->getMessage();
        }
        $this->assertCount($number, $errors, implode(', ', $messages));
    }

    public function testValidEntity(): void
    {
        $this->assertHasErrors($this->getEntity(), 0);
    }

    public function testInvalidBlankEntity(): void
    {
        // Cle composite + cles requises : NotBlank
        $this->assertHasErrors($this->getEntity()->setMavenKey(''), 1);
        $this->assertHasErrors($this->getEntity()->setVersion(''), 1);
        $this->assertHasErrors($this->getEntity()->setDateVersion(''), 1);
        $this->assertHasErrors($this->getEntity()->setNomProjet(''), 1);
        $this->assertHasErrors($this->getEntity()->setAnalyseKey(''), 1);
        // modeCollecte / utilisateurCollecte sont nullable et n'ont pas NotBlank.
        $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
        $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
    }

    public function testInvalidLengthEntity(): void
    {
        $this->assertHasErrors($this->getEntity()->setMavenKey(str_repeat('a', 256)), 1);
        $this->assertHasErrors($this->getEntity()->setVersion(str_repeat('a', 33)), 1);
        $this->assertHasErrors($this->getEntity()->setDateVersion(str_repeat('a', 129)), 1);
        $this->assertHasErrors($this->getEntity()->setNomProjet(str_repeat('a', 129)), 1);
        $this->assertHasErrors($this->getEntity()->setAnalyseKey(str_repeat('a', 33)), 1);
        $this->assertHasErrors($this->getEntity()->setModeCollecte(str_repeat('a', 33)), 1);
        $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(str_repeat('a', 321)), 1);
    }

    /**
     * Liste des setters int contraints par PositiveOrZero (>= 0).
     */
    public static function positiveOrZeroIntProvider(): iterable
    {
        $names = [
            // Versions / repartition / langages
            'VersionRelease', 'VersionSnapshot', 'VersionAutre',
            'RepartitionFrontend', 'RepartitionBackend', 'RepartitionAutre', 'RepartitionInconnu',
            'JavaNoSonar', 'PythonNoSonar', 'PhpNoSonar',
            'SuppressWarning', 'NoPmd', 'CheckStyle',
            'JavaTodo', 'PythonTodo', 'PhpTodo', 'XmlTodo', 'JavascriptTodo', 'TypescriptTodo', 'RubyTodo',
            // Statistiques de code
            'Lines', 'Ncloc', 'Files', 'Classes', 'Functions', 'Statements', 'CommentLines',
            'LineCoverage', 'LinesToCover', 'ConditionsToCover', 'UncoveredConditions',
            // Tests
            'Tests', 'TestExecutionTime', 'TestErrors', 'TestFailures', 'SkippedTests',
            // Duplication / complexity
            'DuplicatedFiles', 'DuplicatedBlocks', 'DuplicatedLines',
            'Complexity', 'CognitiveComplexity',
            // Anomalies
            'OpenIssues', 'ReopenedIssues', 'ConfirmedIssues', 'FalsePositiveIssues',
            'AcceptedIssues', 'HighImpactAcceptedIssues',
            'Violations', 'BlockerViolations', 'CriticalViolations', 'MajorViolations',
            'MinorViolations', 'InfoViolations',
            'SoftwareQualityBlockerIssues', 'SoftwareQualityHighIssues', 'SoftwareQualityMediumIssues',
            'SoftwareQualityLowIssues', 'SoftwareQualityInfoIssues',
            // Code smells / maintenabilite
            'CodeSmells', 'CodeSmellBlocker', 'CodeSmellCritical', 'CodeSmellMajor',
            'CodeSmellMinor', 'CodeSmellInfo',
            'MaintainabilityIssues', 'SqaleIndex', 'EffortToReachMaintainabilityRatingA',
            'SoftwareQualityMaintainabilityRemediationEffort',
            'EffortToReachSoftwareQualityMaintainabilityRatingA',
            // Bugs / fiabilite
            'Bugs', 'BugBlocker', 'BugCritical', 'BugMajor', 'BugMinor', 'BugInfo',
            'ReliabilityRemediationEffort', 'SoftwareQualityReliabilityRemediationEffort',
            // Vulnerabilites / securite
            'Vulnerabilities', 'VulnerabilityBlocker', 'VulnerabilityCritical', 'VulnerabilityMajor',
            'VulnerabilityMinor', 'VulnerabilityInfo',
            'SecurityRemediationEffort', 'SoftwareQualitySecurityRemediationEffort',
            // Hotspots / menaces
            'SecurityHotspot',
            'MenacePotentielleToReviewHigh', 'MenacePotentielleToReviewMedium', 'MenacePotentielleToReviewLow',
            'MenacePotentielleReviewedHigh', 'MenacePotentielleReviewedMedium', 'MenacePotentielleReviewedLow',
            'MenacePotentielleTotale',
        ];
        foreach ($names as $n) {
            yield $n => [$n];
        }
    }

    #[DataProvider('positiveOrZeroIntProvider')]
    public function testValidPositiveOrZeroInt(string $suffix): void
    {
        $entity = $this->getEntity();
        $entity->{'set' . $suffix}(0);
        $this->assertHasErrors($entity, 0);
    }

    #[DataProvider('positiveOrZeroIntProvider')]
    public function testInvalidPositiveOrZeroInt(string $suffix): void
    {
        $entity = $this->getEntity();
        $entity->{'set' . $suffix}(-1);
        $this->assertHasErrors($entity, 1);
    }

    /**
     * Floats contraints par PositiveOrZero (>= 0).
     */
    public static function positiveOrZeroFloatProvider(): iterable
    {
        yield 'CommentLinesDensity' => ['CommentLinesDensity'];
        yield 'SecurityHotspotsReviewed' => ['SecurityHotspotsReviewed'];
    }

    #[DataProvider('positiveOrZeroFloatProvider')]
    public function testValidPositiveOrZeroFloat(string $suffix): void
    {
        $entity = $this->getEntity();
        $entity->{'set' . $suffix}(0.0);
        $this->assertHasErrors($entity, 0);
    }

    #[DataProvider('positiveOrZeroFloatProvider')]
    public function testInvalidPositiveOrZeroFloat(string $suffix): void
    {
        $entity = $this->getEntity();
        $entity->{'set' . $suffix}(-0.1);
        $this->assertHasErrors($entity, 1);
    }

    /**
     * Floats contraints par Range(0, 100).
     */
    public static function rangeFloatProvider(): iterable
    {
        yield 'TestSuccessDensity' => ['TestSuccessDensity'];
        yield 'DuplicatedLinesDensity' => ['DuplicatedLinesDensity'];
        yield 'ComplexityRatio' => ['ComplexityRatio'];
        yield 'CognitiveComplexityRatio' => ['CognitiveComplexityRatio'];
        yield 'SqaleDebtRatio' => ['SqaleDebtRatio'];
        yield 'SoftwareQualityMaintainabilityDebtRatio' => ['SoftwareQualityMaintainabilityDebtRatio'];
    }

    #[DataProvider('rangeFloatProvider')]
    public function testValidRangeFloat(string $suffix): void
    {
        $entity = $this->getEntity();
        $entity->{'set' . $suffix}(0.0);
        $this->assertHasErrors($entity, 0);
        $entity->{'set' . $suffix}(100.0);
        $this->assertHasErrors($entity, 0);
    }

    #[DataProvider('rangeFloatProvider')]
    public function testInvalidRangeFloat(string $suffix): void
    {
        $entity = $this->getEntity();
        $entity->{'set' . $suffix}(-1.0);
        $this->assertHasErrors($entity, 1);
        $entity->{'set' . $suffix}(101.0);
        $this->assertHasErrors($entity, 1);
    }

    /**
     * Strings contraints par Choice(A..E).
     */
    public static function choiceRatingProvider(): iterable
    {
        $names = [
            'CommentLinesRating', 'ComplexityRating', 'CognitiveComplexityRating',
            'SqaleRating', 'SoftwareQualityMaintainabilityRating',
            'ReliabilityRating', 'SoftwareQualityReliabilityRating',
            'SecurityRating', 'SoftwareQualitySecurityRating',
        ];
        foreach ($names as $n) {
            yield $n => [$n];
        }
    }

    #[DataProvider('choiceRatingProvider')]
    public function testValidChoiceRating(string $suffix): void
    {
        foreach (['A', 'B', 'C', 'D', 'E'] as $rating) {
            $entity = $this->getEntity();
            $entity->{'set' . $suffix}($rating);
            $this->assertHasErrors($entity, 0);
        }
    }

    #[DataProvider('choiceRatingProvider')]
    public function testInvalidChoiceRating(string $suffix): void
    {
        $entity = $this->getEntity();
        $entity->{'set' . $suffix}('Z');
        $this->assertHasErrors($entity, 1);
    }

    /**
     * v2.0.0 : l'entite Historique comporte 133 attributs.
     */
    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Historique());
        $this->assertEquals(133, count($reflectionClass->getProperties()));
    }
}
