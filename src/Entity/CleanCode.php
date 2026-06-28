<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2026.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\CleanCodeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/* MODIF 2026-05-15 : entité pour les indicateurs
 * SonarQube 10+ collectés via facettes cleanCodeAttributeCategories,
 * impactSeverities, impactSoftwareQualities + standards OWASP/SANS/CWE. */

/**
 * [Description CleanCode]
 */
#[ORM\Entity(repositoryClass: CleanCodeRepository::class)]
#[ORM\Table(name: "clean_code", schema: "ma_moulinette")]
class CleanCode
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique']
    )]
    private int $id;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255, maxMessage: 'La clé Maven ne doit pas dépasser 255 caractères.')]
    private string $mavenKey;

    #[ORM\Column(
        name: 'project_name',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128, maxMessage: 'Le nom du projet ne doit pas dépasser 128 caractères.')]
    private string $projectName;

    #[ORM\Column(
        name: 'issue_total',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Nombre total d\'issues (paging.total)']
    )]
    #[Assert\PositiveOrZero]
    private int $issueTotal = 0;

    /* ── Clean Code Attribute Categories ───────────────────────────── */

    #[ORM\Column(
        name: 'cc_consistent',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Issues CONSISTENT (conventions, formatage, nommage)']
    )]
    #[Assert\PositiveOrZero]
    private int $ccConsistent = 0;

    #[ORM\Column(
        name: 'cc_intentional',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Issues INTENTIONAL (clarté, logique, complétude)']
    )]
    #[Assert\PositiveOrZero]
    private int $ccIntentional = 0;

    #[ORM\Column(
        name: 'cc_adaptable',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Issues ADAPTABLE (modularité, testabilité)']
    )]
    #[Assert\PositiveOrZero]
    private int $ccAdaptable = 0;

    #[ORM\Column(
        name: 'cc_responsible',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Issues RESPONSIBLE (fiabilité, légalité) — plus critique']
    )]
    #[Assert\PositiveOrZero]
    private int $ccResponsible = 0;

    /* ── Impact Software Qualities ──────────────────────────────────── */

    #[ORM\Column(
        name: 'quality_maintainability',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Issues impactant la MAINTENABILITÉ']
    )]
    #[Assert\PositiveOrZero]
    private int $qualityMaintainability = 0;

    #[ORM\Column(
        name: 'quality_reliability',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Issues impactant la FIABILITÉ']
    )]
    #[Assert\PositiveOrZero]
    private int $qualityReliability = 0;

    #[ORM\Column(
        name: 'quality_security',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Issues impactant la SÉCURITÉ']
    )]
    #[Assert\PositiveOrZero]
    private int $qualitySecurity = 0;

    /* ── Impact Severities (5 niveaux SonarQube 10+) ─────────────── */

    #[ORM\Column(
        name: 'impact_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Impact sévérité BLOCKER']
    )]
    #[Assert\PositiveOrZero]
    private int $impactBlocker = 0;

    #[ORM\Column(
        name: 'impact_high',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Impact sévérité HIGH']
    )]
    #[Assert\PositiveOrZero]
    private int $impactHigh = 0;

    #[ORM\Column(
        name: 'impact_medium',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Impact sévérité MEDIUM']
    )]
    #[Assert\PositiveOrZero]
    private int $impactMedium = 0;

    #[ORM\Column(
        name: 'impact_low',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Impact sévérité LOW']
    )]
    #[Assert\PositiveOrZero]
    private int $impactLow = 0;

    #[ORM\Column(
        name: 'impact_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Impact sévérité INFO']
    )]
    #[Assert\PositiveOrZero]
    private int $impactInfo = 0;

    /* ── Standards de conformité ────────────────────────────────────── */

    #[ORM\Column(
        name: 'owasp_top10',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Issues taguées OWASP Top 10 2021']
    )]
    #[Assert\PositiveOrZero]
    private int $owaspTop10 = 0;

    #[ORM\Column(
        name: 'sans_top25',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Issues taguées SANS Top 25 (basé CWE)']
    )]
    #[Assert\PositiveOrZero]
    private int $sansTop25 = 0;

    #[ORM\Column(
        name: 'cwe',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => '[SQ10+] Issues avec référence CWE (peut cumuler plusieurs tags)']
    )]
    #[Assert\PositiveOrZero]
    private int $cwe = 0;

    /* ── Méta ────────────────────────────────────────────────────────── */

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : [COLLECTE], [TRAITEMENT MANUEL], [TRAITEMENT AUTOMATIQUE]']
    )]
    #[Assert\Length(max: 32)]
    private ?string $modeCollecte = null;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => 'Compte utilisateur ayant réalisé la collecte']
    )]
    #[Assert\Length(max: 320)]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d\'enregistrement']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    /* ── Getters / Setters ─────────────────────────────────────────── */

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }
    public function setMavenKey(string $mavenKey): static
    {
        $this->mavenKey = $mavenKey;
        return $this;
    }

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }
    public function setProjectName(string $projectName): static
    {
        $this->projectName = $projectName;
        return $this;
    }

    public function getIssueTotal(): int
    {
        return $this->issueTotal;
    }
    public function setIssueTotal(int $issueTotal): static
    {
        $this->issueTotal = $issueTotal;
        return $this;
    }

    public function getCcConsistent(): int
    {
        return $this->ccConsistent;
    }
    public function setCcConsistent(int $ccConsistent): static
    {
        $this->ccConsistent = $ccConsistent;
        return $this;
    }

    public function getCcIntentional(): int
    {
        return $this->ccIntentional;
    }
    public function setCcIntentional(int $ccIntentional): static
    {
        $this->ccIntentional = $ccIntentional;
        return $this;
    }

    public function getCcAdaptable(): int
    {
        return $this->ccAdaptable;
    }
    public function setCcAdaptable(int $ccAdaptable): static
    {
        $this->ccAdaptable = $ccAdaptable;
        return $this;
    }

    public function getCcResponsible(): int
    {
        return $this->ccResponsible;
    }
    public function setCcResponsible(int $ccResponsible): static
    {
        $this->ccResponsible = $ccResponsible;
        return $this;
    }

    public function getQualityMaintainability(): int
    {
        return $this->qualityMaintainability;
    }
    public function setQualityMaintainability(int $v): static
    {
        $this->qualityMaintainability = $v;
        return $this;
    }

    public function getQualityReliability(): int
    {
        return $this->qualityReliability;
    }
    public function setQualityReliability(int $v): static
    {
        $this->qualityReliability = $v;
        return $this;
    }

    public function getQualitySecurity(): int
    {
        return $this->qualitySecurity;
    }
    public function setQualitySecurity(int $v): static
    {
        $this->qualitySecurity = $v;
        return $this;
    }

    public function getImpactBlocker(): int
    {
        return $this->impactBlocker;
    }
    public function setImpactBlocker(int $v): static
    {
        $this->impactBlocker = $v;
        return $this;
    }

    public function getImpactHigh(): int
    {
        return $this->impactHigh;
    }
    public function setImpactHigh(int $v): static
    {
        $this->impactHigh = $v;
        return $this;
    }

    public function getImpactMedium(): int
    {
        return $this->impactMedium;
    }
    public function setImpactMedium(int $v): static
    {
        $this->impactMedium = $v;
        return $this;
    }

    public function getImpactLow(): int
    {
        return $this->impactLow;
    }
    public function setImpactLow(int $v): static
    {
        $this->impactLow = $v;
        return $this;
    }

    public function getImpactInfo(): int
    {
        return $this->impactInfo;
    }
    public function setImpactInfo(int $v): static
    {
        $this->impactInfo = $v;
        return $this;
    }

    public function getOwaspTop10(): int
    {
        return $this->owaspTop10;
    }
    public function setOwaspTop10(int $v): static
    {
        $this->owaspTop10 = $v;
        return $this;
    }

    public function getSansTop25(): int
    {
        return $this->sansTop25;
    }
    public function setSansTop25(int $v): static
    {
        $this->sansTop25 = $v;
        return $this;
    }

    public function getCwe(): int
    {
        return $this->cwe;
    }
    public function setCwe(int $v): static
    {
        $this->cwe = $v;
        return $this;
    }

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }
    public function setModeCollecte(?string $modeCollecte): static
    {
        $this->modeCollecte = $modeCollecte;
        return $this;
    }

    public function getUtilisateurCollecte(): ?string
    {
        return $this->utilisateurCollecte;
    }
    public function setUtilisateurCollecte(?string $utilisateurCollecte): static
    {
        $this->utilisateurCollecte = $utilisateurCollecte;
        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }
    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): static
    {
        $this->dateEnregistrement = $dateEnregistrement;
        return $this;
    }
}
