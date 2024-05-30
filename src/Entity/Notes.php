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

use App\Repository\NotesRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: NotesRepository::class)]
#[ORM\Table(name: "notes", schema: "ma_moulinette")]
class Notes
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Identifiant unique la table Notes']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 255,
        nullable: false,
        options: ['comment' => 'Clé Maven du projet']
    )]
    #[Assert\Length(
        max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères."
    )]
    private $mavenKey;

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => 'Type de la note']
    )]
    #[Assert\NotBlank]
    private $type;

    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Valeur de la note']
    )]
    #[Assert\NotNull]
    private $value;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date d’enregistrement de la note']
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getMavenKey(): ?string
    {
        return $this->mavenKey;
    }

    public function setMavenKey(string $mavenKey): static
    {
        $this->mavenKey = $mavenKey;

        return $this;
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

    public function getValue(): ?int
    {
        return $this->value;
    }

    public function setValue(int $value): static
    {
        $this->value = $value;

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
