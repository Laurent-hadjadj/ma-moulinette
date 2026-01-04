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

use App\Repository\HotspotOwaspRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HotspotOwaspRepository::class)]
#[ORM\Table(
    name: 'hotspot_owasp',
    schema: 'ma_moulinette',
    options: ['comment' => 'Table des menaces potentielles de type OWASP.'])]
class HotspotOwasp
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique pour chaque hotspot OWASP'])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'referential_owasp',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Référentiel OWASP 2017 ou 2021'])]
    #[Assert\NotNull(message: 'Le référentiel ne peut pas être null')]
    #[Assert\PositiveOrZero]
    private int $referentialOwasp = 2017;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank(message: "La clé Maven ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private string $mavenKey;

    #[ORM\Column(
        name: 'version',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Version du hotspot OWASP'])]
    #[Assert\NotBlank(message: "La version ne peut pas être vide.")]
    #[Assert\Length(
        max: 32,
        maxMessage: "La version ne doit pas dépasser 32 caractères.")]
    private string $version;

    #[ORM\Column(
        name: 'date_version',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de la version du hotspot OWASP'])]
    #[Assert\NotNull(message: "La date de la version ne peut pas être nulle.")]
    private \DateTimeImmutable $dateVersion;

    #[ORM\Column(
        name: 'menace',
        type: Types::STRING,
        length: 8,
        nullable: false,
        options: ['comment' => 'Menace évaluée du hotspot OWASP'])]
    #[Assert\NotBlank(message: "La menace ne peut pas être vide.")]
    #[Assert\Length(
        max: 8,
        maxMessage: "La menace ne doit pas dépasser 8 caractères.")]
    private string $menace;

    #[ORM\Column(
        name: 'security_category',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'Catégorie de sécurité du hotspot'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 64,
        maxMessage: "La catégorie de sécurité ne doit pas dépasser 64 caractères.")]
    private string $securityCategory;

    #[ORM\Column(
        name: 'rule_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Règle SonarQube associée'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé de la règle ne doit pas dépasser 255 caractères.")]
    private string $ruleKey;

    #[ORM\Column(
        name: 'probability',
        type: Types::STRING,
        length: 8,
        nullable: false,
        options: ['comment' => 'Probabilité du hotspot OWASP'])]
    #[Assert\NotBlank(message: "La probabilité ne peut pas être vide.")]
    #[Assert\Length(
        max: 8,
        maxMessage: "La probabilité ne doit pas dépasser 8 caractères.")]
    private string $probability;

    #[ORM\Column(
        name: 'status',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Statut du hotspot OWASP'])]
    #[Assert\NotBlank(message: "Le statut ne peut pas être vide.")]
    #[Assert\Length(
        max: 16,
        maxMessage: "Le statut ne doit pas dépasser 16 caractères.")]
    private string $status;

    #[ORM\Column(
        name: 'resolution',
        type: Types::STRING,
        length: 16,
        nullable: true,
        options: ['comment' => 'État d’un hotspot REVIEWED : FIXED, SAFE, ACKNOWLEDGED'])]
    #[Assert\Length(
        max: 16,
        maxMessage: "Le statut ne doit pas dépasser 16 caractères.")]
    private ?string $resolution = null;

    #[ORM\Column(
        name: 'niveau',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Niveau de risque du hotspot OWASP'])]
    #[Assert\NotNull(message: "Le niveau ne peut pas être nul.")]
    private int $niveau;

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : COLLECTE | TRAITEMENT MANUEL | TRAITEMENT AUTOMATIQUE'])]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le mode de collecte ne peut pas dépasser 32 caractères.")]
    private ?string $modeCollecte = null;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => "Compte de l'utilisateur qui a réalisé la collecte"])]
    #[Assert\Length(
        max: 320,
        maxMessage: "Le compte de l'utilisateur ne peut pas dépasser 320 caractères.")]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du hotspot OWASP'])]
    #[Assert\NotNull(message: "La date d'enregistrement ne peut pas être nulle.")]
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

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): self
    {
        $this->version = $version;

        return $this;
    }

    public function getDateVersion(): ?\DateTimeImmutable
    {
        return $this->dateVersion;
    }

    public function setDateVersion(\DateTimeImmutable $dateVersion): self
    {
        $this->dateVersion = $dateVersion;

        return $this;
    }

    public function getMenace(): ?string
    {
        return $this->menace;
    }

    public function setMenace(string $menace): self
    {
        $this->menace = $menace;

        return $this;
    }

    public function getProbability(): ?string
    {
        return $this->probability;
    }

    public function setProbability(string $probability): self
    {
        $this->probability = $probability;

        return $this;
    }

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getNiveau(): ?int
    {
        return $this->niveau;
    }

    public function setNiveau(int $niveau): self
    {
        $this->niveau = $niveau;

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

    public function getSecurityCategory(): ?string
    {
        return $this->securityCategory;
    }

    public function setSecurityCategory(string $securityCategory): self
    {
        $this->securityCategory = $securityCategory;

        return $this;
    }

    public function getRuleKey(): ?string
    {
        return $this->ruleKey;
    }

    public function setRuleKey(string $ruleKey): self
    {
        $this->ruleKey = $ruleKey;

        return $this;
    }

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): self
    {
        $this->resolution = $resolution;

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

    public function getReferentialOwasp(): ?int
    {
        return $this->referentialOwasp;
    }

    public function setReferentialOwasp(int $referentialOwasp): self
    {
        $this->referentialOwasp = $referentialOwasp;

        return $this;
    }

}
