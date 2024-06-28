<?php

/*
 *  Ma-Moulinette
 *  --------------
 *  Copyright (c) 2021-2022.
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

#[ORM\Entity(repositoryClass: BatchRepository::class)]
#[ORM\Table(name: "batch", schema: "ma_moulinette")]
class Batch
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique de la table batch'])]
    private $id;

    #[ORM\Column(type: Types::BOOLEAN, nullable: false,
        options: ['comment' => 'Statut d’activité du batch'])]
    #[Assert\NotNull]
    #[Assert\Type(type: 'bool')]
    private $statut = false;

    #[ORM\Column(type: Types::STRING, length: 32, unique: true, nullable: false,
        options: ['comment' => 'Titre du batch, unique'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32,
        maxMessage: "Le titre ne doit pas dépasser 32 caractères.")]
    private $titre;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => 'Description du batch'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128,
        maxMessage: "La description ne doit pas dépasser 128 caractères.")]
    private $description;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => 'Nom de l’utilisateur responsable'])]
    #[Assert\Length(max: 128,
        maxMessage: "Le nom de l'utilisateur ne doit pas dépasser 128 caractères.")]
    private $responsable;

    #[ORM\Column(type: Types::STRING, length: 32, unique: true, nullable: false,
        options: ['comment' => 'Portefeuille de projet, unique'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32,
        maxMessage: "Le portefeuille ne doit pas dépasser 32 caractères.")]
    private $portefeuille = "Aucun";

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Nombre de projets dans le batch'])]
    #[Assert\Type(type: 'integer')]
    private ?int $nombreProjet = 0;

    #[ORM\Column(type: Types::STRING, length: 8, nullable: true,
        options: ['comment' => 'État d’exécution du batch'])]
    private $execution;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true,
        options: ['comment' => 'Date de la dernière modification du batch'])]
    private $dateModification;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement du batch'])]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function isStatut(): ?bool
    {
        return $this->statut;
    }

    public function setStatut(bool $statut): static
    {
        $this->statut = $statut;

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

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): static
    {
        $this->description = $description;

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

    public function getExecution(): ?string
    {
        return $this->execution;
    }

    public function setExecution(?string $execution): static
    {
        $this->execution = $execution;

        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;

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
