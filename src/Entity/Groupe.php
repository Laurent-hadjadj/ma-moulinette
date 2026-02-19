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

use App\Repository\GroupeRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use App\Validator as AcmeAssert;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GroupeRepository::class)]
#[ORM\Table(
    name: "groupe",
    schema: "ma_moulinette",
    options: ['comment' => 'Gestion des groupes / équipes']
)]
class Groupe
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour la table équipe']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'titre',
        type: Types::STRING,
        length: 32,
        unique: true,
        nullable: false,
        options: ['comment' => 'Titre de l’équipe, unique']
    )]
    #[AcmeAssert\ContainsGroupeUnique]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le titre ne doit pas dépasser 32 caractères."
    )]
    private string $titre;

    #[ORM\Column(
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Description de l’équipe']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "La description ne doit pas dépasser 128 caractères."
    )]
    private string $description;

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification de l’équipe']
    )]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement de l’équipe']
    )]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
        $this->dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    // -----------------------
    // Getters & Setters
    // -----------------------

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitre(): string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;
        return $this;
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
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

    public function getDateEnregistrement(): \DateTimeImmutable
    {
        return $this->dateEnregistrement;
    }

    public function setDateEnregistrement(\DateTimeImmutable $dateEnregistrement): self
    {
        $this->dateEnregistrement = $dateEnregistrement;
        return $this;
    }
}
