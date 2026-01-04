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

use App\Repository\PortefeuilleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: PortefeuilleRepository::class)]
#[ORM\Table(
    name: 'portefeuille',
    schema: "ma_moulinette",
    options: ['comment' => 'Identifiant unique pour chaque portefeuille'])]
class Portefeuille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique pour chaque portefeuille'])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'titre',
        type: Types::STRING,
        length: 32,
        nullable: false,
        unique: true,
        options: ['comment' => 'Titre unique du portefeuille'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32, maxMessage: "Le titre ne peut pas dépasser 32 caractères.")]
    private string $titre;

    #[ORM\Column(
        name: 'groupe',
        type: Types::STRING,
        length: 32,
        nullable: false,
        options: ['comment' => 'Nom de l’équipe associée au portefeuille'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32, maxMessage: "Le nom de l'équipe ne peut pas dépasser 32 caractères.")]
    private string $groupe;

    #[ORM\Column(
        name: 'liste',
        type: Types::JSON,
        nullable: false,
        options: ['comment' => 'Liste des éléments ou des activités du portefeuille'])]
    #[Assert\NotNull]
    private array $liste = [];

    #[ORM\Column(
        name: 'date_modification',
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification du portefeuille'])]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du portefeuille'])]
    #[Assert\NotNull]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct()
    {
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

    public function getTitre(): ?string
    {
        return $this->titre;
    }

    public function setTitre(string $titre): self
    {
        $this->titre = $titre;

        return $this;
    }

    public function getGroupe(): ?string
    {
        return $this->groupe;
    }

    public function setGroupe(string $groupe): self
    {
        $this->groupe = $groupe;

        return $this;
    }

    public function getListe(): array
    {
        return $this->liste;
    }

    public function setListe(array $liste): self
    {
        $this->liste = $liste;

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
