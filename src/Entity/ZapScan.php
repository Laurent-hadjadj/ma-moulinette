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

use App\Repository\ZapScanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description ZapScan]
 * MODIF 2026-10-13 : un scan = un rapport ZAP (DAST) pour
 * (maven_key, project_version, target_url, generated_at). Compteurs
 * pre-calcules par niveau de risque ZAP (Informational/Low/Medium/High).
 */
#[ORM\Entity(repositoryClass: ZapScanRepository::class)]
#[ORM\Table(name: 'zap_scan', schema: 'ma_moulinette')]
class ZapScan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\ManyToOne(targetEntity: ZapProcessingQueue::class)]
    #[ORM\JoinColumn(
        name: 'queue_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?ZapProcessingQueue $queue = null;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 511,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $mavenKey;

    #[ORM\Column(
        name: 'project_version',
        type: Types::STRING,
        length: 64,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $projectVersion;

    #[ORM\Column(
        name: 'target_url',
        type: Types::STRING,
        length: 2048,
        nullable: true
    )]
    private ?string $targetUrl = null;

    #[ORM\Column(
        name: 'zap_version',
        type: Types::STRING,
        length: 32,
        nullable: true
    )]
    private ?string $zapVersion = null;

    #[ORM\Column(
        name: 'generated_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false
    )]
    private \DateTimeImmutable $generatedAt;

    #[ORM\Column(
        name: 'alert_count_total',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $alertCountTotal = 0;

    #[ORM\Column(
        name: 'risk_count_high',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $riskCountHigh = 0;

    #[ORM\Column(
        name: 'risk_count_medium',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $riskCountMedium = 0;

    #[ORM\Column(
        name: 'risk_count_low',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $riskCountLow = 0;

    #[ORM\Column(
        name: 'risk_count_informational',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $riskCountInformational = 0;

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

    public function getQueue(): ?ZapProcessingQueue
    {
        return $this->queue;
    }
    public function setQueue(?ZapProcessingQueue $q): self
    {
        $this->queue = $q;
        return $this;
    }

    public function getMavenKey(): string
    {
        return $this->mavenKey;
    }
    public function setMavenKey(string $mk): self
    {
        $this->mavenKey = $mk;
        return $this;
    }

    public function getProjectVersion(): string
    {
        return $this->projectVersion;
    }
    public function setProjectVersion(string $v): self
    {
        $this->projectVersion = $v;
        return $this;
    }

    public function getTargetUrl(): ?string
    {
        return $this->targetUrl;
    }
    public function setTargetUrl(?string $v): self
    {
        $this->targetUrl = $v;
        return $this;
    }

    public function getZapVersion(): ?string
    {
        return $this->zapVersion;
    }
    public function setZapVersion(?string $v): self
    {
        $this->zapVersion = $v;
        return $this;
    }

    public function getGeneratedAt(): \DateTimeImmutable
    {
        return $this->generatedAt;
    }
    public function setGeneratedAt(\DateTimeImmutable $d): self
    {
        $this->generatedAt = $d;
        return $this;
    }

    public function getAlertCountTotal(): int
    {
        return $this->alertCountTotal;
    }
    public function setAlertCountTotal(int $n): self
    {
        $this->alertCountTotal = $n;
        return $this;
    }

    public function getRiskCountHigh(): int
    {
        return $this->riskCountHigh;
    }
    public function setRiskCountHigh(int $n): self
    {
        $this->riskCountHigh = $n;
        return $this;
    }

    public function getRiskCountMedium(): int
    {
        return $this->riskCountMedium;
    }
    public function setRiskCountMedium(int $n): self
    {
        $this->riskCountMedium = $n;
        return $this;
    }

    public function getRiskCountLow(): int
    {
        return $this->riskCountLow;
    }
    public function setRiskCountLow(int $n): self
    {
        $this->riskCountLow = $n;
        return $this;
    }

    public function getRiskCountInformational(): int
    {
        return $this->riskCountInformational;
    }
    public function setRiskCountInformational(int $n): self
    {
        $this->riskCountInformational = $n;
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
