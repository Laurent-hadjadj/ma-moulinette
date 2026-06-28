<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\DcFindingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description DcDependency]
 * MODIF 2026-05-08 : jointure ternaire scan x dependency x cve.
 * Snapshot severity/cvss au moment du scan.
 */
#[ORM\Entity(repositoryClass: DcFindingRepository::class)]
#[ORM\Table(name: 'dc_finding', schema: 'ma_moulinette')]
class DcFinding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\ManyToOne(targetEntity: DcScan::class)]
    #[ORM\JoinColumn(
        name: 'scan_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private DcScan $scan;

    #[ORM\ManyToOne(targetEntity: DcDependency::class)]
    #[ORM\JoinColumn(
        name: 'dependency_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private DcDependency $dependency;

    #[ORM\ManyToOne(targetEntity: DcCve::class)]
    #[ORM\JoinColumn(
        name: 'cve_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private DcCve $cve;

    #[ORM\Column(
        name: 'severity_at_scan',
        type: Types::STRING,
        length: 16,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $severityAtScan;

    #[ORM\Column(
        name: 'cvss_at_scan',
        type: Types::DECIMAL,
        precision: 3,
        scale: 1,
        nullable: true
    )]
    private ?string $cvssAtScan = null;

    /* MODIF 2026-05-10 : 1re version safe pour ce
     * couple (CVE, dependency), extraite du JSON DC. NULL si pas de correctif
     * connu (CVE encore ouvert, projet abandonne) -> minore Upgrade->Mitigation
     * dans DcExecutiveAnalyticsService::computeCveDecisions. */
    #[ORM\Column(
        name: 'fixed_version',
        type: Types::STRING,
        length: 64,
        nullable: true
    )]
    private ?string $fixedVersion = null;

    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false
    )]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getScan(): DcScan
    {
        return $this->scan;
    }
    public function setScan(DcScan $s): self
    {
        $this->scan = $s;
        return $this;
    }

    public function getDependency(): DcDependency
    {
        return $this->dependency;
    }
    public function setDependency(DcDependency $d): self
    {
        $this->dependency = $d;
        return $this;
    }

    public function getCve(): DcCve
    {
        return $this->cve;
    }
    public function setCve(DcCve $c): self
    {
        $this->cve = $c;
        return $this;
    }

    public function getSeverityAtScan(): string
    {
        return $this->severityAtScan;
    }
    public function setSeverityAtScan(string $s): self
    {
        $this->severityAtScan = $s;
        return $this;
    }

    public function getCvssAtScan(): ?string
    {
        return $this->cvssAtScan;
    }
    public function setCvssAtScan(?string $v): self
    {
        $this->cvssAtScan = $v;
        return $this;
    }

    public function getFixedVersion(): ?string
    {
        return $this->fixedVersion;
    }
    public function setFixedVersion(?string $v): self
    {
        $this->fixedVersion = $v;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $d): self
    {
        $this->createdAt = $d;
        return $this;
    }
}
