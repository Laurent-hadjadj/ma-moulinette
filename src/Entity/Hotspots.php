<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\HotspotsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HotspotsRepository::class)]
#[ORM\Table(name: "hotspots", schema: "ma_moulinette")]
class Hotspots
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour la table hotspot']
    )]
    private $id;

    #[ORM\Column(
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
    private $mavenKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Version du hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "La version ne doit pas dépasser 32 caractères."
    )]
    private $version;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de la version du hotspot']
    )]
    #[Assert\NotNull]
    private $dateVersion;

    #[ORM\Column(
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Clé unique du hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "La clé ne doit pas dépasser 32 caractères."
    )]
    private $hotpostKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'Défini la catégorie de sécurité du hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 64,
        maxMessage: "La catégorie de sécurité ne doit pas dépasser 64 caractères."
    )]
    private $securityCategory;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Règle SonarQube']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "La clé de la règle ne doit pas dépasser 128 caractères."
    )]
    private $ruleKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Nom de la règle SonarQube']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom de la règle ne doit pas dépasser 255 caractères."
    )]
    private $ruleName;

    #[ORM\Column(
        type: Types::STRING,
        length: 8,
        nullable: false,
        options: ['comment' => 'Probabilité de risque du hotspot']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 8,
        maxMessage: "La probabilité ne doit pas dépasser 8 caractères."
    )]
    private $probability;

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Statut du hotspot : TO_REVIEW, REVIEWED']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 16,
        maxMessage: "Le statut ne doit pas dépasser 16 caractères."
    )]
    private $status;

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: true,
        options: ['comment' => 'Donne pour un hotspot au statut REVIEWED son état : FIXED, SAFE, ACKNOWLEDGED']
    )]
    #[Assert\Length(
        max: 16,
        maxMessage: "Le statut ne doit pas dépasser 16 caractères."
    )]
    private ?string $resolution=null;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Niveau de risque du hotspot']
    )]
    #[Assert\NotNull]
    private $niveau;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du hotspot']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

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

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getDateVersion(): ?\DateTimeImmutable
    {
        return $this->dateVersion;
    }

    public function setDateVersion(\DateTimeImmutable $dateVersion): static
    {
        $this->dateVersion = $dateVersion;

        return $this;
    }

    public function getProbability(): ?string
    {
        return $this->probability;
    }

    public function setProbability(string $probability): static
    {
        $this->probability = $probability;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getNiveau(): ?int
    {
        return $this->niveau;
    }

    public function setNiveau(int $niveau): static
    {
        $this->niveau = $niveau;

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

    public function getSecurityCategory(): ?string
    {
        return $this->securityCategory;
    }

    public function setSecurityCategory(string $securityCategory): static
    {
        $this->securityCategory = $securityCategory;

        return $this;
    }

    public function getRuleKey(): ?string
    {
        return $this->ruleKey;
    }

    public function setRuleKey(string $ruleKey): static
    {
        $this->ruleKey = $ruleKey;

        return $this;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): static
    {
        $this->resolution = $resolution;

        return $this;
    }

    public function getRuleName(): ?string
    {
        return $this->ruleName;
    }

    public function setRuleName(string $ruleName): static
    {
        $this->ruleName = $ruleName;

        return $this;
    }

    public function getHotpostKey(): ?string
    {
        return $this->hotpostKey;
    }

    public function setHotpostKey(string $hotpostKey): static
    {
        $this->hotpostKey = $hotpostKey;

        return $this;
    }

}
