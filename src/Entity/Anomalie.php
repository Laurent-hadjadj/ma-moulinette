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

use App\Repository\AnomalieRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: AnomalieRepository::class)]
#[ORM\Table(
    name: 'anomalie',
    schema: 'ma_moulinette',
    options: ['comment' => 'Statistiques des anomalies et dettes techniques par projet']
)]
class Anomalie
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique de l’anomalie']
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
        name: 'project_name',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du projet associé à l’anomalie']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $projectName;

    #[ORM\Column(
        name: 'anomalie_total',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total d’anomalies']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $anomalieTotal;

    #[ORM\Column(
        name: 'dette_minute',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes totales de la dette technique']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $detteMinute;

    #[ORM\Column(
        name: 'dette_reliability_minute',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes de la dette de fiabilité']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $detteReliabilityMinute;

    #[ORM\Column(
        name: 'dette_vulnerability_minute',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes de la dette de vulnérabilité']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $detteVulnerabilityMinute;

    #[ORM\Column(
        name: 'dette_code_smell_minute',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Minutes de la dette de mauvaises pratiques']
    )]
    #[Assert\NotNull]
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
    #[Assert\Length(max: 32)]
    private string $detteReliability;

    #[ORM\Column(
        name: 'dette_vulnerability',
        type: Types::STRING,
        length: 32,
        options: ['comment' => 'Dette de vulnérabilité']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $detteVulnerability;

    #[ORM\Column(
        name: 'dette',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Dette générale']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $dette;

    #[ORM\Column(
        name: 'dette_code_smell',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Dette des mauvaises pratiques']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $detteCodeSmell;

    #[ORM\Column(
        name: 'frontend',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes liés au frontend']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $frontend;

    #[ORM\Column(
        name: 'backend',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes liés au backend']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $backend;

    #[ORM\Column(
        name: 'autre',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Autres problèmes techniques']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $autre;

    #[ORM\Column(
        name: 'inconnu',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes inconnus']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $inconnu;

    #[ORM\Column(
        name: 'blocker',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes bloquants']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $blocker;

    #[ORM\Column(
        name: 'critical',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes critiques']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $critical;

    #[ORM\Column(
        name: 'major',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes majeurs']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $major;

    #[ORM\Column(
        name: 'info',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Informations sur les problèmes mineurs']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $info;

    #[ORM\Column(
        name: 'minor',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Problèmes mineurs']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $minor;

    #[ORM\Column(
        name: 'bug',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de bugs']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $bug;

    #[ORM\Column(
        name: 'vulnerability',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de vulnérabilités']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $vulnerability;

    #[ORM\Column(
        name: 'code_smell',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre total de mauvaises pratiques']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $codeSmell;

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: [
            'comment' => 'Mode de collecte : COLLECTE / TRAITEMENT_MANUEL / TRAITEMENT_AUTOMATIQUE'
        ]
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
        options: ['comment' => 'Date et heure d’enregistrement de l’anomalie']
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

    public function getProjectName(): ?string
    {
        return $this->projectName;
    }

    public function setProjectName(string $projectName): self
    {
        $this->projectName = $projectName;

        return $this;
    }

    public function getAnomalieTotal(): ?int
    {
        return $this->anomalieTotal;
    }

    public function setAnomalieTotal(int $anomalieTotal): self
    {
        $this->anomalieTotal = $anomalieTotal;

        return $this;
    }

    public function getDetteMinute(): ?int
    {
        return $this->detteMinute;
    }

    public function setDetteMinute(int $detteMinute): self
    {
        $this->detteMinute = $detteMinute;

        return $this;
    }

    public function getDetteReliabilityMinute(): ?int
    {
        return $this->detteReliabilityMinute;
    }

    public function setDetteReliabilityMinute(int $detteReliabilityMinute): self
    {
        $this->detteReliabilityMinute = $detteReliabilityMinute;

        return $this;
    }

    public function getDetteVulnerabilityMinute(): ?int
    {
        return $this->detteVulnerabilityMinute;
    }

    public function setDetteVulnerabilityMinute(int $detteVulnerabilityMinute): self
    {
        $this->detteVulnerabilityMinute = $detteVulnerabilityMinute;

        return $this;
    }

    public function getDetteCodeSmellMinute(): ?int
    {
        return $this->detteCodeSmellMinute;
    }

    public function setDetteCodeSmellMinute(int $detteCodeSmellMinute): self
    {
        $this->detteCodeSmellMinute = $detteCodeSmellMinute;

        return $this;
    }

    public function getDetteReliability(): ?string
    {
        return $this->detteReliability;
    }

    public function setDetteReliability(string $detteReliability): self
    {
        $this->detteReliability = $detteReliability;

        return $this;
    }

    public function getDetteVulnerability(): ?string
    {
        return $this->detteVulnerability;
    }

    public function setDetteVulnerability(string $detteVulnerability): self
    {
        $this->detteVulnerability = $detteVulnerability;

        return $this;
    }

    public function getDette(): ?string
    {
        return $this->dette;
    }

    public function setDette(string $dette): self
    {
        $this->dette = $dette;

        return $this;
    }

    public function getDetteCodeSmell(): ?string
    {
        return $this->detteCodeSmell;
    }

    public function setDetteCodeSmell(string $detteCodeSmell): self
    {
        $this->detteCodeSmell = $detteCodeSmell;

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

    public function getInconnu(): ?int
    {
        return $this->inconnu;
    }

    public function setInconnu(int $inconnu): self
    {
        $this->inconnu = $inconnu;

        return $this;
    }

    public function getBlocker(): ?int
    {
        return $this->blocker;
    }

    public function setBlocker(int $blocker): self
    {
        $this->blocker = $blocker;

        return $this;
    }

    public function getCritical(): ?int
    {
        return $this->critical;
    }

    public function setCritical(int $critical): self
    {
        $this->critical = $critical;

        return $this;
    }

    public function getMajor(): ?int
    {
        return $this->major;
    }

    public function setMajor(int $major): self
    {
        $this->major = $major;

        return $this;
    }

    public function getInfo(): ?int
    {
        return $this->info;
    }

    public function setInfo(int $info): self
    {
        $this->info = $info;

        return $this;
    }

    public function getMinor(): ?int
    {
        return $this->minor;
    }

    public function setMinor(int $minor): self
    {
        $this->minor = $minor;

        return $this;
    }

    public function getBug(): ?int
    {
        return $this->bug;
    }

    public function setBug(int $bug): self
    {
        $this->bug = $bug;

        return $this;
    }

    public function getVulnerability(): ?int
    {
        return $this->vulnerability;
    }

    public function setVulnerability(int $vulnerability): self
    {
        $this->vulnerability = $vulnerability;

        return $this;
    }

    public function getCodeSmell(): ?int
    {
        return $this->codeSmell;
    }

    public function setCodeSmell(int $codeSmell): self
    {
        $this->codeSmell = $codeSmell;

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
