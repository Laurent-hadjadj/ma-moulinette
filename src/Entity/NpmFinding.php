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

use App\Repository\NpmFindingRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description NpmFinding]
 * Jointure ternaire scan x dependency x vulnerability. Snapshot
 * severity/cvss au moment du scan, plus 3 dimensions sans équivalent DC :
 * isDirect (dépendance directe ou transitive), fixAvailable (source fiable
 * côté npm, contrairement au fixedVersion DC retiré faute de fiabilité),
 * isProd (nullable — voir commentaire sur la colonne).
 *
 * @author     Laurent HADJADJ <laurent_h@me.com>
 * @copyright  Licensed Ma-Moulinette - GNU AGPL 3.0 ou ultérieure (AGPL-3.0-or-later).
 */
#[ORM\Entity(repositoryClass: NpmFindingRepository::class)]
#[ORM\Table(name: 'npm_finding', schema: 'ma_moulinette')]
class NpmFinding
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT
    )]
    private int|string|null $id = null;

    #[ORM\ManyToOne(targetEntity: NpmScan::class)]
    #[ORM\JoinColumn(
        name: 'scan_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'CASCADE'
    )]
    private NpmScan $scan;

    #[ORM\ManyToOne(targetEntity: NpmDependency::class)]
    #[ORM\JoinColumn(
        name: 'dependency_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private NpmDependency $dependency;

    #[ORM\ManyToOne(targetEntity: NpmVulnerability::class)]
    #[ORM\JoinColumn(
        name: 'vulnerability_id',
        referencedColumnName: 'id',
        nullable: false,
        onDelete: 'RESTRICT'
    )]
    private NpmVulnerability $vulnerability;

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

    /* MODIF 2026-11-20 : plage de version vulnérable constatée à ce scan
     * (vulnerabilities[name].range du JSON) — PAS une version installée
     * exacte, npm audit --json ne la restitue pas de façon fiable. Affichée
     * telle quelle, sans fausse précision. */
    #[ORM\Column(
        name: 'range',
        type: Types::STRING,
        length: 255,
        nullable: true
    )]
    private ?string $range = null;

    /* MODIF 2026-11-20 : version réellement installée, quand on peut la
     * connaître — npm audit --json seul ne la restitue jamais (voir le
     * commentaire sur $range). Alimentée uniquement si la CI a aussi
     * envoyé un rapport "installed" (npm ls --all --json) dans l'enveloppe
     * d'upload ; reste null sinon. Si l'arbre installed contient plusieurs
     * versions distinctes du même paquet (conflit de résolution npm), les
     * deux sont conservées, jointes par ", " plutôt que d'en choisir une
     * arbitrairement. */
    #[ORM\Column(
        name: 'installed_version',
        type: Types::STRING,
        length: 255,
        nullable: true
    )]
    private ?string $installedVersion = null;

    #[ORM\Column(
        name: 'is_direct',
        type: Types::BOOLEAN,
        nullable: false,
        options: ['default' => false]
    )]
    private bool $isDirect = false;

    #[ORM\Column(
        name: 'fix_available',
        type: Types::BOOLEAN,
        nullable: false,
        options: ['default' => false]
    )]
    private bool $fixAvailable = false;

    #[ORM\Column(
        name: 'fix_to_version',
        type: Types::STRING,
        length: 64,
        nullable: true
    )]
    private ?string $fixToVersion = null;

    #[ORM\Column(
        name: 'fix_is_semver_major',
        type: Types::BOOLEAN,
        nullable: true
    )]
    private ?bool $fixIsSemVerMajor = null;

    /* MODIF 2026-11-20 : npm audit --json seul ne dit pas si CE paquet
     * vulnérable est en prod ou en dev. null = un seul rapport reçu pour
     * l'instant (croisement avec le rapport "prod-only" pas encore fait) ;
     * true/false = déterminé après croisement des 2 rapports (voir
     * NpmProcessingQueue::SCOPE_*). Volontairement PAS un bool avec défaut
     * false : ne jamais sous-estimer silencieusement le risque prod avant
     * d'avoir la vraie réponse. */
    #[ORM\Column(
        name: 'is_prod',
        type: Types::BOOLEAN,
        nullable: true
    )]
    private ?bool $isProd = null;

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

    public function getScan(): NpmScan
    {
        return $this->scan;
    }
    public function setScan(NpmScan $s): self
    {
        $this->scan = $s;
        return $this;
    }

    public function getDependency(): NpmDependency
    {
        return $this->dependency;
    }
    public function setDependency(NpmDependency $d): self
    {
        $this->dependency = $d;
        return $this;
    }

    public function getVulnerability(): NpmVulnerability
    {
        return $this->vulnerability;
    }
    public function setVulnerability(NpmVulnerability $v): self
    {
        $this->vulnerability = $v;
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

    public function getRange(): ?string
    {
        return $this->range;
    }
    public function setRange(?string $r): self
    {
        $this->range = $r;
        return $this;
    }

    public function getInstalledVersion(): ?string
    {
        return $this->installedVersion;
    }
    public function setInstalledVersion(?string $v): self
    {
        $this->installedVersion = $v;
        return $this;
    }

    public function isDirect(): bool
    {
        return $this->isDirect;
    }
    public function setIsDirect(bool $v): self
    {
        $this->isDirect = $v;
        return $this;
    }

    public function isFixAvailable(): bool
    {
        return $this->fixAvailable;
    }
    public function setFixAvailable(bool $v): self
    {
        $this->fixAvailable = $v;
        return $this;
    }

    public function getFixToVersion(): ?string
    {
        return $this->fixToVersion;
    }
    public function setFixToVersion(?string $v): self
    {
        $this->fixToVersion = $v;
        return $this;
    }

    public function getFixIsSemVerMajor(): ?bool
    {
        return $this->fixIsSemVerMajor;
    }
    public function setFixIsSemVerMajor(?bool $v): self
    {
        $this->fixIsSemVerMajor = $v;
        return $this;
    }

    public function isProd(): ?bool
    {
        return $this->isProd;
    }
    public function setIsProd(?bool $v): self
    {
        $this->isProd = $v;
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
