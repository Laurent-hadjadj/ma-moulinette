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

namespace App\Tests\Unit\Entity\Validator;

use App\Entity\Mesures;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\ConstraintViolation;

/**
 * [Description MesuresValidatorTest]
 *
 * v2.0.0 : tests de validation pour les contraintes de l’entité Mesures.
 */
class MesuresValidatorTest extends KernelTestCase
{
    private function getEntity(): Mesures
    {
        return (new Mesures())
            ->setMavenKey('fr.ma-petite-entreprise:ma-moulinette')
            ->setProjectName('Ma-Moulinette')
            ->setDateEnregistrement(new \DateTimeImmutable('2024-04-12 16:23:11', new \DateTimeZone('Europe/Paris')));
    }

    public function assertHasErrors(Mesures $entity, int $number = 0): void
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
        $this->assertHasErrors($this->getEntity()->setMavenKey(''), 1);
        $this->assertHasErrors($this->getEntity()->setProjectName(''), 1);
        // modeCollecte / utilisateurCollecte sont nullable et n'ont pas NotBlank.
        $this->assertHasErrors($this->getEntity()->setModeCollecte(''), 0);
        $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(''), 0);
    }

    public function testInvalidLengthEntity(): void
    {
        // mavenKey > 255 => 1 erreur
        $this->assertHasErrors($this->getEntity()->setMavenKey(str_repeat('a', 256)), 1);
        // projectName > 128 => 1 erreur
        $this->assertHasErrors($this->getEntity()->setProjectName(str_repeat('a', 129)), 1);
        // modeCollecte > 32 => 1 erreur
        $this->assertHasErrors($this->getEntity()->setModeCollecte(str_repeat('a', 33)), 1);
        // utilisateurCollecte > 320 => 1 erreur
        $this->assertHasErrors($this->getEntity()->setUtilisateurCollecte(str_repeat('a', 321)), 1);
    }

    /* MODIF 2026-05-06 : alignement avec Mesures
     * apres retrait des 22 colonnes orphelines (codeSmell{B/C/M/m/I}, bug{B/C/M/m/I},
     * vulnerability{B/C/M/m/I}, menacePotentielle*) et passage de maintainabilityIssues
     * de INT vers STRING (cf. session 2026-05-05). */
    /**
     * Liste des setters int contraints par PositiveOrZero (>= 0).
     */
    public static function positiveOrZeroIntProvider(): iterable
    {
        $names = [
            'Lines', 'Ncloc', 'Files', 'Classes', 'Functions', 'Statements', 'CommentLines',
            'LineCoverage', 'LinesToCover', 'ConditionsToCover', 'UncoveredConditions',
            'Tests', 'TestExecutionTime', 'TestErrors', 'TestFailures', 'SkippedTests',
            'DuplicatedFiles', 'DuplicatedBlocks', 'DuplicatedLines',
            'Complexity', 'CognitiveComplexity',
            'OpenIssues', 'ReopenedIssues', 'ConfirmedIssues', 'FalsePositiveIssues',
            'AcceptedIssues', 'HighImpactAcceptedIssues',
            'Violations', 'BlockerViolations', 'CriticalViolations', 'MajorViolations',
            'MinorViolations', 'InfoViolations',
            'SoftwareQualityBlockerIssues', 'SoftwareQualityHighIssues', 'SoftwareQualityMediumIssues',
            'SoftwareQualityLowIssues', 'SoftwareQualityInfoIssues',
            'CodeSmells',
            'SqaleIndex', 'EffortToReachMaintainabilityRatingA',
            'SoftwareQualityMaintainabilityRemediationEffort',
            'EffortToReachSoftwareQualityMaintainabilityRatingA',
            'Bugs',
            'ReliabilityRemediationEffort', 'SoftwareQualityReliabilityRemediationEffort',
            'Vulnerabilities',
            'SecurityRemediationEffort', 'SoftwareQualitySecurityRemediationEffort',
            'SecurityHotspot',
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
     * v2.0.0 : l'entite Mesures comporte 106 attributs.
     * MODIF 2026-05-05 : 86 attributs apres alignement avec le DDL.
     */
    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Mesures());
        $this->assertEquals(86, count($reflectionClass->getProperties()));
    }
}
