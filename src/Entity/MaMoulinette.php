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

use App\Repository\MaMoulinetteRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: MaMoulinetteRepository::class)]
#[ORM\Table(name: "ma_moulinette", schema: "ma_moulinette")]
class MaMoulinette
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(
        type: Types::INTEGER,
        nullable: false,
        options: ['comment' => 'Clé unique']
    )]
    private $id;

    #[ORM\Column(
        type: Types::STRING,
        length: 16,
        nullable: false,
        options: ['comment' => "Numéro de la version de l'application MaMoulinette"]
    )]
    #[Assert\NotBlank]
    #[Assert\Length(
        max: 16,
        maxMessage: "La version ne doit pas dépasser 16 caractères."
    )]
    private $version;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => 'Date de création']
    )]
    #[Assert\NotNull]
    private $dateVersion;

    #[ORM\Column(
        type: Types::DATETIMETZ_IMMUTABLE,
        nullable: false,
        options: ['comment' => "Date d'enregistrement"]
    )]
    #[Assert\NotNull]
    private $dateEnregistrement;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getVersion(): ?string
    {
        return $this->version;
    }

    public function setVersion(string $version): static
    {
        $this->version = $version;

        return $this;
    }

    public function getDateVersion(): ?\DateTimeImmutable
    {
        return $this->dateVersion;
    }

    public function setDateVersion(\DateTimeImmutable $dateVersion): static
    {
        $this->dateVersion = $dateVersion;

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
