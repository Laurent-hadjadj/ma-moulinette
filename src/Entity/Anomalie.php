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

use App\Repository\AnomalieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnomalieRepository::class)]
#[ORM\Table(name: "anomalie", schema: "ma_moulinette")]
class Anomalie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique de l’anomalie']
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
        name: 'project_name',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du projet associé à l’anomalie']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "Le nom du projet ne doit pas dépasser 128 caractères."
    )]
    private string $projectName;

    #[ORM\Column(
        name: 'anomalie_total',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total d’anomalies']
    )]
    #[Assert\PositiveOrZero]
    private int $anomalieTotal;

    #[ORM\Column(
        name: 'dette_minute',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes totales de la dette technique']
    )]
    #[Assert\PositiveOrZero]
    private int $detteMinute;

    #[ORM\Column(
        name: 'dette_reliability_minute',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes de la dette de fiabilité']
    )]
    #[Assert\PositiveOrZero]
    private int $detteReliabilityMinute;

    #[ORM\Column(
        name: 'dette_vulnerability_minute',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes de la dette de vulnérabilité']
    )]
    #[Assert\PositiveOrZero]
    private int $detteVulnerabilityMinute;

    #[ORM\Column(
        name: 'dette_code_smell_minute',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes de la dette de mauvaises pratiques']
    )]
    #[Assert\PositiveOrZero]
    private int $detteCodeSmellMinute;

    #[ORM\Column(
        name: 'dette_reliability',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Dette de fiabilité']
    )]
    #[Assert\NotBlank]
    private string $detteReliability;

    #[ORM\Column(
        name: 'dette_vulnerability',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Dette de vulnérabilité']
    )]
    #[Assert\NotBlank]
    private string $detteVulnerability;

    #[ORM\Column(
        name: 'dette',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Dette générale']
    )]
    #[Assert\NotBlank]
    private string $dette;

    #[ORM\Column(
        name: 'dette_code_smell',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Dette des mauvaises pratiques']
    )]
    #[Assert\NotBlank]
    private string $detteCodeSmell;

    #[ORM\Column(
        name: 'frontend',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes liés au frontend']
    )]
    #[Assert\PositiveOrZero]
    private int $frontend;

    #[ORM\Column(
        name: 'backend',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes liés au backend']
    )]
    #[Assert\PositiveOrZero]
    private int $backend;

    #[ORM\Column(
        name: 'autre',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Autres problèmes techniques']
    )]
    #[Assert\PositiveOrZero]
    private int $autre;

    #[ORM\Column(
        name: 'inconnu',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes inconnus']
    )]
    #[Assert\PositiveOrZero]
    private int $inconnu;

    #[ORM\Column(
        name: 'blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes bloquants']
    )]
    #[Assert\PositiveOrZero]
    private int $blocker;

    #[ORM\Column(
        name: 'critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes critiques']
    )]
    #[Assert\PositiveOrZero]
    private int $critical;

    #[ORM\Column(
        name: 'major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes majeurs']
    )]
    #[Assert\PositiveOrZero]
    private int $major;

    #[ORM\Column(
        name: 'info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Informations sur les problèmes mineurs']
    )]
    #[Assert\PositiveOrZero]
    private int $info;

    #[ORM\Column(
        name: 'minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes mineurs']
    )]
    #[Assert\PositiveOrZero]
    private int $minor;

    #[ORM\Column(
        name: 'bug',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de bugs']
    )]
    #[Assert\PositiveOrZero]
    private int $bug;

    #[ORM\Column(
        name: 'vulnerability',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de vulnérabilités']
    )]
    #[Assert\PositiveOrZero]
    private int $vulnerability;

    #[ORM\Column(
        name: 'code_smell',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total d’mauvaises pratiques']
    )]
    #[Assert\PositiveOrZero]
    private int $codeSmell;

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : [COLLECTE], [TRAITEMENT MANUEL], [TRAITEMENT AUTOMATIQUE]']
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
        options: ['comment' => "Compte utilisateur qui a réalisé la collecte."]
    )]
    #[Assert\Length(
        max: 320,
        maxMessage: "Le compte utilisateur ne peut pas dépasser 320 caractères."
    )]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement de l’anomalie']
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

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(string $projectName): static
    {
        $this->projectName = $projectName;

        return $this;
    }

    public function getAnomalieTotal(): ?int
    {
        return $this->anomalieTotal;
    }

    public function setAnomalieTotal(int $anomalieTotal): static
    {
        $this->anomalieTotal = $anomalieTotal;

        return $this;
    }

    public function getDetteMinute(): ?int
    {
        return $this->detteMinute;
    }

    public function setDetteMinute(int $detteMinute): static
    {
        $this->detteMinute = $detteMinute;

        return $this;
    }

    public function getDetteReliabilityMinute(): ?int
    {
        return $this->detteReliabilityMinute;
    }

    public function setDetteReliabilityMinute(int $detteReliabilityMinute): static
    {
        $this->detteReliabilityMinute = $detteReliabilityMinute;

        return $this;
    }

    public function getDetteVulnerabilityMinute(): ?int
    {
        return $this->detteVulnerabilityMinute;
    }

    public function setDetteVulnerabilityMinute(int $detteVulnerabilityMinute): static
    {
        $this->detteVulnerabilityMinute = $detteVulnerabilityMinute;

        return $this;
    }

    public function getDetteCodeSmellMinute(): ?int
    {
        return $this->detteCodeSmellMinute;
    }

    public function setDetteCodeSmellMinute(int $detteCodeSmellMinute): static
    {
        $this->detteCodeSmellMinute = $detteCodeSmellMinute;

        return $this;
    }

    public function getDetteReliability(): ?string
    {
        return $this->detteReliability;
    }

    public function setDetteReliability(string $detteReliability): static
    {
        $this->detteReliability = $detteReliability;

        return $this;
    }

    public function getDetteVulnerability(): ?string
    {
        return $this->detteVulnerability;
    }

    public function setDetteVulnerability(string $detteVulnerability): static
    {
        $this->detteVulnerability = $detteVulnerability;

        return $this;
    }

    public function getDette(): ?string
    {
        return $this->dette;
    }

    public function setDette(string $dette): static
    {
        $this->dette = $dette;

        return $this;
    }

    public function getDetteCodeSmell(): ?string
    {
        return $this->detteCodeSmell;
    }

    public function setDetteCodeSmell(string $detteCodeSmell): static
    {
        $this->detteCodeSmell = $detteCodeSmell;

        return $this;
    }

    public function getFrontend(): ?int
    {
        return $this->frontend;
    }

    public function setFrontend(int $frontend): static
    {
        $this->frontend = $frontend;

        return $this;
    }

    public function getBackend(): ?int
    {
        return $this->backend;
    }

    public function setBackend(int $backend): static
    {
        $this->backend = $backend;

        return $this;
    }

    public function getAutre(): ?int
    {
        return $this->autre;
    }

    public function setAutre(int $autre): static
    {
        $this->autre = $autre;

        return $this;
    }

    public function getInconnu(): ?int
    {
        return $this->inconnu;
    }

    public function setInconnu(int $inconnu): static
    {
        $this->inconnu = $inconnu;

        return $this;
    }

    public function getBlocker(): ?int
    {
        return $this->blocker;
    }

    public function setBlocker(int $blocker): static
    {
        $this->blocker = $blocker;

        return $this;
    }

    public function getCritical(): ?int
    {
        return $this->critical;
    }

    public function setCritical(int $critical): static
    {
        $this->critical = $critical;

        return $this;
    }

    public function getMajor(): ?int
    {
        return $this->major;
    }

    public function setMajor(int $major): static
    {
        $this->major = $major;

        return $this;
    }

    public function getInfo(): ?int
    {
        return $this->info;
    }

    public function setInfo(int $info): static
    {
        $this->info = $info;

        return $this;
    }

    public function getMinor(): ?int
    {
        return $this->minor;
    }

    public function setMinor(int $minor): static
    {
        $this->minor = $minor;

        return $this;
    }

    public function getBug(): ?int
    {
        return $this->bug;
    }

    public function setBug(int $bug): static
    {
        $this->bug = $bug;

        return $this;
    }

    public function getVulnerability(): ?int
    {
        return $this->vulnerability;
    }

    public function setVulnerability(int $vulnerability): static
    {
        $this->vulnerability = $vulnerability;

        return $this;
    }

    public function getCodeSmell(): ?int
    {
        return $this->codeSmell;
    }

    public function setCodeSmell(int $codeSmell): static
    {
        $this->codeSmell = $codeSmell;

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
