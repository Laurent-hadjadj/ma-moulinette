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

use App\Repository\PropertiesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PropertiesRepository::class)]
#[ORM\Table(name: "properties", schema: "ma_moulinette")]
class Properties
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour la table propriété']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Type de propriété']
    )]
    #[Assert\NotBlank]
    private $type;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant du projet dans la base de données']
    )]
    #[Assert\NotNull]
    private $projetBd;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant du projet dans Sonar']
    )]
    #[Assert\NotNull]
    private $projetSonar;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant du profil dans la base de données']
    )]
    #[Assert\NotNull]
    private $profilBd;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant du profil dans Sonar']
    )]
    #[Assert\NotNull]
    private $profilSonar;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de création de la propriété']
    )]
    #[Assert\NotBlank]
    private $dateCreation;

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification du projet']
    )]
    #[Assert\NotNull]
    private $dateModificationProjet;

    #[ORM\Column(
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification du profil']
    )]
    #[Assert\NotNull]
    private $dateModificationProfil;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;

        return $this;
    }

    public function getProjetBd(): ?int
    {
        return $this->projetBd;
    }

    public function setProjetBd(int $projetBd): static
    {
        $this->projetBd = $projetBd;

        return $this;
    }

    public function getProjetSonar(): ?int
    {
        return $this->projetSonar;
    }

    public function setProjetSonar(int $projetSonar): static
    {
        $this->projetSonar = $projetSonar;

        return $this;
    }

    public function getProfilBd(): ?int
    {
        return $this->profilBd;
    }

    public function setProfilBd(int $profilBd): static
    {
        $this->profilBd = $profilBd;

        return $this;
    }

    public function getProfilSonar(): ?int
    {
        return $this->profilSonar;
    }

    public function setProfilSonar(int $profilSonar): static
    {
        $this->profilSonar = $profilSonar;

        return $this;
    }

    public function getDateCreation(): ?\DateTimeImmutable
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeImmutable $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    public function getDateModificationProjet(): ?\DateTimeInterface
    {
        return $this->dateModificationProjet;
    }

    public function setDateModificationProjet(?\DateTimeInterface $dateModificationProjet): static
    {
        $this->dateModificationProjet = $dateModificationProjet;

        return $this;
    }

    public function getDateModificationProfil(): ?\DateTimeInterface
    {
        return $this->dateModificationProfil;
    }

    public function setDateModificationProfil(?\DateTimeInterface $dateModificationProfil): static
    {
        $this->dateModificationProfil = $dateModificationProfil;

        return $this;
    }

}
