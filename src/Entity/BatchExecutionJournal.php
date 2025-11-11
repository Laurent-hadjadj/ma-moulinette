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

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Repository\BatchExecutionJournalRepository;

/**
 * [Description BatchExecutionJournal]
 */
#[ORM\Entity(repositoryClass: BatchExecutionJournalRepository::class)]
#[ORM\Table(name: "batch_execution_journal", schema: "ma_moulinette")]
class BatchExecutionJournal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique de la table batch_execution_journal'])]
    private $id;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Code de statut du traitement (200 = OK, 500 = Erreur, etc.)'])]
    private int $code;

    #[ORM\Column(type: Types::STRING, nullable: false,
        options: ['comment' => 'Nom du projet'])]
    private string $nomProjet;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Portefeuille de projets'])]
    private $portefeuille;

    #[ORM\Column(type: Types::BINARY, nullable: false,
        options: ['comment' => 'Compte rendu HTML compressé du traitement.'])]
    private string $compteRendu;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => "Date d'exécution de la collecte."])]
    private \DateTimeImmutable $dateExecution;

    #[ORM\ManyToOne(targetEntity: BatchExecution::class, inversedBy: 'collectes')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?BatchExecution $job = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function setCompteRendu(string $html): void
    {
        $this->compteRendu = $html;
    }

    public function getCompteRendu(): string
    {
        $data = $this->compteRendu;
        return $data ? gzdecode($data) : '';
    }

    public function setCode(int $code): void
    {
        $this->code = $code;
    }

    public function getCode(): int
    {
        return $this->code;
    }

    public function getNomProjet(): ?string
    {
        return $this->nomProjet;
    }

    public function setNomProjet(string $nomProjet): static
    {
        $this->nomProjet = $nomProjet;

        return $this;
    }

    public function getPortefeuille(): ?string
    {
        return $this->portefeuille;
    }

    public function setPortefeuille(string $portefeuille): static
    {
        $this->portefeuille = $portefeuille;

        return $this;
    }

    public function setDateExecution(\DateTimeImmutable $date): void
    {
        $this->dateExecution = $date;
    }

    public function getDateExecution(): \DateTimeImmutable
    {
        return $this->dateExecution;
    }

    public function setJob(?BatchExecution $job): void
    {
        $this->job = $job;
    }

    public function getJob(): ?BatchExecution
    {
        return $this->job;
    }

    // ----------------------------------------------------
    // Utilitaires
    // ----------------------------------------------------

    public function isSuccess(): bool
    {
        return $this->code === 200;
    }

    public function isError(): bool
    {
        return $this->code >= 400;
    }
}
