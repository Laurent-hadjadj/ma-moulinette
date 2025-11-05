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

use Doctrine\ORM\Mapping as ORM;
use Doctrine\DBAL\Types\Types;
use App\Repository\BatchProfilingRepository;


/**
 * [Description BatchProfiling]
 */
#[ORM\Entity(repositoryClass: BatchProfilingRepository::class)]
#[ORM\Table(name: "batch_profiling", schema: "ma_moulinette")]
class BatchProfiling
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique de la table batch_profiling'])]
    private ?int $id = null;

    #[ORM\Column(type: TYPES::STRING, length: 64)]
    private string $portefeuille;

    #[ORM\Column(type: TYPES::STRING, length: 255, nullable: true)]
    private ?string $executionReference = null;

    #[ORM\Column(type: TYPES::INTEGER)]
    private int $nbProjets;

    #[ORM\Column(type: TYPES::FLOAT)]
    private float $tempsTotal;

    #[ORM\Column(type: TYPES::FLOAT)]
    private float $tempsMoyen;

    #[ORM\Column(type: TYPES::FLOAT)]
    private float $memoirePeak;

    #[ORM\Column(type: TYPES::FLOAT)]
    private float $memoireMoyenne;

    #[ORM\Column(type: TYPES::STRING, length: 128)]
    private string $utilisateur;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => "Date d'exécution de la collecte."])]
    private \DateTimeImmutable $dateExecution;

    public function __construct(
        string $portefeuille,
        int $nbProjets,
        float $tempsTotal,
        float $tempsMoyen,
        float $memoirePeak,
        float $memoireMoyenne,
        string $utilisateur,
        ?string $executionReference = null
    ) {
        $this->portefeuille = $portefeuille;
        $this->nbProjets = $nbProjets;
        $this->tempsTotal = $tempsTotal;
        $this->tempsMoyen = $tempsMoyen;
        $this->memoirePeak = $memoirePeak;
        $this->memoireMoyenne = $memoireMoyenne;
        $this->utilisateur = $utilisateur;
        $this->executionReference = $executionReference;
        $this->dateExecution = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    // getters uniquement pour consultation
    public function getId(): ?int { return $this->id; }
    public function getPortefeuille(): string { return $this->portefeuille; }
    public function getNbProjets(): int { return $this->nbProjets; }
    public function getTempsTotal(): float { return $this->tempsTotal; }
    public function getTempsMoyen(): float { return $this->tempsMoyen; }
    public function getMemoirePeak(): float { return $this->memoirePeak; }
    public function getMemoireMoyenne(): float { return $this->memoireMoyenne; }
    public function getUtilisateur(): string { return $this->utilisateur; }
    public function getDateExecution(): \DateTimeImmutable { return $this->dateExecution; }
    public function getExecutionReference(): ?string { return $this->executionReference; }
}
