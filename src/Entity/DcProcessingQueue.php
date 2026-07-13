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

use App\Repository\DcProcessingQueueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description DcDependency]
 * MODIF 2026-05-08 : creation entity queue OWASP DependencyCheck.
 *
 * Cycle de vie d'une ligne :
 *   queued    -> insère par l'endpoint /api/secure/dependency-check/upload
 *   processing -> claim par le worker (commande app:dependency-check:process)
 *   done      -> traitement OK
 *   failed    -> attempts >= 3 ou erreur fatale
 */
#[ORM\Entity(repositoryClass: DcProcessingQueueRepository::class)]
#[ORM\Table(name: "dc_processing_queue", schema: "ma_moulinette")]
class DcProcessingQueue
{
    public const STATUS_QUEUED     = 'queued';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_DONE       = 'done';
    public const STATUS_FAILED     = 'failed';

    public const CONTENT_JSON = 'json';
    public const CONTENT_GZIP = 'gzip';
    public const CONTENT_ZIP  = 'zip';
    public const CONTENT_TGZ  = 'tgz';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT,
        nullable: false,
        options: ['comment' => 'cle technique auto-générée']
    )]
    private int|string|null $id = null;

    #[ORM\Column(
        name: 'ulid',
        type: Types::STRING,
        length: 26,
        unique: true,
        nullable: false,
        options: ['comment' => 'identifiant public ULID renvoyé au client']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 26)]
    private string $ulid;

    /**
     * Payload JSON DependencyCheck compresse (gzencode niveau 6).
     * Type non type pour eviter TypeError a l'hydratation : Doctrine retourne
     * une `resource` pour les BLOB en lecture, pas une string typee.
     */
    #[ORM\Column(
        name: 'payload_gz',
        type: Types::BLOB,
        nullable: false,
        options: ['comment' => 'JSON DependencyCheck gzippé (gzencode niveau 6)']
    )]
    #[Assert\NotNull]
    private $payloadGz;

    #[ORM\Column(
        name: 'payload_sha256',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'sha256 du JSON décompressé - idempotence']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 64)]
    private string $payloadSha256;

    #[ORM\Column(
        name: 'payload_size',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'taille du JSON décompressé en octets']
    )]
    #[Assert\PositiveOrZero]
    private int $payloadSize;

    #[ORM\Column(
        name: 'content_type',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'format reçu : json | gzip | zip | tgz']
    )]
    #[Assert\Choice(choices: [self::CONTENT_JSON, self::CONTENT_GZIP, self::CONTENT_ZIP, self::CONTENT_TGZ])]
    private string $contentType;

    #[ORM\Column(
        name: 'project_group',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'projectInfo.groupID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $projectGroup;

    #[ORM\Column(
        name: 'project_artifact',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'projectInfo.artifactID']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $projectArtifact;

    #[ORM\Column(
        name: 'project_version',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'projectInfo.version']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $projectVersion;

    #[ORM\Column(
        name: 'status',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'queued | processing | done | failed']
    )]
    #[Assert\Choice(choices: [self::STATUS_QUEUED, self::STATUS_PROCESSING, self::STATUS_DONE, self::STATUS_FAILED])]
    private string $status = self::STATUS_QUEUED;

    #[ORM\Column(
        name: 'attempts',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'compteur de tentatives']
    )]
    #[Assert\PositiveOrZero]
    private int $attempts = 0;

    #[ORM\Column(
        name: 'created_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "date d'insertion"]
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(
        name: 'started_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: true,
        options: ['comment' => 'date de claim par le worker']
    )]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(
        name: 'processed_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: true,
        options: ['comment' => 'date de fin (done ou failed)']
    )]
    private ?\DateTimeImmutable $processedAt = null;

    #[ORM\Column(
        name: 'error_message',
        type: Types::TEXT,
        nullable: true,
        options: ['comment' => 'message d\'erreur si status=failed']
    )]
    private ?string $errorMessage = null;

    /* MODIF 2026-05-12 : métadonnées pom.xml
     * transmises par les headers HTTP X-Parent-Label, X-Parent-Version,
     * X-Archetype-Version a l'upload. Transportées vers dc_scan a l'ingestion. */
    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: true,
        options: ['comment' => 'parent POM ou BOM (springboot-socle-config | groupId:artifactId)']
    )]
    #[Assert\Length(max: 128)]
    private ?string $parentLabel = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: true,
        options: ['comment' => 'version du BOM/parent qui pilote les CVE']
    )]
    #[Assert\Length(max: 64)]
    private ?string $parentVersion = null;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: true,
        options: ['comment' => "version du template d'archetype (optionnel)"]
    )]
    #[Assert\Length(max: 64)]
    private ?string $archetypeVersion = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getUlid(): string
    {
        return $this->ulid;
    }
    public function setUlid(string $ulid): self
    {
        $this->ulid = $ulid;
        return $this;
    }

    /** @return mixed Doctrine renvoie resource a la lecture, string a l'ecriture */
    public function getPayloadGz()
    {
        return $this->payloadGz;
    }
    public function setPayloadGz(string $payloadGz): self
    {
        $this->payloadGz = $payloadGz;
        return $this;
    }

    public function getPayloadSha256(): string
    {
        return $this->payloadSha256;
    }
    public function setPayloadSha256(string $payloadSha256): self
    {
        $this->payloadSha256 = $payloadSha256;
        return $this;
    }

    public function getPayloadSize(): int
    {
        return $this->payloadSize;
    }
    public function setPayloadSize(int $payloadSize): self
    {
        $this->payloadSize = $payloadSize;
        return $this;
    }

    public function getContentType(): string
    {
        return $this->contentType;
    }
    public function setContentType(string $contentType): self
    {
        $this->contentType = $contentType;
        return $this;
    }

    public function getProjectGroup(): string
    {
        return $this->projectGroup;
    }
    public function setProjectGroup(string $projectGroup): self
    {
        $this->projectGroup = $projectGroup;
        return $this;
    }

    public function getProjectArtifact(): string
    {
        return $this->projectArtifact;
    }
    public function setProjectArtifact(string $projectArtifact): self
    {
        $this->projectArtifact = $projectArtifact;
        return $this;
    }

    public function getProjectVersion(): string
    {
        return $this->projectVersion;
    }
    public function setProjectVersion(string $projectVersion): self
    {
        $this->projectVersion = $projectVersion;
        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }
    public function setStatus(string $status): self
    {
        $this->status = $status;
        return $this;
    }

    public function getAttempts(): int
    {
        return $this->attempts;
    }
    public function setAttempts(int $attempts): self
    {
        $this->attempts = $attempts;
        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }
    public function setStartedAt(?\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;
        return $this;
    }

    public function getProcessedAt(): ?\DateTimeImmutable
    {
        return $this->processedAt;
    }
    public function setProcessedAt(?\DateTimeImmutable $processedAt): self
    {
        $this->processedAt = $processedAt;
        return $this;
    }

    public function getErrorMessage(): ?string
    {
        return $this->errorMessage;
    }
    public function setErrorMessage(?string $errorMessage): self
    {
        $this->errorMessage = $errorMessage;
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
}
