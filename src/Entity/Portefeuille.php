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
    name: "portefeuille",
    schema: "ma_moulinette")]
class Portefeuille
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Identifiant unique pour chaque portefeuille'])]
    private $id;

    #[ORM\Column(
        name: 'portefeuille',
        type: Types::STRING, length: 32, nullable: false, unique: true,
        options: ['comment' => 'Nom unique du portefeuille'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 32,
        maxMessage: "Le nom du portefeuille ne peut pas dépasser 32 caractères.")]
    private $portefeuille;

    #[ORM\Column(
        name: 'groupe_fonctionnel',
        type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => 'Nom du groupe fonctionnel associé au portefeuille'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128,
        maxMessage: "Le nom du groupe fonctionnel ne peut pas dépasser 128 caractères.")]
    private $groupeFonctionnel;

    #[ORM\Column(
        name: 'liste',
        type: 'json',
        options: ['comment' => 'Liste des éléments ou des activités du portefeuille'])]
    private ?array $liste = [];

    #[ORM\Column(
        name: 'date_modification',
        type: Types::DATETIME_MUTABLE, nullable: true,
        options: ['comment' => 'Date de la dernière modification du portefeuille'])]
    private $dateModification;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,  nullable: false,
        options: ['comment' => 'Date d’enregistrement du portefeuille'])]
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

    public function getPortefeuille(): ?string
    {
        return $this->portefeuille;
    }

    public function setPortefeuille(string $portefeuille): static
    {
        $this->portefeuille = $portefeuille;

        return $this;
    }

    public function getGroupeFonctionnel(): ?string
    {
        return $this->groupeFonctionnel;
    }

    public function setGroupeFonctionnel(string $groupeFonctionnel): static
    {
        $this->groupeFonctionnel = $groupeFonctionnel;

        return $this;
    }

    public function getListe(): array
    {
        return $this->liste;
    }

    public function setListe(array $liste): static
    {
        $this->liste = $liste;

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
