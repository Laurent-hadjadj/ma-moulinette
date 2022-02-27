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

    #[ORM\Column(type: 'string', length: 255)]
    private $maven_key;

    #[ORM\Column(type: 'integer')]
    private $setup;

    #[ORM\Column(type: 'string', length: 255)]
    private $project_name;

    #[ORM\Column(type: 'string', length: 255)]
    private $project_analyse;

    #[ORM\Column(type: 'string', length: 255)]
    private $project_version;

    #[ORM\Column(type: 'string', length: 255)]
    private $project_date;

    #[ORM\Column(type: 'string', length: 255)]
    private $total_debt;

    #[ORM\Column(type: 'string', length: 255)]
    private $total_debt_bug;

    #[ORM\Column(type: 'string', length: 255)]
    private $total_debt_vulnerability;

    #[ORM\Column(type: 'string', length: 255)]
    private $total_debt_code_smell;

    #[ORM\Column(type: 'integer')]
    private $bug_blocker;

    #[ORM\Column(type: 'integer')]
    private $bug_critical;

    #[ORM\Column(type: 'integer')]
    private $bug_info;

    #[ORM\Column(type: 'integer')]
    private $bug_major;

    #[ORM\Column(type: 'integer')]
    private $bug_minor;

    #[ORM\Column(type: 'integer')]
    private $vulnerability_blocker;

    #[ORM\Column(type: 'integer')]
    private $vulnerability_critical;

    #[ORM\Column(type: 'integer')]
    private $vulnerability_info;

    #[ORM\Column(type: 'integer')]
    private $vulnerability_major;

    #[ORM\Column(type: 'integer')]
    private $vulnerability_minor;

    #[ORM\Column(type: 'integer')]
    private $code_smell_blocker;

    #[ORM\Column(type: 'integer')]
    private $code_smell_critical;

    #[ORM\Column(type: 'integer')]
    private $code_smell_info;

    #[ORM\Column(type: 'integer')]
    private $code_smell_major;

    #[ORM\Column(type: 'integer')]
    private $code_smell_minor;

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

    public function getSetup(): ?int
    {
        return $this->setup;
    }

    public function setSetup(int $setup): self
    {
        $this->setup = $setup;

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

    public function getProjectAnalyse(): ?string
    {
        return $this->project_analyse;
    }

    public function setProjectAnalyse(string $project_analyse): self
    {
        $this->project_analyse = $project_analyse;

        return $this;
    }

    public function getProjectVersion(): ?string
    {
        return $this->project_version;
    }

    public function setProjectVersion(string $project_version): self
    {
        $this->project_version = $project_version;

        return $this;
    }

    public function getProjectDate(): ?string
    {
        return $this->project_date;
    }

    public function setProjectDate(string $project_date): self
    {
        $this->project_date = $project_date;

        return $this;
    }

    public function getTotalDebt(): ?string
    {
        return $this->total_debt;
    }

    public function setTotalDebt(string $total_debt): self
    {
        $this->total_debt = $total_debt;

        return $this;
    }

    public function getTotalDebtBug(): ?string
    {
        return $this->total_debt_bug;
    }

    public function setTotalDebtBug(string $total_debt_bug): self
    {
        $this->total_debt_bug = $total_debt_bug;

        return $this;
    }

    public function getTotalDebtVulnerability(): ?string
    {
        return $this->total_debt_vulnerability;
    }

    public function setTotalDebtVulnerability(string $total_debt_vulnerability): self
    {
        $this->total_debt_vulnerability = $total_debt_vulnerability;

        return $this;
    }

    public function getTotalDebtCodeSmell(): ?string
    {
        return $this->total_debt_code_smell;
    }

    public function setTotalDebtCodeSmell(string $total_debt_code_smell): self
    {
        $this->total_debt_code_smell = $total_debt_code_smell;

        return $this;
    }

    public function getBugBlocker(): ?int
    {
        return $this->bug_blocker;
    }

    public function setBugBlocker(int $bug_blocker): self
    {
        $this->bug_blocker = $bug_blocker;

        return $this;
    }

    public function getBugCritical(): ?int
    {
        return $this->bug_critical;
    }

    public function setBugCritical(int $bug_critical): self
    {
        $this->bug_critical = $bug_critical;

        return $this;
    }

    public function getBugInfo(): ?int
    {
        return $this->bug_info;
    }

    public function setBugInfo(int $bug_info): self
    {
        $this->bug_info = $bug_info;

        return $this;
    }

    public function getBugMajor(): ?int
    {
        return $this->bug_major;
    }

    public function setBugMajor(int $bug_major): self
    {
        $this->bug_major = $bug_major;

        return $this;
    }

    public function getBugMinor(): ?int
    {
        return $this->bug_minor;
    }

    public function setBugMinor(int $bug_minor): self
    {
        $this->bug_minor = $bug_minor;

        return $this;
    }

    public function getVulnerabilityBlocker(): ?int
    {
        return $this->vulnerability_blocker;
    }

    public function setVulnerabilityBlocker(int $vulnerability_blocker): self
    {
        $this->vulnerability_blocker = $vulnerability_blocker;

        return $this;
    }

    public function getVulnerabilityCritical(): ?int
    {
        return $this->vulnerability_critical;
    }

    public function setVulnerabilityCritical(int $vulnerability_critical): self
    {
        $this->vulnerability_critical = $vulnerability_critical;

        return $this;
    }

    public function getVulnerabilityInfo(): ?int
    {
        return $this->vulnerability_info;
    }

    public function setVulnerabilityInfo(int $vulnerability_info): self
    {
        $this->vulnerability_info = $vulnerability_info;

        return $this;
    }

    public function getVulnerabilityMajor(): ?int
    {
        return $this->vulnerability_major;
    }

    public function setVulnerabilityMajor(int $vulnerability_major): self
    {
        $this->vulnerability_major = $vulnerability_major;

        return $this;
    }

    public function getVulnerabilityMinor(): ?int
    {
        return $this->vulnerability_minor;
    }

    public function setVulnerabilityMinor(int $vulnerability_minor): self
    {
        $this->vulnerability_minor = $vulnerability_minor;

        return $this;
    }

    public function getCodeSmellBlocker(): ?int
    {
        return $this->code_smell_blocker;
    }

    public function setCodeSmellBlocker(int $code_smell_blocker): self
    {
        $this->code_smell_blocker = $code_smell_blocker;

        return $this;
    }

    public function getCodeSmellCritical(): ?int
    {
        return $this->code_smell_critical;
    }

    public function setCodeSmellCritical(int $code_smell_critical): self
    {
        $this->code_smell_critical = $code_smell_critical;

        return $this;
    }

    public function getCodeSmellInfo(): ?int
    {
        return $this->code_smell_info;
    }

    public function setCodeSmellInfo(int $code_smell_info): self
    {
        $this->code_smell_info = $code_smell_info;

        return $this;
    }

    public function getCodeSmellMajor(): ?int
    {
        return $this->code_smell_major;
    }

    public function setCodeSmellMajor(int $code_smell_major): self
    {
        $this->code_smell_major = $code_smell_major;

        return $this;
    }

    public function getCodeSmellMinor(): ?int
    {
        return $this->code_smell_minor;
    }

    public function setCodeSmellMinor(int $code_smell_minor): self
    {
        $this->code_smell_minor = $code_smell_minor;

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
