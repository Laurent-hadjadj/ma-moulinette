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
namespace App\Tests\Unit\Entity\Case;

use App\Entity\Mesures;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * [Description MesuresCaseTest]
 *
 * v2.0.0 : test compact via dataProvider couvrant les 106 attributs de l'entite Mesures.
 *
 * MODIF 2026-05-05  :
 *   - Retrait des 22 attributs codeSmell{Blocker/Critical/Major/Minor/Info},
 *     bug{Blocker/Critical/Major/Minor/Info}, vulnerability{Blocker/...} et
 *     menacePotentielle{...} (colonnes retirees du DDL le 2026-03-30).
 *   - maintainabilityIssues bascule int -> string(255) (alignement type DDL VARCHAR(255)).
 *   - Ajout coverageRating et duplicatedLinesRating (presents en DDL, manquaient a l'entite).
 *   - Total attributs : 106 -> 86.
 */
class MesuresCaseTest extends TestCase
{
    /**
     * Liste des [setter/getter suffix, valeur a injecter] pour 104 attributs simples
     * (id, dateEnregistrement testes separement ci-dessous).
     */
    public static function attributesProvider(): iterable
    {
        // Cles d'identification
        yield 'mavenKey' => ['MavenKey', 'fr.ma-petite-entreprise:ma-moulinette'];
        yield 'projectName' => ['ProjectName', 'Ma-Moulinette'];

        // Quality gate
        yield 'alertStatus' => ['AlertStatus', 'OK'];

        // Statistiques de code
        yield 'lines' => ['Lines', 22015];
        yield 'ncloc' => ['Ncloc', 10043];
        yield 'nclocLanguageDistribution' => ['NclocLanguageDistribution', 'java=4278;ts=18690'];
        yield 'files' => ['Files', 18];
        yield 'classes' => ['Classes', 26];
        yield 'functions' => ['Functions', 52];
        yield 'statements' => ['Statements', 1024];
        yield 'commentLines' => ['CommentLines', 200];
        yield 'commentLinesDensity' => ['CommentLinesDensity', 12.5];
        yield 'commentLinesRating' => ['CommentLinesRating', 'A'];
        yield 'coverage' => ['Coverage', 10.3];
        yield 'branchCoverage' => ['BranchCoverage', 8.6];
        /* MODIF 2026-05-07 [tests-validators] : lineCoverage INT→FLOAT (cf MODIF 2026-05-06 [pourcentage-line-coverage]). */
        yield 'lineCoverage' => ['LineCoverage', 5.0];
        yield 'linesToCover' => ['LinesToCover', 100];
        yield 'conditionsToCover' => ['ConditionsToCover', 50];
        yield 'uncoveredConditions' => ['UncoveredConditions', 12];
        yield 'coverageRating' => ['CoverageRating', 'B'];

        // Tests
        yield 'tests' => ['Tests', 123];
        yield 'testExecutionTime' => ['TestExecutionTime', 4200];
        yield 'testErrors' => ['TestErrors', 0];
        yield 'testFailures' => ['TestFailures', 1];
        yield 'skippedTests' => ['SkippedTests', 2];
        yield 'testSuccessDensity' => ['TestSuccessDensity', 99.5];

        // Duplication
        yield 'duplicatedFiles' => ['DuplicatedFiles', 3];
        yield 'duplicatedBlocks' => ['DuplicatedBlocks', 5];
        yield 'duplicatedLines' => ['DuplicatedLines', 250];
        yield 'duplicatedLinesDensity' => ['DuplicatedLinesDensity', 5.1];
        yield 'duplicatedLinesRating' => ['DuplicatedLinesRating', 'C'];

        // Complexity
        yield 'complexity' => ['Complexity', 1500];
        yield 'complexityRating' => ['ComplexityRating', 'B'];
        yield 'cognitiveComplexity' => ['CognitiveComplexity', 800];
        yield 'cognitiveComplexityRating' => ['CognitiveComplexityRating', 'C'];
        yield 'complexityRatio' => ['ComplexityRatio', 30.4];
        yield 'cognitiveComplexityRatio' => ['CognitiveComplexityRatio', 22.7];

        // Anomalies status
        yield 'openIssues' => ['OpenIssues', 200];
        yield 'reopenedIssues' => ['ReopenedIssues', 4];
        yield 'confirmedIssues' => ['ConfirmedIssues', 6];
        yield 'falsePositiveIssues' => ['FalsePositiveIssues', 2];
        yield 'acceptedIssues' => ['AcceptedIssues', 1];
        yield 'highImpactAcceptedIssues' => ['HighImpactAcceptedIssues', 0];

        // Anomalies par severite
        yield 'violations' => ['Violations', 295];
        yield 'blockerViolations' => ['BlockerViolations', 7];
        yield 'criticalViolations' => ['CriticalViolations', 13];
        yield 'majorViolations' => ['MajorViolations', 153];
        yield 'minorViolations' => ['MinorViolations', 13];
        yield 'infoViolations' => ['InfoViolations', 109];
        yield 'softwareQualityBlockerIssues' => ['SoftwareQualityBlockerIssues', 0];
        yield 'softwareQualityHighIssues' => ['SoftwareQualityHighIssues', 5];
        yield 'softwareQualityMediumIssues' => ['SoftwareQualityMediumIssues', 12];
        yield 'softwareQualityLowIssues' => ['SoftwareQualityLowIssues', 8];
        yield 'softwareQualityInfoIssues' => ['SoftwareQualityInfoIssues', 1];

        // Code smells
        yield 'codeSmells' => ['CodeSmells', 198];
        // MODIF 2026-05-05 [fix-mesures-align-ddl] : codeSmell{Blocker/Critical/Major/Minor/Info} retires.
        // MODIF 2026-05-05 [fix-mesures-maintainability-type] : maintainabilityIssues passe de int a string(255).
        yield 'maintainabilityIssues' => ['MaintainabilityIssues', '198'];
        yield 'sqaleIndex' => ['SqaleIndex', 3054];
        yield 'sqaleDebtRatio' => ['SqaleDebtRatio', 26.0];
        yield 'sqaleRating' => ['SqaleRating', 'A'];
        yield 'effortToReachMaintainabilityRatingA' => ['EffortToReachMaintainabilityRatingA', 0];
        yield 'softwareQualityMaintainabilityIssues' => ['SoftwareQualityMaintainabilityIssues', '198'];
        yield 'softwareQualityMaintainabilityRating' => ['SoftwareQualityMaintainabilityRating', 'A'];
        yield 'softwareQualityMaintainabilityDebtRatio' => ['SoftwareQualityMaintainabilityDebtRatio', 26.0];
        yield 'softwareQualityMaintainabilityRemediationEffort' => ['SoftwareQualityMaintainabilityRemediationEffort', 3054];
        yield 'effortToReachSoftwareQualityMaintainabilityRatingA' => ['EffortToReachSoftwareQualityMaintainabilityRatingA', 0];

        // Bug
        yield 'bugs' => ['Bugs', 88];
        // MODIF 2026-05-05 : bug{Blocker/Critical/Major/Minor/Info} retires.
        yield 'reliabilityIssues' => ['ReliabilityIssues', '88'];
        yield 'reliabilityRating' => ['ReliabilityRating', 'E'];
        yield 'reliabilityRemediationEffort' => ['ReliabilityRemediationEffort', 1500];
        yield 'softwareQualityReliabilityIssues' => ['SoftwareQualityReliabilityIssues', '88'];
        yield 'softwareQualityReliabilityRating' => ['SoftwareQualityReliabilityRating', 'E'];
        yield 'softwareQualityReliabilityRemediationEffort' => ['SoftwareQualityReliabilityRemediationEffort', 1500];

        // Vulnerabilities
        yield 'vulnerabilities' => ['Vulnerabilities', 9];
        // MODIF 2026-05-05 [fix-mesures-align-ddl] : vulnerability{Blocker/Critical/Major/Minor/Info} retires.
        yield 'securityIssues' => ['SecurityIssues', '9'];
        yield 'securityRating' => ['SecurityRating', 'D'];
        yield 'securityRemediationEffort' => ['SecurityRemediationEffort', 540];
        yield 'softwareQualitySecurityIssues' => ['SoftwareQualitySecurityIssues', '9'];
        yield 'softwareQualitySecurityRating' => ['SoftwareQualitySecurityRating', 'D'];
        yield 'softwareQualitySecurityRemediationEffort' => ['SoftwareQualitySecurityRemediationEffort', 540];

        // Menaces potentielles
        yield 'securityHotspot' => ['SecurityHotspot', 12];
        yield 'securityReviewRating' => ['SecurityReviewRating', 'A'];
        yield 'securityHotspotsReviewed' => ['SecurityHotspotsReviewed', 75.5];
        // MODIF 2026-05-05 : menacePotentielle{ToReview*/Reviewed*/Totale}
        // (7 attributs) retires car absents du DDL et non utilises dans le code.

        // Mode collecte / utilisateur
        yield 'modeCollecte' => ['ModeCollecte', 'TRAITEMENT MANUEL'];
        yield 'utilisateurCollecte' => ['UtilisateurCollecte', 'laurent.hadjadj@ma-petite-entreprise.fr'];
    }

    #[DataProvider('attributesProvider')]
    public function testGetterSetter(string $suffix, mixed $value): void
    {
        $entity = new Mesures();
        $entity->{'set' . $suffix}($value);
        $this->assertSame($value, $entity->{'get' . $suffix}());
    }

    public function testSettingAndGettingId(): void
    {
        $entity = new Mesures();
        $entity->setId(42);
        $this->assertSame(42, $entity->getId());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $entity = new Mesures();
        $date = new \DateTimeImmutable('2024-04-12 16:23:11', new \DateTimeZone('Europe/Paris'));
        $entity->setDateEnregistrement($date);
        $this->assertEquals($date, $entity->getDateEnregistrement());
    }

    /**
     * v2.0.0 : l'entite Mesures comporte 106 attributs (id + 105 colonnes).
     * MODIF 2026-05-05 : 86 attributs (id + 85 colonnes) apres
     * suppression de 22 colonnes orphelines + ajout de coverageRating et duplicatedLinesRating.
     */
    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Mesures());
        $this->assertEquals(86, count($reflectionClass->getProperties()));
    }
}
