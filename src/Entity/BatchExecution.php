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
#[ORM\Table(name: "batch_execution", schema: "ma_moulinette")]
class BatchExecution
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique de la table batch_execution'])]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Nom du batch exécuté.'])]
    #[Assert\NotBlank]
    private string $nom;

    #[ORM\Column(type: 'ulid', nullable: false,
        options: ['comment' => 'référence unique du journal.'])]
    private ?Ulid $referenceUnique = null;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
    options: ['comment' => 'Mode de collecte : COLLECTE | TRAITEMENT MANUEL | TRAITEMENT AUTOMATIQUE'])]
    #[Assert\NotBlank]
    private string $modeCollecte;

    #[ORM\Column(type: Types::STRING, length: 320, nullable: true,
    options: ['comment' => "Compte de l'utilisateur qui a réalisé la collecte."])]
    #[Assert\Email]
    private ?string $utilisateurCollecte = null;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement du journal de l’exécution du batch.'])]
    private \DateTimeImmutable $dateEnregistrement;

    #[ORM\OneToMany(mappedBy: 'job', targetEntity: BatchExecutionJournal::class, cascade: ['persist', 'remove'])]
    private Collection $collectes;

    public function __construct(
        string $nom,
        Ulid $referenceUnique,
        ?string $utilisateurCollecte,
        string $modeCollecte
    ) {
        $this->nom = $nom;
        $this->referenceUnique = $referenceUnique;
        $this->utilisateurCollecte = $utilisateurCollecte;
        $this->modeCollecte = $modeCollecte;
        $this->dateEnregistrement = new \DateTimeImmutable();
        $this->collectes = new ArrayCollection();
    }

    /**
     * [Description for addJournal]
     *
     * @param BatchExecutionJournal $journal
     *
     * @return void
     *
     * Created at: 25/10/2025 21:58:34 (Europe/Paris)
     * @author     Laurent HADJADJ <laurent_h@me.com>
     * @copyright  Licensed Ma-Moulinette - Creative Common CC-BY-NC-SA 4.0.
     */
    public function addJournal(BatchExecutionJournal $journal): void
    {
        if (!$this->collectes->contains($journal)) {
            $this->collectes->add($journal);
            $journal->setJob($this);
        }
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): string
    {
        return $this->nom;
    }

    public function getReferenceUnique(): ?Ulid
    {
        return $this->referenceUnique;
    }

    public function setReferenceUnique(Ulid $referenceUnique): static
    {
        $this->referenceUnique = $referenceUnique;

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

        public function getDateEnregistrement(): ?\DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): static
    {
        $this->dateEnregistrement = $dateEnregistrement;

        return $this;
    }

    public function getCollectes(): Collection
    {
        return $this->collectes;
    }

    public function addCollecte(BatchExecutionJournal $collecte): void
    {
        $this->collectes->add($collecte);
        $collecte->setJob($this);
    }
}
