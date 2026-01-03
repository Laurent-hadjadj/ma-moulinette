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

use App\Repository\ActivityRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActivityRepository::class)]
#[ORM\Table(name: 'activity', schema: 'ma_moulinette')]
#[ORM\Index(name: 'idx_maven_key', columns: ['maven_key'])]
#[ORM\Index(name: 'idx_project_name', columns: ['project_name'])]
#[ORM\Index(name: 'idx_status_executed_at', columns: ['status', 'executed_at'])]
class Activity
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(name: 'maven_key', type: Types::STRING, length: 255)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $mavenKey;

    #[ORM\Column(name: 'project_name', type: Types::STRING, length: 64)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $projectName;

    #[ORM\Column(name: 'analyse_id', type: Types::STRING, length: 26)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 26)]
    private string $analyseId;

    #[ORM\Column(name: 'analyse_id', type: Types::STRING, length: 16)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 16)]
    private string $status;

    #[ORM\Column(name: 'submitter_login', type: Types::STRING, length: 32)]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $submitterLogin;

    #[ORM\Column(name: 'submitted_at', type: Types::DATETIMETZ_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $submittedAt;

    #[ORM\Column(name: 'started_at', type: Types::DATETIMETZ_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $startedAt;

    #[ORM\Column(name: 'executed_at', type: Types::DATETIMETZ_IMMUTABLE)]
    #[Assert\NotNull]
    private \DateTimeImmutable $executedAt;

    #[ORM\Column(name: 'execution_time', type: Types::INTEGER)]
    #[Assert\NotNull]
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

    public function getAnalyseId(): ?string
    {
        return $this->analyseId;
    }

    public function setAnalyseId(string $analyseId): self
    {
        $this->analyseId = $analyseId;

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

    public function getSubmitterLogin(): ?string
    {
        return $this->submitterLogin;
    }

    public function setSubmitterLogin(string $submitterLogin): self
    {
        $this->submitterLogin = $submitterLogin;

        return $this;
    }

    public function getSubmittedAt(): ?\DateTimeImmutable
    {
        return $this->submittedAt;
    }

    public function setSubmittedAt(\DateTimeImmutable $submittedAt): self
    {
        $this->submittedAt = $submittedAt;

        return $this;
    }

    public function getStartedAt(): ?\DateTimeImmutable
    {
        return $this->startedAt;
    }

    public function setStartedAt(\DateTimeImmutable $startedAt): self
    {
        $this->startedAt = $startedAt;

        return $this;
    }

    public function getExecutedAt(): ?\DateTimeImmutable
    {
        return $this->executedAt;
    }

    public function setExecutedAt(\DateTimeImmutable $executedAt): self
    {
        $this->executedAt = $executedAt;

        return $this;
    }

    public function getExecutionTime(): ?int
    {
        return $this->executionTime;
    }

    public function setExecutionTime(int $executionTime): self
    {
        $this->executionTime = $executionTime;

        return $this;
    }

}
