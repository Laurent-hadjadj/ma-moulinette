<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
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
#[ORM\Table(name: "mesures", schema: "ma_moulinette")]
class Mesures
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque mesure']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères."
    )]
    private $mavenKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private $projectName;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de lignes du projet']
    )]
    #[Assert\NotNull]
    private $lines;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Lignes de code non commentées']
    )]
    #[Assert\NotNull]
    private $ncloc;

    #[ORM\Column(
        type: Types::FLOAT,
        nullable: false,
        options: ['comment' => 'Pourcentage de couverture par les tests']
    )]
    #[Assert\NotNull]
    private $coverage;

    #[ORM\Column(
        type: Types::FLOAT,
        nullable: false,
        options: ['comment' => 'Ratio de dette technique (SQALE)']
    )]
    #[Assert\NotNull]
    private $sqaleDebtRatio;

    #[ORM\Column(
        type: Types::FLOAT,
        nullable: false,
        options: ['comment' => 'Densité de duplication du code']
    )]
    #[Assert\NotNull]
    private $duplicationDensity;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de tests']
    )]
    #[Assert\NotNull]
    private $tests;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de problèmes identifiés']
    )]
    #[Assert\NotNull]
    private $issues;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement de la mesure']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): static
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(string $projectName): static
    {
        $this->projectName = $projectName;

        return $this;
    }

    public function getLines(): ?int
    {
        return $this->lines;
    }

    public function setLines(int $lines): static
    {
        $this->lines = $lines;

        return $this;
    }

    public function getNcloc(): ?int
    {
        return $this->ncloc;
    }

    public function setNcloc(int $ncloc): static
    {
        $this->ncloc = $ncloc;

        return $this;
    }

    public function getCoverage(): ?float
    {
        return $this->coverage;
    }

    public function setCoverage(float $coverage): static
    {
        $this->coverage = $coverage;

        return $this;
    }

    public function getSqaleDebtRatio(): ?float
    {
        return $this->sqaleDebtRatio;
    }

    public function setSqaleDebtRatio(float $sqaleDebtRatio): static
    {
        $this->sqaleDebtRatio = $sqaleDebtRatio;

        return $this;
    }

    public function getDuplicationDensity(): ?float
    {
        return $this->duplicationDensity;
    }

    public function setDuplicationDensity(float $duplicationDensity): static
    {
        $this->duplicationDensity = $duplicationDensity;

        return $this;
    }

    public function getTests(): ?int
    {
        return $this->tests;
    }

    public function setTests(int $tests): static
    {
        $this->tests = $tests;

        return $this;
    }

    public function getIssues(): ?int
    {
        return $this->issues;
    }

    public function setIssues(int $issues): static
    {
        $this->issues = $issues;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): static
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }

}
