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

use App\Repository\GroupeUtilisateurRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;

#[ORM\Entity(repositoryClass: GroupeUtilisateurRepository::class)]
#[ORM\Table(name: "groupe_utilisateur", schema: "ma_moulinette")]
#[UniqueEntity(fields: ['groupeUtilisateur'], message: 'Ce groupe existe déjà.')]
class GroupeUtilisateur
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour la table équipe'])]
    private $id;

    #[ORM\Column(
        name: 'groupe_fonctionnel',
        type: Types::STRING,
        length: 64,
        nullable: false,
        unique: true,
        options: ['comment' => 'Nom du groupe utilisateur.'])]
    #[Assert\Length(
        max: 64,
        maxMessage: "Le nom du groupe utilisateur ne doit pas dépasser 64 caractères.")]
    private string $groupeUtilisateur;

    #[ORM\Column(
        name: 'groupe_id',
        type: Types::STRING,
        length: 26,
        unique: true,
        nullable: false,
        options: ['comment' => 'Identifiant du groupe utilisateur'])]
    #[Assert\Length(
        max: 26,
        maxMessage: "L'identifiant du groupe utilisateur ne doit pas dépasser 26 caractères.")]
    private string $groupeId;

    #[ORM\Column(
        name: 'description',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Description courte du groupe utilisateur.'])
    ]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 255,
        maxMessage: "La description du groupe utilisateur ne doit pas dépasser 255 caractères.")]
    private ?string $description = null;

    #[ORM\Column(
        name: 'date_modification',
        type: Types::DATETIME_MUTABLE, nullable: true,
        options: ['comment' => 'Date de la dernière modification de l’équipe'])]
    private $dateModification;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement de l’équipe'])]
    ## on ne peut pas ajouter la contrainte  notBlank ici
    private $dateEnregistrement;

    public function __construct()
    {
        $this->groupeId = (string) new \Symfony\Component\Uid\Ulid();
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

    public function getGroupeUtilisateur(): string
    {
        return $this->groupeUtilisateur;
    }

    public function setGroupeUtilisateur(string $groupeUtilisateur): static
    {
        $this->groupeUtilisateur = $groupeUtilisateur;

        return $this;
    }

    public function getGroupeId(): string
    {
        return $this->groupeId;
    }

    public function setGroupeId(string $groupeId): static
    {
        $this->groupeId = $groupeId;

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
