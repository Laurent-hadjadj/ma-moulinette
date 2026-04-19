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

use App\Repository\GroupeFonctionnelRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: GroupeFonctionnelRepository::class)]
#[ORM\Table(
    name: "groupe_fonctionnel",
    schema: "ma_moulinette",
    options: ['comment' => 'Gestion des groupes fonctionnels de l’application']
)]
class GroupeFonctionnel
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique pour la table groupe_fonctionnel']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'groupe_fonctionnel',
        type: Types::STRING,
        length: 128,
        unique: true,
        nullable: false,
        options: ['comment' => 'Nom du groupe fonctionnel, unique']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 32,
        maxMessage: "Le nom du groupe fonctionnel ne doit pas dépasser 32 caractères."
    )]
    private string $groupeFonctionnel;

    #[ORM\Column(
        name: 'description',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Description du groupe fonctionnel']
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 128,
        maxMessage: "La description ne doit pas dépasser 128 caractères."
    )]
    private string $description;

    #[ORM\Column(
        name: 'date_modification',
        type: Types::DATETIME_MUTABLE,
        nullable: true,
        options: ['comment' => 'Date de la dernière modification de l’équipe']
    )]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(
        name: 'date_enregistrement',
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

    public function getGroupeFonctionnel(): string
    {
        return $this->groupeFonctionnel;
    }

    public function setGroupeFonctionnel(string $groupeFonctionnel): self
    {
        $this->groupeFonctionnel = $groupeFonctionnel;
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
