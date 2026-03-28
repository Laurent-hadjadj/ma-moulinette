<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\HistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: HistoriqueRepository::class)]
#[ORM\Table(
    name: 'historique',
    schema: 'ma_moulinette',
    options: ['comment' => 'Table de <fées> pour le suivi des statistiques']
)]
class Historique
{
    // -------------------------
    // Clé composite : mavenKey + version + dateVersion
    // -------------------------
    #[ORM\Id]
    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => "Clé Maven pour l'historique des projets"]
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: 'La clé Maven ne doit pas dépasser 255 caractères.'
    )]
    private string $mavenKey;

    #[ORM\Id]
    #[ORM\Column(
        name: 'version',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => "Version du projet dans l'historique"]
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: 'La version du projet ne doit pas dépasser 32 caractères.'
    )]
    private string $version;

    #[ORM\Id]
    #[ORM\Column(
        name: 'date_version',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Date de la version du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: 'La date de la version ne doit pas dépasser 128 caractères.'
    )]
    private string $dateVersion;

    // -------------------------
    // Informations sur le projet
    // -------------------------
    #[ORM\Column(
        name: 'nom_projet',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du projet associé à cette version']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: 'Le nom du projet ne doit pas dépasser 128 caractères.'
    )]
    private string $nomProjet;

    #[ORM\Column(
        name: 'analyse_key',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Clé d’analyse du projet']
    )]
    #[Assert\NotBlank(message: "La clé d'analyse ne peut pas être vide.")]
    #[Assert\Length(
        max: 32,
        maxMessage: "La clé d'analyse ne doit pas dépasser 32 caractères."
    )]
    private string $analyseKey;

    // -------------------------
    // Indicateurs de type de version
    // -------------------------

    #[ORM\Column(
        name: 'version_release',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Indicateur de release pour la version spécifique']
    )]
    #[Assert\PositiveOrZero]
    private ?int $versionRelease = null;

    #[ORM\Column(
        name: 'version_snapshot',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Indicateur de snapshot pour la version spécifique']
    )]
    #[Assert\PositiveOrZero]
    private ?int $versionSnapshot = null;

    #[ORM\Column(
        name: 'version_autre',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Indicateur pour les autres types de versions']
    )]
    #[Assert\PositiveOrZero]
    private ?int $versionAutre = null;

    #[ORM\Column(
        name: 'repartition_frontend',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Développements spécifiques frontend']
    )]
    #[Assert\PositiveOrZero]
    private ?int $repartitionFrontend = null;

    #[ORM\Column(
        name: 'repartition_backend',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Développements spécifiques backend']
    )]
    #[Assert\PositiveOrZero]
    private ?int $repartitionBackend = null;

    #[ORM\Column(
        name: 'repartition_autre',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Développements spécifiques']
    )]
    #[Assert\PositiveOrZero]
    private ?int $repartitionAutre = null;

    #[ORM\Column(
        name: 'repartition_inconnu',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Développements indéterminés']
    )]
    #[Assert\PositiveOrZero]
    private ?int $repartitionInconnu = null;

    // -------------------------
    // Compteurs de qualité
    // -------------------------

    #[ORM\Column(
        name: 'java_no_sonar',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur de l\'utilisation de NoSonar pour JAVA']
    )]
    #[Assert\PositiveOrZero]
    private ?int $javaNoSonar = null;

    #[ORM\Column(
        name: 'python_no_sonar',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur de l\'utilisation de NoSonar pour Python']
    )]
    #[Assert\PositiveOrZero]
    private ?int $pythonNoSonar = null;

    #[ORM\Column(
        name: 'php_no_sonar',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur de l\'utilisation de NoSonar pour PHP']
    )]
    #[Assert\PositiveOrZero]
    private ?int $phpNoSonar = null;

    #[ORM\Column(
        name: 'suppress_warning',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur des suppressions d\'avertissements']
    )]
    #[Assert\PositiveOrZero]
    private ?int $suppressWarning = null;

    #[ORM\Column(
        name: 'no_pmd',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur des suppressions d\'avertissements pour PMD']
    )]
    #[Assert\PositiveOrZero]
    private ?int $noPmd = null;

    #[ORM\Column(
        name: 'check_style',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur des suppressions d\'avertissements pour checkStyle']
    )]
    #[Assert\PositiveOrZero]
    private ?int $checkStyle = null;

    #[ORM\Column(
        name: 'java_todo',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur de l’utilisation de Todo pour JAVA']
    )]
    #[Assert\PositiveOrZero]
    private ?int $javaTodo = null;

    #[ORM\Column(
        name: 'python_todo',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur de l’utilisation de Todo pour PYTHON']
    )]
    #[Assert\PositiveOrZero]
    private ?int $pythonTodo = null;

    #[ORM\Column(
        name: 'php_todo',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur de l’utilisation de Todo pour PHP']
    )]
    #[Assert\PositiveOrZero]
    private ?int $phpTodo = null;

    #[ORM\Column(
        name: 'xml_todo',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur de l’utilisation de Todo pour XML']
    )]
    #[Assert\PositiveOrZero]
    private ?int $xmlTodo = null;

    #[ORM\Column(
        name: 'javascript_todo',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur de l’utilisation de Todo pour javascript']
    )]
    #[Assert\PositiveOrZero]
    private ?int $javascriptTodo = null;

    #[ORM\Column(
        name: 'typescript_todo',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur de l’utilisation de Todo pour typescript']
    )]
    #[Assert\PositiveOrZero]
    private ?int $typescriptTodo = null;

    #[ORM\Column(
        name: 'ruby_todo',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Compteur de l’utilisation de Todo pour RUBY']
    )]
    #[Assert\PositiveOrZero]
    private ?int $rubyTodo = null;

    // -------------------------
    // Quality Gates
    // -------------------------

    #[ORM\Column(
        name: 'alert_status',
        type: Types::STRING,
        length: 16,
        nullable: true,
        options: ['comment' => 'Status de respect de la quality gates.']
    )]
    private ?string $alertStatus = null;

    // -------------------------
    // Statistiques de code
    // -------------------------

    #[ORM\Column(
        name: 'lines',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de lignes dans le projet']
    )]
    #[Assert\PositiveOrZero]
    private ?int $lines = null;

    #[ORM\Column(
        name: 'ncloc',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de lignes de code dans le projet']
    )]
    #[Assert\PositiveOrZero]
    private ?int $ncloc = null;

    #[ORM\Column(
        name: 'files',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de fichier']
    )]
    #[Assert\PositiveOrZero]
    private ?int $files = null;

    #[ORM\Column(
        name: 'classes',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de classe']
    )]
    #[Assert\PositiveOrZero]
    private ?int $classes = null;

    #[ORM\Column(
        name: 'functions',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total méthode']
    )]
    #[Assert\PositiveOrZero]
    private ?int $functions = null;

    #[ORM\Column(
        name: 'statements',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total state']
    )]
    #[Assert\PositiveOrZero]
    private ?int $statements = null;

    #[ORM\Column(
        name: 'comment_lines',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de ligne de commentaire']
    )]
    #[Assert\PositiveOrZero]
    private ?int $commentLines = null;

    #[ORM\Column(
        name: 'comment_lines_density',
        type: Types::FLOAT,
        nullable: true,
        options: ['comment' => 'Pourcentage de couverture des commentaires']
    )]
    #[Assert\PositiveOrZero]
    private ?float $commentLinesDensity = null;

    #[ORM\Column(
        name: 'comment_lines_rating',
        type: Types::STRING,
        length: 4,
        nullable: true,
        options: ['comment' => 'Rating des commentaires']
    )]
    #[Assert\Choice(['A', 'B', 'C', 'D', 'E'])]
    private ?string $commentLinesRating = null;

    #[ORM\Column(
        name: 'coverage',
        type: Types::FLOAT,
        nullable: true,
        options: ['comment' => 'Pourcentage de couverture de code par les tests du projet']
    )]
    private ?float $coverage = null;

    #[ORM\Column(
        name: 'branch_coverage',
        type: Types::FLOAT,
        nullable: true,
        options: ['comment' => 'Pourcentage de couverture de code par les tests de la branche']
    )]
    private ?float $branchCoverage = null;

    #[ORM\Column(
        name: 'line_coverage',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de ligne couverte']
    )]
    #[Assert\PositiveOrZero]
    private ?int $lineCoverage = null;

    #[ORM\Column(
        name: 'lines_to_cover',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de ligne à couvrir']
    )]
    #[Assert\PositiveOrZero]
    private ?int $linesToCover = null;

    #[ORM\Column(
        name: 'conditions_to_cover',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de conditions à couvrir']
    )]
    #[Assert\PositiveOrZero]
    private ?int $conditionsToCover = null;

    #[ORM\Column(
        name: 'uncovered_conditions',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de conditions non couvertes']
    )]
    #[Assert\PositiveOrZero]
    private ?int $uncoveredConditions = null;

    // -------------------------
    // Tests
    // -------------------------

    #[ORM\Column(
        name: 'tests',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de tests unitaires exécutés']
    )]
    #[Assert\PositiveOrZero]
    private ?int $tests = null;

    #[ORM\Column(
        name: 'test_execution_time',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Temps d\'execution en milliseconde']
    )]
    #[Assert\PositiveOrZero]
    private ?int $testExecutionTime = null;

    #[ORM\Column(
        name: 'test_errors',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de tests unitaires en erreur']
    )]
    #[Assert\PositiveOrZero]
    private ?int $testErrors = null;

    #[ORM\Column(
        name: 'test_failures',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de tests unitaires en échec']
    )]
    #[Assert\PositiveOrZero]
    private ?int $testFailures = null;

    #[ORM\Column(
        name: 'skipped_tests',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de tests unitaires non exécuté']
    )]
    #[Assert\PositiveOrZero]
    private ?int $skippedTests = null;

    #[ORM\Column(
        name: 'test_success_density',
        type: Types::FLOAT,
        nullable: true,
        options: ['comment' => 'Pourcentage de tests passés.']
    )]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $testSuccessDensity = null;

    // -------------------------
    // DUPLICATION
    // -------------------------

    #[ORM\Column(
        name: 'duplicated_files',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de fichiers dupliqué']
    )]
    #[Assert\PositiveOrZero]
    private ?int $duplicatedFiles = null;

    #[ORM\Column(
        name: 'duplicated_blocks',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de blocs de codes dupliqué']
    )]
    #[Assert\PositiveOrZero]
    private ?int $duplicatedBlocks = null;

    #[ORM\Column(
        name: 'duplicated_lines',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de lignes dupliqué']
    )]
    #[Assert\PositiveOrZero]
    private ?int $duplicatedLines = null;

    #[ORM\Column(
        name: 'duplicated_lines_density',
        type: Types::FLOAT,
        nullable: true,
        options: ['comment' => 'Pourcentage de duplication dans le code']
    )]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $duplicatedLinesDensity = null;

    // -------------------------
    // Complexity
    // -------------------------

    #[ORM\Column(
        name: 'complexity',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Niveau de complexité cyclomatique']
    )]
    #[Assert\PositiveOrZero]
    private ?int $complexity = null;

    #[ORM\Column(
        name: 'complexity_rating',
        type: Types::STRING,
        length: 4,
        nullable: true,
        options: ['comment' => 'Note pour la complexité']
    )]
    #[Assert\Choice(['A', 'B', 'C', 'D', 'E'])]
    private ?string $complexityRating = null;

    #[ORM\Column(
        name: 'cognitive_complexity',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Niveau de complexité cognitive']
    )]
    #[Assert\PositiveOrZero]
    private ?int $cognitiveComplexity = null;

    #[ORM\Column(
        name: 'cognitive_complexity_rating',
        type: Types::STRING,
        length: 4,
        nullable: true,
        options: ['comment' => 'Note pour la complexité cognitive']
    )]
    #[Assert\Choice(['A', 'B', 'C', 'D', 'E'])]
    private ?string $cognitiveComplexityRating = null;

    #[ORM\Column(
        name: 'complexity_ratio',
        type: Types::FLOAT,
        nullable: true,
        options: ['comment' => 'Pourcentage de complexité']
    )]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $complexityRatio = null;

    #[ORM\Column(
        name: 'cognitive_complexity_ratio',
        type: Types::FLOAT,
        nullable: true,
        options: ['comment' => 'Pourcentage de complexité cognitive']
    )]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $cognitiveComplexityRatio = null;

    // -------------------------
    // Anomalies status
    // -------------------------

    #[ORM\Column(
        name: 'open_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de défauts ouvert']
    )]
    #[Assert\PositiveOrZero]
    private ?int $openIssues = null;

    #[ORM\Column(
        name: 'reopened_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de défauts réouvert']
    )]
    #[Assert\PositiveOrZero]
    private ?int $reopenedIssues = null;

    #[ORM\Column(
        name: 'confirmed_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de défauts confirmé']
    )]
    #[Assert\PositiveOrZero]
    private ?int $confirmedIssues = null;

    #[ORM\Column(
        name: 'false_positive_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de défauts faux positif']
    )]
    #[Assert\PositiveOrZero]
    private ?int $falsePositiveIssues = null;

    #[ORM\Column(
        name: 'accepted_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de défauts accepté']
    )]
    #[Assert\PositiveOrZero]
    private ?int $acceptedIssues = null;

    #[ORM\Column(
        name: 'high_impact_accepted_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de défauts accepté avec fort impact']
    )]
    #[Assert\PositiveOrZero]
    private ?int $highImpactAcceptedIssues = null;

    // -------------------------
    // Anomalies per severities
    // -------------------------

    #[ORM\Column(
        name: 'violations',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de défauts détectés']
    )]
    #[Assert\PositiveOrZero]
    private ?int $violations = null;

    #[ORM\Column(
        name: 'blocker_violations',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre d'anomalies bloquantes"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $blockerViolations = null;

    #[ORM\Column(
        name: 'critical_violations',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre d'anomalies critiques"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $criticalViolations = null;

    #[ORM\Column(
        name: 'major_violations',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre d'anomalies majeures"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $majorViolations = null;

    #[ORM\Column(
        name: 'minor_violations',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre d'anomalies mineures"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $minorViolations = null;

    #[ORM\Column(
        name: 'info_violations',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre d'anomalies d'information"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $infoViolations = null;

    #[ORM\Column(
        name: 'software_quality_blocker_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre d'anomalies bloquantes"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $softwareQualityBlockerIssues = null;

    #[ORM\Column(
        name: 'software_quality_high_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre d'anomalies critiques"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $softwareQualityHighIssues = null;

    #[ORM\Column(
        name: 'software_quality_medium_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre d'anomalies majeures"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $softwareQualityMediumIssues = null;

    #[ORM\Column(
        name: 'software_quality_low_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre d'anomalies mineures"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $softwareQualityLowIssues = null;

    #[ORM\Column(
        name: 'software_quality_info_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre d'anomalies d'information"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $softwareQualityInfoIssues = null;

    // -------------------------
    // Code smells
    // -------------------------

    #[ORM\Column(
        name: 'code_smells',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de mauvaise pratique détectés']
    )]
    #[Assert\PositiveOrZero]
    private ?int $codeSmells = null;

    #[ORM\Column(
        name: 'code_smell_blocker',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre de mauvaises pratiques bloquantes"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $codeSmellBlocker = null;

    #[ORM\Column(
        name: 'code_smell_critical',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de mauvaise pratique critiques']
    )]
    #[Assert\PositiveOrZero]
    private ?int $codeSmellCritical = null;

    #[ORM\Column(
        name: 'code_smell_major',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de mauvaise pratique majeurs']
    )]
    #[Assert\PositiveOrZero]
    private ?int $codeSmellMajor = null;

    #[ORM\Column(
        name: 'code_smell_minor',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de mauvaise pratique mineurs']
    )]
    #[Assert\PositiveOrZero]
    private ?int $codeSmellMinor = null;

    #[ORM\Column(
        name: 'code_smell_info',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre de mauvaise pratique d'information"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $codeSmellInfo = null;

    #[ORM\Column(
        name: 'maintainability_issues',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Liste des mauvaise pratique"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $maintainabilityIssues = null;

    #[ORM\Column(
        name: 'sqale_index',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Dette technique']
    )]
    #[Assert\PositiveOrZero]
    private ?int $sqaleIndex = null;

    #[ORM\Column(
        name: 'sqale_debt_ratio',
        type: Types::FLOAT,
        nullable: true,
        options: ['comment' => 'Ratio de la dette technique (SQALE)']
    )]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $sqaleDebtRatio = null;

    #[ORM\Column(
        name: 'sqale_rating',
        type: Types::STRING,
        length: 4,
        nullable: true,
        options: ['comment' => 'Note SQALE attribuée au projet']
    )]
    #[Assert\Choice(['A', 'B', 'C', 'D', 'E'])]
    private ?string $sqaleRating = null;

    #[ORM\Column(
        name: 'effort_to_reach_maintainability_rating_a',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Effort pour atteindre la note A']
    )]
    #[Assert\PositiveOrZero]
    private ?int $effortToReachMaintainabilityRatingA = null;

    #[ORM\Column(
        name: 'software_quality_maintainability_issues',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['comment' => 'Liste des indicateurs pour les mauvaises pratiques.']
    )]
    private ?string $softwareQualityMaintainabilityIssues = null;

    #[ORM\Column(
        name: 'software_quality_maintainability_rating',
        type: Types::STRING,
        length: 4,
        nullable: true,
        options: ['comment' => 'Note SQALE attribuée au projet']
    )]
    #[Assert\Choice(['A', 'B', 'C', 'D', 'E'])]
    private ?string $softwareQualityMaintainabilityRating = null;

    #[ORM\Column(
        name: 'software_quality_maintainability_debt_ratio',
        type: Types::FLOAT,
        nullable: true,
        options: ['comment' => 'Pourcentage de dette technique']
    )]
    #[Assert\Range(min: 0, max: 100)]
    private ?float $softwareQualityMaintainabilityDebtRatio = null;

    #[ORM\Column(
        name: 'software_quality_maintainability_remediation_effort',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Effort pour corriger les mauvaises pratiques']
    )]
    #[Assert\PositiveOrZero]
    private ?int $softwareQualityMaintainabilityRemediationEffort = null;

    #[ORM\Column(
        name: 'effort_to_reach_software_quality_maintainability_rating_a',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Effort pour atteindre la note A']
    )]
    #[Assert\PositiveOrZero]
    private ?int $effortToReachSoftwareQualityMaintainabilityRatingA = null;

    // -------------------------
    // BUG
    // -------------------------

    #[ORM\Column(
        name: 'bugs',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de bugs détectés']
    )]
    #[Assert\PositiveOrZero]
    private ?int $bugs = null;

    #[ORM\Column(
        name: 'bug_blocker',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de bugs bloquants']
    )]
    #[Assert\PositiveOrZero]
    private ?int $bugBlocker = null;

    #[ORM\Column(
        name: 'bug_critical',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de bugs critiques']
    )]
    #[Assert\PositiveOrZero]
    private ?int $bugCritical = null;

    #[ORM\Column(
        name: 'bug_major',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de bugs majeurs']
    )]
    #[Assert\PositiveOrZero]
    private ?int $bugMajor = null;

    #[ORM\Column(
        name: 'bug_minor',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de bugs mineurs']
    )]
    #[Assert\PositiveOrZero]
    private ?int $bugMinor = null;

    #[ORM\Column(
        name: 'bug_info',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre de bugs d'information"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $bugInfo = null;

    #[ORM\Column(
        name: 'reliability_issues',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['comment' => "Liste des indicateurs de fiabilité."]
    )]
    private ?string $reliabilityIssues = null;

    #[ORM\Column(
        name: 'reliability_rating',
        type: Types::STRING,
        length: 4,
        nullable: true,
        options: ['comment' => "Note de fiabilité attribuée au projet"]
    )]
    #[Assert\Choice(['A', 'B', 'C', 'D', 'E'])]
    private ?string $reliabilityRating = null;

    #[ORM\Column(
        name: 'reliability_remediation_effort',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Effort de remediation pour corriger"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $reliabilityRemediationEffort = null;

    #[ORM\Column(
        name: 'software_quality_reliability_issues',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['comment' => "Liste des indicateurs de fiabilité."]
    )]
    private ?string $softwareQualityReliabilityIssues = null;

    #[ORM\Column(
        name: 'software_quality_reliability_rating',
        type: Types::STRING,
        length: 4,
        nullable: true,
        options: ['comment' => "Note de fiabilité attribuée au projet"]
    )]
    #[Assert\Choice(['A', 'B', 'C', 'D', 'E'])]
    private ?string $softwareQualityReliabilityRating = null;

    #[ORM\Column(
        name: 'software_quality_reliability_remediation_effort',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Effort de remediation pour corriger"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $softwareQualityReliabilityRemediationEffort = null;

    // -------------------------
    // VULNERABILITIES
    // -------------------------

    #[ORM\Column(
        name: 'vulnerabilities',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de vulnérabilités détectées']
    )]
    #[Assert\PositiveOrZero]
    private ?int $vulnerabilities = null;

    #[ORM\Column(
        name: 'vulnerability_blocker',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de vulnérabilités bloquantes']
    )]
    #[Assert\PositiveOrZero]
    private ?int $vulnerabilityBlocker = null;

    #[ORM\Column(
        name: 'vulnerability_critical',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de vulnérabilités critiques']
    )]
    #[Assert\PositiveOrZero]
    private ?int $vulnerabilityCritical = null;

    #[ORM\Column(
        name: 'vulnerability_major',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de vulnérabilités majeures']
    )]
    #[Assert\PositiveOrZero]
    private ?int $vulnerabilityMajor = null;

    #[ORM\Column(
        name: 'vulnerability_minor',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de vulnérabilités mineures']
    )]
    #[Assert\PositiveOrZero]
    private ?int $vulnerabilityMinor = null;

    #[ORM\Column(
        name: 'vulnerability_info',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Nombre de vulnérabilités d'information"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $vulnerabilityInfo = null;

    #[ORM\Column(
        name: 'security_issues',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['comment' => "Liste des indicateurs de sécurités."]
    )]
    private ?string $securityIssues = null;

    #[ORM\Column(
        name: 'security_rating',
        type: Types::STRING,
        length: 4,
        nullable: true,
        options: ['comment' => "Note de sécurité attribuée au projet"]
    )]
    #[Assert\Choice(['A', 'B', 'C', 'D', 'E'])]
    private ?string $securityRating = null;

    #[ORM\Column(
        name: 'security_remediation_effort',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Effort de remediation"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $securityRemediationEffort = null;

    #[ORM\Column(
        name: 'software_quality_security_issues',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['comment' => "Liste des indicateurs de sécurité."]
    )]
    private ?string $softwareQualitySecurityIssues = null;

    #[ORM\Column(
        name: 'software_quality_security_rating',
        type: Types::STRING,
        length: 4,
        nullable: true,
        options: ['comment' => "Note de sécurité attribuée au projet"]
    )]
    #[Assert\Choice(['A', 'B', 'C', 'D', 'E'])]
    private ?string $softwareQualitySecurityRating = null;

    #[ORM\Column(
        name: 'software_quality_security_remediation_effort',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => "Effort de remediation"]
    )]
    #[Assert\PositiveOrZero]
    private ?int $softwareQualitySecurityRemediationEffort = null;

    // -------------------------
    // Menaces potentielles de sécurité
    // -------------------------

    #[ORM\Column(
        name: 'security_hotspot',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de menaces potentielles.']
    )]
    #[Assert\PositiveOrZero]
    private ?int $securityHotspot = null;

    #[ORM\Column(
        name: 'security_review_rating',
        type: Types::STRING,
        length: 4,
        nullable: true,
        options: ['comment' => 'Note pour les menaces potentielles.']
    )]
    private ?string $securityReviewRating = null;

    #[ORM\Column(
        name: 'security_hotspots_reviewed',
        type: Types::FLOAT,
        nullable: true,
        options: ['comment' => 'Pourcentage de menaces potentielles examiné.']
    )]
    #[Assert\PositiveOrZero]
    private ?float $securityHotspotsReviewed = null;

    #[ORM\Column(
        name: 'menace_potentielle_to_review_high',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de menaces potentielles de sécurité de niveau élevé à vérifier']
    )]
    #[Assert\PositiveOrZero]
    private ?int $menacePotentielleToReviewHigh = null;

    #[ORM\Column(
        name: 'menace_potentielle_to_review_medium',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de menaces potentielles de sécurité de niveau moyen à vérifier']
    )]
    #[Assert\PositiveOrZero]
    private ?int $menacePotentielleToReviewMedium = null;

    #[ORM\Column(
        name: 'menace_potentielle_to_review_low',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de menaces potentielles de sécurité de niveau faible à vérifier']
    )]
    #[Assert\PositiveOrZero]
    private ?int $menacePotentielleToReviewLow = null;

    #[ORM\Column(
        name: 'menace_potentielle_reviewed_high',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de menaces potentielles de sécurité de niveau élevé vérifié']
    )]
    #[Assert\PositiveOrZero]
    private ?int $menacePotentielleReviewedHigh = null;

    #[ORM\Column(
        name: 'menace_potentielle_reviewed_medium',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de menaces potentielles de sécurité de niveau moyen vérifié']
    )]
    #[Assert\PositiveOrZero]
    private ?int $menacePotentielleReviewedMedium = null;

    #[ORM\Column(
        name: 'menace_potentielle_reviewed_low',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre de menaces potentielles de sécurité de niveau faible vérifié']
    )]
    #[Assert\PositiveOrZero]
    private ?int $menacePotentielleReviewedLow = null;

    #[ORM\Column(
        name: 'menace_potentielle_totale',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Nombre total de menaces potentielles de sécurité']
    )]
    #[Assert\PositiveOrZero]
    private ?int $menacePotentielleTotale = null;

    // -------------------------
    // Actuator info
    // -------------------------
    #[ORM\Column(
        name: 'actuator_info',
        type: Types::JSON,
        nullable: true,
        options: ['comment' => 'Actuator Info']
    )]
    private ?array $actuatorInfo = null;

    // -------------------------
    // Logger Java
    // -------------------------
    #[ORM\Column(
        name: 'logger_info',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Logger JAVA de type INFO']
    )]
    private ?int $loggerInfo = null;

    #[ORM\Column(
        name: 'logger_warn',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Logger JAVA de type WARN']
    )]
    private ?int $loggerWarn = null;

    #[ORM\Column(
        name: 'logger_error',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Logger JAVA de type ERROR']
    )]
    private ?int $loggerError = null;

    #[ORM\Column(
        name: 'logger_debug',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Logger JAVA de type DEBUG']
    )]
    private ?int $loggerDebug = null;

    // -------------------------
    // Initialisation du projet
    // -------------------------
    #[ORM\Column(
        name: 'initial',
        type: Types::BOOLEAN,
        nullable: false,
        options: ['comment' => "Indique si c'est l'initialisation du projet"]
    )]
    #[Assert\NotNull]
    private bool $initial;

    // -------------------------
    // Mode de collecte et utilisateur
    // -------------------------
    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : COLLECTE | TRAITEMENT MANUEL | TRAITEMENT AUTOMATIQUE']
    )]
    #[Assert\Length(
        max: 32,
        maxMessage: 'Le mode de collecte ne peut pas dépasser 32 caractères.'
    )]
    private ?string $modeCollecte = null;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => "Compte de l'utilisateur qui a réalisé la collecte."]
    )]
    #[Assert\Length(
        max: 320,
        maxMessage: "Le compte de l'utilisateur ne peut pas dépasser 320 caractères."
    )]
    private ?string $utilisateurCollecte = null;

    // -------------------------
    // Date d'enregistrement
    // -------------------------
    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "Date d'enregistrement de l'historique"]
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
        $this->dateEnregistrement = new \DateTimeImmutable();
    }

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    /*
     * Le générateur d'entity ne génère pas le setter pour une clé multiple
        public function setMavenKey(string $mavenKey): self
        {
            $this->mavenKey = $mavenKey;
            return $this;
        }
    */

    /*
     * Le générateur d'entity ne génère pas le setter pour une clé multiple
        public function setVersion(string $version): self
        {
            $this->version = $version;
            return $this;
        }
    */

    /*
     * Le générateur d'entity ne génère pas le setter pour une clé multiple
        public function setDateVersion(string $dateVersion): self
        {
            $this->dateVersion = $dateVersion;
            return $this;
        }
    */

    /**
     * [Description for setMavenKey]
     *
     * @param string $mavenKey
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:58:55 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setMavenKey(string $mavenKey): \App\Entity\Historique
    {
        $this->mavenKey = $mavenKey;
        return $this;
    }

    /**
     * [Description for setVersion]
     *
     * @param string $version
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:58:34 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setVersion(string $version): \App\Entity\Historique
    {
        $this->version = $version;
        return $this;
    }

    /**
     * [Description for setDateVersion]
     *
     * @param string $dateVersion
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:58:08 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setDateVersion(string $dateVersion): \App\Entity\Historique
    {
        $this->dateVersion = $dateVersion;
        return $this;
    }

    /**
     * [Description for getVersion]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:53:53 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getVersion(): ?string
    {
        return $this->version;
    }

    /**
     * [Description for getDateVersion]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:53:55 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getDateVersion(): ?string
    {
        return $this->dateVersion;
    }

    /**
     * [Description for getNomProjet]
     *
     * @return string|null
     *
     * Created at: 02/01/2023, 17:53:57 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function getNomProjet(): ?string
    {
        return $this->nomProjet;
    }

    /**
     * [Description for setNomProjet]
     *
     * @param string $nomProjet
     *
     * @return self
     *
     * Created at: 02/01/2023, 17:53:59 (Europe/Paris)
     * @author    Laurent HADJADJ <laurent_h@me.com>
     * @copyright Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function setNomProjet(string $nomProjet): \App\Entity\Historique
    {
        $this->nomProjet = $nomProjet;

        return $this;
    }

    public function getAnalyseKey(): ?string
    {
        return $this->analyseKey;
    }

    public function setAnalyseKey(string $analyseKey): self
    {
        $this->analyseKey = $analyseKey;

        return $this;
    }

    public function getVersionRelease(): ?int
    {
        return $this->versionRelease;
    }

    public function setVersionRelease(?int $versionRelease): self
    {
        $this->versionRelease = $versionRelease;
        return $this;
    }

    public function getVersionSnapshot(): ?int
    {
        return $this->versionSnapshot;
    }

    public function setVersionSnapshot(?int $versionSnapshot): self
    {
        $this->versionSnapshot = $versionSnapshot;
        return $this;
    }

    public function getVersionAutre(): ?int
    {
        return $this->versionAutre;
    }

    public function setVersionAutre(?int $versionAutre): self
    {
        $this->versionAutre = $versionAutre;
        return $this;
    }

    public function getRepartitionFrontend(): ?int
    {
        return $this->repartitionFrontend;
    }

    public function setRepartitionFrontend(?int $repartitionFrontend): self
    {
        $this->repartitionFrontend = $repartitionFrontend;
        return $this;
    }

    public function getRepartitionBackend(): ?int
    {
        return $this->repartitionBackend;
    }

    public function setRepartitionBackend(?int $repartitionBackend): self
    {
        $this->repartitionBackend = $repartitionBackend;
        return $this;
    }

    public function getRepartitionAutre(): ?int
    {
        return $this->repartitionAutre;
    }

    public function setRepartitionAutre(?int $repartitionAutre): self
    {
        $this->repartitionAutre = $repartitionAutre;
        return $this;
    }

    public function getRepartitionInconnu(): ?int
    {
        return $this->repartitionInconnu;
    }

    public function setRepartitionInconnu(?int $repartitionInconnu): self
    {
        $this->repartitionInconnu = $repartitionInconnu;
        return $this;
    }

    public function getJavaNoSonar(): ?int
    {
        return $this->javaNoSonar;
    }

    public function setJavaNoSonar(?int $javaNoSonar): self
    {
        $this->javaNoSonar = $javaNoSonar;
        return $this;
    }

    public function getPythonNoSonar(): ?int
    {
        return $this->pythonNoSonar;
    }

    public function setPythonNoSonar(?int $pythonNoSonar): self
    {
        $this->pythonNoSonar = $pythonNoSonar;
        return $this;
    }

    public function getPhpNoSonar(): ?int
    {
        return $this->phpNoSonar;
    }

    public function setPhpNoSonar(?int $phpNoSonar): self
    {
        $this->phpNoSonar = $phpNoSonar;
        return $this;
    }

    public function getSuppressWarning(): ?int
    {
        return $this->suppressWarning;
    }

    public function setSuppressWarning(?int $suppressWarning): self
    {
        $this->suppressWarning = $suppressWarning;
        return $this;
    }

    public function getNoPmd(): ?int
    {
        return $this->noPmd;
    }

    public function setNoPmd(?int $noPmd): self
    {
        $this->noPmd = $noPmd;
        return $this;
    }

    public function getCheckStyle(): ?int
    {
        return $this->checkStyle;
    }

    public function setCheckStyle(?int $checkStyle): self
    {
        $this->checkStyle = $checkStyle;
        return $this;
    }

    public function getJavaTodo(): ?int
    {
        return $this->javaTodo;
    }

    public function setJavaTodo(?int $javaTodo): self
    {
        $this->javaTodo = $javaTodo;
        return $this;
    }

    public function getPythonTodo(): ?int
    {
        return $this->pythonTodo;
    }

    public function setPythonTodo(?int $pythonTodo): self
    {
        $this->pythonTodo = $pythonTodo;
        return $this;
    }

    public function getPhpTodo(): ?int
    {
        return $this->phpTodo;
    }

    public function setPhpTodo(?int $phpTodo): self
    {
        $this->phpTodo = $phpTodo;
        return $this;
    }

    public function getXmlTodo(): ?int
    {
        return $this->xmlTodo;
    }

    public function setXmlTodo(?int $xmlTodo): self
    {
        $this->xmlTodo = $xmlTodo;
        return $this;
    }

    public function getJavascriptTodo(): ?int
    {
        return $this->javascriptTodo;
    }

    public function setJavascriptTodo(?int $javascriptTodo): self
    {
        $this->javascriptTodo = $javascriptTodo;
        return $this;
    }

    public function getTypescriptTodo(): ?int
    {
        return $this->typescriptTodo;
    }

    public function setTypescriptTodo(?int $typescriptTodo): self
    {
        $this->typescriptTodo = $typescriptTodo;
        return $this;
    }

    public function getRubyTodo(): ?int
    {
        return $this->rubyTodo;
    }

    public function setRubyTodo(?int $rubyTodo): self
    {
        $this->rubyTodo = $rubyTodo;
        return $this;
    }

    public function getAlertStatus(): ?string
    {
        return $this->alertStatus;
    }

    public function setAlertStatus(?string $alertStatus): self
    {
        $this->alertStatus = $alertStatus;
        return $this;
    }

    public function getLines(): ?int
    {
        return $this->lines;
    }

    public function setLines(?int $lines): self
    {
        $this->lines = $lines;
        return $this;
    }

    public function getNcloc(): ?int
    {
        return $this->ncloc;
    }

    public function setNcloc(?int $ncloc): self
    {
        $this->ncloc = $ncloc;
        return $this;
    }

    public function getFiles(): ?int
    {
        return $this->files;
    }

    public function setFiles(?int $files): self
    {
        $this->files = $files;
        return $this;
    }

    public function getClasses(): ?int
    {
        return $this->classes;
    }

    public function setClasses(?int $classes): self
    {
        $this->classes = $classes;
        return $this;
    }

    public function getFunctions(): ?int
    {
        return $this->functions;
    }

    public function setFunctions(?int $functions): self
    {
        $this->functions = $functions;
        return $this;
    }

    public function getStatements(): ?int
    {
        return $this->statements;
    }

    public function setStatements(?int $statements): self
    {
        $this->statements = $statements;
        return $this;
    }

    public function getCommentLines(): ?int
    {
        return $this->commentLines;
    }

    public function setCommentLines(?int $commentLines): self
    {
        $this->commentLines = $commentLines;
        return $this;
    }

    public function getCommentLinesDensity(): ?float
    {
        return $this->commentLinesDensity;
    }

    public function setCommentLinesDensity(?float $commentLinesDensity): self
    {
        $this->commentLinesDensity = $commentLinesDensity;
        return $this;
    }

    public function getCommentLinesRating(): ?string
    {
        return $this->commentLinesRating;
    }

    public function setCommentLinesRating(?string $commentLinesRating): self
    {
        $this->commentLinesRating = $commentLinesRating;
        return $this;
    }

    public function getCoverage(): ?float
    {
        return $this->coverage;
    }

    public function setCoverage(?float $coverage): self
    {
        $this->coverage = $coverage;
        return $this;
    }

    public function getBranchCoverage(): ?float
    {
        return $this->branchCoverage;
    }

    public function setBranchCoverage(?float $branchCoverage): self
    {
        $this->branchCoverage = $branchCoverage;
        return $this;
    }

    public function getLineCoverage(): ?int
    {
        return $this->lineCoverage;
    }

    public function setLineCoverage(?int $lineCoverage): self
    {
        $this->lineCoverage = $lineCoverage;
        return $this;
    }

    public function getLinesToCover(): ?int
    {
        return $this->linesToCover;
    }

    public function setLinesToCover(?int $linesToCover): self
    {
        $this->linesToCover = $linesToCover;
        return $this;
    }

    public function getConditionsToCover(): ?int
    {
        return $this->conditionsToCover;
    }

    public function setConditionsToCover(?int $conditionsToCover): self
    {
        $this->conditionsToCover = $conditionsToCover;
        return $this;
    }

    public function getUncoveredConditions(): ?int
    {
        return $this->uncoveredConditions;
    }

    public function setUncoveredConditions(?int $uncoveredConditions): self
    {
        $this->uncoveredConditions = $uncoveredConditions;
        return $this;
    }

    public function getTests(): ?int
    {
        return $this->tests;
    }

    public function setTests(?int $tests): self
    {
        $this->tests = $tests;
        return $this;
    }

    public function getTestExecutionTime(): ?int
    {
        return $this->testExecutionTime;
    }

    public function setTestExecutionTime(?int $testExecutionTime): self
    {
        $this->testExecutionTime = $testExecutionTime;
        return $this;
    }

    public function getTestErrors(): ?int
    {
        return $this->testErrors;
    }

    public function setTestErrors(?int $testErrors): self
    {
        $this->testErrors = $testErrors;
        return $this;
    }

    public function getTestFailures(): ?int
    {
        return $this->testFailures;
    }

    public function setTestFailures(?int $testFailures): self
    {
        $this->testFailures = $testFailures;
        return $this;
    }

    public function getSkippedTests(): ?int
    {
        return $this->skippedTests;
    }

    public function setSkippedTests(?int $skippedTests): self
    {
        $this->skippedTests = $skippedTests;
        return $this;
    }

    public function getTestSuccessDensity(): ?float
    {
        return $this->testSuccessDensity;
    }

    public function setTestSuccessDensity(?float $testSuccessDensity): self
    {
        $this->testSuccessDensity = $testSuccessDensity;
        return $this;
    }

    public function getDuplicatedFiles(): ?int
    {
        return $this->duplicatedFiles;
    }

    public function setDuplicatedFiles(?int $duplicatedFiles): self
    {
        $this->duplicatedFiles = $duplicatedFiles;
        return $this;
    }

    public function getDuplicatedBlocks(): ?int
    {
        return $this->duplicatedBlocks;
    }

    public function setDuplicatedBlocks(?int $duplicatedBlocks): self
    {
        $this->duplicatedBlocks = $duplicatedBlocks;
        return $this;
    }

    public function getDuplicatedLines(): ?int
    {
        return $this->duplicatedLines;
    }

    public function setDuplicatedLines(?int $duplicatedLines): self
    {
        $this->duplicatedLines = $duplicatedLines;
        return $this;
    }

    public function getDuplicatedLinesDensity(): ?float
    {
        return $this->duplicatedLinesDensity;
    }

    public function setDuplicatedLinesDensity(?float $duplicatedLinesDensity): self
    {
        $this->duplicatedLinesDensity = $duplicatedLinesDensity;
        return $this;
    }

    public function getComplexity(): ?int
    {
        return $this->complexity;
    }

    public function setComplexity(?int $complexity): self
    {
        $this->complexity = $complexity;
        return $this;
    }

    public function getComplexityRating(): ?string
    {
        return $this->complexityRating;
    }

    public function setComplexityRating(?string $complexityRating): self
    {
        $this->complexityRating = $complexityRating;
        return $this;
    }

    public function getCognitiveComplexity(): ?int
    {
        return $this->cognitiveComplexity;
    }

    public function setCognitiveComplexity(?int $cognitiveComplexity): self
    {
        $this->cognitiveComplexity = $cognitiveComplexity;
        return $this;
    }

    public function getCognitiveComplexityRating(): ?string
    {
        return $this->cognitiveComplexityRating;
    }

    public function setCognitiveComplexityRating(?string $cognitiveComplexityRating): self
    {
        $this->cognitiveComplexityRating = $cognitiveComplexityRating;
        return $this;
    }

    public function getComplexityRatio(): ?float
    {
        return $this->complexityRatio;
    }

    public function setComplexityRatio(?float $complexityRatio): self
    {
        $this->complexityRatio = $complexityRatio;
        return $this;
    }

    public function getCognitiveComplexityRatio(): ?float
    {
        return $this->cognitiveComplexityRatio;
    }

    public function setCognitiveComplexityRatio(?float $cognitiveComplexityRatio): self
    {
        $this->cognitiveComplexityRatio = $cognitiveComplexityRatio;
        return $this;
    }

    public function getOpenIssues(): ?int
    {
        return $this->openIssues;
    }

    public function setOpenIssues(?int $openIssues): self
    {
        $this->openIssues = $openIssues;
        return $this;
    }

    public function getReopenedIssues(): ?int
    {
        return $this->reopenedIssues;
    }

    public function setReopenedIssues(?int $reopenedIssues): self
    {
        $this->reopenedIssues = $reopenedIssues;
        return $this;
    }

    public function getConfirmedIssues(): ?int
    {
        return $this->confirmedIssues;
    }

    public function setConfirmedIssues(?int $confirmedIssues): self
    {
        $this->confirmedIssues = $confirmedIssues;
        return $this;
    }

    public function getFalsePositiveIssues(): ?int
    {
        return $this->falsePositiveIssues;
    }

    public function setFalsePositiveIssues(?int $falsePositiveIssues): self
    {
        $this->falsePositiveIssues = $falsePositiveIssues;
        return $this;
    }

    public function getAcceptedIssues(): ?int
    {
        return $this->acceptedIssues;
    }

    public function setAcceptedIssues(?int $acceptedIssues): self
    {
        $this->acceptedIssues = $acceptedIssues;
        return $this;
    }

    public function getHighImpactAcceptedIssues(): ?int
    {
        return $this->highImpactAcceptedIssues;
    }

    public function setHighImpactAcceptedIssues(?int $highImpactAcceptedIssues): self
    {
        $this->highImpactAcceptedIssues = $highImpactAcceptedIssues;
        return $this;
    }

    public function getViolations(): ?int
    {
        return $this->violations;
    }

    public function setViolations(?int $violations): self
    {
        $this->violations = $violations;
        return $this;
    }

    public function getBlockerViolations(): ?int
    {
        return $this->blockerViolations;
    }

    public function setBlockerViolations(?int $blockerViolations): self
    {
        $this->blockerViolations = $blockerViolations;
        return $this;
    }

    public function getCriticalViolations(): ?int
    {
        return $this->criticalViolations;
    }

    public function setCriticalViolations(?int $criticalViolations): self
    {
        $this->criticalViolations = $criticalViolations;
        return $this;
    }

    public function getMajorViolations(): ?int
    {
        return $this->majorViolations;
    }

    public function setMajorViolations(?int $majorViolations): self
    {
        $this->majorViolations = $majorViolations;
        return $this;
    }

    public function getMinorViolations(): ?int
    {
        return $this->minorViolations;
    }

    public function setMinorViolations(?int $minorViolations): self
    {
        $this->minorViolations = $minorViolations;
        return $this;
    }

    public function getInfoViolations(): ?int
    {
        return $this->infoViolations;
    }

    public function setInfoViolations(?int $infoViolations): self
    {
        $this->infoViolations = $infoViolations;
        return $this;
    }

    public function getSoftwareQualityBlockerIssues(): ?int
    {
        return $this->softwareQualityBlockerIssues;
    }

    public function setSoftwareQualityBlockerIssues(?int $softwareQualityBlockerIssues): self
    {
        $this->softwareQualityBlockerIssues = $softwareQualityBlockerIssues;
        return $this;
    }

    public function getSoftwareQualityHighIssues(): ?int
    {
        return $this->softwareQualityHighIssues;
    }

    public function setSoftwareQualityHighIssues(?int $softwareQualityHighIssues): self
    {
        $this->softwareQualityHighIssues = $softwareQualityHighIssues;
        return $this;
    }

    public function getSoftwareQualityMediumIssues(): ?int
    {
        return $this->softwareQualityMediumIssues;
    }

    public function setSoftwareQualityMediumIssues(?int $softwareQualityMediumIssues): self
    {
        $this->softwareQualityMediumIssues = $softwareQualityMediumIssues;
        return $this;
    }

    public function getSoftwareQualityLowIssues(): ?int
    {
        return $this->softwareQualityLowIssues;
    }

    public function setSoftwareQualityLowIssues(?int $softwareQualityLowIssues): self
    {
        $this->softwareQualityLowIssues = $softwareQualityLowIssues;
        return $this;
    }

    public function getSoftwareQualityInfoIssues(): ?int
    {
        return $this->softwareQualityInfoIssues;
    }

    public function setSoftwareQualityInfoIssues(?int $softwareQualityInfoIssues): self
    {
        $this->softwareQualityInfoIssues = $softwareQualityInfoIssues;
        return $this;
    }

    public function getCodeSmells(): ?int
    {
        return $this->codeSmells;
    }

    public function setCodeSmells(?int $v): self
    {
        $this->codeSmells = $v;
        return $this;
    }

    public function getCodeSmellBlocker(): ?int
    {
        return $this->codeSmellBlocker;
    }

    public function setCodeSmellBlocker(?int $v): self
    {
        $this->codeSmellBlocker = $v;
        return $this;
    }

    public function getCodeSmellCritical(): ?int
    {
        return $this->codeSmellCritical;
    }

    public function setCodeSmellCritical(?int $v): self
    {
        $this->codeSmellCritical = $v;
        return $this;
    }

    public function getCodeSmellMajor(): ?int
    {
        return $this->codeSmellMajor;
    }

    public function setCodeSmellMajor(?int $v): self
    {
        $this->codeSmellMajor = $v;
        return $this;
    }

    public function getCodeSmellMinor(): ?int
    {
        return $this->codeSmellMinor;
    }

    public function setCodeSmellMinor(?int $v): self
    {
        $this->codeSmellMinor = $v;
        return $this;
    }

    public function getCodeSmellInfo(): ?int
    {
        return $this->codeSmellInfo;
    }

    public function setCodeSmellInfo(?int $v): self
    {
        $this->codeSmellInfo = $v;
        return $this;
    }

    public function getMaintainabilityIssues(): ?int
    {
        return $this->maintainabilityIssues;
    }

    public function setMaintainabilityIssues(?int $v): self
    {
        $this->maintainabilityIssues = $v;
        return $this;
    }

    public function getSqaleIndex(): ?int
    {
        return $this->sqaleIndex;
    }

    public function setSqaleIndex(?int $v): self
    {
        $this->sqaleIndex = $v;
        return $this;
    }

    public function getSqaleDebtRatio(): ?float
    {
        return $this->sqaleDebtRatio;
    }

    public function setSqaleDebtRatio(?float $v): self
    {
        $this->sqaleDebtRatio = $v;
        return $this;
    }

    public function getSqaleRating(): ?string
    {
        return $this->sqaleRating;
    }

    public function setSqaleRating(?string $v): self
    {
        $this->sqaleRating = $v;
        return $this;
    }

    public function getEffortToReachMaintainabilityRatingA(): ?int
    {
        return $this->effortToReachMaintainabilityRatingA;
    }

    public function setEffortToReachMaintainabilityRatingA(?int $v): self
    {
        $this->effortToReachMaintainabilityRatingA = $v;
        return $this;
    }

    public function getSoftwareQualityMaintainabilityIssues(): ?string
    {
        return $this->softwareQualityMaintainabilityIssues;
    }

    public function setSoftwareQualityMaintainabilityIssues(?string $v): self
    {
        $this->softwareQualityMaintainabilityIssues = $v;
        return $this;
    }

    public function getSoftwareQualityMaintainabilityRating(): ?string
    {
        return $this->softwareQualityMaintainabilityRating;
    }

    public function setSoftwareQualityMaintainabilityRating(?string $v): self
    {
        $this->softwareQualityMaintainabilityRating = $v;
        return $this;
    }

    public function getSoftwareQualityMaintainabilityDebtRatio(): ?float
    {
        return $this->softwareQualityMaintainabilityDebtRatio;
    }

    public function setSoftwareQualityMaintainabilityDebtRatio(?float $v): self
    {
        $this->softwareQualityMaintainabilityDebtRatio = $v;
        return $this;
    }

    public function getSoftwareQualityMaintainabilityRemediationEffort(): ?int
    {
        return $this->softwareQualityMaintainabilityRemediationEffort;
    }

    public function setSoftwareQualityMaintainabilityRemediationEffort(?int $v): self
    {
        $this->softwareQualityMaintainabilityRemediationEffort = $v;
        return $this;
    }

    public function getEffortToReachSoftwareQualityMaintainabilityRatingA(): ?int
    {
        return $this->effortToReachSoftwareQualityMaintainabilityRatingA;
    }

    public function setEffortToReachSoftwareQualityMaintainabilityRatingA(?int $v): self
    {
        $this->effortToReachSoftwareQualityMaintainabilityRatingA = $v;
        return $this;
    }

    public function getBugs(): ?int
    {
        return $this->bugs;
    }

    public function setBugs(?int $v): self
    {
        $this->bugs = $v;
        return $this;
    }

    public function getBugBlocker(): ?int
    {
        return $this->bugBlocker;
    }

    public function setBugBlocker(?int $v): self
    {
        $this->bugBlocker = $v;
        return $this;
    }

    public function getBugCritical(): ?int
    {
        return $this->bugCritical;
    }

    public function setBugCritical(?int $v): self
    {
        $this->bugCritical = $v;
        return $this;
    }

    public function getBugMajor(): ?int
    {
        return $this->bugMajor;
    }

    public function setBugMajor(?int $v): self
    {
        $this->bugMajor = $v;
        return $this;
    }

    public function getBugMinor(): ?int
    {
        return $this->bugMinor;
    }

    public function setBugMinor(?int $v): self
    {
        $this->bugMinor = $v;
        return $this;
    }

    public function getBugInfo(): ?int
    {
        return $this->bugInfo;
    }

    public function setBugInfo(?int $v): self
    {
        $this->bugInfo = $v;
        return $this;
    }

    public function getReliabilityIssues(): ?string
    {
        return $this->reliabilityIssues;
    }

    public function setReliabilityIssues(?string $v): self
    {
        $this->reliabilityIssues = $v;
        return $this;
    }

    public function getReliabilityRating(): ?string
    {
        return $this->reliabilityRating;
    }

    public function setReliabilityRating(?string $v): self
    {
        $this->reliabilityRating = $v;
        return $this;
    }

    public function getReliabilityRemediationEffort(): ?int
    {
        return $this->reliabilityRemediationEffort;
    }

    public function setReliabilityRemediationEffort(?int $v): self
    {
        $this->reliabilityRemediationEffort = $v;
        return $this;
    }

    public function getSoftwareQualityReliabilityIssues(): ?string
    {
        return $this->softwareQualityReliabilityIssues;
    }

    public function setSoftwareQualityReliabilityIssues(?string $v): self
    {
        $this->softwareQualityReliabilityIssues = $v;
        return $this;
    }

    public function getSoftwareQualityReliabilityRating(): ?string
    {
        return $this->softwareQualityReliabilityRating;
    }

    public function setSoftwareQualityReliabilityRating(?string $v): self
    {
        $this->softwareQualityReliabilityRating = $v;
        return $this;
    }

    public function getSoftwareQualityReliabilityRemediationEffort(): ?int
    {
        return $this->softwareQualityReliabilityRemediationEffort;
    }

    public function setSoftwareQualityReliabilityRemediationEffort(?int $v): self
    {
        $this->softwareQualityReliabilityRemediationEffort = $v;
        return $this;
    }

    public function getVulnerabilities(): ?int
    {
        return $this->vulnerabilities;
    }

    public function setVulnerabilities(?int $v): self
    {
        $this->vulnerabilities = $v;
        return $this;
    }

    public function getVulnerabilityBlocker(): ?int
    {
        return $this->vulnerabilityBlocker;
    }

    public function setVulnerabilityBlocker(?int $v): self
    {
        $this->vulnerabilityBlocker = $v;
        return $this;
    }

    public function getVulnerabilityCritical(): ?int
    {
        return $this->vulnerabilityCritical;
    }

    public function setVulnerabilityCritical(?int $v): self
    {
        $this->vulnerabilityCritical = $v;
        return $this;
    }

    public function getVulnerabilityMajor(): ?int
    {
        return $this->vulnerabilityMajor;
    }

    public function setVulnerabilityMajor(?int $v): self
    {
        $this->vulnerabilityMajor = $v;
        return $this;
    }

    public function getVulnerabilityMinor(): ?int
    {
        return $this->vulnerabilityMinor;
    }

    public function setVulnerabilityMinor(?int $v): self
    {
        $this->vulnerabilityMinor = $v;
        return $this;
    }

    public function getVulnerabilityInfo(): ?int
    {
        return $this->vulnerabilityInfo;
    }

    public function setVulnerabilityInfo(?int $v): self
    {
        $this->vulnerabilityInfo = $v;
        return $this;
    }

    public function getSecurityIssues(): ?string
    {
        return $this->securityIssues;
    }

    public function setSecurityIssues(?string $v): self
    {
        $this->securityIssues = $v;
        return $this;
    }

    public function getSecurityRating(): ?string
    {
        return $this->securityRating;
    }

    public function setSecurityRating(?string $v): self
    {
        $this->securityRating = $v;
        return $this;
    }

    public function getSecurityRemediationEffort(): ?int
    {
        return $this->securityRemediationEffort;
    }

    public function setSecurityRemediationEffort(?int $v): self
    {
        $this->securityRemediationEffort = $v;
        return $this;
    }

    public function getSoftwareQualitySecurityIssues(): ?string
    {
        return $this->softwareQualitySecurityIssues;
    }

    public function setSoftwareQualitySecurityIssues(?string $v): self
    {
        $this->softwareQualitySecurityIssues = $v;
        return $this;
    }

    public function getSoftwareQualitySecurityRating(): ?string
    {
        return $this->softwareQualitySecurityRating;
    }

    public function setSoftwareQualitySecurityRating(?string $v): self
    {
        $this->softwareQualitySecurityRating = $v;
        return $this;
    }

    public function getSoftwareQualitySecurityRemediationEffort(): ?int
    {
        return $this->softwareQualitySecurityRemediationEffort;
    }

    public function setSoftwareQualitySecurityRemediationEffort(?int $v): self
    {
        $this->softwareQualitySecurityRemediationEffort = $v;
        return $this;
    }

    // -------------------------
    // Menaces potentielles de sécurité
    // -------------------------

    public function getSecurityHotspot(): ?int
    {
        return $this->securityHotspot;
    }

    public function setSecurityHotspot(?int $securityHotspot): self
    {
        $this->securityHotspot = $securityHotspot;
        return $this;
    }

    public function getSecurityReviewRating(): ?string
    {
        return $this->securityReviewRating;
    }

    public function setSecurityReviewRating(?string $securityReviewRating): self
    {
        $this->securityReviewRating = $securityReviewRating;
        return $this;
    }

    public function getSecurityHotspotsReviewed(): ?float
    {
        return $this->securityHotspotsReviewed;
    }

    public function setSecurityHotspotsReviewed(?float $securityHotspotsReviewed): self
    {
        $this->securityHotspotsReviewed = $securityHotspotsReviewed;
        return $this;
    }

    public function getMenacePotentielleToReviewHigh(): ?int
    {
        return $this->menacePotentielleToReviewHigh;
    }

    public function setMenacePotentielleToReviewHigh(?int $menacePotentielleToReviewHigh): self
    {
        $this->menacePotentielleToReviewHigh = $menacePotentielleToReviewHigh;
        return $this;
    }

    public function getMenacePotentielleToReviewMedium(): ?int
    {
        return $this->menacePotentielleToReviewMedium;
    }

    public function setMenacePotentielleToReviewMedium(?int $menacePotentielleToReviewMedium): self
    {
        $this->menacePotentielleToReviewMedium = $menacePotentielleToReviewMedium;
        return $this;
    }

    public function getMenacePotentielleToReviewLow(): ?int
    {
        return $this->menacePotentielleToReviewLow;
    }

    public function setMenacePotentielleToReviewLow(?int $menacePotentielleToReviewLow): self
    {
        $this->menacePotentielleToReviewLow = $menacePotentielleToReviewLow;
        return $this;
    }

    public function getMenacePotentielleReviewedHigh(): ?int
    {
        return $this->menacePotentielleReviewedHigh;
    }

    public function setMenacePotentielleReviewedHigh(?int $menacePotentielleReviewedHigh): self
    {
        $this->menacePotentielleReviewedHigh = $menacePotentielleReviewedHigh;
        return $this;
    }

    public function getMenacePotentielleReviewedMedium(): ?int
    {
        return $this->menacePotentielleReviewedMedium;
    }

    public function setMenacePotentielleReviewedMedium(?int $menacePotentielleReviewedMedium): self
    {
        $this->menacePotentielleReviewedMedium = $menacePotentielleReviewedMedium;
        return $this;
    }

    public function getMenacePotentielleReviewedLow(): ?int
    {
        return $this->menacePotentielleReviewedLow;
    }

    public function setMenacePotentielleReviewedLow(?int $menacePotentielleReviewedLow): self
    {
        $this->menacePotentielleReviewedLow = $menacePotentielleReviewedLow;
        return $this;
    }

    public function getMenacePotentielleTotale(): ?int
    {
        return $this->menacePotentielleTotale;
    }

    public function setMenacePotentielleTotale(?int $menacePotentielleTotale): self
    {
        $this->menacePotentielleTotale = $menacePotentielleTotale;
        return $this;
    }

    // -------------------------
    // Actuator info
    // -------------------------
    public function getActuatorInfo(): ?array
    {
        return $this->actuatorInfo;
    }

    public function setActuatorInfo(?array $actuatorInfo): self
    {
        $this->actuatorInfo = $actuatorInfo;
        return $this;
    }

    // -------------------------
    // Logger Java
    // -------------------------
    public function getLoggerInfo(): ?int
    {
        return $this->loggerInfo;
    }

    public function setLoggerInfo(?int $loggerInfo): self
    {
        $this->loggerInfo = $loggerInfo;
        return $this;
    }

    public function getLoggerWarn(): ?int
    {
        return $this->loggerWarn;
    }

    public function setLoggerWarn(?int $loggerWarn): self
    {
        $this->loggerWarn = $loggerWarn;
        return $this;
    }

    public function getLoggerError(): ?int
    {
        return $this->loggerError;
    }

    public function setLoggerError(?int $loggerError): self
    {
        $this->loggerError = $loggerError;
        return $this;
    }

    public function getLoggerDebug(): ?int
    {
        return $this->loggerDebug;
    }

    public function setLoggerDebug(?int $loggerDebug): self
    {
        $this->loggerDebug = $loggerDebug;
        return $this;
    }

    public function isInitial(): bool
    {
        return $this->initial;
    }

    public function setInitial(bool $initial): self
    {
        $this->initial = $initial;
        return $this;
    }

    // -------------------------
    // Mode de collecte et utilisateur
    // -------------------------

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }

    public function setModeCollecte(?string $modeCollecte): self
    {
        $this->modeCollecte = $modeCollecte;
        return $this;
    }

    public function getUtilisateurCollecte(): ?string
    {
        return $this->utilisateurCollecte;
    }

    public function setUtilisateurCollecte(?string $utilisateurCollecte): self
    {
        $this->utilisateurCollecte = $utilisateurCollecte;
        return $this;
    }

    public function getDateEnregistrement(): \DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;
        return $this;
    }
}
