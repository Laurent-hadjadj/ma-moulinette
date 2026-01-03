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

use App\Repository\BatchRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: BatchRepository::class)]
#[ORM\Table(
    name: 'batch',
    schema: 'ma_moulinette',
    options: ['comment' => 'Configuration et métadonnées des batchs']
)]
class Batch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique de la table batch']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'activated',
        type: Types::BOOLEAN,
        options: ['comment' => 'Le traitement est activé ou pas']
    )]
    #[Assert\NotNull]
    private bool $activated = false;

    #[ORM\Column(
        name: 'automatique',
        type: Types::BOOLEAN,
        options: ['comment' => 'Le traitement est automatique ou manuel']
    )]
    #[Assert\NotNull]
    private bool $automatique = false;

    #[ORM\Column(
        name: 'titre',
        type: Types::STRING,
        length: 32,
        unique: true,
        options: ['comment' => 'Titre du batch, unique']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $titre;

    #[ORM\Column(
        name: 'description',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Description du batch']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $description;

    #[ORM\Column(
        name: 'responsable',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Identifiant complet de l’utilisateur responsable']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $responsable;

    #[ORM\Column(
        name: 'responsable_short',
        type: Types::STRING,
        length: 64,
        nullable: false,
        options: ['comment' => 'Identifiant court de l’utilisateur responsable']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 64)]
    private string $responsableShort;

    #[ORM\Column(
        name: 'portefeuille',
        type: Types::STRING,
        length: 32,
        unique: true,
        options: ['comment' => 'Portefeuille de projet, unique']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32)]
    private string $portefeuille;

    #[ORM\Column(
        name: 'nombre_projet',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Nombre de projets dans le batch']
    )]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $nombreProjet = 0;

    #[ORM\Column(
        name: 'execution',
        type: Types::STRING,
        length: 8,
        nullable: true,
        options: ['comment' => 'État d’exécution du batch']
    )]
    private ?string $execution = null;

    #[ORM\Column(
        name: 'traitement_id',
        type: 'ulid',
        unique: true,
        options: ['comment' => 'Identifiant unique pour lier un traitement à son batch (ULID)']
    )]
    private Ulid $traitementId;

    #[ORM\Column(
        name: 'date_modification',
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification du batch']
    )]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        options: ['comment' => 'Date d’enregistrement du batch']
    )]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
        $this->traitementId = new Ulid();
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

    public function isAutomatique(): ?bool
    {
        return $this->automatique;
    }

    public function setAutomatique(bool $automatique): self
    {
        $this->automatique = $automatique;

        return $this;
    }

    public function isActivated(): ?bool
    {
        return $this->activated;
    }

    public function setActivated(bool $activated): self
    {
        $this->activated = $activated;

        return $this;
    }

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    public function setResponsable(string $responsable): self
    {
        $this->responsable = $responsable;

        return $this;
    }

    public function getResponsableShort(): ?string
    {
        return $this->responsableShort;
    }

    public function setResponsableShort(string $responsableShort): self
    {
        $this->responsableShort = $responsableShort;

        return $this;
    }

    public function getPortefeuille(): ?string
    {
        return $this->portefeuille;
    }

    public function setPortefeuille(string $portefeuille): self
    {
        $this->portefeuille = $portefeuille;

        return $this;
    }

    public function getNombreProjet(): ?int
    {
        return $this->nombreProjet;
    }

    public function setNombreProjet(int $nombreProjet): self
    {
        $this->nombreProjet = $nombreProjet;

        return $this;
    }

    public function getExecution(): ?string
    {
        return $this->execution;
    }

    public function setExecution(?string $execution): self
    {
        $this->execution = $execution;

        return $this;
    }

    public function getTraitementId(): Ulid
    {
        return $this->traitementId;
    }

    public function setTraitementId(Ulid $traitementId): self
    {
        $this->traitementId = $traitementId;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): self
    {
        $this->dateModification = $dateModification;

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
