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

use App\Repository\ListeProjetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ListeProjetRepository::class)]
#[ORM\Table(
    name: 'liste_projet',
    schema: "ma_moulinette",
    options: ['comment' => 'Table des projets disponibles sur le serveur SonarQube.'])]
class ListeProjet
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        options: ['comment' => 'Identifiant unique pour chaque instance de ListeProjet'])]
    private ?int $id = null;

    #[ORM\Column(
        name: 'maven_key',
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank(message: "La clé Maven ne peut pas être vide.")]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private string $mavenKey;

    #[ORM\Column(
        name: 'name',
        type: Types::STRING,
        length: 128,
        nullable: false,
        options: ['comment' => 'Nom du projet'])]
    #[Assert\NotBlank(message: "Le nom du projet ne peut pas être vide.")]
    #[Assert\Length(
        max: 128,
        maxMessage: "Le nom du projet ne doit pas dépasser 128 caractères.")]
    private string $name;

    #[ORM\Column(
        name: 'tags',
        type: Types::JSON,
        nullable: false,
        options: ['comment' => 'Tags associés au projet sous forme de tableau JSON'])]
    #[Assert\NotNull(message: "Les tags ne peuvent pas être nuls.")]
    private array $tags = [];

    #[ORM\Column(
        name: 'visibility',
        type: Types::STRING,
        length: 8,
        nullable: false,
        options: ['comment' => 'Visibilité du projet'])]
    #[Assert\NotBlank(message: "La visibilité ne peut pas être vide.")]
    private string $visibility;

    #[ORM\Column(
        name: 'date_enregistrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement du projet'])]
    #[Assert\NotNull(message: "La date d'enregistrement ne peut pas être nulle.")]
    private \DateTimeImmutable $dateEnregistrement;

    public function __construct(
        string $mavenKey,
        string $name,
        string $visibility,
        array $tags = []
    ) {
        $this->mavenKey = $mavenKey;
        $this->name = $name;
        $this->visibility = $visibility;
        $this->tags = $tags;
        $this->dateEnregistrement = new \DateTimeImmutable('now', new \DateTimeZone('Europe/Paris'));
    }

    // Getters et setters
    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): self
    {
        $this->mavenKey = $mavenKey;
        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getTags(): array
    {
        return $this->tags;
    }

    public function setTags(array $tags): self
    {
        $this->tags = $tags;
        return $this;
    }

    public function getVisibility(): string
    {
        return $this->visibility;
    }

    public function setVisibility(string $visibility): self
    {
        $this->visibility = $visibility;
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
