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

use App\Repository\ZapFindingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * [Description ZapFinding]
 * MODIF 2026-10-13 : jointure scan x règle. Snapshot risque/confiance au
 * moment du scan (une règle ZAP peut être requalifiée d'une version à
 * l'autre de l'outil).
 */
#[ORM\Entity(repositoryClass: ZapFindingRepository::class)]
#[ORM\Table(name: 'zap_finding', schema: 'ma_moulinette')]
class ZapFinding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\ManyToOne(targetEntity: ZapScan::class)]
    #[ORM\JoinColumn(
        name: 'scan_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private ZapScan $scan;

    #[ORM\ManyToOne(targetEntity: ZapRule::class)]
    #[ORM\JoinColumn(
        name: 'rule_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private ZapRule $rule;

    #[ORM\Column(
        name: 'riskcode_at_scan',
        type: Types::SMALLINT,
        nullable: false,
        options: ['comment' => 'riskcode ZAP au moment du scan : 0=Informational 1=Low 2=Medium 3=High']
    )]
    private int $riskcodeAtScan;

    #[ORM\Column(
        name: 'confidence_at_scan',
        type: Types::SMALLINT,
        nullable: false,
        options: ['comment' => 'confidence ZAP au moment du scan : 0=Low 1=Medium 2=High 3=Confirmed']
    )]
    private int $confidenceAtScan;

    #[ORM\Column(
        name: 'instance_count',
        type: Types::INTEGER,
        nullable: false
    )]
    private int $instanceCount = 0;

    /** @var array<int, array<string, string>> */
    #[ORM\Column(
        name: 'tags',
        type: Types::JSON,
        nullable: false,
        options: ['jsonb' => true]
    )]
    private array $tags = [];

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

    public function getScan(): ZapScan
    {
        return $this->scan;
    }
    public function setScan(ZapScan $s): self
    {
        $this->scan = $s;
        return $this;
    }

    public function getRule(): ZapRule
    {
        return $this->rule;
    }
    public function setRule(ZapRule $r): self
    {
        $this->rule = $r;
        return $this;
    }

    public function getRiskcodeAtScan(): int
    {
        return $this->riskcodeAtScan;
    }
    public function setRiskcodeAtScan(int $v): self
    {
        $this->riskcodeAtScan = $v;
        return $this;
    }

    public function getConfidenceAtScan(): int
    {
        return $this->confidenceAtScan;
    }
    public function setConfidenceAtScan(int $v): self
    {
        $this->confidenceAtScan = $v;
        return $this;
    }

    public function getInstanceCount(): int
    {
        return $this->instanceCount;
    }
    public function setInstanceCount(int $v): self
    {
        $this->instanceCount = $v;
        return $this;
    }

    /** @return array<int, array<string, string>> */
    public function getTags(): array
    {
        return $this->tags;
    }
    /** @param array<int, array<string, string>> $tags */
    public function setTags(array $tags): self
    {
        $this->tags = $tags;
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
