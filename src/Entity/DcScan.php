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

use App\Repository\DcScanRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description DcDependency]
 * MODIF 2026-05-08 : un scan = un rapport DependencyCheck pour
 * (projet, version, date). Compteurs pre-calcules.
 */
#[ORM\Entity(repositoryClass: DcScanRepository::class)]
#[ORM\Table(name: 'dc_scan', schema: 'ma_moulinette')]
class DcScan
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\ManyToOne(targetEntity: DcProcessingQueue::class)]
    #[ORM\JoinColumn(
        name: 'queue_id',
        referencedColumnName: 'id',
        nullable: true,
        onDelete: 'SET NULL'
    )]
    private ?DcProcessingQueue $queue = null;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 511,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $mavenKey;

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
        name: 'engine_version',
        type: Types::STRING,
        length: 32,
        nullable: true
    )]
    private ?string $engineVersion = null;

    #[ORM\Column(
        name: 'report_schema',
        type: Types::STRING,
        length: 16,
        nullable: true
    )]
    private ?string $reportSchema = null;

    #[ORM\Column(
        name: 'dep_count_total',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $depCountTotal = 0;

    #[ORM\Column(
        name: 'dep_count_vulnerable',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $depCountVulnerable = 0;

    #[ORM\Column(
        name: 'cve_count_critical',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $cveCountCritical = 0;

    #[ORM\Column(
        name: 'cve_count_hight',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $cveCountHigh = 0;

    #[ORM\Column(
        name: 'cve_count_medium',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $cveCountMedium = 0;

    #[ORM\Column(
        name: 'cve_count_low',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $cveCountLow = 0;

    #[ORM\Column(
        name: 'cve_count_zero',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $cveCountInfo = 0;

    #[ORM\Column(
        name: 'cve_count_total',
        type: Types::INTEGER,
        nullable: false
    )]
    #[Assert\PositiveOrZero]
    private int $cveCountTotal = 0;

    /* MODIF 2026-05-12 : métadonnées pom.xml
     * transmises par la CI via headers HTTP a l'upload.
     *   parent_label   = "springboot-config" (archetype) ou "groupId:artifactId" (legacy) ou null
     *   parent_version = version du BOM/parent qui pilote les CVE
     *   archetype_version = version du template (springboot-archetype.version), null hors archetype */
    #[ORM\Column(
        name: 'parent_label',
        type: Types::STRING,
        length: 128,
        nullable: true
    )]
    #[Assert\Length(max: 128)]
    private ?string $parentLabel = null;

    #[ORM\Column(
        name: 'parent_version',
        type: Types::STRING,
        length: 64,
        nullable: true
    )]
    #[Assert\Length(max: 64)]
    private ?string $parentVersion = null;

    #[ORM\Column(
        name: 'archetype_version',
        type: Types::STRING,
        length: 64,
        nullable: true
    )]
    #[Assert\Length(max: 64)]
    private ?string $archetypeVersion = null;

    /* MODIF 2026-05-14 : flags maintenus par
     * App\Service\DcLatestVersionUpdater. Permettent de filtrer les vues
     * agrégées a 1 ligne par (project_group, project_artifact) :
     *   - is_latest_overall : dernière version semver (toute) -> vue dev
     *   - is_latest_release : dernière version semver release stable -> vue prod
     * Invariant : 1 ligne TRUE par couple pour overall, 0 ou 1 pour release.
     */
    #[ORM\Column(
        name: 'is_latest_overall',
        type: Types::BOOLEAN,
        nullable: false,
        options: ['default' => false]
    )]
    private bool $isLatestOverall = false;

    #[ORM\Column(
        name: 'is_latest_release',
        type: Types::BOOLEAN,
        nullable: false,
        options: ['default' => false]
    )]
    private bool $isLatestRelease = false;

    #[ORM\Column(
        name: 'create_at',
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

    public function getQueue(): ?DcProcessingQueue
    {
        return $this->queue;
    }
    public function setQueue(?DcProcessingQueue $q): self
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

    public function getEngineVersion(): ?string
    {
        return $this->engineVersion;
    }
    public function setEngineVersion(?string $v): self
    {
        $this->engineVersion = $v;
        return $this;
    }

    public function getReportSchema(): ?string
    {
        return $this->reportSchema;
    }
    public function setReportSchema(?string $s): self
    {
        $this->reportSchema = $s;
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

    public function getDepCountVulnerable(): int
    {
        return $this->depCountVulnerable;
    }
    public function setDepCountVulnerable(int $n): self
    {
        $this->depCountVulnerable = $n;
        return $this;
    }

    public function getCveCountCritical(): int
    {
        return $this->cveCountCritical;
    }
    public function setCveCountCritical(int $n): self
    {
        $this->cveCountCritical = $n;
        return $this;
    }

    public function getCveCountHigh(): int
    {
        return $this->cveCountHigh;
    }
    public function setCveCountHigh(int $n): self
    {
        $this->cveCountHigh = $n;
        return $this;
    }

    public function getCveCountMedium(): int
    {
        return $this->cveCountMedium;
    }
    public function setCveCountMedium(int $n): self
    {
        $this->cveCountMedium = $n;
        return $this;
    }

    public function getCveCountLow(): int
    {
        return $this->cveCountLow;
    }
    public function setCveCountLow(int $n): self
    {
        $this->cveCountLow = $n;
        return $this;
    }

    public function getCveCountInfo(): int
    {
        return $this->cveCountInfo;
    }
    public function setCveCountInfo(int $n): self
    {
        $this->cveCountInfo = $n;
        return $this;
    }

    public function getCveCountTotal(): int
    {
        return $this->cveCountTotal;
    }
    public function setCveCountTotal(int $n): self
    {
        $this->cveCountTotal = $n;
        return $this;
    }

    public function getParentLabel(): ?string
    {
        return $this->parentLabel;
    }
    public function setParentLabel(?string $v): self
    {
        $this->parentLabel = $v;
        return $this;
    }

    public function getParentVersion(): ?string
    {
        return $this->parentVersion;
    }
    public function setParentVersion(?string $v): self
    {
        $this->parentVersion = $v;
        return $this;
    }

    public function getArchetypeVersion(): ?string
    {
        return $this->archetypeVersion;
    }
    public function setArchetypeVersion(?string $v): self
    {
        $this->archetypeVersion = $v;
        return $this;
    }

    /* MODIF 2026-05-14 : getters/setters flags latest. */
    public function isLatestOverall(): bool
    {
        return $this->isLatestOverall;
    }
    public function setIsLatestOverall(bool $v): self
    {
        $this->isLatestOverall = $v;
        return $this;
    }

    public function isLatestRelease(): bool
    {
        return $this->isLatestRelease;
    }
    public function setIsLatestRelease(bool $v): self
    {
        $this->isLatestRelease = $v;
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
