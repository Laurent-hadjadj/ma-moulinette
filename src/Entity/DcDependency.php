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

use App\Repository\DcDependencyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description DcDependency]
 * MODIF 2026-05-08 : référentiel des dépendances analyses.
 * Cle naturelle : sha1 (le meme .jar partage le meme sha1 entre projets).
 */
#[ORM\Entity(repositoryClass: DcDependencyRepository::class)]
#[ORM\Table(name: 'dc_dependency', schema: 'ma_moulinette')]
class DcDependency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\Column(
        name: 'sha1',
        type: Types::STRING,
        length: 40,
        unique: true,
        nullable: false
    )]
    #[Assert\NotBlank]
    #[Assert\Length(exactly: 40)]
    private string $sha1;

    #[ORM\Column(
        name: 'md5',
        type: Types::STRING,
        length: 32,
        nullable: true
    )]
    private ?string $md5 = null;

    #[ORM\Column(
        name: 'sha256',
        type: Types::STRING,
        length: 64,
        nullable: true
    )]
    private ?string $sha256 = null;

    #[ORM\Column(
        name: 'file_name',
        type: Types::STRING,
        length: 512,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $fileName;

    #[ORM\Column(
        name: 'pkg_coordinates',
        type: Types::STRING,
        length: 512,
        nullable: true
    )]
    private ?string $pkgCoordinates = null;

    #[ORM\Column(
        name: 'vendor',
        type: Types::STRING,
        length: 255,
        nullable: true
    )]
    private ?string $vendor = null;

    #[ORM\Column(
        name: 'product',
        type: Types::STRING,
        length: 255,
        nullable: true
    )]
    private ?string $product = null;

    #[ORM\Column(
        name: 'version',
        type: Types::STRING,
        length: 64,
        nullable: true
    )]
    private ?string $version = null;

    #[ORM\Column(
        name: 'license',
        type: Types::STRING,
        length: 255,
        nullable: true
    )]
    private ?string $license = null;

    #[ORM\Column(
        name: 'description',
        type: Types::TEXT,
        nullable: true
    )]
    private ?string $description = null;

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

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->firstSeenAt = $now;
        $this->lastSeenAt  = $now;
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getSha1(): string
    {
        return $this->sha1;
    }
    public function setSha1(string $sha1): self
    {
        $this->sha1 = $sha1;
        return $this;
    }

    public function getMd5(): ?string
    {
        return $this->md5;
    }
    public function setMd5(?string $md5): self
    {
        $this->md5 = $md5;
        return $this;
    }

    public function getSha256(): ?string
    {
        return $this->sha256;
    }
    public function setSha256(?string $sha256): self
    {
        $this->sha256 = $sha256;
        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }
    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;
        return $this;
    }

    public function getPkgCoordinates(): ?string
    {
        return $this->pkgCoordinates;
    }
    public function setPkgCoordinates(?string $pkg): self
    {
        $this->pkgCoordinates = $pkg;
        return $this;
    }

    public function getVendor(): ?string
    {
        return $this->vendor;
    }
    public function setVendor(?string $v): self
    {
        $this->vendor = $v;
        return $this;
    }

    public function getProduct(): ?string
    {
        return $this->product;
    }
    public function setProduct(?string $p): self
    {
        $this->product = $p;
        return $this;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }
    public function setVersion(?string $v): self
    {
        $this->version = $v;
        return $this;
    }

    public function getLicense(): ?string
    {
        return $this->license;
    }
    public function setLicense(?string $l): self
    {
        $this->license = $l;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }
    public function setDescription(?string $d): self
    {
        $this->description = $d;
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
}
