<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2024.
 *  Laurent HADJADJ <laurent_h@me.com>.
 *  Licensed Creative Common  CC-BY-NC-SA 4.0.
 *  ---
 *  Vous pouvez obtenir une copie de la licence à l'adresse suivante :
 *  http://creativecommons.org/licenses/by-nc-sa/4.0/
 */

namespace App\Entity;

use App\Repository\ActivityHistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActivityHistoriqueRepository::class)]
#[ORM\Table(name: "activity_historique", schema: "ma_moulinette")]
class ActivityHistorique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique de la table historique activité'])]
    private int $id;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Année'])]
    #[Assert\NotNull]
    private int $year;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Nombre de jours'])]
    #[Assert\NotNull]
    private int $day;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => "Nombre d'analyses"])]
    #[Assert\NotNull]
    private int $analyse;

    #[ORM\Column(type: Types::FLOAT, nullable: false,
        options: ['comment' => 'Moyenne des analyses'])]
    #[Assert\NotNull]
    private float $analyseAverage;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Nombre de réussites'])]
    #[Assert\NotNull]
    private int $success;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => "Nombre d'échecs"])]
    #[Assert\NotNull]
    private int $failed;

    #[ORM\Column(type: Types::FLOAT, nullable: false,
        options: ['comment' => 'Taux de réussite'])]
    #[Assert\NotNull]
    private float $successRate;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Temps maximal'])]
    #[Assert\NotNull]
    private int $maxTime;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => "Date et heure d'enregistrement"])]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): static
    {
        $this->year = $year;
        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): static
    {
        $this->day = $day;
        return $this;
    }

    public function getAnalyse(): ?int
    {
        return $this->analyse;
    }

    public function setAnalyse(int $analyse): static
    {
        $this->analyse = $analyse;
        return $this;
    }

    public function getAnalyseAverage(): ?float
    {
        return $this->analyseAverage;
    }

    public function setAnalyseAverage(float $analyseAverage): static
    {
        $this->analyseAverage = $analyseAverage;
        return $this;
    }

    public function getSuccess(): ?int
    {
        return $this->success;
    }

    public function setSuccess(int $success): static
    {
        $this->success = $success;
        return $this;
    }

    public function getFailed(): ?int
    {
        return $this->failed;
    }

    public function setFailed(int $failed): static
    {
        $this->failed = $failed;
        return $this;
    }

    public function getSuccessRate(): ?float
    {
        return $this->successRate;
    }

    public function setSuccessRate(float $successRate): static
    {
        $this->successRate = $successRate;
        return $this;
    }

    public function getMaxTime(): ?int
    {
        return $this->maxTime;
    }

    public function setMaxTime(int $maxTime): static
    {
        $this->maxTime = $maxTime;
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
}
