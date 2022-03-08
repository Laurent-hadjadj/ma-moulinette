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

use App\Repository\AnomalieRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: AnomalieRepository::class)]
class Anomalie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private $id;

    #[ORM\Column(type: 'string', length: 128)]
    private $maven_key;

    #[ORM\Column(type: 'string', length: 128)]
    private $project_name;

    #[ORM\Column(type: 'integer')]
    private $anomalie_total;

    #[ORM\Column(type: 'integer')]
    private $dette_minute;

    #[ORM\Column(type: 'integer')]
    private $dette_reliability_minute;

    #[ORM\Column(type: 'integer')]
    private $dette_vulnerability_minute;

    #[ORM\Column(type: 'integer')]
    private $dette_code_smell_minute;

    #[ORM\Column(type: 'string', length:32)]
    private $dette_reliability;

    #[ORM\Column(type: 'string', length:32)]
    private $dette_vulnerability;

    #[ORM\Column(type: 'string', length:32)]
    private $dette;

    #[ORM\Column(type: 'string', length:32)]
    private $dette_code_smell;

    #[ORM\Column(type: 'integer')]
    private $frontend;

    #[ORM\Column(type: 'integer')]
    private $backend;

    #[ORM\Column(type: 'integer')]
    private $batch;

    #[ORM\Column(type: 'integer')]
    private $blocker;

    #[ORM\Column(type: 'integer')]
    private $critical;

    #[ORM\Column(type: 'integer')]
    private $major;

    #[ORM\Column(type: 'integer')]
    private $info;

    #[ORM\Column(type: 'integer')]
    private $minor;

    #[ORM\Column(type: 'integer')]
    private $bug;

    #[ORM\Column(type: 'integer')]
    private $vulnerability;

    #[ORM\Column(type: 'integer')]
    private $code_smell;

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

    public function getAnomalieTotal(): ?int
    {
        return $this->anomalie_total;
    }

    public function setAnomalieTotal(int $anomalie_total): self
    {
        $this->anomalie_total = $anomalie_total;

        return $this;
    }

    public function getDetteMinute(): ?int
    {
        return $this->dette_minute;
    }

    public function setDetteMinute(int $dette_minute): self
    {
        $this->dette_minute = $dette_minute;

        return $this;
    }

    public function getDetteReliabilityMinute(): ?int
    {
        return $this->dette_reliability_minute;
    }

    public function setDetteReliabilityMinute(int $dette_reliability_minute): self
    {
        $this->dette_reliability_minute = $dette_reliability_minute;

        return $this;
    }

    public function getDetteVulnerabilityMinute(): ?int
    {
        return $this->dette_vulnerability_minute;
    }

    public function setDetteVulnerabilityMinute(int $dette_vulnerability_minute): self
    {
        $this->dette_vulnerability_minute = $dette_vulnerability_minute;

        return $this;
    }

    public function getDetteCodeSmellMinute(): ?int
    {
        return $this->dette_code_smell_minute;
    }

    public function setDetteCodeSmellMinute(int $dette_code_smell_minute): self
    {
        $this->dette_code_smell_minute = $dette_code_smell_minute;

        return $this;
    }

    public function getDetteReliability(): ?string
    {
        return $this->dette_reliability;
    }

    public function setDetteReliability(string $dette_reliability): self
    {
        $this->dette_reliability = $dette_reliability;

        return $this;
    }

    public function getDetteVulnerability(): ?string
    {
        return $this->dette_vulnerability;
    }

    public function setDetteVulnerability(string $dette_vulnerability): self
    {
        $this->dette_vulnerability = $dette_vulnerability;

        return $this;
    }

    public function getDette(): ?string
    {
        return $this->dette;
    }

    public function setDette(string $dette): self
    {
        $this->dette = $dette;

        return $this;
    }

    public function getDetteCodeSmell(): ?string
    {
        return $this->dette_code_smell;
    }

    public function setDetteCodeSmell(string $dette_code_smell): self
    {
        $this->dette_code_smell = $dette_code_smell;

        return $this;
    }

    public function getBlocker(): ?int
    {
        return $this->blocker;
    }

    public function setBlocker(int $blocker): self
    {
        $this->blocker = $blocker;

        return $this;
    }

    public function getCritical(): ?int
    {
        return $this->critical;
    }

    public function setCritical(int $critical): self
    {
        $this->critical = $critical;

        return $this;
    }

    public function getMajor(): ?int
    {
        return $this->major;
    }

    public function setMajor(int $major): self
    {
        $this->major = $major;

        return $this;
    }

    public function getInfo(): ?int
    {
        return $this->info;
    }

    public function setInfo(int $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function getMinor(): ?int
    {
        return $this->minor;
    }

    public function setMinor(int $minor): self
    {
        $this->minor = $minor;

        return $this;
    }

    public function getBug(): ?int
    {
        return $this->bug;
    }

    public function setBug(int $bug): self
    {
        $this->bug = $bug;

        return $this;
    }

    public function getVulnerability(): ?int
    {
        return $this->vulnerability;
    }

    public function setVulnerability(int $vulnerability): self
    {
        $this->vulnerability = $vulnerability;

        return $this;
    }

    public function getCodeSmell(): ?int
    {
        return $this->code_smell;
    }

    public function setCodeSmell(int $code_smell): self
    {
        $this->code_smell = $code_smell;

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

    public function getFrontend(): ?int
    {
        return $this->frontend;
    }

    public function setFrontend(int $frontend): self
    {
        $this->frontend = $frontend;

        return $this;
    }

    public function getBackend(): ?int
    {
        return $this->backend;
    }

    public function setBackend(int $backend): self
    {
        $this->backend = $backend;

        return $this;
    }

    public function getBatch(): ?int
    {
        return $this->batch;
    }

    public function setBatch(int $batch): self
    {
        $this->batch = $batch;

        return $this;
    }

    }
