<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2025.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\AnomalieDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnomalieDetailsRepository::class)]
#[ORM\Table(
    name: 'anomalie_details',
    schema: 'ma_moulinette',
    options: ['comment' => 'Détail des anomalies par type et sévérité']
)]
class AnomalieDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique pour la table anomalie_details']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $mavenKey;

    #[ORM\Column(
        name: 'name',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom de référence de l’anomalie']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $name;

    // ===============================
    // Bugs
    // ===============================

    #[ORM\Column(
        name: 'bug_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bugs bloquants']
    )]
    #[Assert\PositiveOrZero]
    private int $bugBlocker;

    #[ORM\Column(
        name: 'bug_critical', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Nombre de bugs critiques']
    )]
    #[Assert\PositiveOrZero]
    private int $bugCritical;

    #[ORM\Column(
        name: 'bug_major', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Nombre de bugs majeurs']
    )]
    #[Assert\PositiveOrZero]
    private int $bugMajor;

    #[ORM\Column(
        name: 'bug_minor', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Nombre de bugs mineurs']
    )]
    #[Assert\PositiveOrZero]
    private int $bugMinor;

    #[ORM\Column(
        name: 'bug_info', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Nombre de bugs d’information']
    )]
    #[Assert\PositiveOrZero]
    private int $bugInfo;

    // ===============================
    // Vulnerabilities
    // ===============================

    #[ORM\Column(
        name: 'vulnerability_blocker', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Vulnérabilités bloquantes']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerabilityBlocker;

    #[ORM\Column(
        name: 'vulnerability_critical', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Vulnérabilités critiques']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerabilityCritical;

    #[ORM\Column(
        name: 'vulnerability_major', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Vulnérabilités majeures']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerabilityMajor;

    #[ORM\Column(
        name: 'vulnerability_minor', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Vulnérabilités mineures']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerabilityMinor;

    #[ORM\Column(
        name: 'vulnerability_info', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Vulnérabilités d’information']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerabilityInfo;

    // ===============================
    // Code Smells
    // ===============================

    #[ORM\Column(
        name: 'code_smell_blocker', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Mauvaises pratiques bloquantes']
    )]
    #[Assert\PositiveOrZero]
    private int $codeSmellBlocker;

    #[ORM\Column(
        name: 'code_smell_critical', 
        type: Types::INTEGER,
        nullable: false, 
        options: ['comment' => 'Mauvaises pratiques critiques']
        )]
    #[Assert\PositiveOrZero]
    private int $codeSmellCritical;

    #[ORM\Column(
        name: 'code_smell_major', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Mauvaises pratiques majeures']
    )]
    #[Assert\PositiveOrZero]
    private int $codeSmellMajor;

    #[ORM\Column(
        name: 'code_smell_minor', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Mauvaises pratiques mineures']
    )]
    #[Assert\PositiveOrZero]
    private int $codeSmellMinor;

    #[ORM\Column(
        name: 'code_smell_info', 
        type: Types::INTEGER, 
        nullable: false,
        options: ['comment' => 'Mauvaises pratiques d’information']
    )]
    #[Assert\PositiveOrZero]
    private int $codeSmellInfo;

    // ===============================
    // Meta
    // ===============================

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : COLLECTE / TRAITEMENT_MANUEL / TRAITEMENT_AUTOMATIQUE']
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
        options: ['comment' => 'Date et heure d’enregistrement des détails de l’anomalie']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
        $this->dateEnregistrement = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getBugBlocker(): ?int
    {
        return $this->bugBlocker;
    }

    public function setBugBlocker(int $bugBlocker): self
    {
        $this->bugBlocker = $bugBlocker;

        return $this;
    }

    public function getBugCritical(): ?int
    {
        return $this->bugCritical;
    }

    public function setBugCritical(int $bugCritical): self
    {
        $this->bugCritical = $bugCritical;

        return $this;
    }

    public function getBugInfo(): ?int
    {
        return $this->bugInfo;
    }

    public function setBugInfo(int $bugInfo): self
    {
        $this->bugInfo = $bugInfo;

        return $this;
    }

    public function getBugMajor(): ?int
    {
        return $this->bugMajor;
    }

    public function setBugMajor(int $bugMajor): self
    {
        $this->bugMajor = $bugMajor;

        return $this;
    }

    public function getBugMinor(): ?int
    {
        return $this->bugMinor;
    }

    public function setBugMinor(int $bugMinor): self
    {
        $this->bugMinor = $bugMinor;

        return $this;
    }

    public function getVulnerabilityBlocker(): ?int
    {
        return $this->vulnerabilityBlocker;
    }

    public function setVulnerabilityBlocker(int $vulnerabilityBlocker): self
    {
        $this->vulnerabilityBlocker = $vulnerabilityBlocker;

        return $this;
    }

    public function getVulnerabilityCritical(): ?int
    {
        return $this->vulnerabilityCritical;
    }

    public function setVulnerabilityCritical(int $vulnerabilityCritical): self
    {
        $this->vulnerabilityCritical = $vulnerabilityCritical;

        return $this;
    }

    public function getVulnerabilityInfo(): ?int
    {
        return $this->vulnerabilityInfo;
    }

    public function setVulnerabilityInfo(int $vulnerabilityInfo): self
    {
        $this->vulnerabilityInfo = $vulnerabilityInfo;

        return $this;
    }

    public function getVulnerabilityMajor(): ?int
    {
        return $this->vulnerabilityMajor;
    }

    public function setVulnerabilityMajor(int $vulnerabilityMajor): self
    {
        $this->vulnerabilityMajor = $vulnerabilityMajor;

        return $this;
    }

    public function getVulnerabilityMinor(): ?int
    {
        return $this->vulnerabilityMinor;
    }

    public function setVulnerabilityMinor(int $vulnerabilityMinor): self
    {
        $this->vulnerabilityMinor = $vulnerabilityMinor;

        return $this;
    }

    public function getCodeSmellBlocker(): ?int
    {
        return $this->codeSmellBlocker;
    }

    public function setCodeSmellBlocker(int $codeSmellBlocker): self
    {
        $this->codeSmellBlocker = $codeSmellBlocker;

        return $this;
    }

    public function getCodeSmellCritical(): ?int
    {
        return $this->codeSmellCritical;
    }

    public function setCodeSmellCritical(int $codeSmellCritical): self
    {
        $this->codeSmellCritical = $codeSmellCritical;

        return $this;
    }

    public function getCodeSmellInfo(): ?int
    {
        return $this->codeSmellInfo;
    }

    public function setCodeSmellInfo(int $codeSmellInfo): self
    {
        $this->codeSmellInfo = $codeSmellInfo;

        return $this;
    }

    public function getCodeSmellMajor(): ?int
    {
        return $this->codeSmellMajor;
    }

    public function setCodeSmellMajor(int $codeSmellMajor): self
    {
        $this->codeSmellMajor = $codeSmellMajor;

        return $this;
    }

    public function getCodeSmellMinor(): ?int
    {
        return $this->codeSmellMinor;
    }

    public function setCodeSmellMinor(int $codeSmellMinor): self
    {
        $this->codeSmellMinor = $codeSmellMinor;

        return $this;
    }

    public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }

    public function setModeCollecte(?string $modeCollecte): self
    {
        $this->modeCollecte = $modeCollecte;

        return $this;
    }

    public function getUtilisateurCollecte(): ?string
    {
        return $this->utilisateurCollecte;
    }

    public function setUtilisateurCollecte(?string $utilisateurCollecte): self
    {
        $this->utilisateurCollecte = $utilisateurCollecte;

        return $this;
    }

}
