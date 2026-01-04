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

use App\Repository\HotspotDetailsRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: HotspotDetailsRepository::class)]
#[ORM\Table(
    name: 'hotspot_details',
    schema: "ma_moulinette",
    options: ['comment' => 'Table du détails des menaces potentielles.'])]
class HotspotDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique pour la table'])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private string $mavenKey;

    #[ORM\Column(
        name: 'version',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Version du projet'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "La version ne doit pas dépasser 32 caractères.")]
    private string $version;

    #[ORM\Column(
        name: 'date_version',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de publication du projet'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateVersion;

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
        length: 128,
        nullable: false,
        options: ['comment' => 'Règle SonarQube'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "La clé de la règle ne doit pas dépasser 128 caractères.")]
    private string $ruleKey;

    #[ORM\Column(
        name: 'rule_name',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Nom de la règle SonarQube'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le nom de la règle ne doit pas dépasser 255 caractères.")]
    private string $ruleName;

    #[ORM\Column(
        name: 'severity',
        type: Types::STRING,
        length: 8,
        nullable: false,
        options: ['comment' => 'Sévérité du hotspot'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 8, maxMessage: "La sévérité ne doit pas dépasser 8 caractères.")]
    private string $severity;

    #[ORM\Column(
        name: 'status',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Statut du hotspot : TO_REVIEW, REVIEWED'])]
    #[Assert\NotBlank]
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
        options: ['comment' => 'Niveau de risque du hotspot'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $niveau;

    #[ORM\Column(
        name: 'frontend',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Anomalies présentes dans le module frontend'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $frontend;

    #[ORM\Column(
        name: 'backend',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Anomalies présentes dans le module backend'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $backend;

    #[ORM\Column(
        name: 'autre',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Anomalies présentes dans les modules autres'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $autre;

    #[ORM\Column(
        name: 'file_name',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Fichier associé au hotspot'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "Le nom de fichier ne doit pas dépasser 128 caractères.")]
    private string $fileName;

    #[ORM\Column(
        name: 'file_path',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Chemin complet du fichier associé au hotspot'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le chemin du fichier ne doit pas dépasser 255 caractères.")]
    private string $filePath;

    #[ORM\Column(
        name: 'line',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Ligne du fichier où se situe le hotspot'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $line;

    #[ORM\Column(
        name: 'message',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Message descriptif du hotspot'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "Le message ne doit pas dépasser 255 caractères.")]
    private string $message;

    #[ORM\Column(
        name: 'hotspot_key',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Clé unique du hotspot'])]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "La clé ne doit pas dépasser 32 caractères.")]
    private string $hotspotKey;

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
        options: ['comment' => "Compte de l'utilisateur qui a réalisé la collecte."])]
    #[Assert\Length(
        max: 320,
        maxMessage: "Le nom de l'utilisateur ne peut pas dépasser 320 caractères.")]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du détail de hotspot'])]
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

    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): self
    {
        $this->severity = $severity;

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

    public function getStatus(): ?string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getFrontend(): ?int
    {
        return $this->frontend;
    }

    public function setFrontend(int $frontend): self
    {
        $this->frontend = $frontend;

        return $this;
    }

    public function getBackend(): ?int
    {
        return $this->backend;
    }

    public function setBackend(int $backend): self
    {
        $this->backend = $backend;

        return $this;
    }

    public function getAutre(): ?int
    {
        return $this->autre;
    }

    public function setAutre(int $autre): self
    {
        $this->autre = $autre;

        return $this;
    }


    public function getLine(): ?int
    {
        return $this->line;
    }

    public function setLine(int $line): self
    {
        $this->line = $line;

        return $this;
    }


    public function getMessage(): ?string
    {
        return $this->message;
    }

    public function setMessage(string $message): self
    {
        $this->message = $message;

        return $this;
    }

    public function getHotspotKey(): ?string
    {
        return $this->hotspotKey;
    }

    public function setHotspotKey(string $hotspotKey): self
    {
        $this->hotspotKey = $hotspotKey;

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

    public function getResolution(): ?string
    {
        return $this->resolution;
    }

    public function setResolution(?string $resolution): self
    {
        $this->resolution = $resolution;

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

    public function getRuleName(): ?string
    {
        return $this->ruleName;
    }

    public function setRuleName(string $ruleName): self
    {
        $this->ruleName = $ruleName;

        return $this;
    }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(string $fileName): self
    {
        $this->fileName = $fileName;

        return $this;
    }

    public function getFilePath(): ?string
    {
        return $this->filePath;
    }

    public function setFilePath(string $filePath): self
    {
        $this->filePath = $filePath;

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
