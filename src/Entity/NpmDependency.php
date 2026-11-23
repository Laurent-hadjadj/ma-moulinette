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

use App\Repository\NpmDependencyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description NpmDependency]
 * Référentiel dédupliqué des paquets npm analysés. Clé naturelle : name
 * seul — contrairement à DcDependency (sha1 d'un fichier .jar), un paquet
 * npm n'a pas de fichier binaire unique à hasher, et la version exacte
 * installée n'est pas restituée de façon fiable par npm audit --json
 * (seulement une plage de version vulnérable, stockée sur NpmFinding).
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
#[ORM\Entity(repositoryClass: NpmDependencyRepository::class)]
#[ORM\Table(name: 'npm_dependency', schema: 'ma_moulinette')]
class NpmDependency
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        unique: true,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $name;

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

    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $name): self
    {
        $this->name = $name;
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
