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

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Index(name: 'idx_maven_key', columns: ['maven_key'])]
#[ORM\Index(name: 'idx_project_name', columns: ['project_name'])]
#[ORM\Index(name: 'idx_status_executed_at', columns: ['status', 'executed_at'])]
#[ORM\Table(name: "activity", schema: "ma_moulinette")]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique de la table activité']
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
        length: 64,
        nullable: false,
        options: ['comment' => 'Nom du projet associé à la clé maven']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 64,
        maxMessage: "Le nom du projet ne doit pas dépasser 64 caractères."
    )]
    private string $projectName;

    /* MODIF 2026-05-05 : alignement entite ↔ DDL.*/
    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 26,
        nullable: false,
        options: ['comment' => 'Identifiant de l’analyse du projet']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 26,
        maxMessage: "L'identifiant de l'analyse ne doit pas dépasser 26 caractères."
    )]
    private string $analyseId;

    #[ORM\Column(
        name: 'status',
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Statut du traitement d’import']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 16,
        maxMessage: "Le statut ne doit pas dépasser 16 caractères."
    )]
    private string $status;

    #[ORM\Column(
        name: 'submitter_login',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Utilisateur soumettant l’import']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le login ne doit pas dépasser 32 caractères."
    )]
    private string $submitterLogin;

    #[ORM\Column(
        name: 'submitted_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date et heure de la soumission du traitement d’import des données']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $submittedAt;

    #[ORM\Column(
        name: 'started_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date et heure du debut du traitement d’import des données']
    )]
    #[Assert\NotNull]
    private ?\DateTimeImmutable $startedAt = null;

    #[ORM\Column(
        name: 'executed_at',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date et heure de fin du traitement d’import des données']
    )]
    #[Assert\NotNull]
    private \DateTimeImmutable $executedAt;

    #[ORM\Column(
        name: 'execution_time',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Date et heure de fin du traitement d’import des données']
    )]
    #[Assert\PositiveOrZero]
    private int $executionTime;

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

    /* MODIF 2026-05-05 : retour aligne sur DDL NOT NULL. */
    public function getAnalyseId(): string
    {
        return $this->analyseId;
    }

    public function setAnalyseId(string $analyseId): static
    {
        $this->analyseId = $analyseId;

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

    public function getSubmitterLogin(): ?string
    {
        return $this->submitterLogin;
    }

    public function setSubmitterLogin(string $submitterLogin): static
    {
        $this->submitterLogin = $submitterLogin;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): static
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): static
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getExecutedAt(): ?\DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function setExecutedAt(\DateTimeImmutable $executedAt): static
    {
        $this->executedAt = $executedAt;

        return $this;
    }

    public function getExecutionTime(): ?int
    {
        return $this->executionTime;
    }

    public function setExecutionTime(int $executionTime): static
    {
        $this->executionTime = $executionTime;

        return $this;
    }
}
