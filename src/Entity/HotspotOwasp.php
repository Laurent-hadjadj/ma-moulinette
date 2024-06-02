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

use App\Repository\HotspotOwaspRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HotspotOwaspRepository::class)]
#[ORM\Table(name: "hotspot_owasp", schema: "ma_moulinette")]
class HotspotOwasp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque hotspot OWASP'])]
    private $id;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Référentiel OWASP 2017, 2021'])]
    #[Assert\NotNull(message: "Le référentiel ne peut pas être null")]
    private ?int $referentielOwasp=2017;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank(message: "La clé Maven ne peut pas être vide.")]
    #[Assert\Length(max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private $mavenKey;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Version du hotspot OWASP'])]
    #[Assert\NotBlank(message: "La version ne peut pas être vide.")]
    #[Assert\Length(max: 32,
        maxMessage: "La version ne doit pas dépasser 32 caractères.")]
    private $version;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date de la version du hotspot OWASP'])]
    #[Assert\NotNull(message: "La date de la version ne peut pas être nulle.")]
    private $dateVersion;

    #[ORM\Column(type: Types::STRING, length: 8, nullable: false,
        options: ['comment' => 'Menace évaluée du hotspot OWASP'])]
    #[Assert\NotBlank(message: "La menace ne peut pas être vide.")]
    #[Assert\Length(max: 8,
        maxMessage: "La menace ne doit pas dépasser 8 caractères.")]
    private $menace;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: false,
        options: ['comment' => 'Défini la catégorie de sécurité du hotspot'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64,
        maxMessage: "La catégorie de sécurité ne doit pas dépasser 64 caractères.")]
    private $securityCategory;

    #[ORM\Column(type: Types::STRING, length: 255, nullable: false,
        options: ['comment' => 'Règle SonarQube'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255,
        maxMessage: "La catégorie de sécurité ne doit pas dépasser 255 caractères.")]
    private $ruleKey;

    #[ORM\Column(type: Types::STRING, length: 8, nullable: false,
        options: ['comment' => 'Probabilité du hotspot OWASP'])]
    #[Assert\NotBlank(message: "La probabilité ne peut pas être vide.")]
    #[Assert\Length(max: 8,
            maxMessage: "La probabilité ne doit pas dépasser 8 caractères.")]
    private $probability;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: false,
        options: ['comment' => 'Statut du hotspot OWASP'])]
    #[Assert\NotBlank(message: "Le statut ne peut pas être vide.")]
    #[Assert\Length(max: 16,
            maxMessage: "Le statut ne doit pas dépasser 16 caractères.")]
    private $status;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: true,
        options: ['comment' => 'Donne pour un hotspot au statut REVIEWED son état : FIXED, SAFE, ACKNOWLEDGED'])]
    #[Assert\Length(max: 16,
        maxMessage: "Le statut ne doit pas dépasser 16 caractères.")]
    private ?string $resolution=null;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Niveau de risque du hotspot OWASP'])]
    #[Assert\NotNull(message: "Le niveau ne peut pas être nul.")]
    private $niveau;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: true,
    options: ['comment' => 'Mode de collete : MANUEL | AUTOMATIQUE'])]
    #[Assert\Length(max: 32,
        maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères.")]
    private ?string $modeCollecte=null;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: true,
    options: ['comment' => "Nom de l'utilisateur qui a réalisé la collecte."])]
    #[Assert\Length(max: 128,
        maxMessage: "Le nom de l'utilisatzeur ne peut pas dépasser 128 caractères.")]
    private ?string $utilisateurCollecte=null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement du hotspot OWASP'])]
    #[Assert\NotNull(message: "La date d'enregistrement ne peut pas être nulle.")]
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

    public function getMenace(): ?string
    {
        return $this->menace;
    }

    public function setMenace(string $menace): static
    {
        $this->menace = $menace;

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

    public function getReferentielOwasp(): ?int
    {
        return $this->referentielOwasp;
    }

    public function setReferentielOwasp(int $referentielOwasp): static
    {
        $this->referentielOwasp = $referentielOwasp;

        return $this;
    }

}
