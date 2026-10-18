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

use App\Repository\ZapRuleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description ZapRule]
 * MODIF 2026-10-13 : référentiel global des règles ZAP (alertes).
 * MODIF 2026-10-15 : dédupliquée par alert_ref (pas pluginid) — un rapport
 * ZAP réel peut regrouper plusieurs alertes distinctes sous le même pluginid
 * (ex. "90004-2" Cross-Origin-Embedder-Policy et "90004-3"
 * Cross-Origin-Opener-Policy partagent pluginid=90004) : pluginid identifie
 * une famille de règles, alert_ref identifie la règle réellement unique.
 */
#[ORM\Entity(repositoryClass: ZapRuleRepository::class)]
#[ORM\Table(name: 'zap_rule', schema: 'ma_moulinette')]
class ZapRule
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\Column(
        name: 'pluginid',
        type: Types::STRING,
        length: 16,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $pluginid;

    #[ORM\Column(
        name: 'alert_ref',
        type: Types::STRING,
        length: 24,
        unique: true,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $alertRef;

    #[ORM\Column(
        name: 'name',
        type: Types::STRING,
        length: 255,
        nullable: false
    )]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(
        name: 'cweid',
        type: Types::INTEGER,
        nullable: true
    )]
    private ?int $cweid = null;

    #[ORM\Column(
        name: 'wascid',
        type: Types::INTEGER,
        nullable: true
    )]
    private ?int $wascid = null;

    #[ORM\Column(
        name: 'description',
        type: Types::TEXT,
        nullable: false
    )]
    private string $description = '';

    #[ORM\Column(
        name: 'solution',
        type: Types::TEXT,
        nullable: false
    )]
    private string $solution = '';

    #[ORM\Column(
        name: 'reference',
        type: Types::TEXT,
        nullable: false
    )]
    private string $reference = '';

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

    public function getPluginid(): string
    {
        return $this->pluginid;
    }
    public function setPluginid(string $v): self
    {
        $this->pluginid = $v;
        return $this;
    }

    public function getAlertRef(): string
    {
        return $this->alertRef;
    }
    public function setAlertRef(string $v): self
    {
        $this->alertRef = $v;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }
    public function setName(string $v): self
    {
        $this->name = $v;
        return $this;
    }

    public function getCweid(): ?int
    {
        return $this->cweid;
    }
    public function setCweid(?int $v): self
    {
        $this->cweid = $v;
        return $this;
    }

    public function getWascid(): ?int
    {
        return $this->wascid;
    }
    public function setWascid(?int $v): self
    {
        $this->wascid = $v;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }
    public function setDescription(string $v): self
    {
        $this->description = $v;
        return $this;
    }

    public function getSolution(): string
    {
        return $this->solution;
    }
    public function setSolution(string $v): self
    {
        $this->solution = $v;
        return $this;
    }

    public function getReference(): string
    {
        return $this->reference;
    }
    public function setReference(string $v): self
    {
        $this->reference = $v;
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
