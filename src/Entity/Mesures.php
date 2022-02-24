<?php
/*
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 *
 */

namespace App\Entity;

use App\Repository\MesuresRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: MesuresRepository::class)]
class Mesures
{

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 255)]
    private $maven_key;

    #[ORM\Column(type: 'string', length: 255)]
    private $project_name;

    #[ORM\Column(type: 'integer')]
    private $lines;

    #[ORM\Column(type: 'float')]
    private $coverage;

    #[ORM\Column(type: 'float')]
    private $duplication_density;

    #[ORM\Column(type: 'integer')]
    private $tests;

    #[ORM\Column(type: 'integer')]
    private $issues;

    #[ORM\Column(type: 'datetime')]
    private $date_enregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): ?string
    {
        return $this->maven_key;
    }

    public function setMavenKey(string $maven_key): self
    {
        $this->maven_key = $maven_key;

        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->project_name;
    }

    public function setProjectName(string $project_name): self
    {
        $this->project_name = $project_name;

        return $this;
    }

    public function getLines(): ?int
    {
        return $this->lines;
    }

    public function setLines(int $lines): self
    {
        $this->lines = $lines;

        return $this;
    }

    public function getCoverage(): ?float
    {
        return $this->coverage;
    }

    public function setCoverage(float $coverage): self
    {
        $this->coverage = $coverage;

        return $this;
    }

    public function getDuplicationDensity(): ?float
    {
        return $this->duplication_density;
    }

    public function setDuplicationDensity(float $duplication_density): self
    {
        $this->duplication_density = $duplication_density;

        return $this;
    }

    public function getTests(): ?int
    {
        return $this->tests;
    }

    public function setTests(int $tests): self
    {
        $this->tests = $tests;

        return $this;
    }

    public function getIssues(): ?int
    {
        return $this->issues;
    }

    public function setIssues(int $issues): self
    {
        $this->issues = $issues;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeInterface
    {
        return $this->date_enregistrement;
    }

    public function setDateEnregistrement(\DateTimeInterface $date_enregistrement): self
    {
        $this->date_enregistrement = $date_enregistrement;

        return $this;
    }


}

