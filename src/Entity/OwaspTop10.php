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

use App\Repository\OwaspTop10Repository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: OwaspTop10Repository::class)]
class OwaspTop10
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
      type: Types::INTEGER,
      nullable: false,
      options: ['comment' => 'Identifiant unique']
      )]
    private $id;

    #[ORM\Column(
      type: Types::INTEGER,
      nullable: false,
      options: ['comment' => 'Année']
      )]
    #[Assert\NotNull]
    private $year;

    #[ORM\Column(
      type: Types::STRING,
      length: 255,
      nullable: false,
      options: ['comment' => 'Codes comme a1, a2, etc.']
      )]
    #[Assert\NotBlank]
    #[Assert\Length(max: 255)]
    private $category;

    #[ORM\Column(
      type: Types::TEXT,
      nullable: false,
      options: ['comment' => 'Description de la vulnérabilité']
      )]
    #[Assert\NotBlank]
    private $description;

    #[ORM\Column(
      type: Types::TEXT,
      nullable: true,
      options: ['comment' => 'Description détaillée']
      )]
    private $detailedDescription;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getYear(): ?int
    {
        return $this->year;
    }

    public function setYear(int $year): self
    {
        $this->year = $year;
        return $this;
    }

    public function getCategory(): ?string
    {
        return $this->category;
    }

    public function setCategory(string $category): self
    {
        $this->category = $category;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;
        return $this;
    }

    public function getDetailedDescription(): ?string
    {
        return $this->detailedDescription;
    }

    public function setDetailedDescription(?string $detailedDescription): self
    {
        $this->detailedDescription = $detailedDescription;
        return $this;
    }
}
