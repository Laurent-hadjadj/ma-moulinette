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

use App\Repository\OwaspTop10Repository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * [Description OwaspTop10]
 *
 * MODIF 2026-05-07 : ajout Assert NotBlank/Length/PositiveOrZero.
 */
#[ORM\Entity(repositoryClass: OwaspTop10Repository::class)]
#[ORM\Table(name: 'owasp_top10', schema: 'ma_moulinette')]
class OwaspTop10
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        name: 'id',
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'clé unique pour la table OwaspTop10']
    )]
    private ?int $id = null;

    #[ORM\Column(
        name: 'year',
        type: Types::INTEGER
    )]
    #[Assert\PositiveOrZero]
    private int $year;

    #[ORM\Column(
        name: 'category',
        type: Types::STRING,
        length: 255
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private string $category;

    #[ORM\Column(
        name: 'description',
        type: Types::TEXT
    )]
    #[Assert\NotBlank]
    private string $description;

    #[ORM\Column(
        name: 'lien',
        type: Types::STRING,
        length: 128
    )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 128)]
    private string $lien;

    #[ORM\Column(
        name: 'date_renregsitrement',
        type: Types::DATETIMETZ_IMMUTABLE,
        options: ['default' => 'CURRENT_TIMESTAMP']
    )]
    private \DateTimeImmutable $dateEnregistrement;

    // Getters and Setters

    public function getId(): ?int
    {
        return $this->id;
    }
    public function setId(int $id): self
    {
        $this->id = $id;
        return $this;
    }

    public function getYear(): int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;

        return $this;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;

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

    public function getLien(): string
    {
        return $this->lien;
    }

    public function setLien(string $lien): self
    {
        $this->lien = $lien;

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
