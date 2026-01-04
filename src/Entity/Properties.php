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

use App\Repository\PropertiesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropertiesRepository::class)]
#[ORM\Table(
    name: 'properties',
    schema: "ma_moulinette",
    options: ['comment' => "Table des propriétés de l'application"])]
class Properties
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique pour la table propriété'])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'type',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Type de propriété'])]
    #[Assert\NotBlank]
    private string $type;

    #[ORM\Column(
        name: 'projet_bd',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant du projet dans la base de données'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $projetBd;

    #[ORM\Column(
        name: 'projet_sonar',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant du projet dans Sonar'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $projetSonar;

    #[ORM\Column(
        name: 'profil_bd',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant du profil dans la base de données'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $profilBd;

    #[ORM\Column(
        name: 'profil_sonar',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant du profil dans Sonar'])]
    #[Assert\NotNull]
    #[Assert\PositiveOrZero]
    private int $profilSonar;

    #[ORM\Column(
        name: 'date_creation',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de création de la propriété'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateCreation;

    #[ORM\Column(
        name: 'date_modification_projet',
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification du projet'])]
    private ?\DateTimeInterface $dateModificationProjet = null;

    #[ORM\Column(
        name: 'date_modification_profil',
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification du profil'])]
    private ?\DateTimeInterface $dateModificationProfil = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTimeImmutable();
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

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): self
    {
        $this->type = $type;

        return $this;
    }

    public function getProjetBd(): ?int
    {
        return $this->projetBd;
    }

    public function setProjetBd(int $projetBd): self
    {
        $this->projetBd = $projetBd;

        return $this;
    }

    public function getProjetSonar(): ?int
    {
        return $this->projetSonar;
    }

    public function setProjetSonar(int $projetSonar): self
    {
        $this->projetSonar = $projetSonar;

        return $this;
    }

    public function getProfilBd(): ?int
    {
        return $this->profilBd;
    }

    public function setProfilBd(int $profilBd): self
    {
        $this->profilBd = $profilBd;

        return $this;
    }

    public function getProfilSonar(): ?int
    {
        return $this->profilSonar;
    }

    public function setProfilSonar(int $profilSonar): self
    {
        $this->profilSonar = $profilSonar;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): self
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateModificationProjet(): ?\DateTimeInterface
    {
        return $this->dateModificationProjet;
    }

    public function setDateModificationProjet(?\DateTimeInterface $dateModificationProjet): self
    {
        $this->dateModificationProjet = $dateModificationProjet;

        return $this;
    }

    public function getDateModificationProfil(): ?\DateTimeInterface
    {
        return $this->dateModificationProfil;
    }

    public function setDateModificationProfil(?\DateTimeInterface $dateModificationProfil): self
    {
        $this->dateModificationProfil = $dateModificationProfil;

        return $this;
    }

}
