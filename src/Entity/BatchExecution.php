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

use App\Repository\BatchExecutionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;
use Doctrine\ORM\Mapping as ORM;

/**
 * [Description BatchExecution]
 */
#[ORM\Entity(repositoryClass: BatchExecutionRepository::class)]
#[ORM\Table(
    name: 'batch_execution',
    schema: 'ma_moulinette',
    options: ['comment' => 'Journal des exécutions de batchs']
)]
class BatchExecution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique de la table batch_execution']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'nom_traitement',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Nom du traitement exécuté']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $nomTraitement;

    #[ORM\Column(
        name: 'execution_id',
        type: 'ulid',
        nullable: true,
        options: ['comment' => 'Référence unique du journal (ULID)']
    )]
    private ?Ulid $executionId = null;

    #[ORM\Column(
        name: 'traitement_id',
        type: 'ulid',
        nullable: true,
        options: ['comment' => 'Référence unique du traitement (ULID)']
    )]
    private ?Ulid $traitementId = null;

    #[ORM\Column(
        name: 'mode_collecte',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Mode de collecte : COLLECTE | TRAITEMENT MANUEL | TRAITEMENT AUTOMATIQUE']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $modeCollecte;

    #[ORM\Column(
        name: 'utilisateur_collecte',
        type: Types::STRING,
        length: 320,
        nullable: true,
        options: ['comment' => 'Compte utilisateur ayant réalisé la collecte']
    )]
    #[Assert\Email]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du journal de l’exécution du batch']
    )]
    private \DateTimeImmutable $dateEnregistrement;

    /**
     * @var Collection<int, BatchExecutionJournal>
     */
    #[ORM\OneToMany(
        mappedBy: 'batchExecution',
        targetEntity: BatchExecutionJournal::class,
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $collectes;

    public function __construct(
        string $nomTraitement,
        ?Ulid $executionId,
        ?Ulid $traitementId,
        ?string $utilisateurCollecte,
        string $modeCollecte
    ) {
        $this->nomTraitement = $nomTraitement;
        $this->executionId = $executionId ?? new Ulid();
        $this->traitementId = $traitementId ?? new Ulid();
        $this->utilisateurCollecte = $utilisateurCollecte;
        $this->modeCollecte = $modeCollecte;
        $this->dateEnregistrement = new \DateTimeImmutable();
        $this->collectes = new ArrayCollection();
    }

    /**
     * Ajoute un journal à la collection.
     */
    public function addJournal(BatchExecutionJournal $journal): void
    {
        if (!$this->collectes->contains($journal)) {
            $this->collectes->add($journal);
            $journal->setBatchExecution($this);
        }
    }

    /**
     * Supprime un journal de la collection.
     */
    public function removeJournal(BatchExecutionJournal $journal): void
    {
        if ($this->collectes->removeElement($journal) && $journal->getBatchExecution() === $this) {
            $journal->setBatchExecution(null);
        }
    }

    /**
     * Retourne la collection de journaux.
     *
     * @return Collection<int, BatchExecutionJournal>
     */
    public function getCollectes(): Collection
    {
        return $this->collectes;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNomTraitement(): string
    {
        return $this->nomTraitement;
    }

    public function getExecutionId(): ?Ulid
    {
        return $this->executionId;
    }

    public function setExecution(Ulid $executionId): self
    {
        $this->executionId = $executionId;

        return $this;
    }

    public function getTraitementId(): ?Ulid
    {
        return $this->traitementId;
    }

    public function setTraitementId(Ulid $traitementId): self
    {
        $this->traitementId = $traitementId;

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
