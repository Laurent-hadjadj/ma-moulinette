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

use App\Repository\DcCveRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description DcCve]
 * MODIF 2026-05-08 : référentiel global des CVE.
 * Une row = une CVE unique (deduplication par cve_id).
 */
#[ORM\Entity(repositoryClass: DcCveRepository::class)]
#[ORM\Table(name: 'dc_cve', schema: 'ma_moulinette')]
class DcCve
{
    public const SEVERITY_CRITICAL = 'CRITICAL';
    public const SEVERITY_HIGH     = 'HIGH';
    public const SEVERITY_MEDIUM   = 'MEDIUM';
    public const SEVERITY_LOW      = 'LOW';
    public const SEVERITY_INFO     = 'INFO';
    public const SEVERITY_UNKNOWN  = 'UNKNOWN';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\Column(
        name: 'cve_id',
        type: Types::STRING,
        length: 64,
        unique: true,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $cveId;

    #[ORM\Column(
        name: 'source',
        type: Types::STRING,
        length: 32,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $source = 'NVD';

    #[ORM\Column(
        name: 'severity',
        type: Types::STRING,
        length: 16,
        nullable: false
    )]
    #[Assert\Choice(choices: [
        self::SEVERITY_CRITICAL,
        self::SEVERITY_HIGH,
        self::SEVERITY_MEDIUM,
        self::SEVERITY_LOW,
        self::SEVERITY_INFO,
        self::SEVERITY_UNKNOWN,
    ])]
    private string $severity = self::SEVERITY_UNKNOWN;

    #[ORM\Column(
        name: 'cvss_v3_score',
        type: Types::DECIMAL,
        precision: 3,
        scale: 1,
        nullable: true
    )]
    private ?string $cvssV3Score = null;

    #[ORM\Column(
        name: 'cvss_v3_severity',
        type: Types::STRING,
        length: 16,
        nullable: true
    )]
    private ?string $cvssV3Severity = null;

    #[ORM\Column(
        name: 'cvss_v3_attack_vector',
        type: Types::STRING,
        length: 32,
        nullable: true
    )]
    private ?string $cvssV3AttackVector = null;

    #[ORM\Column(
        name: 'cvss_v3_attack_complexity',
        type: Types::STRING,
        length: 32,
        nullable: true
    )]
    private ?string $cvssV3AttackComplexity = null;

    #[ORM\Column(
        name: 'cvss_v3_vector',
        type: Types::STRING,
        length: 255,
        nullable: true
    )]
    private ?string $cvssV3Vector = null;

    /* MODIF 2026-05-16 : columnDefinition explicite JSONB.
     * Types::JSON sans definition map vers JSON simple en PostgreSQL.
     * Les requêtes du repo utilisent jsonb_array_elements_text(c.cwes) qui
     * exige le type JSONB (cf countProjectsByCwe, listTopCriticalCves...).
     * Avec une colonne JSON, la fonction échoue ou retourne vide. Le DDL
     * d'origine (migrations/initialisation/20_tables/dc_cve.sql)
     * declare bien JSONB ; on aligne le mapping ORM. */
    /** @var array<int, string> */
    #[ORM\Column(
        name: 'cwes',
        type: Types::JSON,
        nullable: false,
        options: ['jsonb' => true]
    )]
    private array $cwes = [];

    #[ORM\Column(
        name: 'description',
        type: Types::TEXT,
        nullable: false
    )]
    private string $description = '';

    /** @var array<int, array<string, string>> */
    #[ORM\Column(
        name: 'cve_references',
        type: Types::JSON,
        nullable: false
    )]
    private array $cveReferences = [];

    #[ORM\Column(
        name: 'first_seen_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false
    )]
    private \DateTimeImmutable $firstSeenAt;

    #[ORM\Column(
        name: 'last_seen_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false
    )]
    private \DateTimeImmutable $lastSeenAt;

    #[ORM\Column(
        name: 'updated_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false
    )]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->firstSeenAt = $now;
        $this->lastSeenAt  = $now;
        $this->updatedAt   = $now;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getCveId(): string
    {
        return $this->cveId;
    }
    public function setCveId(string $cveId): self
    {
        $this->cveId = $cveId;
        return $this;
    }

    public function getSource(): string
    {
        return $this->source;
    }
    public function setSource(string $source): self
    {
        $this->source = $source;
        return $this;
    }

    public function getSeverity(): string
    {
        return $this->severity;
    }
    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;
        return $this;
    }

    public function getCvssV3Score(): ?string
    {
        return $this->cvssV3Score;
    }
    public function setCvssV3Score(?string $score): self
    {
        $this->cvssV3Score = $score;
        return $this;
    }

    public function getCvssV3Severity(): ?string
    {
        return $this->cvssV3Severity;
    }
    public function setCvssV3Severity(?string $v): self
    {
        $this->cvssV3Severity = $v;
        return $this;
    }

    public function getCvssV3AttackVector(): ?string
    {
        return $this->cvssV3AttackVector;
    }
    public function setCvssV3AttackVector(?string $v): self
    {
        $this->cvssV3AttackVector = $v;
        return $this;
    }

    public function getCvssV3AttackComplexity(): ?string
    {
        return $this->cvssV3AttackComplexity;
    }
    public function setCvssV3AttackComplexity(?string $v): self
    {
        $this->cvssV3AttackComplexity = $v;
        return $this;
    }

    public function getCvssV3Vector(): ?string
    {
        return $this->cvssV3Vector;
    }
    public function setCvssV3Vector(?string $v): self
    {
        $this->cvssV3Vector = $v;
        return $this;
    }

    /** @return array<int, string> */
    public function getCwes(): array
    {
        return $this->cwes;
    }
    /** @param array<int, string> $cwes */
    public function setCwes(array $cwes): self
    {
        $this->cwes = $cwes;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    /** @return array<int, array<string, string>> */
    public function getCveReferences(): array
    {
        return $this->cveReferences;
    }
    /** @param array<int, array<string, string>> $refs */
    public function setCveReferences(array $refs): self
    {
        $this->cveReferences = $refs;
        return $this;
    }

    public function getFirstSeenAt(): \DateTimeImmutable
    {
        return $this->firstSeenAt;
    }
    public function setFirstSeenAt(\DateTimeImmutable $d): self
    {
        $this->firstSeenAt = $d;
        return $this;
    }

    public function getLastSeenAt(): \DateTimeImmutable
    {
        return $this->lastSeenAt;
    }
    public function setLastSeenAt(\DateTimeImmutable $d): self
    {
        $this->lastSeenAt = $d;
        return $this;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
    public function setUpdatedAt(\DateTimeImmutable $d): self
    {
        $this->updatedAt = $d;
        return $this;
    }
}
