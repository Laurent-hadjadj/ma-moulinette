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

use App\Repository\MesuresRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MesuresRepository::class)]
#[ORM\Table(
    name: 'mesures',
    schema: "ma_moulinette",
    options: ['comment' => 'Tables des indicateurs de type mesures'])]
class Mesures
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique pour chaque mesure'])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private string $mavenKey;

    #[ORM\Column(
        name: 'project_name',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du projet'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "Le nom du projet ne doit pas dépasser 128 caractères.")]
    private string $projectName;

    #[ORM\Column(
        name: 'lines',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de lignes du projet'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $lines;

    #[ORM\Column(
        name: 'ncloc',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Lignes de code non commentées'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $ncloc;

    #[ORM\Column(
        name: 'language_distribution',
        type: Types::JSON,
        nullable: false,
        options: ['comment' => 'Distribution des langages de programmation'])]
    #[Assert\NotNull]
    private array $languageDistribution;

    #[ORM\Column(
        name: 'files',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de fichiers'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $files;

    #[ORM\Column(
        name: 'classes',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de classes'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $classes;

    #[ORM\Column(
        name: 'functions',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de méthodes'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $functions;

    #[ORM\Column(
        name: 'coverage',
        type: Types::FLOAT,
        nullable: false,
        options: ['comment' => 'Pourcentage de couverture par les tests'])]
    #[Assert\NotNull]
    private float $coverage;

    #[ORM\Column(
        name: 'sqale_debt_ratio',
        type: Types::FLOAT,
        nullable: false,
        options: ['comment' => 'Ratio de dette technique (SQALE)'])]
    #[Assert\NotNull]
    private float $sqaleDebtRatio;

    #[ORM\Column(
        name: 'duplicated_lines_density',
        type: Types::FLOAT,
        nullable: false,
        options: ['comment' => 'Densité de duplication du code'])]
    #[Assert\NotNull]
    private float $duplicatedLinesDensity;

    #[ORM\Column(
        name: 'tests',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de tests'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $tests;

    #[ORM\Column(
        name: 'issues',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de problèmes identifiés'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $issues;

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32, nullable: true,
        options: ['comment' => 'Mode de collecte : COLLECTE | TRAITEMENT MANUEL | TRAITEMENT AUTOMATIQUE'])]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères.")]
    private ?string $modeCollecte = null;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => "Compte de l'utilisateur qui a réalisé la collecte."])]
    #[Assert\Length(
        max: 320,
        maxMessage: "Le compte de l’utilisateur ne peut pas dépasser 320 caractères.")]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement de la mesure'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
        $this->dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    // Getters et setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;
        return $this;
    }

    public function getProjectName(): string
    {
        return $this->projectName;
    }

    public function setProjectName(string $projectName): self
    {
        $this->projectName = $projectName;
        return $this;
    }

    public function getLines(): int
    {
        return $this->lines;
    }

    public function setLines(int $lines): self
    {
        $this->lines = $lines;
        return $this;
    }

    public function getNcloc(): int
    {
        return $this->ncloc;
    }

    public function setNcloc(int $ncloc): self
    {
        $this->ncloc = $ncloc;
        return $this;
    }

    public function getLanguageDistribution(): array
    {
        return $this->languageDistribution;
    }

    public function setLanguageDistribution(array $languageDistribution): self
    {
        $this->languageDistribution = $languageDistribution;
        return $this;
    }

    public function getFiles(): int
    {
        return $this->files;
    }

    public function setFiles(int $files): self
    {
        $this->files = $files;
        return $this;
    }

    public function getClasses(): int
    {
        return $this->classes;
    }

    public function setClasses(int $classes): self
    {
        $this->classes = $classes;
        return $this;
    }

    public function getFunctions(): int
    {
        return $this->functions;
    }

    public function setFunctions(int $functions): self
    {
        $this->functions = $functions;
        return $this;
    }

    public function getCoverage(): float
    {
        return $this->coverage;
    }

    public function setCoverage(float $coverage): self
    {
        $this->coverage = $coverage;
        return $this;
    }

    public function getSqaleDebtRatio(): float
    {
        return $this->sqaleDebtRatio;
    }

    public function setSqaleDebtRatio(float $sqaleDebtRatio): self
    {
        $this->sqaleDebtRatio = $sqaleDebtRatio;
        return $this;
    }

    public function getDuplicatedLinesDensity(): float
    {
        return $this->duplicatedLinesDensity;
    }

    public function setDuplicatedLinesDensity(float $duplicatedLinesDensity): self
    {
        $this->duplicatedLinesDensity = $duplicatedLinesDensity;
        return $this;
    }

    public function getTests(): int
    {
        return $this->tests;
    }

    public function setTests(int $tests): self
    {
        $this->tests = $tests;
        return $this;
    }

    public function getIssues(): int
    {
        return $this->issues;
    }

    public function setIssues(int $issues): self
    {
        $this->issues = $issues;
        return $this;
    }

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
