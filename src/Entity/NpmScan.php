<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2015-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed under the GNU AGPL 3.0 or later (AGPL-3.0-or-later).
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  https://www.gnu.org/licenses/agpl-3.0.html
 */

namespace App\Entity;

use App\Repository\NpmScanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description NpmScan]
 * Un scan = un rapport `npm audit --json` pour (projet, version, date).
 * Compteurs pré-calculés, repris directement de metadata.vulnerabilities
 * et metadata.dependencies du rapport (pas de recalcul applicatif).
 */
#[ORM\Entity(repositoryClass: NpmScanRepository::class)]
#[ORM\Table(name: 'npm_scan', schema: 'ma_moulinette')]
class NpmScan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\ManyToOne(targetEntity: NpmProcessingQueue::class)]
    #[ORM\JoinColumn(
        name: 'queue_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?NpmProcessingQueue $queue = null;

    #[ORM\Column(
        name: 'project_group',
        type: Types::STRING,
        length: 255,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $projectGroup;

    #[ORM\Column(
        name: 'project_artifact',
        type: Types::STRING,
        length: 255,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $projectArtifact;

    #[ORM\Column(
        name: 'project_version',
        type: Types::STRING,
        length: 64,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $projectVersion;

    #[ORM\Column(
        name: 'scan_date',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false
    )]
    private \DateTimeImmutable $scanDate;

    #[ORM\Column(
        name: 'audit_report_version',
        type: Types::STRING,
        length: 8,
        nullable: true
    )]
    private ?string $auditReportVersion = null;

    /* MODIF 2026-11-20 : compteurs de vulnérabilités, repris de
     * metadata.vulnerabilities du rapport npm audit. npm utilise "moderate",
     * pas "medium" comme DependencyCheck — terminologie npm native conservée
     * plutôt que forcée dans le vocabulaire DC. */
    #[ORM\Column(name: 'vuln_count_critical', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $vulnCountCritical = 0;

    #[ORM\Column(name: 'vuln_count_high', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $vulnCountHigh = 0;

    #[ORM\Column(name: 'vuln_count_moderate', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $vulnCountModerate = 0;

    #[ORM\Column(name: 'vuln_count_low', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $vulnCountLow = 0;

    #[ORM\Column(name: 'vuln_count_info', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $vulnCountInfo = 0;

    #[ORM\Column(name: 'vuln_count_total', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $vulnCountTotal = 0;

    /* MODIF 2026-11-20 : ventilation des dépendances, reprise de
     * metadata.dependencies — sans équivalent DC (pas de notion
     * prod/dev/optional/peer pour un .jar Maven). */
    #[ORM\Column(name: 'dep_count_prod', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $depCountProd = 0;

    #[ORM\Column(name: 'dep_count_dev', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $depCountDev = 0;

    #[ORM\Column(name: 'dep_count_optional', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $depCountOptional = 0;

    #[ORM\Column(name: 'dep_count_peer', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $depCountPeer = 0;

    #[ORM\Column(name: 'dep_count_peer_optional', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $depCountPeerOptional = 0;

    #[ORM\Column(name: 'dep_count_total', type: Types::INTEGER, nullable: false)]
    #[Assert\PositiveOrZero]
    private int $depCountTotal = 0;

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

    public function getQueue(): ?NpmProcessingQueue
    {
        return $this->queue;
    }
    public function setQueue(?NpmProcessingQueue $q): self
    {
        $this->queue = $q;
        return $this;
    }

    public function getProjectGroup(): string
    {
        return $this->projectGroup;
    }
    public function setProjectGroup(string $g): self
    {
        $this->projectGroup = $g;
        return $this;
    }

    public function getProjectArtifact(): string
    {
        return $this->projectArtifact;
    }
    public function setProjectArtifact(string $a): self
    {
        $this->projectArtifact = $a;
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

    public function getScanDate(): \DateTimeImmutable
    {
        return $this->scanDate;
    }
    public function setScanDate(\DateTimeImmutable $d): self
    {
        $this->scanDate = $d;
        return $this;
    }

    public function getAuditReportVersion(): ?string
    {
        return $this->auditReportVersion;
    }
    public function setAuditReportVersion(?string $v): self
    {
        $this->auditReportVersion = $v;
        return $this;
    }

    public function getVulnCountCritical(): int
    {
        return $this->vulnCountCritical;
    }
    public function setVulnCountCritical(int $n): self
    {
        $this->vulnCountCritical = $n;
        return $this;
    }

    public function getVulnCountHigh(): int
    {
        return $this->vulnCountHigh;
    }
    public function setVulnCountHigh(int $n): self
    {
        $this->vulnCountHigh = $n;
        return $this;
    }

    public function getVulnCountModerate(): int
    {
        return $this->vulnCountModerate;
    }
    public function setVulnCountModerate(int $n): self
    {
        $this->vulnCountModerate = $n;
        return $this;
    }

    public function getVulnCountLow(): int
    {
        return $this->vulnCountLow;
    }
    public function setVulnCountLow(int $n): self
    {
        $this->vulnCountLow = $n;
        return $this;
    }

    public function getVulnCountInfo(): int
    {
        return $this->vulnCountInfo;
    }
    public function setVulnCountInfo(int $n): self
    {
        $this->vulnCountInfo = $n;
        return $this;
    }

    public function getVulnCountTotal(): int
    {
        return $this->vulnCountTotal;
    }
    public function setVulnCountTotal(int $n): self
    {
        $this->vulnCountTotal = $n;
        return $this;
    }

    public function getDepCountProd(): int
    {
        return $this->depCountProd;
    }
    public function setDepCountProd(int $n): self
    {
        $this->depCountProd = $n;
        return $this;
    }

    public function getDepCountDev(): int
    {
        return $this->depCountDev;
    }
    public function setDepCountDev(int $n): self
    {
        $this->depCountDev = $n;
        return $this;
    }

    public function getDepCountOptional(): int
    {
        return $this->depCountOptional;
    }
    public function setDepCountOptional(int $n): self
    {
        $this->depCountOptional = $n;
        return $this;
    }

    public function getDepCountPeer(): int
    {
        return $this->depCountPeer;
    }
    public function setDepCountPeer(int $n): self
    {
        $this->depCountPeer = $n;
        return $this;
    }

    public function getDepCountPeerOptional(): int
    {
        return $this->depCountPeerOptional;
    }
    public function setDepCountPeerOptional(int $n): self
    {
        $this->depCountPeerOptional = $n;
        return $this;
    }

    public function getDepCountTotal(): int
    {
        return $this->depCountTotal;
    }
    public function setDepCountTotal(int $n): self
    {
        $this->depCountTotal = $n;
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
