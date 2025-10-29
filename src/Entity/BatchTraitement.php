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

use App\Repository\BatchTraitementRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Uid\Ulid;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: BatchTraitementRepository::class)]
#[ORM\Table(name: "batch_traitement", schema: "ma_moulinette")]
class BatchTraitement
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique pour la table Batch Traitement'])]
    private $id;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Mode de collecte du traitement'])]
    #[Assert\Choice(choices: ['COLLECTE', 'TRAITEMENT MANUEL', 'TRAITEMENT MANUEL'],
                    message: "Le démarrage doit être Collecte, TRAITEMENT MANUEL ou TRAITEMENT AUTOMATIQUE")]
    #[Assert\NotBlank]
    private $modeCollecte = "TRAITEMENT MANUEL";

    #[ORM\Column(type: Types::BOOLEAN, nullable: true,
        options: ['comment' => 'Indique si le traitement a réussi ou échoué'])]
    #[Assert\Type(type: 'bool',
        message: " Le résultat doit être un booléen.")]
    #[Assert\NotNull]
    private ?bool $success = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: true,
        options: ['comment' => "Indique si le traitement est en attente d'execution"])]
    #[Assert\Type(type: 'bool',
        message: "Le résultat doit être un booléen.")]
    private ?bool $pending = null;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false,
        options: ['comment' => "Indique si le traitement est en cours d'execution"])]
    #[Assert\Type(type: 'bool',
        message: "Le résultat doit être un booléen.")]
    private $inProgress = false;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Titre du traitement'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32,
        maxMessage: "Le titre ne doit pas dépasser 32 caractères.")]
    private $titre;

    #[ORM\Column(type: Types::STRING, length: 32, nullable: false,
        options: ['comment' => 'Nom du portefeuille de projets associé'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32,
        maxMessage: "Le nom du portefeuille ne doit pas dépasser 32 caractères.")]
    private $portefeuille = "Aucun";

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Nombre de projets traités'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'integer',
        message: "Le nombre de projets doit être un entier.")]
    private $nombreProjet = 0;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => 'Responsable du traitement'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128,
        maxMessage: "Le nom du responsable ne doit pas dépasser 128 caractères.")]
    private $responsable;

    #[ORM\Column(type: Types::STRING, length: 64, nullable: false,
        options: ['comment' => 'Identifiant court de l’utilisateur responsable'])]
    #[Assert\Length(max: 64,
        maxMessage: "Le nom de l'utilisateur ne doit pas dépasser 64 caractères.")]
    private $responsableShort;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true,
        options: ['comment' => 'Date et heure de début du traitement'])]
    #[Assert\NotNull]
    private $debutTraitement;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: true,
        options: ['comment' => 'Date et heure de fin du traitement'])]
    #[Assert\NotNull]
    private $finTraitement;

    #[ORM\Column(type: 'ulid', unique: true, nullable: false,
        options: ['comment' => 'Identifiant unique pour lier un traitement (ULID sous forme de texte).']
    )]
    private Ulid $traitementId;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement du traitement dans le système'])]
    #[Assert\NotNull]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getModeCollecte(): ?string
    {
        return $this->modeCollecte;
    }

    public function setModeCollecte(string $modeCollecte): static
    {
        $this->modeCollecte = $modeCollecte;

        return $this;
    }

    public function isSuccess(): ?bool
    {
        return $this->success;
    }

    public function setSuccess(bool $success): static
    {
        $this->success = $success;

        return $this;
    }

    public function isPending(): ?bool
    {
        return $this->pending;
    }

    public function setPending(bool $pending): static
    {
        $this->pending = $pending;

        return $this;
    }

    public function isInProgress(): ?bool
    {
        return $this->inProgress;
    }

    public function setInProgress(bool $inProgress): static
    {
        $this->inProgress = $inProgress;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): static
    {
        $this->titre = $titre;

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

    public function getNombreProjet(): ?int
    {
        return $this->nombreProjet;
    }

    public function setNombreProjet(int $nombreProjet): static
    {
        $this->nombreProjet = $nombreProjet;

        return $this;
    }

    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    public function setResponsable(string $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }

        public function getResponsableShort(): ?string
    {
        return $this->responsableShort;
    }

    public function setResponsableShort(string $responsableShort): static
    {
        $this->responsableShort = $responsableShort;

        return $this;
    }

    public function getDebutTraitement(): ?\DateTimeImmutable
    {
        return $this->debutTraitement;
    }

    public function setDebutTraitement(?\DateTimeImmutable $debutTraitement): static
    {
        $this->debutTraitement = $debutTraitement;

        return $this;
    }

    public function getFinTraitement(): ?\DateTimeImmutable
    {
        return $this->finTraitement;
    }

    public function setFinTraitement(?\DateTimeImmutable $finTraitement): static
    {
        $this->finTraitement = $finTraitement;

        return $this;
    }

    public function getTraitementId(): ?Ulid
    {
        return $this->traitementId;
    }

    public function setTraitementId(Ulid $traitementId): static
    {
        $this->traitementId = $traitementId;

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
