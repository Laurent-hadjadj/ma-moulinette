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

use App\Repository\AnomalieDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnomalieDetailsRepository::class)]
#[ORM\Table(name: "anomalie_details", schema: "ma_moulinette")]
class AnomalieDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour la table Anomalie Détails']
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
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères."
    )]
    private string $mavenKey;

    #[ORM\Column(
        name: 'name',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom ']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "Le nom ne doit pas dépasser 128 caractères."
    )]
    private string $name;

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
        name: 'bug_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de bugs d’information']
    )]
    #[Assert\PositiveOrZero]
    private int $bugInfo;

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
        name: 'vulnerability_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de vulnérabilités bloquantes']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerabilityBlocker;

    #[ORM\Column(
        name: 'vulnerability_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de vulnérabilités critiques']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerabilityCritical;

    #[ORM\Column(
        name: 'vulnerability_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de vulnérabilités d’information']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerabilityInfo;

    #[ORM\Column(
        name: 'vulnerability_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de vulnérabilités majeures']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerabilityMajor;

    #[ORM\Column(
        name: 'vulnerability_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de vulnérabilités mineures']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerabilityMinor;

    #[ORM\Column(
        name: 'code_smell_blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mauvaises pratiques bloquantes']
    )]
    #[Assert\PositiveOrZero]
    private int $codeSmellBlocker;

    #[ORM\Column(
        name: 'code_smell_critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mauvaises pratiques critiques']
    )]
    #[Assert\PositiveOrZero]
    private int $codeSmellCritical;

    #[ORM\Column(
        name: 'code_smell_info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mauvaises pratiques d’information']
    )]
    #[Assert\PositiveOrZero]
    private int $codeSmellInfo;

    #[ORM\Column(
        name: 'code_smell_major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mauvaises pratiques majeures']
    )]
    #[Assert\PositiveOrZero]
    private int $codeSmellMajor;

    #[ORM\Column(
        name: 'code_smell_minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de mauvaises pratiques mineures']
    )]
    #[Assert\PositiveOrZero]
    private int $codeSmellMinor;

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : [COLLECTE] | [TRAITEMENT MANUEL] | [TRAITEMENT AUTOMATIQUE]']
    )]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères."
    )]
    private ?string $modeCollecte = null;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => "Compte de l'utilisateur qui a réalisé la collecte."]
    )]
    #[Assert\Length(
        max: 320,
        maxMessage: "Le compte de l’utilisateur ne peut pas dépasser 320 caractères."
    )]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement des détails de l’anomalie']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

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

    public function setMavenKey(string $mavenKey): static
    {
        $this->mavenKey = $mavenKey;

        return $this;
    }

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getBugBlocker(): ?int
    {
        return $this->bugBlocker;
    }

    public function setBugBlocker(int $bugBlocker): static
    {
        $this->bugBlocker = $bugBlocker;

        return $this;
    }

    public function getBugCritical(): ?int
    {
        return $this->bugCritical;
    }

    public function setBugCritical(int $bugCritical): static
    {
        $this->bugCritical = $bugCritical;

        return $this;
    }

    public function getBugInfo(): ?int
    {
        return $this->bugInfo;
    }

    public function setBugInfo(int $bugInfo): static
    {
        $this->bugInfo = $bugInfo;

        return $this;
    }

    public function getBugMajor(): ?int
    {
        return $this->bugMajor;
    }

    public function setBugMajor(int $bugMajor): static
    {
        $this->bugMajor = $bugMajor;

        return $this;
    }

    public function getBugMinor(): ?int
    {
        return $this->bugMinor;
    }

    public function setBugMinor(int $bugMinor): static
    {
        $this->bugMinor = $bugMinor;

        return $this;
    }

    public function getVulnerabilityBlocker(): ?int
    {
        return $this->vulnerabilityBlocker;
    }

    public function setVulnerabilityBlocker(int $vulnerabilityBlocker): static
    {
        $this->vulnerabilityBlocker = $vulnerabilityBlocker;

        return $this;
    }

    public function getVulnerabilityCritical(): ?int
    {
        return $this->vulnerabilityCritical;
    }

    public function setVulnerabilityCritical(int $vulnerabilityCritical): static
    {
        $this->vulnerabilityCritical = $vulnerabilityCritical;

        return $this;
    }

    public function getVulnerabilityInfo(): ?int
    {
        return $this->vulnerabilityInfo;
    }

    public function setVulnerabilityInfo(int $vulnerabilityInfo): static
    {
        $this->vulnerabilityInfo = $vulnerabilityInfo;

        return $this;
    }

    public function getVulnerabilityMajor(): ?int
    {
        return $this->vulnerabilityMajor;
    }

    public function setVulnerabilityMajor(int $vulnerabilityMajor): static
    {
        $this->vulnerabilityMajor = $vulnerabilityMajor;

        return $this;
    }

    public function getVulnerabilityMinor(): ?int
    {
        return $this->vulnerabilityMinor;
    }

    public function setVulnerabilityMinor(int $vulnerabilityMinor): static
    {
        $this->vulnerabilityMinor = $vulnerabilityMinor;

        return $this;
    }

    public function getCodeSmellBlocker(): ?int
    {
        return $this->codeSmellBlocker;
    }

    public function setCodeSmellBlocker(int $codeSmellBlocker): static
    {
        $this->codeSmellBlocker = $codeSmellBlocker;

        return $this;
    }

    public function getCodeSmellCritical(): ?int
    {
        return $this->codeSmellCritical;
    }

    public function setCodeSmellCritical(int $codeSmellCritical): static
    {
        $this->codeSmellCritical = $codeSmellCritical;

        return $this;
    }

    public function getCodeSmellInfo(): ?int
    {
        return $this->codeSmellInfo;
    }

    public function setCodeSmellInfo(int $codeSmellInfo): static
    {
        $this->codeSmellInfo = $codeSmellInfo;

        return $this;
    }

    public function getCodeSmellMajor(): ?int
    {
        return $this->codeSmellMajor;
    }

    public function setCodeSmellMajor(int $codeSmellMajor): static
    {
        $this->codeSmellMajor = $codeSmellMajor;

        return $this;
    }

    public function getCodeSmellMinor(): ?int
    {
        return $this->codeSmellMinor;
    }

    public function setCodeSmellMinor(int $codeSmellMinor): static
    {
        $this->codeSmellMinor = $codeSmellMinor;

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
}
