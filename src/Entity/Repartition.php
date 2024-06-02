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

use App\Repository\RepartitionRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: RepartitionRepository::class)]
#[ORM\Table(name: "repartition", schema: "ma_moulinette")]
class Repartition
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'ID unique pour la table répartition'])]
    private $id;

    #[ORM\Column( type: Types::STRING, length: 255, nullable: false,
        options: ['comment' => 'Clé Maven du projet'])]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255,
        maxMessage: "La clé Maven ne doit pas dépasser 255 caractères.")]
    private $mavenKey;

    #[ORM\Column(type: Types::STRING, length: 128, nullable: false,
        options: ['comment' => 'Nom de la répartition'])]
    #[Assert\NotBlank]
    private string $name;

    #[ORM\Column(type: Types::TEXT, nullable: false,
        options: ['comment' => 'Détails du composant concerné par la répartition'])]
    #[Assert\NotBlank]
    private string $component;

    #[ORM\Column(type: Types::STRING, length: 16, nullable: false,
        options: ['comment' => 'Type de la répartition'])]
    #[Assert\NotBlank]
    private string $type;

    #[ORM\Column(type: Types::STRING, length: 8, nullable: false,
        options: ['comment' => 'Gravité de la répartition'])]
    #[Assert\NotBlank]
    private string $severity;

    #[ORM\Column(type: Types::INTEGER, nullable: false,
        options: ['comment' => 'Paramètre de configuration pour la répartition'])]
    #[Assert\NotNull]
    private int $setup;

    #[ORM\Column(type: Types::DATETIMETZ_IMMUTABLE, nullable: false,
        options: ['comment' => 'Date d’enregistrement de la répartition dans le système'])]
    #[Assert\NotNull]
    private \DateTimeInterface $dateEnregistrement;

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

    public function getName(): ?string
    {
        return $this->name;
    }

    public function setName(string $name): static
    {
        $this->name = $name;

        return $this;
    }

    public function getComponent(): ?string
    {
        return $this->component;
    }

    public function setComponent(string $component): static
    {
        $this->component = $component;

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

    public function getSeverity(): ?string
    {
        return $this->severity;
    }

    public function setSeverity(string $severity): static
    {
        $this->severity = $severity;

        return $this;
    }

    public function getSetup(): ?int
    {
        return $this->setup;
    }

    public function setSetup(int $setup): static
    {
        $this->setup = $setup;

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
