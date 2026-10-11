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

use App\Entity\Historique;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

/**
 * [Description HistoriqueCaseTest]
 *
 * v2.0.0 : test compact via dataProvider couvrant les 133 attributs de l'entite Historique.
 */
class HistoriqueCaseTest extends TestCase
{
    /**
     * Liste des [setter/getter suffix, valeur a injecter] pour 131 attributs simples.
     * `initial` (bool) et `dateEnregistrement` (DateTimeImmutable) sont testes a part.
     *
     * @return iterable<string, array{0: string, 1: mixed}>
     */
    public static function attributesProvider(): iterable
    {
        // Cle composite + identifiants
        yield 'mavenKey' => ['MavenKey', 'fr.ma-moulinette:ma-moulinette'];
        yield 'version' => ['Version', '1.2.0-RELEASE'];
        yield 'dateVersion' => ['DateVersion', '2024-07-12 16:34:46'];
        yield 'nomProjet' => ['NomProjet', 'ma-moulinette'];
        yield 'analyseKey' => ['AnalyseKey', 'AZCc05qWgfifxdiJPzns'];

        // Versions
        yield 'versionRelease' => ['VersionRelease', 0];
        yield 'versionSnapshot' => ['VersionSnapshot', 0];
        yield 'versionAutre' => ['VersionAutre', 1];

        // Repartition modules
        yield 'repartitionFrontend' => ['RepartitionFrontend', 21];
        yield 'repartitionBackend' => ['RepartitionBackend', 136];
        yield 'repartitionAutre' => ['RepartitionAutre', 0];
        yield 'repartitionInconnu' => ['RepartitionInconnu', 10];

        // NoSonar par langage
        yield 'javaNoSonar' => ['JavaNoSonar', 0];
        yield 'pythonNoSonar' => ['PythonNoSonar', 0];
        yield 'phpNoSonar' => ['PhpNoSonar', 0];

        // Avertissements
        yield 'suppressWarning' => ['SuppressWarning', 8];
        yield 'noPmd' => ['NoPmd', 0];
        yield 'checkStyle' => ['CheckStyle', 0];

        // To.do par langage
        yield 'javaTodo' => ['JavaTodo', 17];
        yield 'pythonTodo' => ['PythonTodo', 0];
        yield 'phpTodo' => ['PhpTodo', 0];
        yield 'xmlTodo' => ['XmlTodo', 0];
        yield 'javascriptTodo' => ['JavascriptTodo', 0];
        yield 'typescriptTodo' => ['TypescriptTodo', 0];
        yield 'rubyTodo' => ['RubyTodo', 0];

        // Quality gate
        yield 'alertStatus' => ['AlertStatus', 'OK'];

        // Statistiques de code
        yield 'lines' => ['Lines', 17049];
        yield 'ncloc' => ['Ncloc', 8928];
        // MODIF 2026-05-05 : ajout ncloc_language_distribution.
        yield 'nclocLanguageDistribution' => ['NclocLanguageDistribution', 'java=4278;ts=18690'];
        yield 'files' => ['Files', 226];
        yield 'classes' => ['Classes', 123];
        yield 'functions' => ['Functions', 457];
        yield 'statements' => ['Statements', 1024];
        yield 'commentLines' => ['CommentLines', 200];
        yield 'commentLinesDensity' => ['CommentLinesDensity', 12.5];
        yield 'commentLinesRating' => ['CommentLinesRating', 'A'];

        // Coverage
        yield 'coverage' => ['Coverage', 50.1];
        yield 'branchCoverage' => ['BranchCoverage', 40.2];
        /* MODIF 2026-05-07 [tests-validators] : lineCoverage INT→FLOAT (cf MODIF 2026-05-06 [pourcentage-line-coverage]). */
        yield 'lineCoverage' => ['LineCoverage', 5.0];
        yield 'linesToCover' => ['LinesToCover', 100];
        yield 'conditionsToCover' => ['ConditionsToCover', 50];
        yield 'uncoveredConditions' => ['UncoveredConditions', 12];

        // Tests
        yield 'tests' => ['Tests', 55];
        yield 'testExecutionTime' => ['TestExecutionTime', 4200];
        yield 'testErrors' => ['TestErrors', 0];
        yield 'testFailures' => ['TestFailures', 1];
        yield 'skippedTests' => ['SkippedTests', 2];
        yield 'testSuccessDensity' => ['TestSuccessDensity', 99.5];

        // Duplication
        yield 'duplicatedFiles' => ['DuplicatedFiles', 3];
        yield 'duplicatedBlocks' => ['DuplicatedBlocks', 5];
        yield 'duplicatedLines' => ['DuplicatedLines', 250];
        yield 'duplicatedLinesDensity' => ['DuplicatedLinesDensity', 0.2];

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
        yield 'codeSmellBlocker' => ['CodeSmellBlocker', 0];
        yield 'codeSmellCritical' => ['CodeSmellCritical', 4];
        yield 'codeSmellMajor' => ['CodeSmellMajor', 109];
        yield 'codeSmellMinor' => ['CodeSmellMinor', 13];
        yield 'codeSmellInfo' => ['CodeSmellInfo', 72];
        // MODIF 2026-05-05 : passage int -> string(255) (DDL VARCHAR(255)).
        yield 'maintainabilityIssues' => ['MaintainabilityIssues', '198'];
        yield 'sqaleIndex' => ['SqaleIndex', 3054];
        yield 'sqaleDebtRatio' => ['SqaleDebtRatio', 1.0];
        yield 'sqaleRating' => ['SqaleRating', 'A'];
        yield 'effortToReachMaintainabilityRatingA' => ['EffortToReachMaintainabilityRatingA', 0];
        yield 'softwareQualityMaintainabilityIssues' => ['SoftwareQualityMaintainabilityIssues', '198'];
        yield 'softwareQualityMaintainabilityRating' => ['SoftwareQualityMaintainabilityRating', 'A'];
        yield 'softwareQualityMaintainabilityDebtRatio' => ['SoftwareQualityMaintainabilityDebtRatio', 1.0];
        yield 'softwareQualityMaintainabilityRemediationEffort' => ['SoftwareQualityMaintainabilityRemediationEffort', 3054];
        yield 'effortToReachSoftwareQualityMaintainabilityRatingA' => ['EffortToReachSoftwareQualityMaintainabilityRatingA', 0];

        // Bug
        yield 'bugs' => ['Bugs', 88];
        yield 'bugBlocker' => ['BugBlocker', 7];
        yield 'bugCritical' => ['BugCritical', 0];
        yield 'bugMajor' => ['BugMajor', 44];
        yield 'bugMinor' => ['BugMinor', 0];
        yield 'bugInfo' => ['BugInfo', 37];
        yield 'reliabilityIssues' => ['ReliabilityIssues', '88'];
        yield 'reliabilityRating' => ['ReliabilityRating', 'E'];
        yield 'reliabilityRemediationEffort' => ['ReliabilityRemediationEffort', 1500];
        yield 'softwareQualityReliabilityIssues' => ['SoftwareQualityReliabilityIssues', '88'];
        yield 'softwareQualityReliabilityRating' => ['SoftwareQualityReliabilityRating', 'E'];
        yield 'softwareQualityReliabilityRemediationEffort' => ['SoftwareQualityReliabilityRemediationEffort', 1500];

        // Vulnerabilities
        yield 'vulnerabilities' => ['Vulnerabilities', 9];
        yield 'vulnerabilityBlocker' => ['VulnerabilityBlocker', 0];
        yield 'vulnerabilityCritical' => ['VulnerabilityCritical', 9];
        yield 'vulnerabilityMajor' => ['VulnerabilityMajor', 0];
        yield 'vulnerabilityMinor' => ['VulnerabilityMinor', 0];
        yield 'vulnerabilityInfo' => ['VulnerabilityInfo', 0];
        yield 'securityIssues' => ['SecurityIssues', '9'];
        yield 'securityRating' => ['SecurityRating', 'D'];
        yield 'securityRemediationEffort' => ['SecurityRemediationEffort', 540];
        yield 'softwareQualitySecurityIssues' => ['SoftwareQualitySecurityIssues', '9'];
        yield 'softwareQualitySecurityRating' => ['SoftwareQualitySecurityRating', 'D'];
        yield 'softwareQualitySecurityRemediationEffort' => ['SoftwareQualitySecurityRemediationEffort', 540];

        // Menaces potentielles
        yield 'securityHotspot' => ['SecurityHotspot', 0];
        yield 'securityReviewRating' => ['SecurityReviewRating', 'A'];
        yield 'securityHotspotsReviewed' => ['SecurityHotspotsReviewed', 75.5];
        yield 'menacePotentielleToReviewHigh' => ['MenacePotentielleToReviewHigh', 0];
        yield 'menacePotentielleToReviewMedium' => ['MenacePotentielleToReviewMedium', 0];
        yield 'menacePotentielleToReviewLow' => ['MenacePotentielleToReviewLow', 0];
        yield 'menacePotentielleReviewedHigh' => ['MenacePotentielleReviewedHigh', 0];
        yield 'menacePotentielleReviewedMedium' => ['MenacePotentielleReviewedMedium', 0];
        yield 'menacePotentielleReviewedLow' => ['MenacePotentielleReviewedLow', 0];
        yield 'menacePotentielleTotale' => ['MenacePotentielleTotale', 0];

        // Actuator info (JSON)
        yield 'actuatorInfo' => ['ActuatorInfo', ['build' => '1.2.3', 'env' => 'prod']];

        // Logger Java
        yield 'loggerInfo' => ['LoggerInfo', 14];
        yield 'loggerWarn' => ['LoggerWarn', 0];
        yield 'loggerError' => ['LoggerError', 15];
        yield 'loggerDebug' => ['LoggerDebug', 8];

        // Mode collecte / utilisateur
        yield 'modeCollecte' => ['ModeCollecte', 'COLLECTE'];
        yield 'utilisateurCollecte' => ['UtilisateurCollecte', 'admin@ma-moulinette.fr'];

        // MODIF 2026-10-08 : marqueur version du serveur SonarQube (traçabilité, pas d'UI).
        yield 'sonarqubeServerVersion' => ['SonarqubeServerVersion', '10.4.1'];

        // MODIF 2026-10-11 : couverture — 58 attributs ajoutés aux sessions précédentes
        // (RCI, scope, Empreinte Écologique, Clean Code 10+) n'avaient jamais été
        // exercés par ce dataProvider malgré testCountAttribut() à jour.

        // Clean Code attribute categories (SonarQube 10+)
        yield 'ccConsistent' => ['CcConsistent', 12];
        yield 'ccIntentional' => ['CcIntentional', 8];
        yield 'ccAdaptable' => ['CcAdaptable', 5];
        yield 'ccResponsible' => ['CcResponsible', 3];

        // Impact software qualities / severities (SonarQube 10+)
        yield 'qualityMaintainability' => ['QualityMaintainability', 20];
        yield 'qualityReliability' => ['QualityReliability', 10];
        yield 'qualitySecurity' => ['QualitySecurity', 5];
        yield 'impactBlocker' => ['ImpactBlocker', 1];
        yield 'impactHigh' => ['ImpactHigh', 2];
        yield 'impactMedium' => ['ImpactMedium', 3];
        yield 'impactLow' => ['ImpactLow', 4];
        yield 'impactInfo' => ['ImpactInfo', 5];

        // Compliance (OWASP Top 10 / SANS Top 25 / CWE)
        yield 'owaspTop10' => ['OwaspTop10', 7];
        yield 'sansTop25' => ['SansTop25', 4];
        yield 'cwe' => ['Cwe', 9];

        // RCI (Risk Criticality Index) — global + par module
        yield 'rciIndicator' => ['RciIndicator', 2.5];
        yield 'rciRating' => ['RciRating', 'B'];
        yield 'rciNcloc' => ['RciNcloc', 15000];
        yield 'rciWeight' => ['RciWeight', 300];
        yield 'rciFrontendIndicator' => ['RciFrontendIndicator', 1.8];
        yield 'rciFrontendRating' => ['RciFrontendRating', 'A'];
        yield 'rciFrontendNcloc' => ['RciFrontendNcloc', 5000];
        yield 'rciFrontendWeight' => ['RciFrontendWeight', 90];
        yield 'rciBackendIndicator' => ['RciBackendIndicator', 3.1];
        yield 'rciBackendRating' => ['RciBackendRating', 'C'];
        yield 'rciBackendNcloc' => ['RciBackendNcloc', 7000];
        yield 'rciBackendWeight' => ['RciBackendWeight', 150];
        yield 'rciAutreIndicator' => ['RciAutreIndicator', 0.5];
        yield 'rciAutreRating' => ['RciAutreRating', 'A'];
        yield 'rciAutreNcloc' => ['RciAutreNcloc', 2000];
        yield 'rciAutreWeight' => ['RciAutreWeight', 30];
        yield 'rciInconnuIndicator' => ['RciInconnuIndicator', 0.0];
        yield 'rciInconnuRating' => ['RciInconnuRating', 'A'];
        yield 'rciInconnuNcloc' => ['RciInconnuNcloc', 1000];
        yield 'rciInconnuWeight' => ['RciInconnuWeight', 30];

        // Empreinte Écologique (plugins SonarQube Creedengo) — global + par langage
        yield 'ecoAnomalies' => ['EcoAnomalies', 25];
        yield 'ecoDetteMinute' => ['EcoDetteMinute', 300];
        yield 'ecoRating' => ['EcoRating', 'B'];
        yield 'ecoJavaAnomalies' => ['EcoJavaAnomalies', 10];
        yield 'ecoJavaDetteMinute' => ['EcoJavaDetteMinute', 120];
        yield 'ecoJavaRating' => ['EcoJavaRating', 'A'];
        yield 'ecoJavascriptAnomalies' => ['EcoJavascriptAnomalies', 8];
        yield 'ecoJavascriptDetteMinute' => ['EcoJavascriptDetteMinute', 90];
        yield 'ecoJavascriptRating' => ['EcoJavascriptRating', 'B'];
        yield 'ecoPhpAnomalies' => ['EcoPhpAnomalies', 5];
        yield 'ecoPhpDetteMinute' => ['EcoPhpDetteMinute', 60];
        yield 'ecoPhpRating' => ['EcoPhpRating', 'A'];
        yield 'ecoPythonAnomalies' => ['EcoPythonAnomalies', 2];
        yield 'ecoPythonDetteMinute' => ['EcoPythonDetteMinute', 30];
        yield 'ecoPythonRating' => ['EcoPythonRating', 'A'];

        // Répartition par portée SonarQube (MAIN/TEST), globale et par catégorie
        yield 'scopeMain' => ['ScopeMain', 80];
        yield 'scopeTest' => ['ScopeTest', 20];
        yield 'bugScopeMain' => ['BugScopeMain', 5];
        yield 'bugScopeTest' => ['BugScopeTest', 1];
        yield 'vulnerabilityScopeMain' => ['VulnerabilityScopeMain', 3];
        yield 'vulnerabilityScopeTest' => ['VulnerabilityScopeTest', 0];
        yield 'codeSmellScopeMain' => ['CodeSmellScopeMain', 72];
        yield 'codeSmellScopeTest' => ['CodeSmellScopeTest', 19];

        // Métriques "optionnelles" (pas encore LTS, cf. BuildMapHistoryService::optionalMetricsKey())
        yield 'maintainabilityIssueSeverity' => ['MaintainabilityIssueSeverity', 25];
        yield 'reliabilityIssueSeverity' => ['ReliabilityIssueSeverity', 15];
        yield 'securityIssueSeverity' => ['SecurityIssueSeverity', 25];

        // Oubliés de la première passe (getter/setter jamais exercés)
        yield 'webTodo' => ['WebTodo', 2];
        yield 'coverageRating' => ['CoverageRating', 'B'];
        yield 'duplicatedLinesRating' => ['DuplicatedLinesRating', 'A'];
        yield 'loggerBreakdown' => ['LoggerBreakdown', ['info' => ['SLF4J' => 3], 'warn' => []]];
    }

    #[DataProvider('attributesProvider')]
    public function testGetterSetter(string $suffix, mixed $value): void
    {
        $entity = new Historique();
        $entity->{'set' . $suffix}($value);
        $this->assertSame($value, $entity->{'get' . $suffix}());
    }

    public function testSettingAndGettingInitial(): void
    {
        $entity = new Historique();
        $entity->setInitial(true);
        $this->assertTrue($entity->isInitial());
        $entity->setInitial(false);
        $this->assertFalse($entity->isInitial());
    }

    public function testSettingAndGettingDateEnregistrement(): void
    {
        $entity = new Historique();
        $date = new \DateTimeImmutable('2024-06-28 17:55:45+02:00');
        $entity->setDateEnregistrement($date);
        $this->assertEquals($date, $entity->getDateEnregistrement());
    }

    /**
     * MODIF 2026-05-05 : 134 attributs apres ajout
     * de nclocLanguageDistribution (présente en DDL, manquait a l’entité).
     * MODIF 2026-05-07 : 134 → 135 (ajout webTodo le 2026-05-06).
     * MODIF 2026-05-15 : 135 → 136 (ajout loggerBreakdown JSONB).
     * MODIF 2026-05-16 : 136 → 138 (ajout
     * coverageRating + duplicatedLinesRating oubliées de l’entité).
     * MODIF 2026-05-17 : 138 → 153 (+15 colonnes
     * cc_*, quality_*, impact_*, owasp_top10, sans_top25, cwe).
     * MODIF 2026-09-29 : 163 → 173 (+10 colonnes ncloc/weight brutes,
     * global + par module, en plus de l'indicateur/note RCI déjà présents).
     * MODIF 2026-10-06 : 173 → 181 (+8 colonnes de répartition par portée
     * SonarQube MAIN/TEST, globale et par catégorie bug/vulnerability/code_smell).
     * MODIF 2026-10-08 : 181 → 182 (+1 colonne sonarqube_server_version,
     * simple marqueur de traçabilité, pas d'UI associée).
     * MODIF 2026-10-08 : 182 → 184 (+2 colonnes eco_anomalies/eco_dette_minute,
     * Empreinte Écologique, plugins SonarQube Creedengo).
     * MODIF 2026-10-09 : 184 → 197 (+13 colonnes : eco_rating global + détail
     * par langage java/javascript/php/python, 3 colonnes chacun).
     * MODIF 2026-10-11 : 197 → 200 (+3 colonnes maintainability/reliability/
     * security_issue_severity, métriques optionnelles pas encore LTS).
     */
    public function testCountAttribut(): void
    {
        $reflectionClass = new \ReflectionClass(new Historique());
        $this->assertEquals(200, count($reflectionClass->getProperties()));
    }
}
