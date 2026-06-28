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

use App\Repository\LoggerDetailRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * MODIF 2026-05-15 : entité pour stocker le détail
 * des loggers détectés par le plugin track-logger-method v2+. 1 ligne
 * par occurrence (fichier × ligne). Table dropped/refilled a chaque
 * collecte pour la maven_key.
 *
 * Convention alignée sur Logger (mavenKey 255, modeCollecte 32,
 * utilisateurCollecte 320, dateEnregistrement DATETIMETZ_IMMUTABLE).
 */
#[ORM\Entity(repositoryClass: LoggerDetailRepository::class)]
#[ORM\Table(name: 'logger_details', schema: 'ma_moulinette')]
#[ORM\Index(name: 'idx_logger_details_maven_key', columns: ['maven_key'])]
#[ORM\Index(name: 'idx_logger_details_level',     columns: ['level'])]
#[ORM\Index(name: 'idx_logger_details_framework', columns: ['framework'])]
class LoggerDetail
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::BIGINT,
        options: ['comment' => 'ID unique pour la table logger_details']
    )]
    private int|string|null $id = null;

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
        name: 'project_version',
        type: Types::STRING,
        length: 64,
        nullable: true,
        options: ['comment' => 'Version du projet (null si non fournie par le caller)']
    )]
    #[Assert\Length(max: 64)]
    private ?string $projectVersion = null;

    #[ORM\Column(
        name: 'level',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Niveau du logger : info / warn / error / debug']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    private string $level;

    #[ORM\Column(
        name: 'framework',
        type: Types::STRING,
        length: 64,
        nullable: true,
        options: ['comment' => 'Framework de logging (SLF4J, Commons Logging…). Null en plugin v1.x.']
    )]
    #[Assert\Length(max: 64)]
    private ?string $framework = null;

    #[ORM\Column(
        name: 'file_path',
        type: Types::TEXT,
        nullable: false,
        options: ['comment' => 'Chemin relatif du fichier source.']
    )]
    #[Assert\NotBlank]
    private string $filePath;

    #[ORM\Column(
        name: 'file_name',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Basename du fichier.']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $fileName;

    #[ORM\Column(
        name: 'class_name',
        type: Types::STRING,
        length: 255,
        nullable: true,
        options: ['comment' => 'Nom de classe (file_name sans extension).']
    )]
    #[Assert\Length(max: 255)]
    private ?string $className = null;

    #[ORM\Column(
        name: 'line_number',
        type: Types::INTEGER,
        nullable: true,
        options: ['comment' => 'Numéro de ligne dans le fichier.']
    )]
    #[Assert\PositiveOrZero]
    private ?int $lineNumber = null;

    #[ORM\Column(
        name: 'sonar_issue_key',
        type: Types::STRING,
        length: 64,
        nullable: true,
        options: ['comment' => 'UUID issue Sonar pour audit.']
    )]
    #[Assert\Length(max: 64)]
    private ?string $sonarIssueKey = null;

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: true,
        options: ['comment' => 'Mode de collecte : [COLLECTE] | [TRAITEMENT MANUEL] | [TRAITEMENT AUTOMATIQUE]']
    )]
    #[Assert\Length(max: 32)]
    private ?string $modeCollecte = null;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => "Compte de l'utilisateur qui a réalisé la collecte."]
    )]
    #[Assert\Length(max: 320)]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "Date d'enregistrement."]
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
        $this->dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    public function getId(): int|string|null
    {
        return $this->id;
    }

    public function getMavenKey(): string
    {
        return $this->mavenKey;
    }
    public function setMavenKey(string $v): self
    {
        $this->mavenKey = $v;
        return $this;
    }

    public function getProjectVersion(): ?string
    {
        return $this->projectVersion;
    }
    public function setProjectVersion(?string $v): self
    {
        $this->projectVersion = $v;
        return $this;
    }

    public function getLevel(): string
    {
        return $this->level;
    }
    public function setLevel(string $v): self
    {
        $this->level = $v;
        return $this;
    }

    public function getFramework(): ?string
    {
        return $this->framework;
    }
    public function setFramework(?string $v): self
    {
        $this->framework = $v;
        return $this;
    }

    public function getFilePath(): string
    {
        return $this->filePath;
    }
    public function setFilePath(string $v): self
    {
        $this->filePath = $v;
        return $this;
    }

    public function getFileName(): string
    {
        return $this->fileName;
    }
    public function setFileName(string $v): self
    {
        $this->fileName = $v;
        return $this;
    }

    public function getClassName(): ?string
    {
        return $this->className;
    }
    public function setClassName(?string $v): self
    {
        $this->className = $v;
        return $this;
    }

    public function getLineNumber(): ?int
    {
        return $this->lineNumber;
    }
    public function setLineNumber(?int $v): self
    {
        $this->lineNumber = $v;
        return $this;
    }

    public function getSonarIssueKey(): ?string
    {
        return $this->sonarIssueKey;
    }
    public function setSonarIssueKey(?string $v): self
    {
        $this->sonarIssueKey = $v;
        return $this;
    }

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }
    public function setModeCollecte(?string $v): self
    {
        $this->modeCollecte = $v;
        return $this;
    }

    public function getUtilisateurCollecte(): ?string
    {
        return $this->utilisateurCollecte;
    }
    public function setUtilisateurCollecte(?string $v): self
    {
        $this->utilisateurCollecte = $v;
        return $this;
    }

    public function getDateEnregistrement(): \DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }
    public function setDateEnregistrement(\DateTimeImmutable $v): self
    {
        $this->dateEnregistrement = $v;
        return $this;
    }
}
