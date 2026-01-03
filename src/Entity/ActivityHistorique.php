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

use App\Repository\ActivityHistoriqueRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ActivityHistoriqueRepository::class)]
#[ORM\Table(name: 'activity_historique', schema: 'ma_moulinette',
    options: ['comment' => 'Table d’historisation des statistiques d’activité']
)]
class ActivityHistorique
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique de la table historique activité']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'year',
        type: Types::INTEGER,
        options: ['comment' => 'Année de référence des statistiques']
    )]
    #[Assert\NotNull]
    #[Assert\Positive]
    private int $year;

    #[ORM\Column(
        name: 'day',
        type: Types::INTEGER,
        options: ['comment' => 'Nombre de jour']
    )]
    #[Assert\NotNull]
    #[Assert\Range(min: 1, max: 366)]
    private int $day;

    #[ORM\Column(
        name: '"analyse"',
        type: Types::INTEGER,
        options: ['comment' => 'Nombre total d’analyses']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $analyse;

    #[ORM\Column(
        name: 'analyse_average',
        type: Types::FLOAT,
        options: ['comment' => 'Moyenne du nombre d’analyses']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private float $analyseAverage;

    #[ORM\Column(
        name: 'success',
        type: Types::INTEGER,
        options: ['comment' => 'Nombre de traitements réussis']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $success;

    #[ORM\Column(
        name: 'failed',
        type: Types::INTEGER,
        options: ['comment' => 'Nombre de traitements en échec']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $failed;

    #[ORM\Column(
        name: 'success_rate',
        type: Types::FLOAT,
        options: ['comment' => 'Taux de réussite en pourcentage']
    )]
    #[Assert\NotNull]
    #[Assert\Range(min: 0, max: 100)]
    private float $successRate;

    #[ORM\Column(
        name: 'max_time',
        type: Types::INTEGER,
        options: ['comment' => 'Temps maximum de traitement (en secondes)']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $maxTime;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        options: ['comment' => 'Date et heure d’enregistrement des statistiques']
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

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;
        return $this;
    }

    public function getDay(): ?int
    {
        return $this->day;
    }

    public function setDay(int $day): self
    {
        $this->day = $day;
        return $this;
    }

    public function getAnalyse(): ?int
    {
        return $this->analyse;
    }

    public function setAnalyse(int $analyse): self
    {
        $this->analyse = $analyse;
        return $this;
    }

    public function getAnalyseAverage(): ?float
    {
        return $this->analyseAverage;
    }

    public function setAnalyseAverage(float $analyseAverage): self
    {
        $this->analyseAverage = $analyseAverage;
        return $this;
    }

    public function getSuccess(): ?int
    {
        return $this->success;
    }

    public function setSuccess(int $success): self
    {
        $this->success = $success;
        return $this;
    }

    public function getFailed(): ?int
    {
        return $this->failed;
    }

    public function setFailed(int $failed): self
    {
        $this->failed = $failed;
        return $this;
    }

    public function getSuccessRate(): ?float
    {
        return $this->successRate;
    }

    public function setSuccessRate(float $successRate): self
    {
        $this->successRate = $successRate;
        return $this;
    }

    public function getMaxTime(): ?int
    {
        return $this->maxTime;
    }

    public function setMaxTime(int $maxTime): self
    {
        $this->maxTime = $maxTime;
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
}
